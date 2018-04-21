<?php

require_once BASEPATH.'autoload.php';

echo userHTML( );

$requests = getRequestOfUser( $_SESSION['user'], $status = 'PENDING' );

if( count( $requests ) < 1 )
    echo alertUser( "No pending request found" );
else
    echo alertUser( "You have following pending requests" );

foreach( $requests as $request )
{
    $tobefiltered = Array( 
        'gid', 'created_by', 'rid', 'modified_by', 'timestamp'
        , 'url' , 'status', 'external_id'
    );
    $gid = $request['gid'];

    echo "<div style=\"font-size:small;\">";
    echo "<table class=\"info\" >";
    echo "<tr>";
    echo "<td>" . arrayToTableHTML( $request, "requests", NULL, $tobefiltered );
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
    echo '</div>';
}
echo goBackToPageLink( "user", "Go back" );


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
        echo '<form method="post" action="'.site_url('user/private_event_edit').'">';
        echo "<table style=\"width:600px\">";
        echo "<tr><td> <strong>Group id $gid </strong>";
        echo "<button name=\"response\" title=\"Cancel this group\" 
                onclick=\"AreYouSure(this)\" >Cancel Group</button>
                ";

        // If this event if from external talk, then do not allow user to edit
        // it here.
        if( ! isEventOfTalk( $group ) )
            echo "<button title=\"Edit this event\" name=\"response\" 
                    value=\"edit\" font-size=\"small\">Edit Group</button>";
        else
            echo "This event belongs to a talk, 
                to edit it <a href=\"user_manage_talk.php\" > edit its talk</a> .";

        echo "</td></tr>";
        echo "<input type=\"hidden\" name=\"gid\" value=\"$gid\">";
        echo '</form>';

        $today = dbDate( 'today' );
        $events = getTableEntries( 'events', 'date,start_time'
                        , "gid='$gid' AND date >= '$today' AND status='VALID' " );

        if( count( $events ) < 1 )
            continue;

        foreach( $events as $event )
        {
            if( $event[ 'status' ] != 'VALID' )
                continue;

            echo '<tr>';
            echo '<td>';
            echo '<form method="post" action="'.site_url('user/private_event_edit').'">';
            echo arrayToTableHTML( $event, 'events', '', $hide );
            echo "<td colspan=\"2\"><button name=\"response\" title=\"Cancel this event\" 
                    onclick=\"AreYouSure(this)\" > <i class=\"fa fa-trash\"></i>
                    </button>
                </td>";
            echo '</tr>';

            $eid = $event[ 'eid' ];
            echo "<input type=\"hidden\" name=\"eid\" value=\"$eid\">";
            echo "<input type=\"hidden\" name=\"gid\" value=\"$gid\">";
            echo '</form>';

        }

        echo "</table>";
        echo "<br>";
    }
    echo '</div>';
}

echo goBackToPageLink('user', 'Go Back');

?>
