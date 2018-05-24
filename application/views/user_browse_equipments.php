<?php
require_once BASEPATH.'autoload.php';
echo userHTML();

$ref = "user";
if(isset($controller))
    $ref=$controller;

$piOrHost = getPIOrHost( whoAmI() );
$equipments = getTableEntries( 'equipments', 'name', "status='GOOD' AND faculty_in_charge='$piOrHost'");
echo printInfo( "Following " . count( $equipments ). " equipments are available for booking 
    for faculty-in-charge " . mailto( $piOrHost ) 
    );

echo arraysToCombinedTableHTML( $equipments, 'info', 'status,last_modified_on,edited_by' );


echo goBackToPageLink( "$ref/home", "Go Home" );
?>
