<?php

require_once BASEPATH . 'autoload.php';

class Errors extends CI_Controller 
{
    // NOTE: Checking for permission is in pre-hook.

    public function loadview( $view, $data = array())
    {
        $data['controller'] = 'info';
        $this->template->set('header', 'header.php');
        $this->template->load($view);
    }

    public function page_missing( $arg = '' )
    {
        $req = $_SERVER['REDIRECT_URL'];
        $uri = explode( '/', $req );
        $page = end( $uri );
        if( $page == 'allevents.php' )
        {
            redirect( 'info/booking' );
            return;
        }
        elseif( $page == 'rss.php' )
        {
            redirect( 'feed/rss' );
            return;
        }
        elseif( $page == 'info.php' )
        {
            redirect( 'info/info' );
            return;
        }
        elseif( $page == 'execute.php' )
        {
            $id = $_GET['id'];
            redirect( "user/execute/$id" );
        }

        flashMessage( "Page you have requested is not found <tt>$req</tt>." );
        redirect( "welcome" );
    }
}

?>
