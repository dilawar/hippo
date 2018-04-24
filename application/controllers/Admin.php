<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';

require_once __DIR__.'/AdminAcad.php';


class Admin extends CI_Controller
{
    use AdminAcad;

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


    public function addupdatedelete( )
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'admin_updateuser' );
    }

    public function updateuser( $user = '' )
    {
        $toUpdate = 'roles,title,joined_on,eligible_for_aws,laboffice' .
           ',status,valid_until,alternative_email,pi_or_host,specialization';
        $res = updateTable( 'logins', 'login', $toUpdate, $_POST );
        if( $res )
            echo flashMessage("Successfully updated.");

        redirect('admin/addupdatedelete');
    }

    public function deleteuser( $md5 = '' )
    {
        $user = $_POST['login'];
        $res = deleteFromTable( 'logins', 'login', $_POST  );
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
