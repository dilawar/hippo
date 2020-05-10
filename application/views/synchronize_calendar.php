<?php

require_once BASEPATH . 'autoload.php';
include_once BASEPATH . 'calendar/NCBSCalendar.php';
include_once BASEPATH . 'calendar/methods.php';

// We come here from google-calendar
// When we come here from ./authenticate_gcalendar.php page, the GOOGLE API
// sends us a GET response. Use this token to process all other queries.

$res = synchronize_google_calendar();
