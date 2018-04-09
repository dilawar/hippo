<?php

include_once 'header.php';

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN' ) );

include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

echo userHTML( );

// Javascript.
$slots = getTableEntries( 'slots', 'groupid' );

//var_dump( $speakers );

$slotsMap = array( );
$slotsId = array_map( function( $x ) { return $x['id']; }, $slots );

foreach( $slots as $slot )
    $slotsMap[ $slot[ 'id' ] ] = $slot;

?>

<script type="text/javascript" charset="utf-8">
// Autocomplete speaker.
$( function() {
    var slotsDict = <?php echo json_encode( $slotsMap ) ?>;
    var slots = <?php echo json_encode( $slotsId ); ?>;
    $( "#slot" ).autocomplete( { source : slots }); 
    $( "#slot" ).attr( "placeholder", "autocomplete" );
});
</script>

<?php

// Logic for POST requests.
$slot = array( 'id' => '', 'day' => '', 'start_time' => '', 'end_time' => '' );

echo slotTable( );

echo "<h2>Slot details</h2>";
echo '<form method="post" action="">';
echo '<input id="slot" name="id" type="text" value="" >';
echo '<button type="submit" name="response" value="show">Show details</button>';
echo '</form>';


// Show speaker image here.
if( array_key_exists( 'id', $_POST ) )
{
    // Show emage.
    $slot = $slotsMap[ $_POST['id'] ];
    echo arrayToVerticalTableHTML( $slot, 'slot' );
}

echo '<h3>Edit slot details</h3>';

echo '<form method="post" action="admin_acad_manages_slots_action.php">';
echo dbTableToHTMLTable( 'slots', $slot 
    , 'id,day,start_time,end_time', 'submit' 
    );

echo '<button title="Delete this entry" type="submit" onclick="AreYouSure(this)"
    name="response" value="Delete">' . $symbDelete .
    '</button>';

echo '</form>';


echo "<br/><br/>";
echo goBackToPageLink( 'admin_acad.php', 'Go back' );

?>
