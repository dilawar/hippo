<?php

include_once 'header.php';
include_once 'database.php' ;
include_once 'methods.php';

function loginOrIntranet( )
{
    return "<p>You must either <a href=\"index.php\">login</a> or use
        intranet to access this page<p>";
}

function isAuthenticated( )
{
    if( __get__( $_SESSION, 'AUTHENTICATED', false ) )
        return true;
    return false;
}

function requiredPrivilege( $role )
{
    $user = __get__( $_SESSION, 'user', '' );
    if( ! $user )
        return false;

    $roles = getRoles( $user );
    return in_array( $role, $roles );
}

function anyOfTheseRoles( $roles )
{
    if( is_string( $roles ) )
        $roles = explode( ',', $roles );

    $user = __get__( $_SESSION, 'user', '' );
    if( ! $user )
        return false;

    $userRoles = getRoles( $_SESSION['user'] );
    foreach( $roles as $role )
        if( in_array( $role, $userRoles ) )
            return true;

    return false;
}

function allOfTheseRoles( $roles )
{
    if( is_string( $roles ) )
        $roles = explode( ',', $roles );

    if( ! $roles )
        return false;

    $user = __get__( $_SESSION, 'user', '' );
    if( ! $user )
        return false;

    $userRoles = getRoles( $_SESSION['user'] );
    foreach( $roles as $role )
        if( ! in_array( $role, $userRoles ) )
            return false;
    return true;
}

function mustHaveAnyOfTheseRoles( $roles )
{
    if( anyOfTheseRoles( $roles ) )
        return true;
    else
    {
        echo printWarning( "You don't have permission to access this page" );
        goToPage( "index.php", 3 );
        exit( 0 );
    }
}

function mustHaveAllOfTheseRoles( $roles )
{
    if( allOfTheseRoles( $roles ) )
        return true;
    else
    {
        echo printWarning( "You don't have permission to access this page" );
        goToPage( "index.php", 3 );
        exit( 0 );
    }
}


// Get the IP address of user.
function getRealIpAddr()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
        $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
        $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
        $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
    * @brief Check if user is logged in from intranet. FIXME: This may be a
    * foolproof way to do this.
    *
    * @return
 */
function isIntranet( )
{
    $serverIP = explode('.',$_SERVER['SERVER_ADDR']);
    $localIP  = explode( '.', getRealIpAddr( ) );

    //echo alertUser( "Accessing page from IP address: " . implode('.', $localIP));

    $isIntranet = ($serverIP[0] == $localIP[0])
                        && ($serverIP[1] == $localIP[1])
                        && ( in_array($localIP[0], array('127','10','172','192') )
                   );

    return $isIntranet;
}


?>
