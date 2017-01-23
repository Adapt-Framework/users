<?php

namespace adapt\users{
    
    /* Prevent Direct Access */
    defined('ADAPT_STARTED') or die;
    
    class bundle_users extends \adapt\bundle{
        
        protected $_users;
        
        public function __construct($data){
            parent::__construct('users', $data);
            
            $this->_users = [];
            
            $this->register_config_handler('users', 'users', 'process_users_tag');
        }
        
        public function boot(){
            if (parent::boot()){
                
                $this->dom->head->add(new adapt\html_link(array('type' => 'text/css', 'rel' => 'stylesheet', 'href' => "/adapt/users/users-{$this->version}/static/css/users.css")));

                /* Add user property to the session object */
                \adapt\sessions\model_session::extend('pget_user', function($_this){
                    $user = $_this->store('users.user');
                    if ($user && $user instanceof \adapt\model && $user->table_name = 'user'){
                        return $user;
                    }else{
                        $user = new \adapt\users\model_user();
                        
                        $user_id = $_this->data('users.user.user_id');
                        if ($user_id > 0){
                            $user->load($user_id);
                            $_this->store('users.user', $user);
                        }elseif(!is_null($_this->cookie('login_token'))){
                            $token = new \adapt\users\model_user_login_token();
                            if ($token->load_by_token($_this->cookie('login_token')) && $token->token_type == 'Keep me signed in'){
                                $user->load($token->user_id);
                                //$_this->store('users.user', $user);
                                $_this->session->user = $user;
                                $_this->session->save();
                            }
                        }elseif(isset($_this->request['alt'])){
                            /*
                             * alt = Auto login token.
                             * I didn't want to use a name that was obvious
                             * cos on a security front, that would be pretty
                             * stupid.
                             */
                            $token = new \adapt\users\model_user_login_token();
                            if ($token->load_by_token($_this->request('alt')) && $token->token_type == 'Auto login'){
                                $user->load($token->user_id);
                                $_this->store('users.user', $user);
                            }
                            
                        }
                    }
                    
                    return $user;
                });
                
                \adapt\sessions\model_session::extend('pset_user', function($_this, $user){
                    if ($user && $user instanceof \adapt\users\model_user){
                        $_this->store('users.user', $user);
                        if ($user->is_loaded){
                            $_this->data('users.user.user_id', $user->user_id);
                            // Apply user settings
                        }else{
                            $_this->remove_data('users.user.user_id');
                            // Remove user settings
                        }
                        $_this->save();
                    }else{
                        $_this->remove_store('users.user');
                        $_this->remove_data('users.user.user_id');
                        $_this->save();
                        // Remove user settings
                    }
                });
                
                /* Add is_logged_in read only property to the session property */
                \adapt\sessions\model_session::extend('pget_is_logged_in', function($_this){
                    return $_this->user->is_loaded;
                });
                
                
                /*
                 * Extend the root controller and add a view_sign_in
                 */
                \application\controller_root::extend('view_sign_in', function($_this){
                    if ($_this->session->is_logged_in){
                        $_this->redirect("/");
                    }else{
                        if ($_this->setting('users.username_type') == 'Username'){
                            $_this->add_view(new \adapt\users\view_login_panel(\adapt\users\view_login_panel::USERNAME));
                        }else{
                            $_this->add_view(new \adapt\users\view_login_panel(\adapt\users\view_login_panel::EMAIL));
                        }
                    }
                });
                
                /*
                 * Extend the root controller and add a view_forgot_password
                 */
                \application\controller_root::extend('view_forgot_password', function($_this){
                    $_this->add_view(new \adapt\users\view_forgot_password());
                });
                
                /*
                 * Extend the root controller and add a view_password_reminder_sent
                 */
                \application\controller_root::extend('view_password_reminder_sent', function($_this){
                    $_this->add_view(new \adapt\users\view_password_reminder_sent());
                    //$_this->add_view(new html_pre(print_r($_SERVER, true)));
                });
                
                \application\controller_root::extend('view_reset_password', function($_this){
                    if (isset($_this->request['token'])){
                        $_this->add_view(new \adapt\users\view_password_change(false, $_this->request));
                    }else{
                        $_this->add_view(new \adapt\users\view_invalid_token());
                    }
                });
                
                \application\controller_root::extend('view_change_password', function($_this){
                    if ($_this->session->is_logged_in){
                        $_this->add_view(new \adapt\users\view_password_change());
                    }else{
                        $_this->redirect("/");
                    }
                });
                
                \application\controller_root::extend('view_verify_email', function($_this){
                    if ($_this->session->is_logged_in){
                        $_this->add_view(new \adapt\users\view_verify_email());
                    }else{
                        $_this->redirect("/");
                    }
                });
                
                \application\controller_root::extend('view_verify_email_sent', function($_this){
                    if ($_this->session->is_logged_in){
                        $_this->add_view(new \adapt\users\view_verify_email_sent());
                    }else{
                        $_this->redirect("/");
                    }
                });
                
                \application\controller_root::extend('view_email_verified', function($_this){
                    if ($_this->session->is_logged_in){
                        $_this->add_view(new \adapt\users\view_email_verified());
                    }else{
                        $_this->redirect("/");
                    }
                });
                
                \application\controller_root::extend('view_password_changed', function($_this){
                    $_this->add_view(new \adapt\users\view_password_changed());
                });
                
                /*
                 * Extend the root controller and a action_request_password_reset
                 */
                \application\controller_root::extend('action_request_password_reset', function($_this){
                    if (!$_this->session->is_logged_in){
                        $user = new model_user();
                        if ($user->load_by_email_address($_this->request['email'])){
                            /*
                             * We have a user with this email so we need to create a login
                             * token and then email it to the user.
                             */
                            $token = new model_user_login_token();
                            $token->user_id = $user->user_id;
                            $token->token_type = "Password reset";
                            $token->access_count = 0;
                            
                            if ($token->save()){
                                $url = "http";
                                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != ""){
                                    $url .= "s";
                                }
                                $url .= "://" . $_SERVER['SERVER_NAME'];
                                if ($_SERVER['SERVER_PORT'] != "80" && $_SERVER['SERVER_PORT'] != "443"){
                                    $url .= ":" . $_SERVER['SERVER_PORT'];
                                }
                                $url .= "/reset-password?actions=password-token-login&token=" . $token->token;
                                
                                $email = $_this->email_account->new_email_from_template('user_password_reset_template');
                                
                                $vars = array('password-reset-url' => $url);
                                $hash_vars = $user->to_hash_string();
                                
                                //foreach($hash_vars as $pair){
                                //    $key = $pair['key'];
                                //    $value = $pair['value'];
                                //    $vars[$key] = $value;
                                //}
                                
                                $email->variables(array_merge($vars, $hash_vars));
                                
                                $email->to($_this->request['email']);
                                $email->send(false);
                                
                                $_this->redirect($_SERVER['REQUEST_URI']);
                            }
                        }
                    }
                });
                
                \application\controller_root::extend('action_set_new_password', function($_this){
                    if ($_this->session->is_logged_in){
                        if ($_this->request['token']){
                            
                            /* Lets check the token is valid, we are going to do this at sql
                             * level because we do not want to change the access count or
                             * invalidate the token in anyway.
                             */
                            $sql = $_this->data_source->sql;
                            
                            $sql->select('*')
                                ->from('user_login_token')
                                ->where(
                                    new sql_and(
                                        new sql_cond('token', sql::EQUALS, sql::q($_this->request['token'])),
                                        new sql_cond('user_id', sql::EQUALS, sql::q($_this->session->user->user_id)),
                                        new sql_cond('token_type', sql::EQUALS, sql::q("Password reset")),
                                        new sql_cond('date_deleted', sql::IS, new sql_null())
                                    )
                                );
                            
                            $sql->execute(0); //Ensure the result is not from the cache
                            
                            /* Get the results */
                            $results = $sql->results();
                            
                            if (count($results) == 1){
                                /* Success */
                                $_this->session->user->password = $_this->request['new_password'];
                                if ($_this->session->user->save()){
                                    $_this->redirect("/password-changed");
                                }else{
                                    $_this->respond('change_password', array('errors' => array("We were unable to change your password at this time, please try again.")));
                                    $_this->redirect("/reset-password?token=" . $_this->request['token']);
                                }
                                
                            }else{
                                $_this->respond('change_password', array('errors' => array("We were unable to change your password at this time, please try again.")));
                                $_this->redirect("/change-password"); //Because we do not have a token
                            }
                            
                        }else{
                            $_this->respond('change_password', array('errors' => array("We were unable to change your password at this time, please try again.")));
                            $_this->redirect("/change-password"); //Because we do not have a token
                        }
                    }
                    
                });
                
                \application\controller_root::extend('action_change_password', function($_this){
                    if ($_this->session->is_logged_in){
                        $password = $_this->request['current_password'];
                        $new_password = $_this->request['new_password'];
                        
                        /* Check against previous password */
                        $current_password_raw = $_this->session->user->password;
                        list($salt, $current_password) = explode(":", $current_password_raw);
                        
                        $hashed_password = model_user::hash_password($password, $salt);
                        
                        if ($hashed_password == $current_password_raw){
                            $_this->session->user->password = $new_password;
                            if (!$_this->session->user->save()){
                                $_this->respond('change_password', array('errors' => array("We were unable to change your password at this time.")));
                                $_this->redirect("/change-password");
                                return;
                            }
                        }else{
                            $_this->respond('change_password', array('errors' => array("Your current password was incorrect, please try again.")));
                            $_this->redirect("/change-password");
                            return;
                        }
                        
                        $_this->redirect("/password-changed");
                        return;
                    }
                    
                    $_this->redirect("/");
                });
                
                /*
                 * Extend the root controller and add a action_sign_in
                 */
                \application\controller_root::extend('action_sign_in', function($_this){
                    
                    if ($_this->setting('users.username_type') == 'Email'){
                        if (isset($_this->request['email']) && isset($_this->request['password'])){
                            $user = new \adapt\users\model_user();
                            if ($user->load_by_email_address_password($_this->request['email'], $_this->request['password'])){
                                $_this->session->user = $user;
                                $_this->session->save();
                                
                                //TODO: Apply user settings
                                
                                /*
                                 * TODO:
                                 * We need a setting to turn this feature off (stay_signed_in) as
                                 * it isn't always desired.
                                 */
                                
                                if (isset($_this->request['stay_signed_in']) && $_this->request['stay_signed_in'] == 'Yes'){
                                    /* We are going to generate a new token for this user */
                                    $token = new \adapt\users\model_user_login_token();
                                    $token->user_id = $user->user_id;
                                    $token->token_type = 'Keep me signed in';
                                    $token->access_count = 0;
                                    $token->save();
                                }
                                
                                $redirect_url = "/";
                                if (isset($_this->request['redirect_url']) && $_this->request['redirect_url'] != ""){
                                    $redirect_url = $_this->request['redirect_url'];
                                }
                                
                                $_this->redirect($redirect_url, false);
                                //header('location: ' . $redirect_url);
                                //exit(0);
                            }else{
                                $errors['email'] = "Invalid email address or password, please try again.";
                                $_this->respond('sign_in_email', array('errors' => $errors));
                                
                                $_this->request('password', '');
                                
                                $_this->redirect($_this->request['current_url']);
                            }
                        }else{
                            $errors['email'] = "Invalid email address or password, please try again.";
                            $_this->respond('sign_in_email', array('errors' => $errors));
                            
                            $_this->request('password', '');
                            
                            $_this->redirect($_this->request['current_url']);
                        }
                    }else{
                        if (isset($_this->request['username']) && isset($_this->request['password'])){
                            $user = new \adapt\users\model_user();
                            if ($user->load_by_username_password($_this->request['username'], $_this->request['password'])){
                                $_this->session->user = $user;
                                $_this->session->save();
                                
                                header('location: ' . $_this->request['redirect_url']);
                                exit(0);
                            }else{
                                $_this->add_view(new html_pre('Login failed'));
                            }
                        }else{
                            $_this->add_view(new html_pre('Login missing info'));
                        }
                    }
                    
                });
                
                /*
                 * Extend the root controller and add action_sign_out
                 */
                \application\controller_root::extend('action_sign_out', function($_this){
                    //We need to clear any token cookies we have
                    $_this->cookie('login_token', '', 1);
                    $_this->session->user = new \adapt\users\model_user();
                    $_this->redirect("/");
                    
                    //TODO: Remove user settings
                });
                
                /*
                 * Extend the root controller and add action_join
                 */
                \application\controller_root::extend('action_join', function($_this){
                    $model = new model_user();
                    $errors = array();
                    if ($_this->setting('users.username_type') == 'Email'){
                        if ($model->load_by_email_address($_this->request['email'])){
                            $errors['email'] = "This email address has already been registered.";
                            $_this->respond('join_email', array('errors' => $errors));
                            
                            $_this->request('password', '');
                            $_this->request('confirm_password', '');
                            
                            $_this->redirect($_this->request['current_url']);
                        }else{
                            /* Create the user */
                            $contact = new model_contact();
                            $contact->title = 'Mr';
                            $contact->save();
                            
                            $email_type = new model_contact_email_type();
                            $email_type->load_by_name('Home');
                            
                            $contact_email = new model_contact_email();
                            $contact_email->contact_id = $contact->contact_id;
                            $contact_email->contact_email_type_id = $email_type->contact_email_type_id;
                            $contact_email->priority = 1;
                            $contact_email->email = $_this->request['email'];
                            $contact_email->save();
                            $contact->add($contact_email);
                            
                            $user = new \extensions\users\model_user();
                            $user->status = 'Active';
                            $user->contact_id = $contact->contact_id;
                            $user->username = 'contact_' . $contact->contact_id;
                            $user->password = $_this->request['password'];
                            $user->password_change_required = 'No';
                            $user->save();
                            $user->add($contact);
                            
                            /* Send user registration email */
                            if ($_this->setting('users.verify_email_address') == "Yes"){
                                
                                
                                $token = new model_user_login_token();
                                $token->user_id = $user->user_id;
                                $token->token_type = "Email verification";
                                $token->access_count = 0;
                                
                                if ($token->save()){
                                    $url = "http";
                                    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != ""){
                                        $url .= "s";
                                    }
                                    $url .= "://" . $_SERVER['SERVER_NAME'];
                                    if ($_SERVER['SERVER_PORT'] != "80" && $_SERVER['SERVER_PORT'] != "443"){
                                        $url .= ":" . $_SERVER['SERVER_PORT'];
                                    }
                                    $url .= "/verify-email?actions=email-token-login&token=" . $token->token;
                                    
                                    $email = $_this->email_account->new_email_from_template('user_registration_verify_email_template');
                                    
                                    $vars = array('verify-email-url' => $url);
                                    $hash_vars = $user->to_hash_string();
                                    
                                    //foreach($hash_vars as $pair){
                                    //    $key = $pair['key'];
                                    //    $value = $pair['value'];
                                    //    $vars[$key] = $value;
                                    //}
                                    
                                    $email->variables(array_merge($vars, $hash_vars));
                                    
                                    $email->to($_this->request['email']);
                                    $email->send(false);
                                }
                                
                                
                                //$_this->email_account->new_email_from_template('user_registration_verify_email_template')
                                //    ->to($contact_email->email)
                                //    ->send(false);
                            }else{
                                $_this->email_account->new_email_from_template('user_registration_template')
                                    ->to($contact_email->email)
                                    ->send(true);
                            }
                            
                            /* Set the session */
                            $_this->session->user = $user;
                            
                            
                            /* Should redirect at this point */
                            $this->redirect("/"); //TODO: Redirect back to the page where they came from
                        }
                    }else{
                        /* Username */
                        if ($model->load_by_username($_this->request['username'])){
                            $errors['email'] = "This username has already been registered.";
                            $_this->respond('join_username', array('errors' => $errors));
                            
                            $_this->request('password', '');
                            $_this->request('confirm_password', '');
                            
                            $_this->redirect($_this->request['current_url']);
                        }else{
                            /* Create the user */
                            $contact = new model_contact();
                            $contact->title = 'Mr';
                            $contact->save();
                            
                            $user = new \adapt\users\model_user();
                            $user->status = 'Active';
                            $user->contact_id = $contact->contact_id;
                            $user->username = $_this->request['username'];
                            $user->password = $_this->_request['password'];
                            $user->password_change_required = 'No';
                            $user->save();
                            
                            /* Set the session */
                            $_this->session->user = $user;
                            
                            /* Should redirect at this point */
                            $this->redirect("/"); //TODO: Redirect back to the page where they came from
                        }
                    }
                });
                
                \application\controller_root::extend('action_password_token_login', function($_this){
                    $token = new model_user_login_token();
                    if ($token->load_by_token($_this->request['token']) && $token->token_type == "Password reset"){
                        $_this->session->user = new model_user($token->user_id);
                        $_this->redirect("/reset-password?token=" . $_this->request['token']);
                    }
                });
                
                \application\controller_root::extend('action_email_token_login', function($_this){
                    $token = new model_user_login_token();
                    if ($token->load_by_token($_this->request['token']) && $token->token_type == "Email verification"){
                        $_this->session->user = new model_user($token->user_id);
                        $contact = $_this->session->user->contact;
                        if ($contact instanceof \adapt\model && $contact->table_name == "contact"){
                            $children = $contact->get();
                            foreach($children as $child){
                                if ($child instanceof \adapt\model && $child->table_name == "contact_email"){
                                    $child->email_address_verified = 'Yes';
                                    $child->save();
                                    $this->redirect("/email-verified");
                                    break;
                                }
                            }
                        }
                    }
                });
                
                \application\controller_root::extend('action_send_email_verification', function($_this){
                    if ($_this->session->is_logged_in){
                        
                        $contact = $_this->session->user->contact;
                        if ($contact instanceof \adapt\model && $contact->table_name == "contact"){
                            $children = $contact->get();
                            foreach($children as $child){
                                if ($child instanceof \adapt\model && $child->table_name == "contact_email"){
                                    $child->email = $_this->request['email'];
                                    $child->save();
                                    break;
                                }
                            }
                        }
                        
                        
                        $token = new model_user_login_token();
                        $token->user_id = $_this->session->user->user_id;
                        $token->token_type = "Email verification";
                        $token->access_count = 0;
                        
                        if ($token->save()){
                            $url = "http";
                            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != ""){
                                $url .= "s";
                            }
                            $url .= "://" . $_SERVER['SERVER_NAME'];
                            if ($_SERVER['SERVER_PORT'] != "80" && $_SERVER['SERVER_PORT'] != "443"){
                                $url .= ":" . $_SERVER['SERVER_PORT'];
                            }
                            $url .= "/verify-email?actions=email-token-login&token=" . $token->token;
                            
                            $email = $_this->email_account->new_email_from_template('user_verify_email_template');
                            
                            $vars = array('verify-email-url' => $url);
                            $hash_vars = $_this->session->user->to_hash_string();
                            
                            //foreach($hash_vars as $pair){
                            //    $key = $pair['key'];
                            //    $value = $pair['value'];
                            //    $vars[$key] = $value;
                            //}
                            
                            $email->variables(array_merge($vars, $hash_vars));
                            
                            $email->to($_this->request['email']);
                            $email->send(false);
                        }
                        
                        //$_this->email_account->new_email_from_template('user_verify_email_template')
                        //    ->to($_this->session->user->contact->email)
                        //    ->send(false);
                    }
                });
                
                
                if (!preg_match("/verify-email/", $this->request['url']) && $this->session->is_logged_in && $this->setting('users.verify_email_address') == "Yes"){
                    //TODO: Check the email address is verified and redirect to a page if not
                    $contact = $this->session->user->contact;
                    $has_verified_email = false;
                    
                    if ($contact instanceof \adapt\model && $contact->table_name == 'contact'){
                        $children = $contact->get();
                        foreach($children as $child){
                            if ($child instanceof \adapt\model && $child->table_name == 'contact_email'){
                                if ($child->email_address_verified == "Yes"){
                                    $has_verified_email = true;
                                }
                                break; //Only the primary that recieves email needs to be verified
                            }
                        }
                        
                        if ($has_verified_email == false){
                            $this->redirect("/verify-email");
                        }
                    }
                    
                }
                
                if (!preg_match("/change-password/", $this->request['url']) && !preg_match("/change-password/", $this->request['actions']) && $this->session->is_logged_in && $this->session->user->password_change_required == "Yes"){
                    //TODO: Redirect to password change page
                    $this->redirect("/change-password");
                    //print new html_pre('PW needed');
                }
                
                return true;
            }
            
            return false;
        }
        
        public function process_users_tag($bundle, $tag_data){
            if ($bundle instanceof \adapt\bundle && $tag_data instanceof \adapt\xml){
                $this->register_install_handler($this->name, $bundle->name, 'install_users');
                
                $user_nodes = $tag_data->get();
                $this->_users[$bundle->name] = [];
                
                foreach($user_nodes as $user_node){
                    if ($user_node instanceof \adapt\xml && $user_node->tag == 'user'){
                        $child_nodes = $user_node->get();
                        $user = [];
                        foreach($child_nodes as $child_node){
                            if ($child_node instanceof \adapt\xml){
                                switch($child_node->tag){
                                case "username":
                                case "password":
                                case "password_change_required":
                                    $user[$child_node->tag] = $child_node->get(0);
                                    break;
                                case "contact":
                                    $contact_nodes = $child_node->get();
                                    $contact = [];
                                    foreach($contact_nodes as $contact_node){
                                        if ($contact_node instanceof \adapt\xml){
                                            switch($contact_node->tag){
                                            case "country":
                                            case "title":
                                            case "forename":
                                            case "middle_names":
                                            case "surname":
                                            case "nickname":
                                            case "date_of_birth":
                                                $contact[$contact_node->tag] = $contact_node->get(0);
                                                break;
                                            case "contact_email":
                                                $contact_email_nodes = $contact_node->get();
                                                $contact_email = [];
                                                foreach($contact_email_nodes as $contact_email_node){
                                                    if ($contact_email_node instanceof \adapt\xml){
                                                        switch($contact_email_node->tag){
                                                        case "type":
                                                        case "priority":
                                                        case "email":
                                                        case "email_verified":
                                                            $contact_email[$contact_email_node->tag] = $contact_email_node->get(0);
                                                            break;
                                                        }
                                                    }
                                                }
                                                if (!is_array($contact['contact_email'])) $contact['contact_email'] = [];
                                                $contact['contact_email'][] = $contact_email;
                                                break;
                                            }
                                        }
                                    }
                                    $user['contact'] = $contact;
                                    break;
                                default:
                                    $user[$child_node->tag] = $child_node->get(0);
                                }
                            }
                        }
                        if (!is_array($this->_users[$bundle->name])) $this->_users[$bundle_name] = [];
                        $this->_users[$bundle->name][] = $user;
                    }
                }
            }
        }
        
        public function install_users($bundle){
            if ($bundle instanceof \adapt\bundle){
                if (is_array($this->_users[$bundle->name])){
                    foreach($this->_users[$bundle->name] as $user){
                        $u = new model_user();
                        
                        foreach($user as $key => $value){
                            if ($key != "contact"){
                                $u->$key = $value;
                            }
                        }
                        
                        if (is_array($user['contact'])){
                            $c = new model_contact();
                            $country = new model_country();
                            if ($country->load_by_name($user['contact']['country'])){
                                $c->country_id = $country->country_id;
                            }
                            $c->title = $user['contact']['title'];
                            $c->forename = $user['contact']['forename'];
                            $c->middle_names = $user['contact']['middle_names'];
                            $c->surname = $user['contact']['surname'];
                            $c->nickname = $user['contact']['nickname'];
                            $c->date_of_birth = $user['contact']['date_of_birth'];
                            $c->save();
                            
                            if (is_array($user['contact']['contact_email'])){
                                foreach($user['contact']['contact_email'] as $contact_email){
                                    $contact_email_type = new model_contact_email_type();
                                    if ($contact_email_type->load_by_name($contact_email['type'])){
                                        $ce = new model_contact_email();
                                        $ce->contact_id = $c->contact_id;
                                        $ce->contact_email_type_id = $contact_email_type->contact_email_type_id;
                                        $ce->priority = $contact_email['priority'];
                                        $ce->email = $contact_email['email'];
                                        $ce->email_address_verified = $contact_email['email_verified'];
                                        $ce->save();
                                    }
                                }
                            }
                            $u->contact_id = $c->contact_id;
                        }
                        $u->save();
                    }
                }
            }
        }
        
    }
    
    
}

?>
