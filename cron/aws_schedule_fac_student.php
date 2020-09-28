<?php

function aws_schedule_fac_student_cron()
{
    /* 9 weeks earlier, if student fails to sign-up, select one from the list */
    if (trueOnGivenDayAndTime('this monday', '15:00')) {
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
        if(! $awses && ($nAlreadyAWSes < 3)) {
            echo("No temp schedule on $afterNWeeks.");
            sendHTMLEmail("Could not assign AWS for $afterNWeeks"
                , "Error! Could not schdule AWS"
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
}
