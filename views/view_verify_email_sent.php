<?php

namespace adapt\users{

    /* Prevent Direct Access */
    defined('ADAPT_STARTED') or die;
    
    class view_verify_email_sent extends view{
        
        const EMAIL = 'email';
        const USERNAME = 'username';
        
        public function __construct(){
            parent::__construct('div');
            $form_view = null;
            $form_title = null;
            $form = new \adapt\forms\model_form();
            
            $data = array('email' => $this->session->user->contact->email);
            
            $data = array_merge($data, $this->request);
            //$this->add(new html_pre(print_r($data, true)));
            $name = "verify_email";
            
            
            if ($form->load_by_name($name)){
                
                
                
                $form_view = $form->get_view($data);
                if ($form_view instanceof \adapt\html){
                    $form_title = $form_view->find('h1')->detach();
                    //if ($form_title && $form_title instanceof \frameworks\adapt\html){
                        $form_title = $form_title->get(0);
                    //}
                }
            }
            
            $row = new \bootstrap\views\view_row();
            
            $left_col = new \bootstrap\views\view_cell(new html_h3('Verify your email address'), 12, 12, 12, 12);
            $left_col->add(new html_p("We have resent your verification email."));
            //$right_col = new \bootstrap\views\view_cell(new html_h3('Join now'), 12, 12, 6, 6);
            $left_col->add($form_view);
            
            
            $row->add($left_col);
            
            
            $panel = new \bootstrap\views\view_panel($row);
            $this->add($panel);
            
            if ($form_title) $panel->title = $form_title;
            
            
            
            
            
        }
        
    }
    
}


?>