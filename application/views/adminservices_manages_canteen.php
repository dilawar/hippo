<?php
include_once FCPATH . 'system/autoload.php';
echo userHTML( );

echo '<h1>Quick Add/Update Menu</h1>';

$mealHtml = arrayToSelectList('which_meal', getTableColumnTypes('canteen_menu', 'which_meal'));
echo '<form action="'. site_url("adminservices/canteen/quickadd") .'" method="post">
    <table class="editable_canteen_menu_quick">
    <tr>
        <td>Canteen Name</td>
        <td>
            <input type="text" name="canteen_name" id="" value="" />
        </td>
    </tr>
    <tr>
        <td>Day</td>
        <td>
            <input type="text" name="day" placeholder="Tue,Wed etc." value="" />
        </td>
    </tr>
    <tr>
        <td>Which Meal?</td>
        <td>
            ' . $mealHtml . '
        </td>
    </tr>
    <tr>
        <td>Available From</td>
        <td>
            <input class="timepicker" name="available_from" id="" value="" />
        </td>
    </tr>
    <tr>
        <td>Available Upto</td>
        <td>
            <input class="timepicker" name="available_upto" id="" value="" />
        </td>
    </tr>
    <tr>
        <td>Menu</td>
        <td>
            <textarea name="menu" rows="4" cols="60"
                placeholder="name1=price1,name2=price2"
                required
                > item1=2;item2=3;
            </textarea>
        </td>
    </tr>
    </table>
    <button class="submit">Submit</button>
    </form>';

echo ' <br /> <br />';


//echo '<h1>Add/Update Menu Items</h1>';
//$default = ['canteen'=>''
//    , 'name'=>''
//    , 'description'=>''
//    , 'price'=>''
//    , 'which_meal' => ''
//    , 'days' => ''
//    , 'available_from'=>''
//    , 'available_upto'=>''
//    , 'canteen_name' => ''
//    , 'days_csv' => ''
//    , 'modified_by'=> whoAmI()
//    , 'status' => 'VALID'
//];
//
//$editables = array_keys($default);
//$hide = 'day,modified_by,popularity';
//$action = 'add';
//if(intval(__get__($_POST, 'id', 0)) > 0)
//{
//    $action = 'update';
//    $entry = getTableEntry( 'canteen_menu', 'id', $_POST);
//    $entry['days_csv'] = $entry['day'];
//    $default = array_merge($default, $entry);
//}
//
//echo '<form action="'.site_url("adminservices/canteen/$action") .'" method="post">';
//echo dbTableToHTMLTable('canteen_menu', $default, $editables, $action, $hide);
//echo '</form>';

// Now show the menu.
$items = getTableEntries('canteen_menu'
    , 'canteen_name,which_meal,available_from'
    , "status='VALID'");

if( $items )
    echo p("Total items : " . count($items));

$itemGrouped = [];
foreach( $items as $item)
    $itemGrouped[$item['canteen_name']][$item['day']][] = $item;

$hide = 'id,description,days_csv,modified_by,modified_on,status,popularity';
foreach( $itemGrouped as $canteen => $dayItems)
{
    echo "<h2> Menu for $canteen </h2>";
    foreach( $dayItems as $day => $items)
    {
        echo "<h3> On day $day </h3>";
        $table = '<table class="info">';
        $table .= arrayToTHRow($items[0], 'info', $hide);
        foreach( $items as $item)
        {
            $id = $item['id'];
            $table .= '<tr>';
            $table .= arrayToRowHTML($item, 'info', $hide, '', false);
            $table .= '<td>
                <form action="#" method="post">
                    <input type="hidden" name="id" value="'. $item['id'] . '"></input>
                    <button>Edit</button></form>
                </form>';
            $table .= "</td><td>";
            $table .= '<form action="' .site_url("adminservices/canteen/delete/$id") .'" method="post">';
            $table .= "<button>Delete</button>";
            $table .= '</form>';
            $table .= '</td></tr>'; 
        }
        $table .= '</table>';
        echo $table;
    }
}

echo '<br />';
echo goBackToPageLink( "$controller/home", "Go back" );

?>

<script src="<?=base_url()?>./node_modules/xlsx/dist/xlsx.core.min.js"></script>
<script src="<?=base_url()?>./node_modules/file-saverjs/FileSaver.min.js"></script>
<script src="<?=base_url()?>./node_modules/tableexport/dist/js/tableexport.min.js"></script>
<script type="text/javascript" charset="utf-8">
TableExport(document.getElementsByClassName("exportable"));
</script>

<script>
$(#accordion).accordion({
    heightStyle : "fill"
    , collapsible: true
});
</script>
