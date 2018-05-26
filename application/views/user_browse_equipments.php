<?php
require_once BASEPATH.'autoload.php';
echo userHTML();

$ref = "user";
if(isset($controller))
    $ref=$controller;

$user = whoAmI();
$piOrHost = getPIOrHost( $user );
$equipments = getTableEntries( 'equipments', 'name', "status='GOOD' AND faculty_in_charge='$piOrHost'");

echo '<h1>Book Equipment</h1>';

echo printNote( '
    Use either of the two forms given below. First one is to book for single time only. Form on right
    can be used to book for multiple days. 
    ' );

$equipIDS = array_map( function($x) { return $x['id']; }, $equipments);
$enames = array_map( function($x) { return $x['name']; }, $equipments);
$equipSelect = arrayToSelectList( 'equipment_id', $equipIDS, $enames);

$editable = 'equipment_id,date,start_time,end_time,comment';
$default = array( 'id' => getUniqueID('equipment_bookings')
                    , 'booked_by' => whoAmI() 
                    , 'created_on' => dbDateTime('now')
                    , 'modified_on' => dbDateTime('now')
                    , 'equipment_id' => $equipSelect
                );

$multiBook = ' <table class="editable_book_equipments">';
$multiBook .= '<caption>Multiple booking using repeat pattern.</caption>';
$multiBook .= "
    <tr>
        <td class='db_table_fieldname'>Equipment ID</td> <td> $equipSelect </td>
    </tr>
    <tr>
        <td class='db_table_fieldname'>Day pattern <br /> <small>(separated by ,)</small></td>
        <td> <input type='text' name='day_pattern' value='' placeholder='Tue,Wed,Fri' />
    </tr>
    <tr>
        <td class='db_table_fieldname'>Number of repeats</td>
        <td> <input type='text' name='num_repeat' value='4' placeholder='4' /> </td>
    </tr>
    <tr>
        <td class='db_table_fieldname'>Start Time</td>
        <td><input class='timepicker' name='start_time' value='' />
    </tr>
    <tr>
        <td class='db_table_fieldname'>End Time</td>
        <td><input class='timepicker' name='end_time' value='' />
    </tr>
    <tr>
        <td class='db_table_fieldname'>Booked By</td>
        <td><input type='hidden' name='booked_by' value='$user' />$user</td>
    </tr>
    <tr>
        <td class='db_table_fieldname'>Status</td>
        <td><input type='hidden' name='status' value='VALID' />VALID</td>
    </tr>
    <tr>
        <td class='db_table_fieldname'>Comment</td>
        <td><input type='text' name='comment' value='' />
    </tr>
    <tr>
        <td colspan='2'><button style='float:right' type='submit'>Multiple book</button> </td>
    </tr>
    ";
$multiBook .= '</table>';

echo '<table><tr><td>';
echo '<form action="'. site_url( "user/book_equipment") .'" method="post" accept-charset="utf-8">';
echo dbTableToHTMLTable( 'equipment_bookings', $default, $editable, 'Book');
echo '</form>';
echo '</td><td>';
echo '<form action="'. site_url( "user/multibook_equipment") .'" method="post" accept-charset="utf-8">';
echo $multiBook;
echo '</form>';
echo '</td></tr>';
echo '</table>';

echo '<h1>Available equipments</h1>';
echo printNote( "Following " . count( $equipments ). " equipments are available for booking 
    for faculty-in-charge " . mailto( $piOrHost )  . '.'
    );

if(count($equipments) > 0)
{

    echo arraysToCombinedTableHTML( $equipments, 'info book', 'status,last_modified_on,edited_by' );
    echo ' <table class="info" >';
    echo '</table>';
}


echo ' <br />';

echo goBackToPageLink( "$ref/home", "Go Home" );
?>

