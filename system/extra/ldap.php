<?php

include_once BASEPATH. 'extra/methods.php';
include_once BASEPATH. 'database.php';

function labofficeToName( $laboffice )
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

function getGroupWithLaboffice($laboffice, $prune=true) 
{
    $ldap_ip="ldap.ncbs.res.in";
    $ds = connectToLDAP($ldap_ip);

    if(! $ds)
        return [];

    $base_dn = "dc=ncbs,dc=res,dc=in";
    $sr = @ldap_search($ds, $base_dn , "(profilelaboffice=$laboffice)");
    $info = @ldap_get_entries($ds, $sr);
    $result = [];

    for( $s=0; $s < $info['count']; $s++) {
        $i = $info[$s];
        $p = pruneLDAPResponsse($i);
        if(strtolower($p['is_active']) === 'true')
            $result[] = $p;
    }
    return $result;
}

function getUserInfoFromLdap($query, bool $prune=true, bool $multi=false) : array
{
    $ldap_ip = 'ldap.ncbs.res.in';
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
        if($prune)
            $result[ ] = pruneLDAPResponsse($i);
        else
            $result[] = $i;
    }

    // Return just one.
    if(count($result) > 0 && (! $multi))
        return $result[0];
    return $result;
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

    $res = ["fname" => $i['profilefirstname'][0]
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

    $res['name'] = $res['fname'] . ($res['mname']? ' ' . $res['mname'] . ' ': ' ') . $res['lname'];
    $res['pi_or_host'] = $res['laboffice'];

    return $res;
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

function authenticateUsingLDAPPort(string $user, string $pass, int $port): bool
{
    global $LDAP_PORTS;

    // bit ad-hoc but what isn't 
    $auth = false;
    if($port == 27206)   // 'ext'
        $dc = 'ext,dc=ncbs';
    else if($port == 389)
        $dc = 'ncbs';
    else if($port == 18288)
        $dc = 'instem';
    else if($port == 19554)
        $dc = 'ccamp';
    else
        $dc = 'ncbs';

    $ds = ldap_connect( "ldap.ncbs.res.in", $port );
    $ldapQuery = "uid=$user,ou=People,dc=$dc,dc=res,dc=in";
    if( $ds )
    {
        $bind = ldap_bind( $ds, $ldapQuery, $pass );
        if( $bind )
        {
            $auth = true;
            ldap_unbind( $ds );
        }
        if($ds)
            ldap_close( $ds );
    }
    return $auth;
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
function authenticateUsingLDAP( string $user, string $pass) : array
{
    global $LDAP_PORTS;
    $res = ['success' => false, 'msg' => ''];

    if( strlen( trim($user) ) < 1 ) {
        $res['msg'] = "Username is too short '$user'";
        return $res;
    }

    $auth = false;
    $port = 389;

    $whichSection = '*'; // by default try all.
    if(filter_var($user, FILTER_VALIDATE_EMAIL)) {
        // user passed email.
        $split = explode('@', $user);
        $user = $split[0];
        $domain = $split[1];  // @ncbs.res.in etc.
        $whichSection = explode('.', $domain)[0]; // ncbs, instem, ext etc.
        $port = $LDAP_PORTS[$whichSection];
        if(! $port)
            return ['sucess' => false, 'msg' => "Could not determine LDAP $port"];
    }

    if($whichSection === '*') {
        foreach(  $LDAP_PORTS as $dc => $port )
        {
            $auth = @authenticateUsingLDAPPort($user, $pass, $port);
            if($auth) {
                $res['success'] = true;
                $res['msg'] .= " Success with port $port.";
                return $res;
            }
        }
        $res['msg'] .= " Tried all LDAP ports";
    }
    else {
        $auth = @authenticateUsingLDAPPort($user, $pass, $port);
        if(! $auth) 
            $res['msg'] .= " Could not login using LDAP port $port";
        else {
            $res['success'] = $auth;
            $res['msg'] .= " Success with LDAP port $port.";
        }
    }
    return $res;
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
    $res = pingLDAP( $server, 389 );
    if( $res )
        return true;
    return false;
}

