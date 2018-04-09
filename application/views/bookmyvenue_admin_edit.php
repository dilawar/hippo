<?php

include_once ("header.php" );
include_once( "database.php" );
include_once( "tohtml.php" );
include_once 'check_access_permissions.php';

mustHaveAllOfTheseRoles( array( 'BOOKMYVENUE_ADMIN' ) );
echo userHTML( );

$gid = $_POST['gid'];
$eid = $_POST['eid'];

if( strcasecmp($_POST['response'], 'edit' ) == 0 )
{
    // Get a representative event of this group.
    $event = getEventsById( $gid, $eid );
    echo printInfo( "Chaging following event $gid . $eid " );
    echo arrayToTableHTML( $event, 'events' );
    echo "<br><br>";
    echo '<form method="post" action="bookmyvenue_admin_edit_submit.php">';
    echo dbTableToHTMLTable( 'events'
        , $defaults = $event
        , $editables = Array( 
            'status', 'class', 'is_public_event'
            , 'title', 'description'
        ));

    // Let admin select the whole group.
    echo 'Update all events in this series? 
        <input type="radio" name="update_all" value="Yes" > Yes
        <input type="radio" name="update_all" value="No" checked > No
        ';
    echo "</form>";
}

echo goBackToPageLink( "bookmyvenue_admin.php", "Go back" );

?>
