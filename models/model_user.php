<?php

namespace adapt\users{
    
    /* Prevent Direct Access */
    defined('ADAPT_STARTED') or die;
    
    class model_user extends model{
        
        const EVENT_ON_LOAD_BY_USERNAME = 'model_user.on_load_by_username';
        const EVENT_ON_LOAD_BY_USERNAME_PASSWORD = 'model_user.on_load_by_username_password';
        const EVENT_ON_LOAD_BY_EMAIL_ADDRESS = 'model_user.on_load_by_email_address';
        const EVENT_ON_LOAD_BY_EMAIL_ADDRESS_PASSWORD = 'model_user.on_load_by_email_address_password';
        
        public function __construct($id = null){
            parent::__construct('user', $id);
        }
        
        /* Over-ride the initialiser to auto load children */
        public function initialise(){
            /* We must initialise first! */
            parent::initialise();
            
            /* We need to limit what we auto load */
            $this->_auto_load_only_tables = array(
                'contact',
                'user_setting'
            );
            
            /* Switch on auto loading */
            $this->_auto_load_children = true;
        }
        
        public function mset_password($value){
            $value = self::hash_password($value);
            $this->_changed_fields['password'] = array(
                'old_value' => $this->password,
                'new_value' => $value
            );
            $this->_data['password'] = $value;
            $this->_has_changed = true;
            
            $this->password_change_required = 'No';
            return true;
        }
        
        //public function mget_password(){
        //    return "";
        //}
        
        public function pget_contact(){
            $children = $this->get();
            foreach($children as $child){
                if ($child instanceof \adapt\model && $child->table_name == 'contact'){
                    return $child;
                }
            }
            return null;
        }
        
        public function load_by_username($username){
            $this->initialise();
            
            /* Make sure name is set */
            if (isset($username)){
                
                /* We need to check this table has a username field */
                $fields = array_keys($this->_data);
                
                if (in_array('username', $fields)){
                    $sql = $this->data_source->sql;
                    
                    $sql->select('*')
                        ->from($this->table_name)
                        ->where(
                            new sql_and(
                                new sql_cond('username', sql::EQUALS, sql::q($username)),
                                new sql_cond('date_deleted', sql::IS, new sql_null())
                            )
                        );
                    
                    
                    /* Get the results */
                    $results = $sql->execute()->results();
                    
                    if (count($results) == 1){
                        $this->trigger(self::EVENT_ON_LOAD_BY_USERNAME);
                        return $this->load_by_data($results[0]);
                    }elseif(count($results) == 0){
                        $this->error("Unable to find a record with the username '{$username}'");
                    }elseif(count($results) > 1){
                        $this->error(count($results) . " records found for username '{$username}'.");
                    }
                    
                }else{
                    $this->error('Unable to load by username, this table has no \'username\' field.');
                }
            }else{
                $this->error('Unable to load by username, no name supplied');
            }
            
            return false;
        }
        
        public function load_by_username_password($username, $password){
            if ($this->load_by_username($username)){
                
                if (password_verify($password, $this->password)){
                    $this->trigger(self::EVENT_ON_LOAD_BY_USERNAME_PASSWORD);
                    return true;
                }
            }
            
            $this->error('Unknown username or password');
            return false;
        }
        
        public function load_by_email_address($email_address){
            $this->initialise();
            
            /* Make sure name is set */
            if (isset($email_address)){
                
                $sql = $this->data_source->sql;
                
                $sql->select('u.*')
                    ->from($this->table_name, 'u')
                    ->join('contact', 'c', new sql_cond('u.contact_id', sql::EQUALS, 'c.contact_id'))
                    ->join('contact_email', 'ce', new sql_cond('c.contact_id', sql::EQUALS, 'ce.contact_id'))
                    ->where(
                        new sql_and(
                            new sql_cond('ce.email', sql::EQUALS, sql::q($email_address)),
                            new sql_cond('u.date_deleted', sql::IS, new sql_null())
                        )
                    );
                
                /* Get the results */
                $results = $sql->execute()->results();
                
                if (count($results) == 1){
                    $this->trigger(self::EVENT_ON_LOAD_BY_EMAIL_ADDRESS);
                    return $this->load_by_data($results[0]);
                }elseif(count($results) == 0){
                    $this->error("Unable to find a record with the email address '{$email_address}'");
                }elseif(count($results) > 1){
                    $this->error(count($results) . " records found for email address '{$email_address}'.");
                }
                
                
            }else{
                $this->error('Unable to load by email address, no email address supplied');
            }
            
            return false;
        }
        
        public function load_by_email_address_password($email_address, $password){
            if ($this->load_by_email_address($email_address)){
                
                if (password_verify($password, $this->password)){
                    $this->trigger(self::EVENT_ON_LOAD_BY_EMAIL_ADDRESS_PASSWORD);
                    return true;
                }
            }
            
            $this->error('Unknown email address or password');
            return false;
        }
        
        public function setting($key, $value = null){
            $children = $this->get();
            
            if (is_null($value)){
                foreach($children as $child){
                    if ($child instanceof \adapt\model && $child->table_name == 'user_setting'){
                        if ($child->name == $key){
                            return $child->value;
                        }
                    }
                }
                
            }else{
                foreach($children as $child){
                    if ($child instanceof \adapt\model && $child->table_name == 'user_setting'){
                        if ($child->name == $key){
                            $child->value = $value;
                            return null;
                        }
                    }
                }
                
                /* We didn't find the setting, so let create a new one */
                $setting = new model_user_setting();
                $setting->name = $key;
                $setting->value = $value;
                $this->add($setting);
            }
            
            return null;
        }
        
        public static function hash_password($password, $salt = ''){ // $salt is now depricated and should be removed
            
            $password = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);

            return $password;
        }
    }
}

?>