<?php

include_once '../ldap.php';
include_once '../methods.php';
include_once '../database.php';
include_once '../tohtml.php';
include_once '../mail.php';

$nextMonday = dbDate( strtotime( 'next monday' ) );
$subject = 'Next Week AWS (' . humanReadableDate( $nextMonday) . ') by ';

echo "next monday $nextMonday";

$upcomingAws = getUpcomingAWS( $nextMonday );

$html = '';

$speakers = array( );
$logins = array( );

foreach( $upcomingAws as $aws )
{
    $html .= awsToHTML( $aws );
    array_push( $logins, $aws[ 'speaker' ] );
    array_push( $speakers, __ucwords__( loginToText( $aws['speaker'], false ) ) );
    break;
}

echo( $html );

?>
