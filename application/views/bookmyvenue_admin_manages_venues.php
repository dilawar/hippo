<?php 

require_once BASEPATH.'autoload.php';
echo userHTML( );

$symbDelete = '<i class="fa fa-trash"></i>';

$default = array( );

$venues = getVenues( $sortby = 'id' );
$venuesId = array( );
foreach( $venues as $venue )
    $venuesId[ ] = $venue[ 'id' ];

$venueIdSelect = arrayToSelectList( 'venue_id', $venuesId );

echo '<form method="post" action="">';
echo $venueIdSelect;
echo ' <input type="submit" name="response" value="Select" /> ';
echo '</form>';
$task = 'add new';

if( __get__( $_POST, 'response', '' ) == 'Select'  )
{
    echo printInfo('Edit the following form to update selected venue.');
    $id = $_POST[ 'venue_id' ];
    $venue = getVenueById( $id );
    $default = array_merge( $default, $venue );
    $task ='update';
}

$editables = 'name,institute,building_name,floor,location,type,strength';
$editables .= ',distance_from_ncbs';
$editables .= ',suitable_for_conference,has_projector,has_skype';

echo ' <h2>Add or Update venue </h2> ';
echo '<form method="post" action="'.site_url('adminbmv/venues_action').'">';
if( $task == 'add new' )     // Append id to the editable since we are creating new entry.
{
    $editables .= ',id';
}
echo dbTableToHTMLTable( 'venues', $default, $editables, $task );

if( $task != 'add new' )
{
    echo '<button name="response" value="delete" onClick="AreYouSure(this)" 
            title="Delete this entry">' . $symbDelete . '</button>';
}

echo '</form>';
echo goBackToPageLink( 'adminbmv/home', 'Go back' );

echo '<h1> List of venues </h1>';

echo '<table class="show_venue info">';

echo arrayHeaderRow( $venues[0], 'venue' );
foreach( $venues as $venue )
{
    echo arrayToRowHTML( $venue, 'venue' );
    //echo '<form action="'.site_url('adminbmv/venues_action') .'" method="post">';
    //echo ' <td></td> ';
    //echo ' <td></td> ';
    //echo '</form>';
}
echo '</table>';

echo goBackToPageLink( 'adminbmv/home', 'Go back' );


?>
