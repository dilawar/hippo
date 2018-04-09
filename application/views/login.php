<?php 

include_once( "header.php" );
include_once( "methods.php" );
include_once( "database.php" );
include_once( "ldap.php" );

include_once "./helper/imap.php";

$conf = $_SESSION['conf'];
$login = $_POST['username'];

// If user use @instem.ncbs.res.in or @ncbs.res.in, ignore it.
$ldap = explode( '@', $login);
$ldap = $ldap[0];

$pass = $_POST['pass'];

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

    // NOTE: This is not NEEDED. Un-neccessary for sending user to info page.
    // They should be sent directly to their HOME ASAP.
    //if( $userInfo['title'] == 'UNSPECIFIED' )
    //{
    //   echo printInfo( "Please review your details " );
    //   goToPage( "user_info.php", 1 );
    //   exit;
    //}

    goToPage( "user.php", 0 );
    exit;
}
else 
{
    echo printWarning( "Loging unsucessful. Going back" );
    goToPage( "index.php", 3 );
    exit;
}

?>
