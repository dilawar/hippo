<?php

include_once 'header.php';
include_once 'database.php';
include_once 'methods.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAllOfTheseRoles( array( 'AWS_ADMIN' ) );

echo userHTML( );

$aws = getMyAwsOn( $_POST[ 'speaker' ], $_POST[ 'date' ] );

echo printWarning( "Currently this feature is not implemented. Only speaker of 
    this AWS may be able to edit it. Please inform the speaker to edit details.
    " );

echo goBackToPageLink( $_SERVER['HTTP_REFERER'], 'Go back' );


?>
