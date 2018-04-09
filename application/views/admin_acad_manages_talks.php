<?php

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN', 'BOOKMYVENUE_ADMIN' ) );

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
$whereExpr = "status!='INVALID' ORDER BY created_on ASC";
$talks = getTableEntries( 'talks', '', $whereExpr );

$upcomingTalks = array( );

/* Filter talk which have not been delivered yet. */
foreach( $talks as $t )
{
    // If talk has been delivered, then dont display.
    $event = getEventsOfTalkId( $t['id'] );
    if( $event )
        if( strtotime($event[ 'date' ] ) <= strtotime( 'yesterday' ) )
            // This talk has been delivered successfully.
            continue;
    array_push( $upcomingTalks, $t );
}

echo "<h1>Upcoming talks</h1>";

// Show upcoming talks to user. She has edit, delete or schedule them.
echo '<div style="font-size:x-small">';
// Outer table
echo '<table class="table_in_table">';
foreach( $upcomingTalks as $t )
{
    echo '<tr>';
    /***************************************************************************
     * FIRST COLUMN: Speaker picture.
     */
    echo '<td>';
    echo "Speaker ID: " . $t['speaker_id'] . '<br />';
    echo inlineImageOfSpeakerId( $t['speaker_id'], $height = '100px', $width = '100px' );
    echo '</td>';

    /***************************************************************************
     * SECOND COLUMN: Talk information.
     */
    echo '<td>';
    echo '<form method="post" action="admin_acad_manages_talks_action.php">';
    echo arrayToVerticalTableHTML( $t, 'info', '', 'speaker_id');

    // Put an edit button. 
    echo '<button style="float:right" title="Edit this talk"
            name="response" value="edit">' . $symbEdit . '</button>';

    echo '<input type="hidden" name="id" value="' . $t[ 'id' ] . '" />
        <button onclick="AreYouSure(this)" name="response" 
            title="Delete this talk" >' . $symbDelete . '</button>';
    echo '</form>';
    echo '</td>';

    /***************************************************************************
     * THIRD COLUMN: Booking related to this talk.
     */

    // Check if this talk has already been approved or in pending approval.
    $externalId = getTalkExternalId( $t );
    $event = getTableEntry( 'events', 'external_id,status'
        , array( 'external_id' => $externalId, 'status' => 'VALID' )
        );

    $request = getTableEntry( 'bookmyvenue_requests', 'external_id,status'
        , array( 'external_id' => $externalId, 'status'  => 'PENDING' )
        );

    // If either a request of event is found, don't let user schedule the talk. 
    // Here we disable the schedule button.

    if( ! ($request || $event ) )
    {
        echo '<td>';
        echo '<form method="post" action="admin_acad_manages_talks_action.php">';
        echo '<input type="hidden" name="id" value="' . $t[ 'id' ] . '" />';
        echo '<button title="Schedule this talk" 
            name="response" value="schedule">' . $symbCalendar . '</button>';
        echo '</form>';
        echo '</td>';
    }
    else
    {
        echo '<td>';
        if( $event )
        {
            // If event is already approved, show it here.
            echo alertUser( "<strong>This talk is confirmed.</strong>" );

            $html = arrayToVerticalTableHTML( $event, 'events', 'lightyellow'
                , 'eid,class,url,modified_by,timestamp,calendar_id' . 
                ',status,calendar_event_id,last_modified_on' );

            /* PREPARE email template */
            $talkid = explode( '.', $event[ 'external_id' ])[1];
            $talk = getTableEntry( 'talks', 'id', array( 'id' => $talkid ) );
            if( ! $talk )
                continue;

            $talkHTML = talkToHTML( $talk, false );

            $subject = __ucwords__( $talk[ 'class' ] ) . " by " . $talk['speaker'] . ' on ' .
                humanReadableDate( $event[ 'date' ] );

            $hostInstitite = emailInstitute( $talk[ 'host' ] );

            $templ = emailFromTemplate(
                "this_event" 
                , array( 'EMAIL_BODY' => $talkHTML
                        , 'HOST_INSTITUTE' => strtoupper( $hostInstitite )
                    ) 
                );
            $templ = htmlspecialchars( json_encode( $templ ) );

            $html .= '<form method="post" action="./admin_acad_send_email.php">';
            $html .= '<input type="hidden" name="subject" value="'. $subject . '" >';
            $html .= '<input type="hidden" name="template" value="'. $templ . '" >';


            $html .= "<p>You can send email: ";
            $html .= '<button title="Send email" name="response" value="send email">Email</button>';
            $html .= '</p>';
            $html .= '</form>';
            echo $html;
        }
        // Else there might be a pending request.
        else if( $request )
        {
            echo alertUser( "Shown below is the booking request pending review for 
                    above talk." );

            $gid = $request[ 'gid' ];

            echo arrayToVerticalTableHTML( $request, 'requests', ''
                , 'eid,class,external_id,url,modified_by,timestamp,calendar_id' . 
                ',status,calendar_event_id,last_modified_on' );

            echo '<form method="post" action="user_show_requests_edit.php">';
            echo "<button onclick=\"AreYouSure(this)\" 
                name=\"response\" title=\"Cancel this request\"> 
                $symbCancel </button>";
            echo "<button name=\"response\" title=\"Edit this request\"
                value=\"edit\"> $symbEdit </button>";
            echo "<input type=\"hidden\" name=\"gid\" value=\"$gid\">";
            echo '</form>';
        }
        echo '</td>';
    }
    echo '</tr>';
}
echo '</table>';
echo '</div>';
    
echo goBackToPageLink( "user.php", "Go back" );

?>
