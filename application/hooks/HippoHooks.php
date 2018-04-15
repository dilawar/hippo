<?php

/**
 * 
 */
class HippoHooks 
{
    
    /**
     * 
     */
    public function __construct()
    {
        $this->CI =& get_instance();

        
    }

    public function PreController( )
    {

        if( ! $this->CI->session->AUTHENTICATED )
        {
            $page = basename( $_SERVER[ 'PHP_SELF'] );
            if( $page == 'index.php' || $page == 'welcome'  || $page == 'login' )
            {
                // Already authenticated. Send to user
                return;
            }

            redirect( 'welcome' );
        }
    }
}


?>
