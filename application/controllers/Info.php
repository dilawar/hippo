<?php

require_once BASEPATH . 'autoload.php';

class Info extends CI_Controller 
{
    public function __construct( )
    {
        parent::__construct();

        // Show it only if accessed from intranet or user have logged in.
        if( ! (isIntranet( ) || isAuthenticated( ) ) )
        {
            echo printWarning( "To access this page, either use Intranet or log-in first" );
            echo closePage( );
            exit;
        }

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
}

?>
