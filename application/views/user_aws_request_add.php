<?php

include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAllOfTheseRoles( array( 'USER' ) );

echo '<h3>Submit a request for AWS</h3>';

// User add a request for editing the AWS.
if( $_POST['response'] == 'edit' )
{
    $id = $_POST[ 'id' ];
    $aws = getAwsById( $id );
    echo dbTableToHTMLTable( 'annual_work_seminars'
        , $aws , array( 'title', 'abstract' )
        );
}



?>
