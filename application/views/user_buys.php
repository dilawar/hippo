<?php 

include_once( "header.php" );
include_once( "methods.php" );
include_once( "database.php" );
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAnyOfTheseRoles( array( 'USER' ) );

echo userHTML( );


$user = $_SESSION[ 'user' ];
$myBids = getTableEntries(
             'nilami_offer', 'created_on'
            , "created_by='$user' AND status='VALID' "
        );


$offerById = array( );
if( count( $myBids ) > 0 )
{
    echo ' <h2>My Items </h2> ';
    echo printInfo( 'Click on <button disabled>' . $symbEdit . '</button> to
                    change the value of offer or to withdraw. '
                );

    foreach( $myBids as $bid )
    {
        $offerById[ $bid['item_id' ] ] = $bid;

        echo ' <table border="0">';
        echo '<tr><td>';
        echo ' <form action="" method="post" accept-charset="utf-8">';
        echo arrayToTableHTML( $offer, 'info', '' , 'created_by');
        echo '</td><td>';
        echo ' <input type="hidden" name="id" value=" ' . $bid[ 'id' ] . '" />';
        echo ' <button title="Edit this bid" 
                    name="response" value="EditBid">' . $symbEdit .' </button> ';
        echo '</form>';
        echo '</table>';
    }
}

// Edit the bid.
if( 'EditBid' == __get__( $_POST, 'response', '' ) )
{
    // Create entry for editing bid.
    $bidEntry = getTableEntry( 'nilami_offer', 'id', $_POST );
    echo ' <form action="user_buys_action.php" method="post" accept-charset="utf-8">';
    echo dbTableToHTMLTable('nilami_offer', $bidEntry, 'offer,status', 'Update Bid');
    echo '</form>';
}

echo ' <h2>Items for selling </h2>';
$tags = __get__( $_GET, 'tags', '' );

echo '
    <form action="" method="get" accept-charset="utf-8">
        A single word is usually best: <input type="text" name="tags" value="' . $tags . '" />
        <button name="response" type="submit">Filter</button>
    </form>
    ';

$user = $_SESSION[ 'user' ];
$from = dbDate( strtotime( 'now' ) - 30 * 24 * 3600 );

// Get all entries from the nilami items which do not belong to current user. 
$where = "created_by!='$user' AND status='AVAILABLE' AND created_on='$from'";
$where .= " AND ( tags LIKE '%$tags%' OR item_name LIKE '%$tags%' ) ";
$entries = getTableEntries( 'nilami_items', 'created_on DESC' , $where );

// Get list of all items and 
echo "<h3>Following items are available for sell </h3>";

echo "<div style=\"font-size:small\">";
echo '<table border="0">';
foreach( $entries as $et )
{
    echo '<tr><td>';

    $et[ 'owner' ] = getLoginEmail( $et[ 'created_by' ] );
    echo arrayToTableHTML( $et, 'info', ''
            , 'last_updated_on,created_by,status'
        );
    echo '</td>';

    // Form to bid for item.
    echo ' <form action="" method="post" accept-charset="utf-8">';
    echo ' <input type="hidden" name="id" value="' . $et[ 'id' ] . '" /> ';

    $extra = '';
    if( array_key_exists( $et['id'], $offerById ) )
        $extra = 'disabled';

    echo ' <td> <button name="response" value="bid" ' . $extra . '>Bid</td> </td> ';
    echo '</form>';

    echo '</tr>';

    $itemId = $et[ 'id' ];
    $allBids = getTableEntries( 'nilami_offer', 'bid'
                    , "item_id='$itemId' AND status='VALID'" 
                );
    // Add another row of offer.
    if( count( $allBids ) > 0 )
    {
        echo "<tr>";
        echo "<td> This item has following offer : ";
        foreach( $allBids as $bid )
            echo "$symbRupee " . $bid['bid'] . "(" . $bid[ 'created_by' ] . ') ';
        echo "</tr>";
    }
}
echo '</table>';
echo '</div>';

if( 'bid' == __get__( $_POST, 'response', '' ) )
{
    $entry = getTableEntry( 'nilami_items', 'id', $_POST );

    echo alertUser( "Your bidding entry " );

    $itemId = $_POST[ 'id' ];

    //echo arrayToVerticalTableHTML( $entry, 'requests' );

    echo ' <form action="user_buys_action.php" method="post" accept-charset="utf-8">';
    // Line for my bid.
    echo ' <input type="hidden" name="item_id" value="' . $_POST['id'] . '" />';
    echo '<table><tr><td>';
    echo 'My bid for id ' . $itemId . '</td>
        <td> <input type="text" name="bid" value="" /> </td></tr>
        <tr><td> Mesage for owner </td><td>
        <textarea cols="50" rows="3" name="comment" ></textarea>
        ';
    echo '</td></tr>';
    echo '<tr><td></td><td>';
    echo ' <button name="response" value="NewBid">Create Bid</button> ';
    echo ' </td></tr>';
    echo '</table>';
    echo '</form>';
}

echo goBackToPageLink( "user.php", "Go back" );

?>
