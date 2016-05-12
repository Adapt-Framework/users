<?php

namespace adapt\users{
    
    /* Prevent Direct Access */
    defined('ADAPT_STARTED') or die;
    
    class model_user_login_token extends model{
        
        const EVENT_ON_LOAD_BY_TOKEN = 'model_user_login_token.on_load_by_token';
        
        public function __construct($id = null){
            parent::__construct('user_login_token', $id);
        }
        
        public function load_by_token($token){
            if (isset($token)){
                $sql = $this->data_source->sql;
                
                $sql->select('*')
                    ->from($this->table_name)
                    ->where(
                        new sql_and(
                            new sql_cond('token', sql::EQUALS, sql::q($token)),
                            new sql_cond('date_deleted', sql::IS, new sql_null())
                        )
                    );
                
                /* Get the results */
                $results = $sql->execute()->results();
                
                if (count($results) == 1){
                    $this->trigger(self::EVENT_ON_LOAD_BY_TOKEN);
                    if ($this->load_by_data($results[0])){
                        
                        $this->access_count = $this->access_count + 1;
                        $this->save();
                        
                        if ($this->token_type == 'Auto login'){
                            if ($this->access_count > $this->setting('users.max_auto_login_count')){
                                $this->delete();
                                $this->error("The login token '{$token}' has expired.");
                            }else{
                                return true;
                            }
                        }elseif($this->token_type == "Password reset"){
                            if ($this->access_count > 1){
                                $this->delete();
                                $this->error("The login token '{$token}' has expired, password reset tokens can be used only once.");
                            }else{
                                return true;
                            }
                        }elseif($this->token_type == "Email verification"){
                            if ($this->access_count > 1){
                                $this->delete();
                                $this->error("The login token '{$token}' has expired, email verification tokens can be used only once.");
                            }else{
                                return true;
                            }
                        }else{
                            return true;
                        }
                        
                    }
                }elseif(count($results) == 0){
                    $this->error("Unable to load with token '{$token}', the token could not found.");
                }elseif(count($results) > 1){
                    $this->error(count($results) . " records found for token '{$token}'.");
                }
            }else{
                $this->error('Failed to load with empty token');
            }
            
            $this->_is_loaded = false;
            return false;
        }
        
        public function save(){
            if (!$this->is_loaded){
                $chars = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ!@£$%^&*()_-+={}[]:;|";
                
                $token = date("Ymdhis");
                for($i = 0; $i < 64; $i++){
                    $token .= substr($chars, rand(0, strlen($chars) - 1), 1);
                }
                
                $this->token = sha1($token);
            }
            
            $return = parent::save();
            
            if ($return && $this->token_type == 'Keep me signed in'){
                $this->cookie('login_token', $this->token, time() + 60 * 60 * 24 * 365);
            }
            
            return $return;
        }
        
    }
}

?>