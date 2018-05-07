<?php

include_once ("header.php" );
include_once( "database.php" );
include_once( "tohtml.php" );


if( strcasecmp($_POST['response'], 'submit' ) == 0 )
{
    // If is_public_event is set to NO then purge calendar id and event id.
    if( $_POST[ 'is_public_event' ] == 'NO' )
    {
        if( strlen( $_POST[ 'calendar_event_id' ] ) > 1 )
        {
            $_POST[ 'calendar_id' ] = '';
            $_POST[ 'calendar_event_id' ] = '';
        }
    }

    $where = 'gid,eid';
    if( "Yes" == $_POST['update_all'] )
        $where = 'gid';

    $res = updateTable( 'events', $where
        , array( 'is_public_event', 'class', 'title', 'description', 'status' )
        , $_POST 
    );

    if( $res )
    {
        echo printInfo( "updated succesfully" );
        // TODO: may be we can call calendar API here. currently we are relying 
        // on synchronize google calendar feature.
        goToPage( 'bookmyvenue_admin.php', 1 );
        exit;
    }
    else
        echo printWarning( "Above events were not updated" );

}

echo goBackToPageLink( "admin.php", "Go back" );

?>
