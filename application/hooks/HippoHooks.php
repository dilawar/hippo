<?php

/**
 * 
 */
class HippoHooks 
{
    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function PreController( )
    {

        // If user is already is authenticated but somehow come to welcome page
        // etc. Move this to home.
        if( $this->CI->session->AUTHENTICATED )
        {
            $page = basename( $_SERVER[ 'PHP_SELF'] );
            if( $page == 'index.php' || $page == 'welcome')
            {
                // Already authenticated. Send to user
                redirect( 'user/home' );
            }
            else if($page == 'login' )
                return;

        }
        else
        {
            $this->CI->session->set_flashdata( 'error', "Not yet authenticated" );
            $page = basename( $_SERVER[ 'PHP_SELF'] );
            if( $page != 'login' && $page != 'welcome' )
                redirect( 'welcome' );
        }
    }
}


?>
