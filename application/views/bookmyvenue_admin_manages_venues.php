<?php 
include_once "header.php";
include_once "methods.php";
include_once "database.php";
include_once "tohtml.php";
include_once "check_access_permissions.php";

mustHaveAnyOfTheseRoles( array( 'BOOKMYVENUE_ADMIN' ) );
echo userHTML( );

echo '<h1>Manage venues</h1>';

$default = array( );

$venues = getVenues( $sortby = 'id' );
$venuesId = array( );
foreach( $venues as $venue )
    $venuesId[ ] = $venue[ 'id' ];

$venueIdSelect = arrayToSelectList( 'venue_id', $venuesId );

echo '<form method="post" action="#">';
echo $venueIdSelect;
echo ' <input type="submit" name="response" value="Select" /> ';
echo '</form>';
$task = 'add new';

if( __get__( $_POST, 'response', '' ) == 'Select'  )
{
    echo ' <h3>Editing selected venue </h3> ';
    $id = $_POST[ 'venue_id' ];
    $venue = getVenueById( $id );
    $default = array_merge( $default, $venue );
    $task ='update';
}

$editables = 'name,institute,building_name,floor,location,type,strength';
$editables .= ',distance_from_ncbs';
$editables .= ',suitable_for_conference,has_projector,has_skype';

echo ' <h2>Add or Update venue </h2> ';
echo '<form method="post" action="bookmyvenue_admin_manages_venues_action.php">';
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



echo '<h2> List of venues </h2>';
echo '<table class="show_venue">';
echo arrayHeaderRow( $venues[0], 'venue' );
foreach( $venues as $venue )
    echo arrayToRowHTML( $venue, 'venue' );
echo '</table>';

// Go back to BOOKMYVENUE_ADMIN page.
echo goBackToPageLink( 'bookmyvenue_admin.php', 'Go back' );


?>
