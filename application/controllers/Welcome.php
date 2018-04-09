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
            $this->load->view( 'login', $data );

            $conf = $_SESSION['conf'];
            $login = __get__( $data, 'username', '' );

            // If user use @instem.ncbs.res.in or @ncbs.res.in, ignore it.
            $ldap = explode( '@', $login);
            $ldap = $ldap[0];

            $pass = __get__($data, 'pass' );

            $_SESSION['AUTHENTICATED'] = FALSE;

            // Check if ldap is available. If it is use LDAP else fallback to imap based 
            // authentication.
            $auth = null;
            if( ldapAlive( 'ldap.ncbs.res.in' ) )
                $auth = authenticateUsingLDAP( $ldap, $pass );
            else
            {
                // Try login using IMAP.
                $auth = authenticateUsingIMAP( $ldap, $pass );
                if( ! $auth )
                {
                    echo printErrorSevere("Error: Username/password is incorrect. Try again...");
                    goToPage( 'index.php', 2 );
                    $auth = null;
                }
            }

            if( $auth )
            {
                echo printInfo( "Login successful" );

                $_SESSION['AUTHENTICATED'] = TRUE;
                $_SESSION['user'] = $ldap;

                $ldapInfo = getUserInfoFromLdap( $ldap );
                $email = $ldapInfo[ 'email' ];
                $_SESSION['email'] = $email;

                $type = __get__( $ldapInfo, 'title', 'UNKNOWN' );

                // In any case, create a entry in database.
                createUserOrUpdateLogin( $ldap, $ldapInfo, $type );

                // Update email id.
                $res = updateTable( 'logins', 'login', 'email'
                    , array( 'login' => $ldap, 'email' => $email )
                );

                // If user title is unspecified then redirect him/her to edit user info
                $userInfo = getUserInfo( $ldap );

                redirect( "user/home", 'refresh' );
            }
            else 
            {
                echo printWarning( "Loging unsucessful. Going back" );
                redirect( "welcome", 'refresh' );
            }
        }
}

?>
