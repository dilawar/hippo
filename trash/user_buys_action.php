<?php 

include_once "header.php";
include_once "methods.php";
include_once "database.php";
include_once 'tohtml.php';
include_once 'mail.php';
include_once 'check_access_permissions.php';


mustHaveAnyOfTheseRoles( array( 'USER' ) );

echo userHTML( );

// Create a offer.
if( $_POST[ 'response' ] == 'NewBid' )
{
    echo printInfo( "Creating a new offer .. " );
    $totalEntries = getNumberOfEntries( 'nilami_offer', 'id' );
    $id = intval( __get__( $totalEntries, 'id', 0 ) ) + 1;

    $_POST[ 'id' ] = $id;
    $_POST[ 'created_by' ] = $_SESSION[ 'user' ];
    $_POST[ 'created_on' ] = dbDateTime( 'now' );
    $_POST[ 'contact_info' ] = getLoginEmail( $_SESSION[ 'user' ] );

    // If old offer already exists then update else create a new entry.
    $oldBid = getTableEntry( 'nilami_offer', 'item_id,created_by', $_POST );

    $res = null;
    if( $oldBid )
    {
        $_POST[ 'status' ] = 'VALID';
        $_POST[ 'last_modified_on' ] = dbDateTime( 'now' );
        $res = updateTable( 'nilami_offer', 'item_id,created_by'
                    , 'offer,status', $_POST );
    }
    else
        $res = insertIntoTable(
                'nilami_offer'
                , 'id,created_by,created_on,item_id,offer,status,contact_info'
                , $_POST 
            );

    if( $res )
    {
        echo printInfo( "Successfully added your offer ... " );

        // Send email.
        $item = getTableEntry( 'nilami_items', 'id'
                        , array( 'id' => $_POST[ 'item_id' ] ) 
                    );
        $msg = initUserMsg( $item[ 'created_by' ] );
    
        $to = getLoginEmail( $item[ 'created_by' ] );
        $cclist = getLoginEmail( $_POST[ 'created_by' ] );
        $subject = 'A new offer has been made on your entry by ' . $cclist ;

        $msg = arrayToTableHTML( $_POST, 'info' );
        $msg .= "<br>";
        $msg .= "<p> Comment : " . $_POST[ 'comment' ] . "</p>";
        sendHTMLEmail( $msg, $subject, $to, $cclist );

        echo goBack( "user_buys.php", 0 );
        exit;
    }
    else
        echo alertUser( "Could not create your entry" );
}
if( $_POST[ 'response' ] == 'Update Bid' )
{
    $res = updateTable( 'nilami_offer', 'id', 'offer,status', $_POST );
    if( $res )
    {
        echo printInfo( "Successfully updated your offer " );
        echo goBack( "user_buys.php", 0 );
        exit;
    }
    else
        echo minionEmbarrassed( "Could not update your offer!" );
}


echo goBackToPageLink( "user.php", "Go back" );

?>
