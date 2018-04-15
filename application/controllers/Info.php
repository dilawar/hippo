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

        $this->load->view('header' );
    }

    public function aws( )
    {
        $this->load->view('aws' );
    }

    public function events( )
    {
        $this->load->view('events' );
    }

    public function booking( )
    {
        $this->load->view('allevents');
    }
}

?>
