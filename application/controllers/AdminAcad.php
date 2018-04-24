<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';

trait AdminAcad
{
    function index()
    {
        $this->home();
    }

    public function acad( $action = '' )
    {
        // If no action is selected, view admin page.
        if( ! $action )
        {
            $this->template->set( 'header', 'header.php' );
            $this->template->load( 'admin_acad.php' );
        }
        else
            $this->acad_action( $action );
    }

    public function acad_action( $action )
    {
        if( $action == 'manages_upcoming_aws' )
        {
            $this->template->set( 'header', 'header.php');
            $this->template->load( 'admin_acad_manages_upcoming_aws.php' );
        }
        else
        {
            flashMessage( "$action is not implemented yet");
            redirect( 'admin/acad' );
        }
    }
}

?>
