<?php
include_once 'header.php';
include_once 'database.php';
include_once 'methods.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAnyOfTheseRoles('USER' );
echo userHTML( );

$myItems = getMyInvetory( );
// Show user inventory here.
echo ' <h1>Show my inventory</h1> ';
$filter = 'last_modified_on,edited_by';


if( count( $myItems ) > 0 )
{
    echo '<table class="info"> ';
    foreach( $myItems as $item )
    {
        echo '<tr><td>';
        echo arrayToTableHTML( $item, 'db_entry_long' );
        echo '</td>';
        echo ' <form action="user_add_inventory_action.php" method="post" accept-charset="utf-8">';
        echo '<td> <button onclick="AreYouSure(this)" name="response" >Remove</button> </td>';
        echo ' <input type="hidden" name="id" value="' . $item['id'] . '" /> ';
        echo '</td>';
        echo '</form>';
    }
    echo '</table>';
}
else
    echo printWarning( "You don't have any item in inventory." );


echo ' <h1>Add new item to inventory</h1> ';

$editables = 'common_name,exact_name,vendor,description,quantity_with_unit';
$default = array( 'edited_by' => whoAmI()
    , 'last_modified_on' => dbDateTime( 'now' )
    , 'id' => getUniqueID( 'inventory' )
    , 'owner' => whoAmI( )
    );

// This button is for show/hide form. It must not be part of form below.
echo '<button class="show_as_link" onclick="ToggleShowHide(this)">Show Form</button> ';

// Prepare table.
$table = ' <div id="show_hide" style="display:none"> ';
$table .= dbTableToHTMLTable( 'inventory', $default, $editables, 'Add New Item' );
$table .= '</div>';

echo ' <form action="user_add_inventory_action.php" method="post" accept-charset="utf-8"> ';
echo $table;
echo '</form>';

echo goBackToPageLink( "user.php", "Go Back" );
?>
