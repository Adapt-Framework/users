<?php

namespace adapt\users{
    
    /* Prevent Direct Access */
    defined('ADAPT_STARTED') or die;
    
    class model_user extends model{
        
        const EVENT_ON_LOAD_BY_USERNAME = 'model_user.on_load_by_username';
        const EVENT_ON_LOAD_BY_USERNAME_PASSWORD = 'model_user.on_load_by_username_password';
        const EVENT_ON_LOAD_BY_EMAIL_ADDRESS = 'model_user.on_load_by_email_address';
        const EVENT_ON_LOAD_BY_EMAIL_ADDRESS_PASSWORD = 'model_user.on_load_by_email_address_password';

        // User statuses
        const USER_ACTIVE = 'Active';
        const USER_SUSPENDED = 'Suspended';
        
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
        
        public function pget_is_password_hashed()
        {
            return $this->_is_password_hashed;
        }
        
        public function pset_is_password_hashed($value)
        {
            $this->_is_password_hashed = $value;
            
        }
        
        public function pset_hashed_password($password){
            $this->data['password'] = $password;
            $this->_changed_fields['password'] = array(
                'old_value' => '',
                'new_value' => $password
            );
            $this->_has_changed = true;
            
            $this->password_change_required = 'No';
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
                    $results = $sql->execute(0)->results();
                    
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
                    ->join('contact', 'c', 
                           new sql_and(
                               new sql_cond('u.contact_id', sql::EQUALS, 'c.contact_id')),
                               new sql_cond('u.date_deleted', sql::IS, sql::NULL)
                           )
                    ->join('contact_email', 'ce', 
                        new sql_and(
                            new sql_cond('c.contact_id', sql::EQUALS, 'ce.contact_id'),
                            new sql_cond('ce.date_deleted', sql::IS, sql::NULL)
                        )
                    )
                    ->where(
                        new sql_and(
                            new sql_cond('ce.email', sql::EQUALS, sql::q($email_address)),
                            new sql_cond('u.date_deleted', sql::IS, new sql_null())
                        )
                    );
                
                /* Get the results */
                $results = $sql->execute(0)->results();
                
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
                        if ($child->key_name == $key){
                            return $child->value;
                        }
                    }
                }
                
            }else{
                foreach($children as $child){
                    if ($child instanceof \adapt\model && $child->table_name == 'user_setting'){
                        if ($child->key_name == $key){
                            $child->value = $value;
                            return null;
                        }
                    }
                }
                
                /* We didn't find the setting, so let create a new one */
                $setting = new model_user_setting();
                $setting->key_name = $key;
                $setting->value = $value;
                $this->add($setting);
            }
            
            return null;
        }
        
        public function get_settings(){
            $output = [];
            $children = $this->get();
            
            foreach($children as $child){
                if ($child instanceof \adapt\model && $child->table_name == 'user_setting'){
                    $output[$child->key_name] = $child->value;
                }
            }
            
            return $output;
        }
        
        public function apply_settings(){
            $settings = $this->get_settings();
            
            if (is_array($settings)){
                foreach($settings as $key => $value){
                    parent::setting($key, $value);
                }
            }
        }
        
        public function save(){
            if (!$this->is_loaded){
                /* Check if the username and/or email address is unique */
                if (strtolower($this->setting('users.username_type')) == 'username'){
                    if (!$this->username){
                        $this->error("Username required");
                        return false;
                    }
                    
                    if (static::has_username($this->username)){
                        $this->error("Username has already been used.");
                        return false;
                    }
                    
                    if ($this->contact->email){
                        if (static::has_email_address($this->contact->email)){
                            $this->error("This email address has already been registered.");
                            return false;
                        }
                    }
                }else{
                    if (!$this->contact->email){
                        $this->error("Email is required");
                        return false;
                    }
                    
                    if (static::has_email_address($this->contact->email)){
                        $this->error("This email address has already been registered.");
                        return false;
                    }
                    
                    if ($this->username){
                        if (static::has_username($this->username)){
                            $this->error("Username has already been used.");
                            return false;
                        }
                    }
                }
            }
            
            return parent::save();
        }
        
        public static function hash_password($password, $salt = ''){ // $salt is now deprecated and should be removed
            
            $password = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);

            return $password;
        }
        
        public static function has_email_address($email_address){
            $adapt = $GLOBALS['adapt'];
            $sql = $adapt->data_source->sql;    
            $sql->select('u.*')
                ->from('user', 'u')
                ->join('contact', 'c', 
                    new sql_and(
                        new sql_cond('u.contact_id', sql::EQUALS, 'c.contact_id'),
                        new sql_cond('c.date_deleted', sql::IS, sql::NULL)
                    )
                )
                ->join('contact_email', 'ce', 
                    new sql_and(
                        new sql_cond('c.contact_id', sql::EQUALS, 'ce.contact_id'),
                        new sql_cond('ce.date_deleted', sql::IS, sql::NULL)
                    )
                )
                ->where(
                    new sql_and(
                        new sql_cond('ce.email', sql::EQUALS, sql::q($email_address)),
                        new sql_cond('u.date_deleted', sql::IS, new sql_null())
                    )
                );
            
            $results = $sql->execute(0)->results();
            
            if (is_array($results) && count($results)){
                return true;
            }
            
            return false;
        }
        
        public static function has_username($username){
            $adapt = $GLOBALS['adapt'];
            $sql = $adapt->data_source->sql;
            $sql->select('username')
                ->from('user')
                ->where(
                    new sql_and(
                        /* Not checking date deleted to prevent spoofing */
                        new sql_cond('username', sql::EQUALS, q($username))
                    )
                );
            
            $results = $sql->execute(0)->results();
            
            if (is_array($results) && count($results)){
                return true;
            }
            
            return false;
        }
        
    }
}
