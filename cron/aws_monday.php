<?php

require_once FCPATH . '/system/extra/acad.php';

function aws_monday_morning_cron()
{
    error_log('Monday 10amm. Notify about AWS');
    echo printInfo('Today is Monday. Send out emails for AWS');
    $thisMonday = dbDate(strtotime('this monday'));
    $subject = 'Today\'s AWS (' . humanReadableDate($thisMonday) . ') by ';
    $res = awsEmailForMonday($thisMonday);
    $to = 'academic@lists.ncbs.res.in';

    if ($res['speakers']) {
        echo printInfo("Sending mail about today's AWS");
        $subject .= implode(', ', $res['speakers']);

        $mail = $res['email_body'];
        $cclist = $res['cc'];
        $to = $res['recipients'];

        error_log("Sending to $to, $cclist with subject $subject");
        echo "Sending to $to, $cclist with subject $subject";

        $ret = sendHTMLEmail($mail, $subject, $to, $cclist);
    } else {
        // There is no AWS this monday.
        $subject = 'No Annual Work Seminar today : ' .
            humanReadableDate($nextMonday);
        $mail = $res['email']['email_body'];
        sendHTMLEmail($mail, $subject, $to, $res['email']['cc']);
    }
}

function notifyAcadOfficeUnassignedSlotOrMissingChair()
{
    $thisMonday = dbDate('this monday');
    // Check for next 6 weeks.
    $table = '<table>';
    $table .= '<caption>Unassigned slots or missing chairs</caption>';
    $table .= '<tr><th>Date</th><th>Number of unassigned slots</th><th>Missing/Unconfirmed Chair</th></tr>';

    $totalMissing = 0;
    for ($i = 1; $i <= 8; ++$i) {
        $weekDate = dbDate(strtotime("+$i weeks", strtotime($thisMonday)));
        if (isAWSHoliday($weekDate)) {
            continue;
        }

        echo " | This week is $weekDate <br /> ";
        $awses = getUpcomingAWSOnThisMonday($weekDate);
        $chair = count($awses) > 0?$awses[0]['chair']:'';
        $chairConfirmed = count($awses) > 0?$awses[0]['has_chair_confirmed']:'';
        $nMissing = 3 - count($awses);
        if (($nMissing > 0) || (! $chair) || ($chair && $chairConfirmed==='NO')) {
            $table .= "<tr><td> $weekDate </td><td> $nMissing </td>";
            $table .= "<td>$chair (Chair confirmed: $chairConfirmed)</td>";
            $table .= "</tr>";
            $totalMissing += $nMissing;
        }
    }
    $table .= '</table>';
    echo "$table";

    if (0 == $totalMissing) {
        return;
    }

    $email = emailFromTemplate('NOTIFY_ACADOFFICE_UNASSIGNED_SLOTS', ['TABLE' => $table]);
    if (!$email) {
        echo p('Could not find email template');

        return;
    }

    sendHTMLEmail(
        $email['email_body'],
        'Some AWS slots and chairs are still not assgined',
        $email['recipients'],
        $email['cc']
    );
}

/* --------------------------------------------------------------------------*/
/**
 * @Synopsis Book venue for upcoming aws.
 *
 * @Returns
 */
/* ----------------------------------------------------------------------------*/
function bookVenueForAWS()
{
    $today = dbDate('today');
    $upAWSs = getTableEntries('upcoming_aws', 'date', "status='VALID' AND date > '$today'");
    echo printInfo('Found total ' . count($upAWSs) . ' awses.');

    $awsOnThisDay = [];
    foreach ($upAWSs as $aws) {
        $naws = __get__($awsOnThisDay, $aws['date'], 0) + 1;
        $awsOnThisDay[$aws['date']] = $naws;

        // Each AWS is 25 mins.
        $aws['time'] = dbTime(strtotime($aws['time']) + ($naws - 1) * 30 * 60);

        /* Book avenue for this AWS. Remove any colliding request/booking. Send
         * email to the booking party.
         */
        $res = bookAVenueForThisAWS($aws, $removeCollision = true);
        if (!$res['success']) {
            echo p('Failure: ' . $res['msg']);
        } else {
            echo p('Success: ' . $res['msg']);
        }

        foreach ($res['collision'] as $collision) {
            echo p('Colliding with.');
            echo arrayToTableHTML($collision, 'info');
        }
    }
}

function assignChair()
{
    // Assign chair 4 weeks in advance.
    $monday = strtotime('this monday') + 4*7*86400; // this monday + 4 weeks.
    $monDate = dbDate($monday);
    echo "Assigning chair $monDate";

    $awses = getTableEntries('upcoming_aws', 'date', "date='$monDate'");
    if(count($awses) == 0) {
        echo printInfo("No AWS on this day");
        return;
    }

    $alreadyAssigned = [];
    foreach(getTableEntries('upcoming_aws', 'date', "status='VALID'", 'chair') as $ch) {
        if($ch['chair']) {
            $alreadyAssigned[] = $ch['chair'];
        }
    }

    $chair = $awses[0]['chair'];
    if(! $chair) {
        $potentialChairs = getTableEntries(
            'faculty', 'email',
            "willing_to_chair_aws='YES'", 'email'
        );

        $chairs = [];
        foreach($potentialChairs as $ch) {
            $email = $ch['email'];
            $chairs[] = $email;
        }

        $potentialChairs = array_diff($chairs, $alreadyAssigned);
        shuffle($potentialChairs);
        if(count($potentialChairs) > 0) {
            $chair = $potentialChairs[0];
            assignAWSChair($chair, $monDate);
            echo printInfo(" ... Assigned $chair on $monDate");
        } else {
            echo printInfo("No one is found to be chair on $monDate");
        }
    }
    else {
        echo printInfo("Already a chair $chair on $monDate");
    }

}
