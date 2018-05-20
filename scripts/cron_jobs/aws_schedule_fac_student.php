<?php

require_once 'cron_jobs/helper.php';

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Send email to PI. From 10 weeks from now, a slot has been open
    * up for his lab. He must ask her students to sign up.
    *
    * NOTE: If no one signs up for next two weeks, assign the student by itself.
    * @Param 'today'
 */
///* ----------------------------------------------------------------------------*/
//if( trueOnGivenDayAndTime( 'this monday', '10:00' ) )
//{
//    $today = 'today';
//    printInfo( ' Monday and 9am. Tell faculty that their lab has been selected.' );
//
//    // Get the monday after 10 weeks.
//    $afterNWeeks = dbDate( strtotime( 'today' ) + 10 * 7 * 86400 );
//    echo printInfo( "Today is monday and after 10 weeks $afterNWeeks" );
//
//    echo humanReadableDate( $afterNWeeks );
//
//    // Get scheduled AWS on this week.
//    $awses = getTentativeAWSSchedule( $afterNWeeks );
//
//    // Assign these AWS and send email to speaker.
//    $piSlots = array( );
//    foreach( $awses as $aws )
//    {
//        $speaker = $aws[ 'speaker' ];
//        $piOrHost = getPIOrHost( $speaker );
//        $piSlots[ $piOrHost ][] = getLoginInfo( $speaker );
//        $specialization = getFacultySpecialization( $piOrHost );
//    }
//
//    foreach( $piSlots as $piOrHost => $speakers )
//    {
//        $numSpeakers = count( $speakers );
//
//        $numSlotExpr = "$numSpeakers slot is ";
//        if( count( $numSpeakers ) > 1 )
//            $numSlotExpr = "$numSpeakers slots are ";
//
//        $defaultAssignmentTable = arrayToHtmlTableOfLogins( $speakers );
//
//        $listSpeakers = getAWSSpeakers( 'login', "pi_or_host='$piOrHost'" );
//        $allSpeakersTable = arrayToHtmlTableOfLogins( $listSpeakers );
//
//        $date = $aws[ 'date' ];
//
//        $pi = findAnyoneWithEmail( $piOrHost );
//        $piHTML = arrayToName( $pi );
//
//
//        $macros = array( "FACULTY" => $piHTML
//            , 'AWS_DATE' => humanReadableDate( $afterNWeeks )
//            , 'AWS_THEME' => "'$specialization'"
//            , 'NUMBER_OF_SLOTS_EXPR' => $numSlotExpr
//            , 'DEFAULT_ASSIGNMENT_TABLE' => $defaultAssignmentTable
//            , 'AWS_SPEAKERS_TABLE' => $allSpeakersTable
//        );
//
//        $templ = emailFromTemplate(
//            'NOTIFY_SUPERVISOR_ABOUT_AWS_SLOT_N_WEEKS_IN_ADVANCE'
//            , $macros
//        );
//
//        // Send email.
//        $to = $piOrHost;
//        $subject = "Your lab has been assigned Annual Work Seminar slot(s) on $afterNWeeks";
//        sendHTMLEmail( $templ['email_body'], $subject, $to, $templ[ 'cc' ] );
//    }
//}

/* 9 weeks earlier, if student fails to sign-up, select one from the list */
if( trueOnGivenDayAndTime( 'this monday', '11:30' ) )
{
    echo printInfo( 'Monday and 11:30am. Assign AWS' );
    $afterNWeeks = dbDate( strtotime( 'this monday' ) + 9 * 7 * 86400 );
    echo printInfo( "Today is monday and after 9 weeks $afterNWeeks" );

    echo humanReadableDate( $afterNWeeks );
    // Get scheduled AWS on this week.
    $awses = getTentativeAWSSchedule( $afterNWeeks );

    // Assign these AWS and send email to speaker.
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
    }
    rescheduleAWS( );
}

?>
