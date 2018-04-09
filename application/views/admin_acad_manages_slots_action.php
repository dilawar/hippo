<?php

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN', 'ADMIN' ) );

include_once 'methods.php';
include_once 'database.php';
include_once 'tohtml.php';

if( $_POST['response'] == 'DO_NOTHING' )
{
    echo printInfo( "User said do nothing.");
    goBack( "admin_acad_manages_slots.php", 0 );
    exit;
}
else if( $_POST['response'] == 'delete' )
{
    // We may or may not get email here. Email will be null if autocomplete was 
    // used in previous page. In most cases, user is likely to use autocomplete 
    // feature.
    if( strlen($_POST[ 'id' ]) > 0 )
        $res = deleteFromTable( 'slots', 'id', $_POST );
    if( $res )
    {
        echo printInfo( "Successfully deleted entry" );
        goBack( 'admin_acad_manages_slots.php', 0 );
        exit;
    }
    else
        echo minionEmbarrassed( "Failed to delete speaker from database" );
}
else   // update
{
    // Get group id of slot.
    $gid = slotGroupId( $_POST['id'] );
    $_POST[ 'groupid' ] = $gid;

    $res = insertOrUpdateTable( 'slots'
            , 'id,groupid,day,start_time,end_time'
            , 'day,start_time,end_time'
            , $_POST 
        );

    if( $res )
    {
        echo printInfo( 'Updated/Inserted slot' );
        goBack( 'admin_acad_manages_slots.php', 0 );
        exit;
    }
}

echo goBackToPageLink( 'admin_acad_manages_slots.php', 'Go back' );


?>
