<?php

require_once BASEPATH . 'autoload.php';

// Get the referece page. These tasks are shared by both ADMIN_ACAD and
// ADMINBMV. Controller must set controller parameter.
$ref = $controller;
echo userHTML();

global $symbEdit;
global $symbDelete;
global $symbCalendar;
global $symbCancel;

// Logic for POST requests.
$speaker = [
    'first_name' => '', 'middle_name' => '', 'last_name' => '', 'email' => '', 'department' => '', 'institute' => '', 'title' => '', 'id' => '', 'homepage' => '',
    ];

// Get talks only in future.
$whereExpr = "status!='INVALID' ORDER BY created_on DESC";
$talks = getTableEntries('talks', '', $whereExpr);

$upcomingTalks = [];

/* Filter talk which have not been delivered yet. */
foreach ($talks as $t) {
    // If talk has been delivered, then dont display.
    $event = getEventsOfTalkId($t['id']);
    if ($event) {
        if (strtotime($event['date']) <= strtotime('yesterday')) {
            // This talk has been delivered successfully.
            continue;
        }
    }
    array_push($upcomingTalks, $t);
}

// A form to select a date and see the talks.
$form = '<form action="#" method="post" accept-charset="utf-8">';
$form .= '<table>';
$form .= "<tr>
    <td> 
        <input class='datepicker' placeholder='Select a date' name='date'/>
    </td>
    <td>
        <button class='btn btn-primary'>Select</button>
    </td>
    </tr>";
$form .= '</table>';
$form .= '</form>';
echo $form;

// If we have a post request.
if (__get__($_POST, 'date', '')) {
    $date = $_POST['date'];
    echo "<h3>Talks on $date</h3>";
    $talksWithEvent = getTalksOnThisDay($date);
    foreach ($talksWithEvent as $entry) {
        $t = $entry['talk'];
        $tid = $t['id'];
        echo '<td>';
        echo '<form method="post" action="' . site_url("$ref/updatetalk/$tid") . '">';
        echo arrayToVerticalTableHTML($t, 'info', '', 'speaker_id');
        echo '</form>';
        // Put an edit button.
        echo '<form method="post" action="' . site_url("$ref/edittalk/$tid") . '">';
        echo '<button class="btn btn-primary" style="float:right" title="Edit this talk"
                name="response" value="edit">' . $symbEdit . '</button>';
        echo '</form>';
    }
    unset($_POST);
}

echo heading('Upcoming talks (Newest first)');
// Show upcoming talks to user. She has edit, delete or schedule them.
echo '<div style="font-size:x-small">';
// Outer table
echo '<table class="table table-hover table_in_table">';
foreach ($upcomingTalks as $t) {
    echo '<tr>';
    /***************************************************************************
     * FIRST COLUMN: Speaker picture.
     */
    echo '<td>';
    echo 'Speaker ID: ' . $t['speaker_id'] . '<br />';
    echo inlineImageOfSpeakerId($t['speaker_id'], $height = '100px', $width = '100px');
    echo '</td>';

    /***************************************************************************
     * SECOND COLUMN: Talk information.
     */
    $tid = $t['id'];

    echo '<td>';
    echo '<form method="post" action="' . site_url("$ref/updatetalk/$tid") . '">';
    echo arrayToVerticalTableHTML($t, 'info', '', 'speaker_id');
    echo '</form>';

    // Put an edit button.
    echo '<form method="post" action="' . site_url("$ref/edittalk/$tid") . '">';
    echo '<button class="btn btn-primary" style="float:right" title="Edit this talk"
            name="response" value="edit">' . $symbEdit . '</button>';
    echo '</form>';

    echo '<form method="post" action="' . site_url("$ref/deletetalk/$tid") . '">';
    echo '<input type="hidden" name="id" value="' . $t['id'] . '" />
        <button class="btn btn-danger" 
            onclick="AreYouSure(this)" name="response" 
            title="Delete this talk" >' . $symbDelete . '</button>';
    echo '</form>';
    echo '</td>';

    /***************************************************************************
     * THIRD COLUMN: Booking related to this talk.
     */

    // Check if this talk has already been approved or in pending approval.
    $externalId = getTalkExternalId($t);
    $event = getTableEntry(
        'events',
        'external_id,status',
        ['external_id' => $externalId, 'status' => 'VALID']
    );

    $request = getTableEntry(
        'bookmyvenue_requests',
        'external_id,status',
        ['external_id' => $externalId, 'status' => 'PENDING']
    );

    // If either a request of event is found, don't let user schedule the talk.
    // Here we disable the schedule button.

    if (!($request || $event)) {
        echo '<td>';
        echo '<form method="post" action="' . site_url("$ref/scheduletalk/$tid") . '">';
        echo '<input type="hidden" name="id" value="' . $t['id'] . '" />';
        echo '<button class="btn btn-secondary" title="Schedule this talk" 
            name="response" value="schedule">' . $symbCalendar . '</button>';
        echo '</form>';
        echo '</td>';
    } else {
        echo '<td>';
        if ($event) {
            // If event is already approved, show it here.
            echo alertUser('<strong>This talk is confirmed.</strong>', false);

            $html = arrayToVerticalTableHTML($event, 'events', 'lightyellow', 'eid,class,url,modified_by,timestamp,calendar_id,status,calendar_event_id,last_modified_on');

            /* PREPARE email template */
            $talkid = explode('.', $event['external_id'])[1];
            $talk = getTableEntry('talks', 'id', ['id' => $talkid]);
            if (!$talk) {
                continue;
            }

            $talkHTML = talkToHTML($talk, false);


            $subject = __ucwords__($talk['class']) . ' by ' . $talk['speaker'] . ' on ' .
                humanReadableDate($event['date']);

            $hostInstitite = emailInstitute($talk['host'], $talk['host_extra']);
            $templ = emailFromTemplate(
                'this_event',
                ['EMAIL_BODY' => $talkHTML, 'HOST_INSTITUTE' => strtoupper($hostInstitite),
                    ]
            );

            $templ = htmlspecialchars(json_encode($templ));

            $html .= '<form method="post" action="' . site_url("$ref/send_email") . '">';
            $html .= '<input type="hidden" name="subject" value="' . $subject . '" >';
            $html .= '<input type="hidden" name="template" value="' . $templ . '" >';
            $html .= '<input type="hidden" name="attachments" value="" >';

            $html .= '<div><button class="btn btn-primary float-left" 
                        title="Send email" name="response" value="send email">
                        Send Email</button></div>';

            $html .= '</form>';
            $html .= '<form method="post" action="' . site_url("$ref/delete_booking") . '">';
            $html .= "<button class='btn btn-danger' onclick=\"AreYouSure(this)\" 
                name=\"response\" title=\"Remove this booking.\"> 
                $symbCancel </button>";

            $eid = $event['eid'];
            $gid = $event['gid'];
            $html .= "<input type=\"hidden\" name=\"eid\" value=\"$eid\">";
            $html .= "<input type=\"hidden\" name=\"gid\" value=\"$gid\">";
            $html .= '</form>';
            echo $html;
        }
        // Else there might be a pending request.
        elseif ($request) {
            echo alertUser('Booking request pending review for above talk.', false);

            $gid = $request['gid'];

            echo arrayToTableHTML($request, 'info table table-sm',
                '', 'eid,class,external_id,url,vc_url,modified_by,timestamp,calendar_id' .
                ',status,calendar_event_id,last_modified_on');

            echo '<form method="post" action="' . site_url("$ref/update_requests") . '">';
            echo "<button class='btn btn-danger' onclick=\"AreYouSure(this)\" 
                name=\"response\" title=\"Cancel this request\"> 
                $symbCancel </button>";
            echo "<button class='btn btn-primary' name=\"response\" title=\"Edit this request\"
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

echo goBackToPageLink("$ref/home", 'Go back');
