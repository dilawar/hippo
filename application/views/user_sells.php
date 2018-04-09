<?php 

include_once( "header.php" );
include_once( "methods.php" );
include_once( "database.php" );
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAnyOfTheseRoles( array( 'USER' ) );

echo userHTML( );


$user = $_SESSION[ 'user' ];
$entries = getTableEntries( 'nilami_items', 'created_on'
                , "created_by='$user' AND status='AVAILABLE'"
            );

$action = 'Add';

$id = __get__( $_POST, 'id', 0 );
$default = array( 'created_by' => $_SESSION[ 'user' ] 
                , 'created_on' => dbDateTime( strtotime( 'now' ) )
                );

if( $id < 1 )   // Create a new entry.
    $id = __get__( $_POST, 'id', 0 );
else            // For updating entry.
{
    $action = 'Update';
    $default = getTableEntry( 'nilami_items', 'id', array( 'id' => $id ) );
    $default[ 'last_modified_on' ] = dbDateTime( 'now' );
}


if( count( $entries ) > 0 )
{
    echo "<h3>My entries</h3>";
    echo "<div style=\"font-size:small\">";

    echo "<table>";

    echo printInfo( 'Click on <button disabled> ' . $symbEdit . "</button> to " 
                . "change the status to <tt>SOLD</tt> or to withdraw the item."
            );

    foreach( $entries as $ent )
    {
        echo "<tr><td>";
        echo '<form action="" method="post" accept-charset="utf-8">';
        echo arrayToTableHTML( $ent, 'info', ''
                , 'id,created_by,contact_info,status,last_updated_on,comment'
                 );
        echo "</td><td>";
        echo '<input type="hidden" name="id" value="' . $ent[ 'id' ] . '"/>';
        echo '<button name="response" value="Edit">' . $symbEdit . '</td>';
        echo "</td></tr>";
        echo '</form>';

        // Add new row when bid is available.
        $itemId = $ent[ 'id' ];
        $allBids = getTableEntries( 'nilami_bids', 'bid'
                            , "item_id='$itemId' AND status='VALID'" 
                        );
        // Add another row of bids.
        if( count( $allBids ) > 0 )
        {
            echo "<tr>";
            echo "<td> We have following bids : ";
            foreach( $allBids as $bid )
                echo "$symbRupee " . $bid['bid'] . "(" . $bid[ 'created_by' ] . ') ';
            echo "</tr>";
        }
    }
    echo "</table>";

    echo "</div>";
}


echo "<h3>$action an entry to Nilami Store</h3>";
echo printInfo( 
    "At least one tag is required, separete <tt>TAGS</tt> by comma or space.
    <tt>COMMENT</tt> is optional.
    " );


echo '<form method="post" action="user_sells_action.php">';
echo dbTableToHTMLTable( 'nilami_items'
                , $default 
                , 'item_name,status,description,price,contact_info,comment,tags'
                , $action
                );
echo '</form>';


echo goBackToPageLink( "user.php", "Go back" );

?>
