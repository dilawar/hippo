<?php

function aws_schedule_fac_student_cron()
{
    $afterNWeeks = dbDate(strtotime('this monday') + 9 * 7 * 86400);
    echo printInfo("Today is monday and after 9 weeks $afterNWeeks");

    $res = rescheduleAWS();
    print_r($res);

    // Get how many aws are scheduled.
    $alreadyAWS = getUpcomingAWSOnThisMonday($afterNWeeks);
    $nAlreadyAWSes = count($alreadyAWS);

    echo( humanReadableDate($afterNWeeks) . ". Already assigned aws $nAlreadyAWSes  <br />");

    // Get scheduled AWS on this week.
    $awses = getTentativeAWSSchedule($afterNWeeks);
    if(count($awses) === 0 && ($nAlreadyAWSes < 3)) {
        echo("No temp schedule on $afterNWeeks.");
        sendHTMLEmail( "Could not assign AWSs for $afterNWeeks. May be it is a holiday. If not, this is a system failure!"
            , "Warning! Could not schdule AWS for $afterNWeeks"
            , "acadoffice@ncbs.res.in", "hippo@lists.ncbs.res.in"
        );
    }

    // Assign these AWS and send email to speaker.
    for ($i = 0; $i < min(3 - $nAlreadyAWSes, count($awses)); ++$i) {
        $aws = $awses[$i];
        $speaker = $aws['speaker'];
        $date = $aws['date'];
        $res = acceptScheduleOfAWS($speaker, $date);
        if ($res['awsid'] > 0) {
            echo printInfo("Successfully assigned $speaker for $date");
            notifyUserAboutUpcomingAWS($speaker, $date);
        }
    }
}
