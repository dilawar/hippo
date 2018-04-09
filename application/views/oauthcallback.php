<?php 
session_start( );

include_once 'header.php';
include_once 'methods.php';
include_once 'tohtml.php';
include_once 'database.php';
include_once 'check_access_permissions.php';
require_once './calendar/NCBSCalendar.php';
require_once './calendar/methods.php';

mustHaveAllOfTheseRoles( 'BOOKMYVENUE_ADMIN', 'ADMIN', 'AWS_ADMIN' );


echo userHTML( );

// We come here from google-calendar 
// When we come here from ./authenticate_gcalendar.php page, the GOOGLE API 
// sends us a GET response. Use this token to process all other queries.

if( array_key_exists( 'google_command', $_SESSION ) )
{ 
    if( $_SESSION['google_command'] == 'synchronize_all_events' )
    {
        synchronize_google_calendar( );
        ob_flush(); flush( );
    }
    else
        echo printWarning( "Unsupported  command " .  $_SESSION['google_command'] );
}
else
    echo printInfo( "No command is given regarging google calendar" );

echo goBackToPageLink( "bookmyvenue_admin.php", "Go back" );
echo '<br> <br> <br>';

exit;

?>
