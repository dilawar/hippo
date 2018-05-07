<?php

include_once 'header.php';
include_once 'database.php';
include_once 'mail.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN' ) );

echo userHTML( );


if( ! $_POST[ 'response' ] )
{
    // Go back to previous page.
    goBack( );
    exit;
}
else if( $_POST[ 'response' ] == 'delete' )
{
    // Delete this entry from talks.
    $res = deleteFromTable( 'talks', 'id', $_POST );
    if( $res )
    {
        echo printInfo( 'Successfully deleted talk' );

        $success = true;
        $externalId = getTalkExternalId( $_POST );

        // echo( "External id for bookings: $externalId <br>" );

        $events = getTableEntries( 'events'
            , 'external_id', "external_id='$externalId' AND status='VALID'" 
        );
        $requests = getTableEntries( 'bookmyvenue_requests'
            , 'external_id', "external_id='$externalId' AND status='PENDING'" 
        );

        foreach( $events as $e )
        {
            echo printInfo( "Cancelling associated booking." );
            echo arrayToTableHTML( $e, 'info' );
            $e[ 'status' ] = 'CANCELLED';
            // Now cancel this talk in requests, if there is any.
            $res = updateTable( 'events', 'external_id', 'status', $e );
        }

        foreach( $requests as $r )
        {
            echo printInfo( "Cancelling associated booking request " );
            echo arrayToTableHTML( $r, 'info' );

            $r[ 'status' ] = 'CANCELLED';
            $res = updateTable( 'bookmyvenue_requests', 'external_id', 'status', $r);
        }

        // /* VALIDATION: Check the bookings are deleted  */
        $events = getTableEntries( 'events'
            , 'external_id', "external_id='$externalId' AND status='VALID'"
        );
        $requests = getTableEntries( 'bookmyvenue_requests'
            , 'external_id', "external_id='$externalId' AND status='VALID'"
        );
        assert( ! $events );
        assert( ! $requests );
        
        echo printInfo( "Successfully deleted related events/requests." );
    }
    else
        echo printWarning( "Failed to delete the talk " );
}
else if( $_POST[ 'response' ] == 'DO_NOTHING' )
{
    echo printInfo( "User said NO!" );
    goBack( 'admin_acad.php' );
    exit;
}
else if( $_POST[ 'response' ] == 'edit' )
{
    echo printInfo( "Here you can only change the host, class, title and description
        of the talk." );

    $id = $_POST[ 'id' ];
    $talk = getTableEntry( 'talks', 'id', $_POST );

    echo '<form method="post" action="admin_acad_manages_talks_action_update.php">';
    echo dbTableToHTMLTable('talks', $talk
        , 'class,coordinator,host,title,description'
        , 'submit');
    echo '</form>';
}
else if( $_POST[ 'response' ] == 'schedule' )
{
    // We are sending this to quickbook.php as GET request. Only external_id is 
    // sent to page.
    //var_dump( $_POST );
    $external_id = getTalkExternalId( $_POST );
    $query = "&external_id=".$external_id;
    header( "Location: quickbook.php?" . $query );
    exit;
}

echo goBackToPageLink( "admin_acad_manages_talks.php", "Go back" );
exit;

?>
