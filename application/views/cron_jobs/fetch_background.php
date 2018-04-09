<?php

include_once 'header.php';
include_once 'methods.php';
include_once './cron_jobs/helper.php';


if( trueOnGivenDayAndTime( 'today', '1am' ) )
{
    echo( "executing cron job to synchronize calendar" );
    $res = shell_exec( 'python ./fetch_backgrounds.py' );
    echo $res;
}


?>
