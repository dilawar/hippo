<?php

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'USER' ) );

include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

echo userHTML( );

$sem = $_POST[ 'semester' ];
$year = $_POST[ 'year' ];
$cid = $_POST[ 'course_id' ];

if( $_POST[ 'response' ] == 'submit' )
{


}




echo goBackToPageLink( "user.php", "Go back" );

?>
