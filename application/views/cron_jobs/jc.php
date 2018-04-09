<?php

require_once 'cron_jobs/helper.php';


////////////////////////////////////////////////////////////////////////////
// JOURNAL CLUB
/////

// At 3PM, we send notification about upcoming JC on 3 days in advance.
if( trueOnGivenDayAndTime( 'today', '15:00' ) )
{
    printInfo( '3pm. Check for presentations after 3 days' );
    $upcomingPresentations = getUpcomingJCPresentations( );

    foreach( $upcomingPresentations as $i => $presentation )
    {
        if(! $presentation[ 'presenter'] )
            continue;

        // If they are exactly after 3 days; send an email.
        if( diffDates( $presentation[ 'date' ], 'today', 'day' ) == 3 )
        {
            $jcID = $presentation['jc_id'];
            $jcInfo = getJCInfo( $jcID );

            $listOfAdmins = array_values( getAllAdminsOfJC( $jcID ) );
            $tableOfJCCoords = arraysToTable( $listOfAdmins );

            $title = getPresentationTitle( $presentation );
            $day = date( 'l', strtotime( $jcInfo[ 'day' ] ) );
            $presenter = loginToText( $presentation[ 'presenter' ], false );

            $macro = array(
                'VENUE' => venueSummary( $jcInfo[ 'venue' ] )
                , 'TITLE' => $title
                , 'DATE' => humanReadableDate( $presentation['date'] )
                , 'TIME' => humanReadableTime( $jcInfo[ 'time' ] )
                , 'PRESENTER' => loginToHTML( $presentation[ 'presenter' ] )
                , 'DESCRIPTION' => presentationToHTML( $presentation )
                , 'TABLE_OF_JC_COORDINATORS' => $tableOfJCCoords
            );

            $mail = emailFromTemplate( 'NOTIFY_ACADEMIC_UPCOMING_JC', $macro );

            $subject = "$jcID (This $day) | '$title' by $presenter";
            $msg = $mail[ 'email_body' ];

            $res = sendHTMLEmail( $msg, $subject, $mail['recipients'], $mail['cc' ] );
            if( $res )
                echo printInfo( 'Email sent successfully' );
        }
    }
}

// Send reminder about today JC.
if( trueOnGivenDayAndTime( 'today', '9:00' ) )
{
    printInfo( "9am. Check for today's presentations" );
    $upcomingPresentations = getUpcomingJCPresentations( );
    foreach( $upcomingPresentations as $i => $presentation )
    {
        if( ! trim( $presentation[ 'presenter' ] ) )
            continue;

        if( diffDates( $presentation[ 'date' ], 'today', 'day' ) == 0 )
        {
            $jcID = $presentation['jc_id'];
            $jcInfo = getJCInfo( $jcID );

            $listOfAdmins = array_values( getAllAdminsOfJC( $jcID ) );
            $tableOfJCCoords = arraysToTable( $listOfAdmins );

            $title = getPresentationTitle( $presentation );

            if( ! trim($title) )
                continue;

            $day = date( 'l', strtotime( $jcInfo[ 'day' ] ) );

            $macro = array( 'VENUE' => venueSummary( $jcInfo[ 'venue' ] )
                , 'TITLE' => $title
                , 'DATE' => humanReadableDate( $presentation['date'] )
                , 'TIME' => humanReadableTime( $jcInfo[ 'time' ] )
                , 'PRESENTER' => loginToHTML( $presentation[ 'presenter' ] )
                , 'DESCRIPTION' => presentationToHTML( $presentation )
                , 'TABLE_OF_JC_COORDINATORS' => $tableOfJCCoords
            );


            $presenter = loginToText( $presentation[ 'presenter' ], false );
            $mail = emailFromTemplate( 'NOTIFY_ACADEMIC_UPCOMING_JC', $macro );

            $subject = "$jcID (Today) | '$title' by $presenter";
            $msg = $mail[ 'email_body' ];
            $res = sendHTMLEmail( $msg, $subject, $mail['recipients'], $mail['cc' ] );
            if( $res )
                echo printInfo( 'Email sent successfully' );
        }
    }
}

?>
