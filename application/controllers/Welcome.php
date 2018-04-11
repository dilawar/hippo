<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH. "extra/methods.php" ;
require_once BASEPATH. "extra/ldap.php" ;
require_once BASEPATH. "database.php" ;
require_once BASEPATH. "extra/helper/imap.php";

class Welcome extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
            $this->load->view('index');
	}

        public function login( )
        {
            $data = $this->input->post();

            $login = __get__( $data, 'username', '' );
            $pass = __get__($data, 'pass' );

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
                    redirect( "/welcome/login" );
                }
            }

            if( $auth )
            {
                $_SESSION['AUTHENTICATED'] = TRUE;
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

                $this->session->set_flashdata( 'success', "Loging sucessful.!" );
                redirect( "/user/home" );
            }
            else 
            {
                $this->session->set_flashdata( 'error', "Loging unsucessful. Try again!" );
                redirect( "/welcome/login" );
            }
        }
}

?>
