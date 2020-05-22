<?php

function jc_cron()
{
    // At 3PM, we send notification about upcoming JC on 3 days in advance.
    if (trueOnGivenDayAndTime('today', '15:00')) {
        echo printInfo('3pm. Check for presentations after 3 days');
        $upcomingPresentations = getUpcomingJCPresentations();

        foreach ($upcomingPresentations as $i => $presentation) {
            $jcID = $presentation['jc_id'];
            if (!(trim($presentation['presenter']) && trim($jcID))) {
                printWarning('Invalid presenter or jcid ');

                continue;
            }

            // If they are exactly after 3 days; send an email.
            if (3 == diffDates($presentation['date'], 'today', 'day')) {
                $listOfAdmins = array_values(getAllAdminsOfJC($jcID));
                $presenters = getJCPresenters($presentation);
                $tableOfJCCoords = arraysToTable($listOfAdmins);

                $jcInfo = getJCInfo($jcID);
                $title = getPresentationTitle($presentation);
                $day = date('l', strtotime($jcInfo['day']));

                $macro = [
                    'BODY' => jcToHTML($presentation), 'TABLE_OF_JC_COORDINATORS' => $tableOfJCCoords,
                ];

                $mail = emailFromTemplate('NOTIFY_ACADEMIC_UPCOMING_JC', $macro);

                $subject = "$jcID (This $day) | '$title' by $presenters";
                $msg = $mail['email_body'];

                $res = sendHTMLEmail($msg, $subject, $mail['recipients'], $mail['cc']);
                if ($res) {
                    echo printInfo('Email sent successfully');
                }
            }
        }
    }

    // Send reminder about today JC.
    if (trueOnGivenDayAndTime('today', '8:00')) {
        echo printInfo("9am. Check for today's presentations");
        $upcomingPresentations = getUpcomingJCPresentations();
        foreach ($upcomingPresentations as $i => $presentation) {
            $jcID = $presentation['jc_id'];
            if (!(trim($presentation['presenter']) && trim($jcID))) {
                printWarning('Invalid presenter or jcid ');

                continue;
            }

            // If JC is today.
            if (0 == diffDates($presentation['date'], 'today', 'day')) {
                $listOfAdmins = array_values(getAllAdminsOfJC($jcID));
                $presenters = getJCPresenters($presentation);
                $tableOfJCCoords = arraysToTable($listOfAdmins);

                $jcInfo = getJCInfo($jcID);
                $title = getPresentationTitle($presentation);
                $day = date('l', strtotime($jcInfo['day']));

                $macro = [
                    'BODY' => jcToHTML($presentation), 'TABLE_OF_JC_COORDINATORS' => $tableOfJCCoords,
                ];

                $mail = emailFromTemplate('NOTIFY_ACADEMIC_UPCOMING_JC', $macro);

                $subject = "$jcID (Today) | '$title' by $presenters";
                $msg = $mail['email_body'];

                $res = sendHTMLEmail($msg, $subject, $mail['recipients'], $mail['cc']);
                if ($res) {
                    echo printInfo('Email sent successfully');
                }
            }
        }
    }
}

function remind_presenter() 
{
    // 1 week in advance.
    $day = dbDate(strtotime('today')+7*86400);
    $upcomingPresentations = getUpcomingJCPresentationsOnThisDay($day);
    foreach ($upcomingPresentations as $i => $presentation) {
        $body = p("You have a upcoming JC presentation on $day");
        $body .= arrayToTableHTML($presentation, '');
        $body .= p("This is one time reminder sent 1 week in advance.");
        $subject = "Friendly reminder: Upcoming jc on $day";
        $emails = findAnyoneWithLoginOrEmail($presentation['presenter']);
        if($emails)
            sendHTMLEmail($body, $subject, $emails[0]['email'], 'hippo@lists.ncbs.res.in');
    }
}
