<?php

include_once 'header.php';
include_once 'methods.php';
include_once './cron_jobs/helper.php';


if( trueOnGivenDayAndTime( 'today', '4pm' ) || trueOnGivenDayAndTime( 'today', '8am') )
{
    echo( "executing cron job to synchronize calendar" );
    $res = shell_exec( 'php ./synchronize_calendar.php' );
    echo $res;
}


?>
