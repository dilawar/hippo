<?php

/*
 * Task 1. If today is Friday. Then prepare a list of upcoming AWS and send out
 * and email at 4pm.
*/
function aws_friday_notification_cron()
{
    $today = dbDate(strtotime('today'));
    echo printInfo('Today is ' . humanReadableDate($today));
    if (trueOnGivenDayAndTime('this friday', '16:00')) {
        echo printInfo('Today is Friday 4pm. Send out emails for AWS');
        $nextMonday = dbDate(strtotime('next monday'));
        $subject = 'Next Week AWS (' . humanReadableDate($nextMonday) . ') by ';

        $cclist = 'ins@ncbs.res.in,reception@ncbs.res.in';
        $cclist .= ',multimedia@ncbs.res.in,hospitality@ncbs.res.in';
        $to = 'academic@lists.ncbs.res.in';

        $res = awsEmailForMonday($nextMonday);

        if ($res['speakers']) {
            $subject = 'Next week Annual Work Seminar (' . humanReadableDate($nextMonday) . ') by ';
            $subject .= implode(', ', $res['speakers']);
            $mail = $res['email_body'];
            $res = sendHTMLEmail($mail, $subject, $to, $cclist);
        } else {
            // There is no AWS this monday.
            $subject = 'No Annual Work Seminar next week (' . humanReadableDate($nextMonday) . ')';

            $mail = $res['email_body'];
            echo  "Sending to $to, $cclist with subject $subject";
            sendHTMLEmail($mail, $subject, $to, $cclist);
        }
    }
}
