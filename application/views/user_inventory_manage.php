<?php
require_once BASEPATH.'autoload.php';
echo userHTML();

$piOrHost = getPIOrHost( whoAmI() );
echo printNote( "This inventory belongs to PI or Host <tt>$piOrHost</tt>." );

$items = getTableEntries( 'inventory', 'name', "faculty_in_charge='$piOrHost'");
if( count($items) > 0)
{
    $hide = 'id,last_modified_on,edited_by,faculty_in_charge,status';
    echo '<table class="info">';
    echo arrayToTHRow( $items[0], 'info', $hide );
    foreach($items as $i => $item )
    {
        echo '<tr>';
        $inventoryID = $item['id'];
        echo arrayToRowHTML( $item, 'info', $hide, true, false );
        echo '<form action="'.site_url("user/delete_inventory/$inventoryID"). '" method="post">
               <td><button name="response" onclick="AreYouSure(this)">Delete</button>
            </form>';

        echo '<form action="" method="post">
                <button name="response" value="update">Update</button>
                <input type="hidden" name="id" value="'.$inventoryID.'" />
                </form>
            </td>';
        echo '</tr>';
    }
    echo '</table>';
}

echo goBackToPageLink( "user/home", "Go Home" );

echo '<h1> Add/Update inventory item</h1>';

$newID = getUniqueID( 'inventory');
$inventory = array( 'id' => $newID, 'faculty_in_charge' => $piOrHost
        , 'last_modified_on' => dbDateTime( 'now' )
        , 'edited_by' => whoAmI()
        );

// If updateing the old entries, use the previous values as default parameters.
if( __get__($_POST, 'response', '') == 'update')
    if( __get__($_POST, 'id', 0 )  > 0 )
        $inventory = getTableEntry( 'inventory', 'id', $_POST );

$action = 'Add';

$editable = 'name,scientific_name,vendor,description,person_in_charge,item_condition,expiry_date';
$editable .= ',quantity_with_unit,edited_by,requires_booking';

echo '<button class="show_as_link"  id="button_show_hide"
    value="Show" onclick="toggleShowHide( this, \'show_hide\')">Show Form</button>';
echo '<div id="show_hide" style="display:block">';
echo '<form action="'.site_url("user/add_inventory_item"). '" method="post" accept-charset="utf-8">';
echo dbTableToHTMLTable('inventory', $inventory, $editable, $action);
echo '</form>';
echo ' <br />';

echo goBackToPageLink( "user/home", "Go Home" );

?>
<script type="text/javascript" charset="utf-8">
    $("#inventory_person_in_charge").attr( "placeholder", "email" );
</script>
<script type="text/javascript" charset="utf-8">
function toggleShowHide(button, elemid)
{
    var elem = document.getElementById(elemid);
    if(button.value == "Show")
    {
        elem.style.display="block";
        button.value = "Hide";
        button.innerHTML = "Hide Form";
    }
    else if(button.value == "Hide")
    {
        button.value = "Show";
        button.innerHTML = "Show Form";
        elem.style.display="none";
    }
    else
        console.log( "Unsupported action " + button.value );
}
</script>
