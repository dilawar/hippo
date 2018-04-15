<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once BASEPATH. "extra/methods.php" ;
require_once BASEPATH. "extra/ldap.php" ;
require_once BASEPATH. "database.php" ;
require_once BASEPATH. "extra/helper/imap.php";

class User extends CI_Controller {

    public function home()
    {
        log_message( "info", "User home" );
        $this->load->view('user');
    }

    public function book( $args = null )
    {
        log_message( 'info', 'Booking page' );
        $this->load->view( 'quickbook' );
    }

}

?>
