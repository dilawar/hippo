<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH. "extra/methods.php" ;
require_once BASEPATH. "extra/ldap.php" ;
require_once BASEPATH. "database.php" ;
require_once BASEPATH. "extra/helper/imap.php";

class Welcome extends CI_Controller {

    public function index()
    {
        $this->load->view('index');
    }

    public function login( )
    {
        $login = __get__( $_POST, 'username', '' );
        $pass = __get__($_POST, 'pass' );

        // If user use @instem.ncbs.res.in or @ncbs.res.in, ignore it.
        $ldap = explode( '@', $login)[0];
        $_SESSION['AUTHENTICATED'] = false;

        // Check if ldap is available. If it is use LDAP else fallback to imap based 
        // authentication.
        $auth = false;
        if( ldapAlive( 'ldap.ncbs.res.in' ) )
            $auth = authenticateUsingLDAP( $ldap, $pass );
        else
        {
            // Try login using IMAP.
            $auth = authenticateUsingIMAP( $ldap, $pass );
            if( ! $auth )
            {
                $this->session->set_flashdata( 'error', "Loging unsucessful. Try again!" );
                redirect( "/welcome" );
            }
        }

        if( $auth )
        {
            $_SESSION['AUTHENTICATED'] = true;
            $_SESSION['user'] = $ldap;

            $ldapInfo = getUserInfoFromLdap( $ldap );
            if( $ldapInfo )
            {
                $email = $ldapInfo[ 'email' ];
                $_SESSION['email'] = $email;
                $type = __get__( $ldapInfo, 'title', 'UNKNOWN' );

                // In any case, create a entry in database.
                createUserOrUpdateLogin( $ldap, $ldapInfo, $type );

                // Update email id.
                $res = updateTable( 'logins', 'login', 'email'
                    , array( 'login' => $ldap, 'email' => $email )
                );
            }

            echo printInfo( "Loging successful!" );
            $this->session->set_flashdata( 'success', "Loging sucessful.!" );
            redirect( "/user/home" );
        }
        else 
        {
            $this->session->set_flashdata( 'error', "Loging unsucessful. Try again!" );
            redirect( "/welcome" );
        }
    }
}

?>
