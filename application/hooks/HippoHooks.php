<?php

require_once BASEPATH.'extra/check_access_permissions.php';

/**
 * 
 */
class HippoHooks 
{
    public function __construct()
    {
        $this->CI =& get_instance();
    }

    // Somehow I have to make sur that this is not triggered on Info pages.
    public function PreController( )
    {
        $class = $this->CI->router->fetch_class();
        if( $class === 'info' ) 
        {
            // Just check we are inside intranet.
            if( !(isAuthenticated() || isIntranet()))
            {
                echo flashMessage( "To access this page, either login first or use intranet." );
                redirect( "welcome");
                return;
            }
            return;
        }

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
            $page = basename( $_SERVER[ 'PHP_SELF'] );
            if( ! ( $page == 'index.php' || $page == 'welcome' || $page == 'login') )
            {
                $this->CI->session->set_flashdata('error', "You are not authenticated yet." );
                redirect( 'welcome' );
                return;
            }
        }
    }
}


?>
