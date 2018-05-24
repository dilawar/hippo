<?php
require_once BASEPATH.'autoload.php';
echo userHTML();

$ref = "user";
if(isset($controller))
    $ref=$controller;

$piOrHost = getPIOrHost( whoAmI() );
$equipments = getTableEntries( 'equipments', 'name', "status='GOOD' AND faculty_in_charge='$piOrHost'");

echo '<h1>Book Equipment</h1>';

$equipIDS = array_map( function($x) { return $x['id']; }, $equipments);
$enames = array_map( function($x) { return $x['name']; }, $equipments);
$eqiptSelect = arrayToSelectList( 'equipment_id', $equipIDS, $enames);

$editable = 'equipment_id,date,start_time,end_time,comment';
$default = array( 'id' => getUniqueID('equipment_bookings')
                    , 'booked_by' => whoAmI() 
                    , 'created_on' => dbDateTime('now')
                    , 'modified_on' => dbDateTime('now')
                    , 'equipment_id' => $eqiptSelect
                );

echo '<form action="'. site_url( "user/book_equipment") .'" method="post" accept-charset="utf-8">';
echo dbTableToHTMLTable( 'equipment_bookings', $default, $editable, 'Book');
echo '</form>';



echo '<h1>Available equipments</h1>';
echo ( "Following " . count( $equipments ). " equipments are available for booking 
    for faculty-in-charge " . mailto( $piOrHost ) 
    );

echo arraysToCombinedTableHTML( $equipments, 'info book', 'status,last_modified_on,edited_by' );
echo ' <table class="info" >';

echo '</table>';


echo goBackToPageLink( "$ref/home", "Go Home" );
?>

