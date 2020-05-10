<?php
require_once BASEPATH . 'autoload.php';

// From constants.php
global $symbDelete;
global $symbEdit;
global $symbCalendar;
global $symbCancel;

echo userHTML();

// Logic for POST requests.
$speaker = [
    'first_name' => '', 'middle_name' => '', 'last_name' => '', 'email' => '', 'department' => '', 'institute' => '', 'title' => '', 'id' => '', 'homepage' => '',
    ];

// Get talks only in future.
if (!isset($months)) {
    $months = 18;
}

$cutoff = dbDateTime("today -$months months");
$whereExpr = "created_by='" . whoAmI() . "'";
$whereExpr .= "AND status!='INVALID' AND DATE(created_on)>'$cutoff'";
$talks = getTableEntries('talks', 'created_on DESC', $whereExpr);

$upcomingTalks = [];

/* Filter talk which have not been delivered yet. */
foreach ($talks as $t) {
    // If talk has been delivered more than 12 hours ago, then do not display
    // them.
    $event = getEventsOfTalkId($t['id']);
    if ($event) {
        // This talk has been delivered successfully.
        if (strtotime($event['date']) <= strtotime('today') - 12 * 3600) {
            continue;
        }
    }
    $upcomingTalks[] = $t;
}

echo '<h2>Upcoming events such as talks, seminar and lectures.</h2>';

if (count($upcomingTalks) < 1) {
    echo alertUser("You don't have any upcoming talk.", false);
} else {
    echo printNote('Following talks were created by you. You can only see upcoming
    talks and talks delivered less than 12 hours ago.');
}

// Show upcoming talks to user. She has edit, delete or schedule them.
foreach ($upcomingTalks as $t) {
    // Outer table
    echo '<table class="table table-sm"><tr><td>';
    // Get image of speaker if available.

    echo inlineImageOfSpeaker($t['speaker_id'], $height = '100px', $width = '100px');
    echo '</td>';

    echo '<td colspan="2">';
    echo '<form method="post" action="' . site_url('user/manage_talks_action') . '">';
    echo '<table border="0">';
    echo '<tr>';
    echo '<div style="font:x-small">';
    echo arrayToTableHTML($t, 'info', '', 'speaker_id,created_by,status,description,id');
    echo '</div>';
    echo '</tr><tr>';
    echo '
        <input type="hidden" name="id" value="' . $t['id'] . '" />
        <td><button class="btn btn-danger" 
                onclick="AreYouSure(this)" name="response" 
            title="Delete this entry" >' . $symbDelete . '</button></td>';

    // Check if this talk has already been approved or in pending approval.
    $event = getTableEntry(
        'events',
        'external_id,status',
        ['external_id' => 'talks.' . $t['id'], 'status' => 'VALID']
    );

    $request = getTableEntry(
        'bookmyvenue_requests',
        'external_id,status',
        ['external_id' => 'talks.' . $t['id'], 'status' => 'PENDING']
    );

    // If either a request of event is found, don't let user schedule the talk.
    // Here we disable the schedule button.
    if (!($request || $event)) {
        echo '<td>';
        echo '<button class="btn btn-primary"
            style="float:right" title="Schedule this talk" 
            name="response" value="schedule">Book A Venue</button> <br />';
        echo '</td>';
        echo printNote('IMP: No venue has been booked yet for this event.');
    } else {
        echo '<td></td>';
    }

    // Put an edit button.
    echo '<td><button class="btn btn-primary" 
            style="float:right" title="Edit this entry"
            name="response" value="edit">' . $symbEdit . '</button></td>';
    echo '</tr></table>';
    echo '</form>';

    // Close outer table.
    echo '</td>';
    echo '</tr></table>';

    // Now put a table showing bookmyvenue_requests/events for this talk.
    // To make sure that user dont' confuse these two table as different
    // talks rather than one talk and one is event/request; reduce the size
    // of second table.
    if ($event) {
        // If event is already approved, show it here.
        echo '<strong>Above talk has been confirmed and event detail is shown 
            below.</strong>';

        $html = arrayToTableHTML($event, 'events', '', 'eid,class,external_id,url,modified_by,timestamp,calendar_id' .
            ',status,calendar_event_id,last_modified_on');

        echo $html;
    }
    // Else there might be a pending request.
    elseif ($request) {
        echo '<strong>The booking request pending review for above talk. </strong>';
        $gid = $request['gid'];
        echo arrayToTableHTML($request, 'requests table table-sm', '', 'eid,class,external_id,url,modified_by,timestamp,calendar_id' .
            ',status,calendar_event_id,last_modified_on');

        echo '<form method="post" action="' . site_url("user/delete_booking_of_talk/$gid") . '">';
        echo '<table class="show_requests"><tr>';
        echo "<td><button onclick=\"AreYouSure(this)\" 
            name=\"response\" title=\"Cancel this request\"> $symbCancel </button></td>";
        echo '</form>';

        echo '<form method="post" action="' . site_url("user/edit_booking_of_talk/$gid") . '">';
        echo "<td style=\"float:right\">
            <button class='btn btn-primary' 
                name=\"response\" title=\"Edit this request\"
            value=\"edit\"> $symbEdit </button></td>";
        echo "<input type=\"hidden\" name=\"gid\" value=\"$gid\" />";
        echo '</form>';
        echo '</tr></table>';
    }
    echo '<hr>';
    echo '<br />';
}

echo goBackToPageLink('user/home', 'Go Home');

echo ' <br /> <br />';
echo '<div id="show_hide">';
echo "<h2>Your booked events in last $months months</h2>";
$hide = 'id,speaker_id,created_by,status';
if ($talks) {
    echo arraysToCombinedTableHTML($talks, 'table table-sm table-hover', $hide);
}
echo '</div>';
?>

