<?php
session_start( );

// Update a group of events. Before this script is called, the gid of event must 
// be set.

$_SESSION[ 'event_gid' ] = $_GET[ 'event_gid' ];

if( ! array_key_exists( 'event_gid' , $_SESSION ) )
{
    echo printWarning( 'Warning for developer: event gid must be set before 
        this script can be called. ' . $_SERVER[ 'PHP_SELF' ] );
    exit;
}

$_SESSION[ 'google_command' ] = 'update_eventgroup';


include_once( 'calendar/authenticate_gcalendar.php' );

exit;

?>
