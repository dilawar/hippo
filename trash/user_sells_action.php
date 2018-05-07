<?php 

include_once( "header.php" );
include_once( "methods.php" );
include_once( "database.php" );
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAnyOfTheseRoles( array( 'USER' ) );

echo userHTML( );

$_POST[ 'tags' ] = fixTags( $_POST[ 'tags' ] );
if( count( explode( ',', $_POST[ 'tags' ] ) ) < 1 )
{
    echo alertUser( 'Sorry but you must provide at least 1 tag for your entry' );
    echo goBackToPageLink( 'user_sells.php', 'Go back' );
    exit;
}

// Create a new entry.
if( $_POST[ 'response' ] == 'Add' )
{
    //var_dump( $_POST );
    echo printInfo( "Creating a new entry" );
    $totalEntries = getNumberOfEntries( 'nilami_items', 'id' );
    $id = intval( __get__( $totalEntries, 'id', 0 ) ) + 1;
    $_POST[ 'id' ] = $id;
    $res = insertIntoTable( 'nilami_items'
        , 'id,created_by,created_on,item_name,description,price,status,'
            . 'contact_info,tags,comment'
            , $_POST 
        );
    if( $res )
    {
        echo printInfo( "Successfully put your item in nilami store" );
        echo goBack( "user_sells.php", 0 );
        exit;
    }
    else
        echo alertUser( "Could not create your entry" );
}
if( $_POST[ 'response' ] == 'Update' )
{
    echo printInfo( "Updating old entry" );
    $totalEntries = getNumberOfEntries( 'nilami_items', 'id' );
    $_POST[ 'last_updated_on' ] = dbDateTime( 'now' );

    $res = updateTable( 'nilami_items'
            , 'id'
            , 'item_name,last_updated_on,description,price,status' . 
                ',contact_info,tags,comment'
            , $_POST 
        );

    if( $res )
    {
        echo printInfo( "Successfully updated your item in nilami store" );
        // Now notify all users who have bid of it.
        $itemId = $_POST[ 'id' ];
        $bids = getTableEntries( 'nilami_bids', 'bid'
                        , "item_id='$itemId' AND status='VALID'"
                    );

        if( count( $bids ) > 0 )
        {
            echo "Notifying the bidder about the status change";
            foreach( $bids as $bid )
            {
                $bidder = $bid[ 'created_by' ];
                $item = getTableEntry( 'nilami_items', 'id', array( 'id' => $itemId ) );

                $to = getLoginEmail( $bidder );
                $msg = initUserMsg( $bidder );
                $subject = 'Some changes have been made to the item you bid for ';
                $msg .= "<p> The current entry looks like the following </p>";
                $msg .= arrayToVerticalTableHTML( $item, 'info' );
                $msg .= "<p>I thought I let you know. </p>";

                sendHTMLEmail( $msg, $subject, $to );
            }

        }
        
        echo goBack( "user_sells.php", 0 );
        exit;
    }
    else
        echo minionEmbarrassed( "Could not update your entry." );
}
else
{
    echo printInfo( "Unknown response : " . $_POST[ 'response' ] );
}



echo goBackToPageLink( "user.php", "Go back" );

?>
