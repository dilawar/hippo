<?php

function aws_schedule_fac_student_cron( )
{

    /* 9 weeks earlier, if student fails to sign-up, select one from the list */
    if( trueOnGivenDayAndTime( 'this friday', '10:00' ) )
    {
        $afterNWeeks = dbDate( strtotime( 'this monday' ) + 9 * 7 * 86400 );
        echo printInfo( "Today is monday and after 9 weeks $afterNWeeks" );

        echo humanReadableDate( $afterNWeeks );
        // Get scheduled AWS on this week.
        $awses = getTentativeAWSSchedule( $afterNWeeks );

        // Assign these AWS and send email to speaker.
        $speakers = [];
        foreach( $awses as $aws )
        {
            $speaker = $aws[ 'speaker' ];
            $date = $aws[ 'date' ];
            $res = acceptScheduleOfAWS( $speaker, $date );
            if( $res )
            {
                echo printInfo( "Successfully assigned $speaker for $date" );
                $res = notifyUserAboutUpcomingAWS( $speaker, $date );
            }
            $speakers[] = loginToText( $speaker );
        }
        rescheduleAWS( );
    }
}

?>
