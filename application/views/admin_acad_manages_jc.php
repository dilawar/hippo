<?php
require_once BASEPATH.'autoload.php';
echo userHTML( );

$venues = getVenues( );

echo "<h1>List of Journal Clubs</h1>";
$jcs = getTableEntries( 'journal_clubs', 'id', "status!='INVALID'" );

echo '<table><tr>';
foreach( $jcs as $i => $jc )
{
    echo '<td>';
    echo arrayToVerticalTableHTML( $jc, 'info' );
    echo '</td>';

    // Form to update.
    echo '<form action="" method="post" accept-charset="utf-8">';
    echo '<td><button type="submit" name="response" value="Edit">Edit</button>';
    echo '<input type="hidden" name="id" value="' . $jc['id'] . '" />';
    echo '</form>';

    // Form to detele.
    echo '<form action="'.site_url('adminacad/jc_action').'" method="post">';
    echo '<button type="submit" name="response" value="Delete">Delete</button></td>';
    echo '<input type="hidden" name="id" value="' . $jc['id'] . '" />';
    echo '</form>';
    if($i+1%2 == 0)
        echo '</tr><tr>';

}
echo '</tr></table>';

$editables = 'id,title,status,description,day,time,venue';
$action = 'Add';
$default = array(
    'venue' => venuesToHTMLSelect( $venues )
    );

if( __get__( $_POST, 'response', '' ) == 'Edit' )
{
    $editables = 'title,status,description,day,time,venue';
    $default = getTableEntry( 'journal_clubs', 'id', $_POST );
    $default[ 'venue' ] = venuesToHTMLSelect( $venues, false, 'venue', array( $default['venue'] )  );
    $action = 'Update';

    echo printInfo( "Please update the table shown below: " );
}

echo "<h1>$action Journal Club </h1>";

echo '<form action="'.site_url('adminacad/jc_action').'" method="post">';
echo dbTableToHTMLTable( 'journal_clubs', $default, $editables, $action );
echo '</form>';

echo goBackToPageLink( "adminacad/home", "Go back" );

?>
