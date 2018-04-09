<?php
include_once( "header.php" );
include_once( "database.php" );

if( strcasecmp($_POST['response'], 'submit' ) == 0 )
{
    $res = updateEventGroup( $_POST['gid'] ,  $_POST );
    if( $res )
    {
        echo printInfo( "Successfully updated event" );
        goToPage( "user_show_events.php", 1 );
    }
    else
    {
        echo printWarning( "Failed to update event" );
        echo goBackToPageLink( "user_show_events.php", "Go back" );
    }
}
?>
