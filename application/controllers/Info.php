<?php

require_once BASEPATH . 'autoload.php';

class Info extends CI_Controller 
{
    // NOTE: Checking for permission is in pre-hook.

    public function loadview( $view, $data = array())
    {
        $data['controller'] = 'info';
        $this->template->set('header', 'header.php');
        $this->template->load($view);
    }

    public function aws( $arg = '' )
    {
        if( $arg == 'search' )
        {
            $this->template->set( 'header', 'header.php' );
            $this->template->load( 'user_aws_search' );
        }
        else if( $arg == 'roster' )
        {
            $this->template->set( 'header', 'header.php' );
            $this->template->load( 'aws_roster' );
        }
        else
        {
            $this->template->set('header', 'header.php' );
            $this->template->load('aws' );
        }
    }

    public function events( )
    {
        $this->template->set('header', 'header.php' );
        $this->template->load('events' );
    }

    public function booking( )
    {
        $this->template->set('header', 'header.php' );
        $this->template->load('allevents' );
    }

    public function statistics( )
    {
        $this->template->set('header', 'header.php' );
        $this->template->load('statistics' );
    }

    public function courses( )
    {
        $this->template->set('header', 'header.php' );
        $this->template->load('courses' );

    }

    public function jc( )
    {
        $this->loadview( 'jc.php' );
    }

}

?>
