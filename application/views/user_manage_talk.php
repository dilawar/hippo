<?php

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'USER' ) );

include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

echo userHTML( );

// Logic for POST requests.
$speaker = array( 
    'first_name' => '', 'middle_name' => '', 'last_name' => '', 'email' => ''
    , 'department' => '', 'institute' => '', 'title' => '', 'id' => ''
    , 'homepage' => ''
    );

// Get talks only in future.
$whereExpr = "created_by='" . $_SESSION[ 'user' ] . "'";
$whereExpr .= "AND status!='INVALID' ORDER BY created_on DESC";
$talks = getTableEntries( 'talks', '', $whereExpr );
$upcomingTalks = array( );

/* Filter talk which have not been delivered yet. */
foreach( $talks as $t )
{
    // If talk has been delivered more than 12 hours ago, then do not display 
    // them.
    $event = getEventsOfTalkId( $t['id'] );
    if( $event )
        if( strtotime($event[ 'date' ] ) <= strtotime( 'today' ) - 12 * 3600 )
            // This talk has been delivered successfully.
            continue;

    array_push( $upcomingTalks, $t );
}

if( count( $upcomingTalks ) < 1 )
    echo alertUser( "You don't have any upcoming talk." );
else
    echo alertUser( "Following talks were created by you. You can only see upcoming
    talks and talks delivered less than 12 hours ago." );

// Show upcoming talks to user. She has edit, delete or schedule them.
foreach( $upcomingTalks as $t )
{
    // Outer table
    echo '<table><tr><td>';
    // Get image of speaker if available.

    echo inlineImageOfSpeaker( $t['speaker_id'], $height = '100px', $width = '100px' );
    echo '</td><td>';
    echo '<form method="post" action="user_manage_talks_action.php">';
    echo '<table border="0">';
    echo '<tr>';

    echo '<div style="font:small">';
    echo arrayToTableHTML( $t, 'info', ''
                , 'speaker_id,created_by,status'
            );
    echo '</div>';

    echo '</tr><tr>';
    echo '
        <input type="hidden" name="id" value="' . $t[ 'id' ] . '" />
        <td><button onclick="AreYouSure(this)" name="response" 
            title="Delete this entry" >' . $symbDelete . '</button></td>';

    // Check if this talk has already been approved or in pending approval.
    $event = getTableEntry( 'events', 'external_id,status'
        , array( 'external_id' => 'talks.' . $t[ 'id' ], 'status' => 'VALID' )
        );

    $request = getTableEntry( 'bookmyvenue_requests', 'external_id,status'
        , array( 'external_id' => 'talks.' . $t[ 'id' ], 'status'  => 'PENDING' )
        );

    // If either a request of event is found, don't let user schedule the talk. 
    // Here we disable the schedule button.
    if( ! ($request || $event ) )
        echo '<td><button style="float:right" title="Schedule this talk" 
        name="response" value="schedule">' . $symbCalendar . '</button></td>';
    else
        echo '<td></td>';

    // Put an edit button. 
    echo '<td><button style="float:right" title="Edit this entry"
            name="response" value="edit">' . $symbEdit . '</button></td>';

    echo '</tr></table>';
    echo '</form>';

    // Close outer table.
    echo '</td></tr></table>';

    // Now put a table showing bookmyvenue_requests/events for this talk.
    // To make sure that user dont' confuse these two table as different 
    // talks rather than one talk and one is event/request; reduce the size 
    // of second table.
    echo "<div style=\"font-size:x-small\">";
    if( $event )
    {
        // If event is already approved, show it here.
        echo "<strong>Above talk has been confirmed and event detail is shown 
            below.</strong>";
        $html = arrayToTableHTML( $event, 'events', ''
            , 'eid,class,external_id,url,modified_by,timestamp,calendar_id' . 
            ',status,calendar_event_id,last_modified_on' );
        echo $html;
    }
    // Else there might be a pending request.
    else if( $request )
    {
        echo "<strong>Shown below is the booking request pending review for 
                above talk. </strong>
            ";
        $gid = $request[ 'gid' ];

        echo arrayToTableHTML( $request, 'requests', ''
            , 'eid,class,external_id,url,modified_by,timestamp,calendar_id' . 
            ',status,calendar_event_id,last_modified_on' );

        echo '<form method="post" action="user_show_requests_edit.php">';
        echo "<table class=\"show_requests\"><tr>";
        echo "<td><button onclick=\"AreYouSure(this)\" 
            name=\"response\" title=\"Cancel this request\"> 
            $symbCancel </button></td>";
        echo "<td style=\"float:right\">
            <button name=\"response\" title=\"Edit this request\"
            value=\"edit\"> $symbEdit </button></td>";
        echo "</tr></table>";
        echo "<input type=\"hidden\" name=\"gid\" value=\"$gid\">";
        echo '</form>';
    }
    echo "</div>";
    echo "<hr>";
    echo "<br />";
}
    
echo goBackToPageLink( "user.php", "Go back" );

?>
