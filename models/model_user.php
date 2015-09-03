<?php

namespace extensions\users{
    
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
            $this->_data['password'] = self::hash_password($value, $this->is_loaded ? md5($this->user_id) : "");
            $this->password_change_required = 'No';
            return true;
        }
        
        public function pget_contact(){
            $children = $this->get();
            
            foreach($children as $child){
                if ($child instanceof model && $child->table_name == 'contact'){
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
                    
                    $sql->select(new \frameworks\adapt\sql('*'))
                        ->from($this->table_name);
                    
                    /* Do we have a date_deleted field? */
                    if (in_array('date_deleted', $fields)){
                        
                        $name_condition = new \frameworks\adapt\sql_condition(new \frameworks\adapt\sql('username'), '=', $username);
                        $date_deleted_condition = new \frameworks\adapt\sql_condition(new \frameworks\adapt\sql('date_deleted'), 'is', new \frameworks\adapt\sql('null'));
                        
                        $sql->where(new \frameworks\adapt\sql_and($name_condition, $date_deleted_condition));
                        
                    }else{
                        
                        $sql->where(new \frameworks\adapt\sql_condition(new \frameworks\adapt\sql('username'), '=', $username));
                    }
                    
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
                list($salt, $pass) = explode(":", $this->password);
                
                $password = self::hash_password($password, $salt);
                
                if ($password == $this->password){
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
                
                $sql->select(new \frameworks\adapt\sql('*'))
                    ->from($this->table_name, 'u')
                    ->join('contact', 'c', new \frameworks\adapt\sql_condition(new \frameworks\adapt\sql('u.contact_id'), '=', new \frameworks\adapt\sql('c.contact_id')))
                    ->join('contact_email', 'ce', new \frameworks\adapt\sql_condition(new \frameworks\adapt\sql('c.contact_id'), '=', new \frameworks\adapt\sql('ce.contact_id')));
                
                /* Do we have a date_deleted field? */
                if (!is_null($this->data_source->get_field_structure('user', 'date_deleted'))){
                    
                    $name_condition = new \frameworks\adapt\sql_condition(new \frameworks\adapt\sql('ce.email'), '=', $email_address);
                    $date_deleted_condition = new \frameworks\adapt\sql_condition(new\frameworks\adapt\sql('u.date_deleted'), 'is', new \frameworks\adapt\sql('null'));
                    
                    $sql->where(new \frameworks\adapt\sql_and($name_condition, $date_deleted_condition));
                    
                }else{
                    
                    $sql->where(new \frameworks\adapt\sql_condition(new \frameworks\adapt\sql('ce.email'), '=', $email_address));
                }
                
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
                list($salt, $pass) = explode(":", $this->password);
                
                $password = self::hash_password($password, $salt);
                
                if ($password == $this->password){
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
                    if ($child instanceof model && $child->table_name == 'user_setting'){
                        if ($child->name == $key){
                            return $child->value;
                        }
                    }
                }
                
            }else{
                foreach($children as $child){
                    if ($child instanceof model && $child->table_name == 'user_setting'){
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
        
        public static function hash_password($password, $salt = ""){
            $adapt = $GLOBALS['adapt'];
            $salt_chars = "abcdefghijklmnopqrstuvwzyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $salt_length = $adapt->setting('users.salt_length');
            
            if (!is_int($salt_length) || $salt_length <= 0) $salt_length = 30;
            
            if ($salt == ""){
                for($i = 0; $i < $salt_length; $i++){
                    $salt .= substr($salt_chars, rand(0, strlen($salt_chars) - 1), 1);
                }
            }
            
            //$salt = sha1($salt);
            $password = $salt . ":" . crypt($password, $salt);
            
            return $password;
        }
    }
}

?>