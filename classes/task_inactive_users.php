<?php

namespace adapt\users{
    
    /* Prevent Direct Access */
    defined('ADAPT_STARTED') or die;
    
    class task_inactive_users extends \adapt\scheduler\task{
        
        public function task(){
            parent::task();
            
            $this->_log->label = "Checking for inactive users";
            $this->_log->save();
            
            $output = "";
            
            $sql = $this->data_source->sql;
            
            $sql->select('*')
                ->from('');
            
            /* Children should override this with the code they wish to run */
            return $output;
        }

    }
    
}

?>