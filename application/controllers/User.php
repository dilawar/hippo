<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once BASEPATH. "extra/methods.php" ;
require_once BASEPATH. "extra/ldap.php" ;
require_once BASEPATH. "database.php" ;
require_once BASEPATH. "extra/helper/imap.php";

class User extends CI_Controller {

    function __construct( ) 
    {
        parent::__construct( );
        $this->load->view('header');
    }

    public function home()
    {
        $this->load->view('user');
    }

    public function book( $args = null )
    {
        log_message( 'info', 'Booking page' );
        $this->load->view( 'quickbook' );
    }

    public function booking_request( $args = null )
    {
        log_message( 'info', 'Creating booking requests' );
        $this->load->view( 'user_submit_booking_request' );
    }


    public function logout( )
    {
        $this->session->sess_destroy();
        redirect( '' );
    }

}

?>
