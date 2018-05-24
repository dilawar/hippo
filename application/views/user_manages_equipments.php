<?php
require_once BASEPATH.'autoload.php';
echo userHTML();

$piOrHost = getPIOrHost( whoAmI() );
echo printInfo( "All equipment you create belongs to your PI/HOST (<tt>$piOrHost</tt>)." );

$equipments = getTableEntries( 'equipments', 'name', "faculty_in_charge='$piOrHost'");
if( count($equipments) > 0)
{
    $hide = 'id,last_modified_on,edited_by,faculty_in_charge';
    echo '<table class="info">';
    echo arrayToTHRow( $equipments[0], 'info', $hide );
    foreach($equipments as $i => $equip )
    {
        echo '<tr>';
        $equipmentID = $equip['id'];
        echo arrayToRowHTML( $equip, 'info', $hide, true, false );
        echo '<form action="'.site_url("user/delete_equipment/$equipmentID"). '" method="post">
               <td><button name="response" onclick="AreYouSure(this)">Delete</button></td>
            </form>';

        echo '<td>
                <form action="" method="post">
                <button name="response" value="update">Update</button>
                <input type="hidden" name="id" value="'.$equipmentID.'" />
                </form>
            </td>';
        echo '</tr>';
    }
    echo '</table>';
}


echo '<h1> Add/Update equipment</h1>';

$newID = getUniqueID( 'equipments');
$equipment = array( 'id' => $newID, 'faculty_in_charge' => $piOrHost
        , 'last_modified_on' => dbDateTime( 'now' )
        );

// If updateing the old entries, use the previous values as default parameters.
if( __get__($_POST, 'response', '') == 'update')
    if( __get__($_POST, 'id', 0 )  > 0 )
        $equipment = getTableEntry( 'equipments', 'id', $_POST );

$editable = 'name,vendor,description,person_in_charge,status';
echo '<form action="'.site_url("user/add_equipment"). '" method="post" accept-charset="utf-8">';
echo dbTableToHTMLTable('equipments', $equipment, $editable);
echo '</form>';

?>
<script type="text/javascript" charset="utf-8">
    $("#equipments_person_in_charge").attr( "placeholder", "email" );
</script>
