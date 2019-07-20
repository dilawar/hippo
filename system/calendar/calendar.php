<?php

require_once BASEPATH. 'calendar/NCBSCalendar.php' ;
require_once BASEPATH. 'database.php' ;
require_once BASEPATH. 'extra/methods.php' ;
require_once BASEPATH. 'extra/tohtml.php' ;

function calendarIFrame( )
{
    $url = calendarURL( );
    if( $url )
        return '
            <iframe class="google_calendar"
                allowtransparency="true"
                src="' . calendarURL() . '&mode=WEEK"
                style="border: 0" width="800" height="400" frameborder="0"
                scrolling="yes">
            </iframe>
        ';

    return '';
}


function addEventToGoogleCalendar($calendar_name, $event, $client )
{
}

// This function uses gcalcli command to sync my local caledar with google
// calendar.
function addAllEventsToCalednar( $calendarname, $client )
{
}

function updateEventGroupInCalendar( $gid )
{
    $events = getEventsByGroupId( $gid );
    $calendar = new NCBSCalendar( $_SESSION[ 'oauth_credential' ] );
    foreach( $events as $event )
        $calendar->updateEvent( $event );
}

?>
