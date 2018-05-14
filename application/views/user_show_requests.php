<?php

require_once BASEPATH.'autoload.php';

echo userHTML( );

$requests = getRequestOfUser( whoAmI(), $status = 'PENDING' );

echo '<h1>Pending booking</h1>';
if( count( $requests ) < 1 )
    echo alertUser( "No pending request found." );

foreach( $requests as $request )
{
    $tobefiltered = Array( 
        'gid', 'created_by', 'rid', 'modified_by', 'timestamp'
        , 'url' , 'status', 'external_id'
    );
    $gid = $request['gid'];

    echo "<table class=\"info\" >";
    echo "<tr>";
    echo "<td>" . arrayToTableHTML( $request, "info", NULL, $tobefiltered );
    echo '<form method="post" action="'.site_url("user/private_request_edit") .'">';
    echo "</td></tr><tr>";
    echo "</td><td><button name=\"response\" title=\"Cancel this request\"
            onclick=\"AreYouSure( this )\" > <i class=\"fa fa-trash\"></i> </button>";
    echo "<td><button name=\"response\" title=\"Edit this request\"
        value=\"edit\"> <i class=\"fa fa-pencil\"></i> </button>";
    echo "</td></tr>";
    echo "</table>";
    echo "<input type=\"hidden\" name=\"gid\" value=\"$gid\">";
    echo '</form>';
}

echo goBackToPageLink( "user", "Go back" );


echo '<h1>Approved booking</h1>';
$groups = getEventsOfUser( whoAmI() );
if( count( $groups ) < 1 )
    echo alertUser( "No upcoming events." );
else 
{
    echo "<h2>You have following upcoming events </h2>";

    $hide = 'last_modified_on,created_by,external_id,is_public_event' 
                    .  ',calendar_id,calendar_event_id,url,status,description';

    foreach( $groups as $group )
    {

        echo '<div class="important">';
        $gid = $group['gid'];

        echo '<table>';
        echo '<form method="post" action="'.site_url('user/private_event_edit').'">';
        echo "<tr><td> <strong>Group id $gid </strong>";
        echo '<button name="response" title="Cancel this group" 
            onclick="AreYouSure(this,\'DELETE GROUP\')" >Cancel Group</button></td>';

        // If this event if from external talk, then do not allow user to edit
        // it here.
        if( ! isEventOfTalk( $group ) )
            echo "<td><button title=\"Edit this event\" name=\"response\" 
                    value=\"edit\" font-size=\"small\">Edit Group</button></td>";
        else
            echo 'This event belongs to a talk, 
                to edit it <a href="'.site_url('user/manage_talk').'" > edit its talk.</a>';

        echo "</tr>";
        echo "<input type=\"hidden\" name=\"gid\" value=\"$gid\">";
        $today = dbDate( 'today' );
        $events = getTableEntries( 'events', 'date,start_time'
            , "gid='$gid' AND date >= '$today' AND status='VALID'" 
        );

        if( count( $events ) < 1 )
            continue;

        echo '</table>';
        echo '</form>';

        echo '<table class="condensed">';
        echo arrayToTHRow( $events[0], 'events', $hide );
        foreach( $events as $event )
        {
            if( $event[ 'status' ] != 'VALID' )
                continue;

            echo '<tr>';
            echo '<form method="post" action="'.site_url('user/private_event_edit').'">';
            echo arrayToRowHTML( $event, 'events', $hide, false, false );
            echo "<td colspan=\"2\"><button name=\"response\" title=\"Cancel this event\" 
                    onclick=\"AreYouSure(this,'DELETE EVENT')\" > <i class=\"fa fa-trash\"></i>
                    </button></td>";
            echo '</tr>';

            $eid = $event[ 'eid' ];
            echo "<input type=\"hidden\" name=\"eid\" value=\"$eid\">";
            echo "<input type=\"hidden\" name=\"gid\" value=\"$gid\">";
            echo '</form>';

        }
        echo "</table>";
        echo '</div>';
        echo "<br>";
    }
}

echo goBackToPageLink('user', 'Go Back');

?>
