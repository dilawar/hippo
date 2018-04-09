<?php 

include_once __DIR__ . '/../check_access_permissions.php';

// mustHaveAllOfTheseRoles( array( 'BOOKMYVENUE_ADMIN', 'ADMIN' ) );

include_once 'NCBSCalendar.php';

$calendar = new NCBSCalendar( "/etc/hippo/hippo-service-account-key.json"
    , $_SESSION[ 'calendar_id' ] );

if( $calendar )
{
    header( 'Location:' . '../oauthcallback.php' );
    exit;
}
else
{
    echo minionEmbarrassed( 
        "Failed to created calendar instance. This is an error" 
    );
    echo goBackToPageLink( "bookmyvenue_admin.php", "Go back" );
    exit;
}

?>
