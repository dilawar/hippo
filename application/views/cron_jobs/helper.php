<?php

include_once 'methods.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'mail.php';

ini_set( 'date.timezone', 'Asia/Kolkata' );
ini_set( 'log_errors', 1 );
ini_set( 'error_log', '/var/log/hippo.log' );

$now = dbDateTime( strtotime( 'now' ) );
error_log( "Executed by cron at $now" );
printInfo( "PHP: Executed by cron at $now" );

function trueOnGivenDayAndTime( $day, $time )
{
    $now = strtotime( 'today' );
    if( $now != strtotime( $day ) )
        return false;

    $away = strtotime( 'now' ) - strtotime( "$time" );
    if( $away >= -1 && $away < 15 * 60 )
        return true;

    return false;
}

function isNowEqualsGivenDayAndTime( $day, $time )
{
    return trueOnGivenDayAndTime( $day, $time );

}

?>
