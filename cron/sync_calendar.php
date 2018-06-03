<?php

include_once FCPATH.'methods.php';
include_once __DIR__.'/helper.php';


if( trueOnGivenDayAndTime( 'today', '4pm' ) || trueOnGivenDayAndTime( 'today', '8am') )
{
    echo( "executing cron job to synchronize calendar" );
    $res = shell_exec( 'php ./synchronize_calendar.php' );
    echo $res;
}


?>
