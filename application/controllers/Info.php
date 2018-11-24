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

    public function talks( )
    {
        $this->template->set('header', 'header.php' );
        $this->template->load('talks' );
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

    public function publications()
    {
        $this->template->set('header', 'header.php' );
        $this->template->load('publications' );
    }

    public function rss( )
    {
        redirect( 'Feed/rss' );
    }

    public function jc( )
    {
        $this->loadview( 'jc.php' );
    }

    public function preprints()
    {
        $this->loadview( 'preprints.php');
    }

}

?>
