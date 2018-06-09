<?php
require_once BASEPATH .'autoload.php';
echo userHTML( );

//$gid = $_POST['gid'];
//$eid = $_POST['eid'];
// Get a representative event of this group.
if(! ($gid && $eid) )
{
    printErrors( "Either gid ($gid) or eid ($eid) is not valid." );
    echo goBackToPageLink( "adminbmv/home", "Go Home" );
    exit;
}

$event = getEventsById( $gid, $eid );
echo printInfo( "Chaging following event $gid . $eid " );
echo arrayToTableHTML( $event, 'events' );

echo "<br /><br />";
echo '<form method="post" action="'.site_url("adminbmv/edit_action") . '">';
echo dbTableToHTMLTable( 'events', $defaults = $event
    , $editables = 'status,class,is_public_event,title,description'
    , 'Update'
    );

// Let admin select the whole group.
echo 'Update all events in this series? 
    <input type="radio" name="update_all" value="Yes" > Yes
    <input type="radio" name="update_all" value="No" checked > No
    ';
echo "</form>";

echo goBackToPageLink( "adminbmv/home", "Go Home" );

?>
