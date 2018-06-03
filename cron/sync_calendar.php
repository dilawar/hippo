<?php

include_once BASEPATH.'calendar/methods.php';

function sync_calendar_cron()
{
    if( trueOnGivenDayAndTime( 'today', '4pm' ) || trueOnGivenDayAndTime( 'today', '8am') )
    {
        echo( "executing cron job to synchronize calendar" );
        synchronize_google_calendar();
        echo $res;
    }
}


?>
