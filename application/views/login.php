<?php 

require_once __DIR__ . '/header.php';
require_once BASEPATH. "extra/methods.php" ;
require_once BASEPATH. "extra/ldap.php" ;
require_once BASEPATH. "database.php" ;
require_once BASEPATH. "extra/helper/imap.php";

$conf = $_SESSION['conf'];
$login = __get__( $_POST, 'username', '' );

// If user use @instem.ncbs.res.in or @ncbs.res.in, ignore it.
$ldap = explode( '@', $login);
$ldap = $ldap[0];

$pass = __get__($_POST, 'pass' );

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

    goToPage( "user/home", 0 );
    exit;
}
else 
{
    echo printWarning( "Loging unsucessful. Going back" );
    goToPage( "welcome", 3 );
    exit;
}

?>
