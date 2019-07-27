<?php
require_once BASEPATH . 'autoload.php';

class Forum extends CI_Controller 
{
    // NOTE: Checking for permission is in pre-hook.

    public function loadview( $view, $data = array())
    {
        $data['controller'] = 'forum';
        $this->template->set('header', 'header.php');
        $this->template->load($view, $data);
    }

    public function forum($arg='')
    {
        if(! $args)
            $args = 'list';

        if($args === 'list')
        {
        }

    }
}
