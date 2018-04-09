<?php

include_once 'header.php';
include_once 'database.php';

$_SESSION[ 'google_command'] = 'synchronize_all_events';
header( 'Location:oauthcallback.php' );
exit;


?>
