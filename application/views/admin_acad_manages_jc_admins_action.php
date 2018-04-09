<?php

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN' ) );

include_once 'database.php';
include_once 'methods.php';
include_once 'tohtml.php';


if( $_POST[ 'response' ] == 'Add New Admin' )
{
    // The user may alredy be subscribed to this JC. If yes, then update the 
    // subscription_type to ADMIN.
    $res = insertOrUpdateTable( 'jc_subscriptions'
        , 'login,jc_id,subscription_type,last_modified_on'
        , 'subscription_type,last_modified_on'
        , $_POST
    );

    if( $res )
    {
        echo "Added JC admin successfully";
        echo goToPage( "admin_acad_manages_jc_admins.php", 1 );
    }
}
else if( $_POST[ 'response' ] == 'Remove Admin' )
{
    $_POST[ 'subscription_type' ] = 'NORMAL';
    $res = updateTable( 'jc_subscriptions'
        , 'jc_id,login', 'subscription_type', $_POST );
    if( $res )
    {
        echo "Successfully removed JC ADMIN from JC";
        echo goToPage( "admin_acad_manages_jc_admins.php", 1 );
    }
}


echo goBackToPageLink( "admin_acad_manages_jc_admins.php", "Go back" );

?>
