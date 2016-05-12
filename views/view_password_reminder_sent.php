<?php

namespace adapt\users{

    /* Prevent Direct Access */
    defined('ADAPT_STARTED') or die;
    
    class view_password_reminder_sent extends view{
        
        
        public function __construct(){
            parent::__construct('div');
            
            $row = new \bootstrap\views\view_row();
            
            $left_col = new \bootstrap\views\view_cell(new html_h3('Reset password'), 12, 12, 12, 12);
            //$right_col = new \bootstrap\views\view_cell(new html_h3('Join now'), 12, 12, 6, 6);
            $left_col->add(new html_p("We have emailed you a link for you to reset your password."));
            
            
            $row->add($left_col);
            
            
            $panel = new \bootstrap\views\view_panel($row);
            $this->add($panel);
            
            $panel->title = "Reset your password";
            
            
            
            
            
        }
        
    }
    
}


?>