<?php

require_once BASEPATH . '/extra/admin.php';

function events_everyday_morning_cron()
{
    $today = dbDate('today');
    echo printInfo('8am. Event for today');
    $todaysEvents = getPublicEventsOnThisDay($today);
    $nTalks = 0;
    $fcmBody = '';
    if (count($todaysEvents) > 0) {
        foreach ($todaysEvents as $event) {
            $external_id = $event['external_id'];

            // External id has the format TALKS.TALK_ID
            $talkid = explode('.', $external_id);
            if (2 == count($talkid)) {
                $data = ['id' => $talkid[1]];
                $talk = getTableEntry('talks', 'id', $data);
                if ($talk) {
                    $fcmBody .= '<br />' . $talk['title'];
                    $html = talkToHTML($talk);
                    ++$nTalks;

                    // Now prepare an email to sent to mailing list.
                    $macros = ['EMAIL_BODY' => $html, 'DATE' => $today];
                    $subject = 'Today (' . humanReadableShortDate($today) . '): ';
                    $subject .= talkToShortEventTitle($talk);

                    $template = emailFromTemplate('todays_events', $macros);

                    if (array_key_exists('email_body', $template) && $template['email_body']) {
                        // Send it out.
                        $to = $template['recipients'];
                        $ccs = $template['cc'];
                        $msg = $template['email_body'];

                        $attachment = '';

                        try {
                            $attachment = eventToICALFile($event);
                        } catch (Exception $e) {
                        }
                        // echo printInfo("Event $subject; attachment: $attachment");
                        $res = sendHTMLEmail($msg, $subject, $to, $ccs, $attachment);
                        if ($res) {
                            echo printInfo('Email sent successfully');
                        }
                    }
                }
            }
        }
        if ($fcmBody) {
            sendFirebaseCloudMessage('academic', "Today's academic events", $fcmBody);
        }
    } else {
        echo printInfo('No event found on day ' . $today);
    }

}

// JC emails.
function jc_every_morning()
{
    $today = dbDate('today');
    $jcs = getJournalClubs();
    foreach ($jcs as $jc) {
        $jcid = $jc['id'];
        $jc = getJCPresentation($jcid);
        if ($jc) {
            sendFirebaseCloudMessage($jcid, "Today's $jcid by " . $jc['presenter'], $jc['title']);
        }
    }

    $courses = getRunningCoursesAtThisDay('today');
    $html = '';
    foreach ($courses as $course) {
        $html = '';
        $sinfo = getSlotInfo($course['slot'],
            $course['ignore_tiles']);
        $html .= $course['name'];
        $html .= ' at ' . $course['venue'];
        $html .= ', ' . $sinfo;
        $html .= '.<br />';
        sendFirebaseCloudMessage($course['id'], 'One of your courses is running today', $html);
    }
}

function pending_requests()
{
    $nrToday = getNumPendingRequestsOnThisDay('today');
    $nrTomorrow = getNumPendingRequestsOnThisDay('tomorrow');
    $data = ['NUM_TODAY' => $nrToday, 'NUM_TOMORROW' => $nrTomorrow];

    if($nrToday + $nrTomorrow > 0) {
        $email = emailFromTemplate('PENDING_REQUESTS', $data);
        sendHTMLEmail($email['email_body']
            , "Pending requests requires action"
            , $email['recipients']
            , $email['cc']
        );
    }
}
