<?php

include_once BASEPATH. 'extra/methods.php';
include_once BASEPATH. 'database.php';

function findGroup( $laboffice )
{
    if( strcasecmp( $laboffice, "faculty" ) == 0 )
        return "FACULTY";
    if( strcasecmp( $laboffice, "instem" ) == 0 )
        return "FACULTY";
    return $laboffice;
}

function serviceping($host, $port=389, $timeout=1)
{
    $op = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$op) return 0; //DC is N/A
    else {
        fclose($op); //explicitly close open socket connection
        return 1; //DC is up & running, we can safely connect with ldap_connect
    }
}

function connectToLDAP($ldap_ip)
{
    $ldap_ip = $ldap_ip or getConfigValue( 'LDAP_QUERY_SERVER' );
    $port = getConfigValue( 'LDAP_QUERY_SERVER_PORT' );
    // Search on all ports.
    $info = array( 'count' => 0 );
    if( 0 == serviceping( $ldap_ip, $port, 2 ) )
    {
        error_log( "Could not connect to $ldap_ip : $port . Timeout ... " );
        return null;
    }
    $ds = @ldap_connect($ldap_ip, $port );
    @ldap_set_option( $ds, LDAP_OPT_TIMELIMIT, 1 );
    $r = @ldap_bind($ds);
    if(! $r)
    {
        echo "LDAP binding failed. TODO: Ask user to edit details ";
        return null; 
    }
    return $ds;
}

function getUserInfoFromLdap($query, $ldap_ip="ldap.ncbs.res.in") : array
{
    $ds = connectToLDAP($ldap_ip);
    if(! $ds)
        return [];

    $login = explode("@", $query)[0];
    $base_dn = "dc=ncbs,dc=res,dc=in";
    $sr = @ldap_search($ds, $base_dn , "(uid=$login)");
    $info = @ldap_get_entries($ds, $sr);
    $result = [];

    for( $s=0; $s < $info['count']; $s++)
    {
        $i = $info[$s];
        $result[ ] = pruneLDAPResponsse($i);
    }

    // Return just one.
    if(count($result) > 0)
        return $result[0];
    return array( );
}

function pruneLDAPResponsse(array $i):array
{
    $laboffice = __get__( $i, 'profilelaboffice', array( 'NA') );
    $joinedOn = __get__( $i, 'profiledateofjoin', array( 'NA' ) );

    // We construct an array with ldap entries. Some are dumplicated with
    // different keys to make it suitable to pass to other functions as
    // well.
    if( trim( $i['sn'][0] ) == 'NA' )
        $i['sn'][0] = '';

    $profileId = __get__( $i, 'profileidentification', array( -1 ) );
    $profileidentification = $profileId[0];
    $title = $i[ 'profilecontracttype'][0];
    $designation = $i[ 'profiledesignation'][0];
    $active = $i[ 'profileactive' ][0];
    return ["fname" => $i['profilefirstname'][0]
        , "first_name" => $i['profilefirstname'][0]
        , "mname" => __get__($i, 'profilemiddlename', [''])[0]
        , "middle_name" => __get__($i, 'profilemiddlename', [''])[0]
        , "lname" => $i['profilelastname'][0]
        , "last_name" => $i['profilelastname'][0]
        , "extension" => $i['profilelandline'][0]
        , "uid" => $profileidentification
        , "id" => $profileidentification
        , "email" => $i['mail'][0]
        , "laboffice" => $laboffice[0]
        , "joined_on" => $joinedOn[0]
        , "title" => $title
        , "designation" => $designation
        , 'is_active' => $active];
}


function getUserInfoFromLdapRelaxed($q, $ldap_ip="ldap.ncbs.res.in") : array
{
    $ds = connectToLDAP($ldap_ip);
    if(! $ds)
        return [];

    $base_dn = "dc=ncbs,dc=res,dc=in";
    $sr = @ldap_search($ds, $base_dn
        , "("
            . "|(uid=$q*)(profilelastname=*$q*)(profilefirstname=*$q*)(profilelaboffice=*$q*)"
            . "(profilelandline=$q*)"
            . ")"
        );
    $info = @ldap_get_entries($ds, $sr);
    $result = array();
    for( $s=0; $s < $info['count']; $s++)
    {
        $i = $info[$s];
        $result[] = pruneLDAPResponsse($i);
    }
    return $result;
}


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Use LDAP to authenticate user.
    *
    * @Param $user
    * @Param $pass
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function authenticateUsingLDAP( string $user, string $pass ) : bool
{
    if( strlen( trim($user) ) < 1 )
        return false;

    $auth = false;
    $ports = array( "ncbs" => 389, "ext" => 27206, "instem" => 18288, "ccamp" => 19554 );
    foreach(  $ports as $dc => $port )
    {
        $res = @ldap_connect( "ldap.ncbs.res.in", $port );
        if($dc === 'ext')
            $dc = 'ext,dc=ncbs';
        $ldapQuery = "uid=$user,ou=People,dc=$dc,dc=res,dc=in";
        if( $res )
        {
            $bind = @ldap_bind( $res, $ldapQuery, $pass );

            if( $bind )
            {
                $auth = true;
                @ldap_unbind( $res );
                break;
            }
            @ldap_close( $res );
        }
    }

    return $auth;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  PING ldap server.
    *
    * @Param $server
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function pingLDAP( $server, $port )
{
    // echo "Pinging $server:$port";
    $fp = @fsockopen( $server, $port, $errCode, $errStr, 1 );
    return $fp;
}

function ldapAlive( $server )
{
    $res = @pingLDAP( $server, 389 );
    if( $res )
        return true;
    return false;
}

?>
