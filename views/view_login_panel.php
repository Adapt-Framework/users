<?php

namespace adapt\users{

    /* Prevent Direct Access */
    defined('ADAPT_STARTED') or die;
    
    class view_login_panel extends view{
        
        const EMAIL = 'email';
        const USERNAME = 'username';
        
        public function __construct($style = self::EMAIL){
            parent::__construct('div');
            
            $form_view = null;
            $form_title = null;
            $form = new \adapt\forms\model_form();
            
            $data = array();
            
            $data = array_merge($data, $this->request);
            
            $name = "sign_in_email";
            if ($style == self::USERNAME) $name = "sign_in_username";
            
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
            
            if ($this->setting('users.allow_registrations') == 'Yes'){
                $left_col = new \bootstrap\views\view_cell(new html_h3('Sign in'), 12, 12, 6, 6);
                $right_col = new \bootstrap\views\view_cell(new html_h3('Join now'), 12, 12, 6, 6);
                $left_col->add($form_view);
                
                $name = "join_email";
                if ($style == self::USERNAME) $name = "join_username";
                $join_form = new \adapt\forms\model_form();
                if ($form->load_by_name($name)){
                    $join_view = $form->get_view($data);
                    if ($join_view instanceof \adapt\html){
                        $join_view->find('h1')->detach();
                    }
                    $right_col->add($join_view);
                }
                
                $row->add(array($left_col, $right_col));
            }else{
                $left_col = new \bootstrap\views\view_cell($form_view, 12, 12, 12, 12);
                $row->add(array($left_col));
            }
            
            $panel = new \bootstrap\views\view_panel($row);
            $this->add($panel);
            
            if ($form_title) $panel->title = $form_title;
            
            
            
            
            
        }
        
    }
    
}


?>