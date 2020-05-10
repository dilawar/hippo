<?php

require_once BASEPATH . 'autoload.php';
include_once BASEPATH . 'calendar/methods.php';

function sync_calendar_cron()
{
    global $proxy;
    if (trueOnGivenDayAndTime('today', '21:00') || trueOnGivenDayAndTime('today', '8am')) {
        echo  'executing cron job to synchronize calendar';
        synchronize_google_calendar();
    }
}
