<?php

include_once 'header.php';

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN' ) );

include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

echo userHTML( );

$venues = getVenues( );

echo "<h1>List of Journal Clubs</h1>";
$jcs = getTableEntries( 'journal_clubs', 'id', "status!='INVALID'" );

echo '<table>';
foreach( $jcs as $jc )
{
    echo '<tr><td>';
    echo arrayToTableHTML( $jc, 'info' );
    echo '</td>';

    // Form to update.
    echo '<form action="#" method="post" accept-charset="utf-8">';
    echo '<td><button type="submit" name="response" value="Edit">Edit</button>';
    echo '<input type="hidden" name="id" value="' . $jc['id'] . '" />';
    echo '</form>';

    // Form to detele.
    echo '<form action="./admin_acad_manages_jc_action.php" method="post" accept-charset="utf-8">';
    echo '<button type="submit" name="response" value="Delete">Delete</button></td>';
    echo '<input type="hidden" name="id" value="' . $jc['id'] . '" />';
    echo '</form>';
    echo '</tr>';

}
echo '</table>';

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
    echo alertUser( "Please update the table shown below: " );
}

echo "<h1>$action Journal Club </h1>";

echo '<form action="admin_acad_manages_jc_action.php" method="post" accept-charset="utf-8">';
echo dbTableToHTMLTable( 'journal_clubs', $default, $editables, $action );
echo '</form>';

echo goBackToPageLink( "admin_acad.php", "Go back" );

?>
