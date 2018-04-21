<?php

require_once BASEPATH.'autoload.php';

echo userHTML( );

$groups = getEventsOfUser( $_SESSION['user'] );
if( count( $groups ) < 1 )
    echo alertUser( "No upcoming events." );
else 
{
    echo "<h2>You have following upcoming events </h2>";
    echo '<div style="font-size:small">';

    $hide = 'last_modified_on,created_by,external_id,is_public_event' 
                    .  ',calendar_id,calendar_event_id,url';

    foreach( $groups as $group )
    {

        $gid = $group['gid'];
        echo '<form method="post" action="'.site_url('user/public_event_edit').'">';
        echo "<strong>Group id $gid </strong>";
        echo "<button name=\"response\" title=\"Cancel this group\" 
                onclick=\"AreYouSure(this,'DELETE GROUP')\" >Cancel Group</button>
                ";

        // If this event if from external talk, then do not allow user to edit
        // it here.
        if( ! isEventOfTalk( $group ) )
            echo "<button title=\"Edit this event\" name=\"response\" 
                    value=\"edit\" font-size=\"small\">Edit Group</button>";
        else
            echo "This event belongs to a talk, 
                to edit it <a href=\"".site_url('user/manage_talk')."\" > edit its talk</a> .";
        echo "<input type=\"hidden\" name=\"gid\" value=\"$gid\">";
        echo '</form>';

        $today = dbDate( 'today' );
        $events = getTableEntries( 'events', 'date,start_time'
                        , "gid='$gid' AND date >= '$today' AND status='VALID' " );

        if( count( $events ) < 1 )
            continue;

        echo '<table class="condensed">';
        echo arrayToTHRow( $events[0], 'events', $hide );
        foreach( $events as $event )
        {
            if( $event[ 'status' ] != 'VALID' )
                continue;

            echo '<tr>';
            // echo '<td>';
            echo '<form method="post" action="'.site_url('user/public_event_edit').'">';
            echo arrayToRowHTML( $event, 'events', $hide, false, false );
            echo "<td colspan=\"2\"><button name=\"response\" title=\"Cancel this event\" 
                    onclick=\"AreYouSure(this)\"><i class=\"fa fa-trash\"></i></button>
                </td>";
            echo '</tr>';

            $eid = $event[ 'eid' ];
            echo "<input type=\"hidden\" name=\"eid\" value=\"$eid\">";
            echo "<input type=\"hidden\" name=\"gid\" value=\"$gid\">";
            echo '</form>';
        }
        echo '</table>';
    }
    echo '</div>';
}

echo goBackToPageLink( "user/home", "Go back" );

?>
