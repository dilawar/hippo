<?php

include_once 'check_access_permissions.php';
mustHaveAllOfTheseRoles( array( 'AWS_ADMIN' ) );

include_once 'database.php';
include_once 'tohtml.php';

echo userHTML( );

if( $_POST[ 'response' ] == 'update' )
{
    $awsId = $_POST[ 'id' ];
    echo alertUser( "You are updating AWS with id $awsId" );
    $aws = getUpcomingAWSById( $awsId );

    echo '<form method="post" action="admin_acad_update_upcoming_aws_submit.php">';
    echo editableAWSTable( -1, $aws );
    echo '<input type="hidden", name="id" value="' . $awsId . '">';
    echo '</form>';
}

echo goBackToPageLink( 'admin_acad.php', 'Go back' );

?>
