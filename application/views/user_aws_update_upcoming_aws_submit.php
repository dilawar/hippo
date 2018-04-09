<?php

include_once( "header.php" );
include_once( "methods.php" );
include_once( 'tohtml.php' );
include_once( "check_access_permissions.php" );

mustHaveAnyOfTheseRoles( Array( 'USER' ) );

$res = updateTable( 'upcoming_aws', 'id'
    , 'supervisor_1,supervisor_2,tcm_member_1,tcm_member_2,tcm_member_3' .
    ',tcm_member_4,title,abstract,is_presynopsis_seminar'
    , $_POST
);

if( $res )
{
    echo printInfo( "Successfully updated entry" );
    goToPage( "user_aws.php", 1 );
    exit;
}

echo goBackToPageLink( "./user_aws.php", "Go back" );


?>
