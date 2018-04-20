<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';


class Admin extends CI_Controller
{
    function index()
    {
        $this->home();
    }

    // Show user home.
    public function home()
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'admin' );
    }


    public function updateuser( $user )
    {

        $user = $_POST[ 'login' ];
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'admin_updateuser' );
    }

    public function deleteuser( $user )
    {
        $res = deleteFromTable( 'logins', 'login', array( 'login' => $user ) ); 
        if( $res )
        {
            echo flashMessage( "Successfully deleted $user." );
            if( $this->agent->is_referral() )
                redirect( $this->agent->referrer() );
            else
                redirect( 'admin' );
        }
    }

    public function showusers( $arg = '' )
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'admin_showusers' );
    }
}

?>
