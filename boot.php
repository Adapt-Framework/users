<?php

namespace extensions\users;
use \frameworks\adapt as adapt;

/* Prevent direct access */
defined('ADAPT_STARTED') or die;

$adapt = $GLOBALS['adapt'];

$adapt->dom->head->add(new adapt\html_link(array('type' => 'text/css', 'rel' => 'stylesheet', 'href' => '/adapt/extensions/users/static/css/users.css')));

/* Add user property to the session object */
\extensions\sessions\model_session::extend('pget_user', function($_this){
    $user = $_this->store('users.user');
    if ($user && $user instanceof \frameworks\adapt\model && $user->table_name = 'user'){
        return $user;
    }else{
        $user = new \extensions\users\model_user();
        
        $user_id = $_this->data('users.user.user_id');
        if ($user_id > 0){
            $user->load($user_id);
            $_this->store('users.user', $user);
        }elseif(!is_null($_this->cookie('login_token'))){
            $token = new \extensions\users\model_user_login_token();
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
            $token = new \extensions\users\model_user_login_token();
            if ($token->load_by_token($_this->request('alt')) && $token->token_type == 'Auto login'){
                $user->load($token->user_id);
                $_this->store('users.user', $user);
            }
            
        }
    }
    
    return $user;
});

\extensions\sessions\model_session::extend('pset_user', function($_this, $user){
    if ($user && $user instanceof \extensions\users\model_user){
        $_this->store('users.user', $user);
        if ($user->is_loaded){
            $_this->data('users.user.user_id', $user->user_id);
        }else{
            $_this->remove_data('users.user.user_id');
        }
        $_this->save();
    }else{
        $_this->remove_store('users.user');
        $_this->remove_data('users.user.user_id');
        $_this->save();
    }
});

/* Add is_logged_in read only property to the session property */
\extensions\sessions\model_session::extend('pget_is_logged_in', function($_this){
    return $_this->user->is_loaded;
});


/*
 * Extend the root controller and add a view_sign_in
 */
\application\controller_root::extend('view_sign_in', function($_this){
    if ($_this->setting('users.username_type') == 'Username'){
        $_this->add_view(new \extensions\users\view_login_panel(\extensions\users\view_login_panel::USERNAME));
    }else{
        $_this->add_view(new \extensions\users\view_login_panel(\extensions\users\view_login_panel::EMAIL));
    }
});

/*
 * Extend the root controller and add a view_forgot_password
 */
\application\controller_root::extend('view_forgot_password', function($_this){
    if ($_this->setting('users.username_type') == 'Username'){
        $_this->add_view(new \extensions\users\view_login_panel(\extensions\users\view_login_panel::USERNAME));
    }else{
        $_this->add_view(new \extensions\users\view_login_panel(\extensions\users\view_login_panel::EMAIL));
    }
});



/*
 * Extend the root controller and add a action_sign_in
 */
\application\controller_root::extend('action_sign_in', function($_this){
    
    if ($_this->setting('users.username_type') == 'Email'){
        if (isset($_this->request['email']) && isset($_this->request['password'])){
            $user = new \extensions\users\model_user();
            if ($user->load_by_email_address_password($_this->request['email'], $_this->request['password'])){
                $_this->session->user = $user;
                $_this->session->save();
                
                
                /*
                 * TODO:
                 * We need a setting to turn this feature off (stay_signed_in) as
                 * it isn't always desired.
                 */
                
                if (isset($_this->request['stay_signed_in']) && $_this->request['stay_signed_in'] == 'Yes'){
                    /* We are going to generate a new token for this user */
                    $token = new \extensions\users\model_user_login_token();
                    $token->user_id = $user->user_id;
                    $token->token_type = 'Keep me signed in';
                    $token->access_count = 0;
                    $token->save();
                }
                
                $redirect_url = "/";
                if (isset($_this->request['redirect_url'])){
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
            $_this->respond('sign-in', array('errors' => $errors));
            
            $_this->request('password', '');
            
            $_this->redirect($_this->request['current_url']);
        }
    }else{
        if (isset($_this->request['username']) && isset($_this->request['password'])){
            $user = new \extensions\users\model_user();
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
    $_this->session->user = new \extensions\users\model_user();
    $_this->redirect("/");
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
            
            $user = new \extensions\users\model_user();
            $user->status = 'Active';
            $user->contact_id = $contact->contact_id;
            $user->username = 'contact_' . $contact->contact_id;
            $user->password = $_this->_request['password'];
            $user->password_change_required = 'No';
            $user->save();
            
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
            
            $user = new \extensions\users\model_user();
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



?>