<?php
require_once BASEPATH.'autoload.php';
echo userHTML();

global $symbDelete;

// Javascript.
$slots = getTableEntries('slots', 'groupid');

$slotsMap = array( );
$slotsId = array_map(function ($x) {
    return $x['id'];
}, $slots);

foreach ($slots as $slot) {
    $slotsMap[ $slot[ 'id' ] ] = $slot;
}

?>

<script type="text/javascript" charset="utf-8">
// Autocomplete speaker.
$( function() {
    var slotsDict = <?php echo json_encode($slotsMap) ?>;
    var slots = <?php echo json_encode($slotsId); ?>;
    $( "#slot" ).autocomplete( { source : slots }); 
    $( "#slot" ).attr( "placeholder", "autocomplete" );
});
</script>

<?php

// Logic for POST requests.
$slot = array( 'id' => '', 'day' => '', 'start_time' => '', 'end_time' => '' );

echo ' <h1>Slot Table</h1>';
echo slotTable();

echo "<h2>Slot details</h2>";
echo '<form method="post" action="">';
echo '<input id="slot" name="id" type="text" value="" >';
echo '<button type="submit" name="response" value="show">Show details</button>';
echo '</form>';


// Show speaker image here.
if (array_key_exists('id', $_POST)) {
    // Show emage.
    $slot = $slotsMap[ $_POST['id'] ];
    echo arrayToVerticalTableHTML($slot, 'slot');
}

echo '<h3>Edit slot details</h3>';

echo '<form method="post" action="'.site_url('adminacad/slots_action') .'">';
echo dbTableToHTMLTable('slots', $slot, 'id,day,start_time,end_time', 'submit');
echo '<button title="Delete this entry" type="submit" onclick="AreYouSure(this)"
    name="response" value="Delete">' . $symbDelete .  '</button>';
echo '</form>';

echo "<br/>";
echo goBackToPageLink('adminacad/home', 'Go back');

?>
