<?php

require_once 'cron_jobs/helper.php';

/*
 * Task 3: Annoy AWS speaker if they have not completed their entry.
 */
$today = strtotime( 'today' );
$endDay = strtotime( 'next friday' );
$startDay = $endDay - (3 * 24 * 3600 );
if( $today > $startDay && $today <= $endDay )
{
    $awayFrom = strtotime( 'now' ) - strtotime( '10:00 am' );
    if( $awayFrom > -1 && $awayFrom < 15 * 60 )
    {
        printInfo( 'Annoy AWS speakers' );
        // Every day 10 am. Annoy.
        $upcomingAws = getUpcomingAWSOnThisMonday( 'next monday' );
        foreach( $upcomingAws as $aws )
        {
            if( $aws[ 'title' ] && $aws['abstract'] )
                continue;

            // Otherwise annoy
            $subject = "Details of your upcoming AWS are still incomplete!";
            $to = getLoginEmail( $aws[ 'speaker' ] );
            $pi = getPIOrHost( $aws[ 'speaker' ] );

            $macros = array( 'USER' => loginToHTML( $aws['speaker'] )
                            , 'DATE' => humanReadableDate( $today )
                        );

            $templ = emailFromTemplate( 'hippo_annoys_aws_speaker', $macros );

            // Log it.
            error_log( "AWS entry incomplete. Annoy " . $to  );
            sendHTMLEmail(
                $templ[ 'email_body' ], $subject, $to, $templ[ 'cc' ]
                );
        }

    }
}


/* If user has not acknowledged their aws date, send them this reminder on even
 * days; at 10 AM.
 */
if( trueOnGivenDayAndTime( 'today', '10:00' ) )
{
    $dayNo = date( 'N', strtotime( 'today' ) );
    // Send this reminder only on Monday and Friday only.
    // 1. Is monday, 7 is sunday.
    if( ($dayNo + 3) % 4 == 0 ) // Monday and Friday.
    {
        error_log( "Checking for upcoming aws which has not been acknowleged" );
        echo printInfo( "Checking for upcoming aws which has not been acknowleged" );

        // Get all events which are grouped.
        $nonConfirmedUpcomingAws = getUpcomingAWS( );

        foreach( $nonConfirmedUpcomingAws as $aws )
        {
            if( $aws[ 'acknowledged' ] == 'YES' )
            {
                echo printInfo( $aws[ 'speaker' ] . " has already confirmed " );
                continue;
            }

            //var_dump( $aws );
            $speaker = $aws[ 'speaker' ];
            $table = arrayToVerticalTableHTML( $aws, 'aws' );
            $to = getLoginEmail( $speaker );

            $email = emailFromTemplate( 'REMIND_SPEAKER_TO_CONFIRM_AWS_DATE'
                        , array( 'USER' => loginToHTML( $speaker )
                        , 'AWS_DATE' => humanReadableDate( $aws[ 'date' ] ) )
                );

            $subject = "Please confirm your annual work seminar (AWS) date";
            $body = $email[ 'email_body' ];

            // Check if URL exists in query table.
            $queryID = getQueryWithIdOrExtId( 'upcoming_aws.' . $aws[ 'id' ] );
            if( $queryID > -1 )
            {
                $clickableURL = queryToClickableURL( $queryID, 'Click here to acknowlege' );
                $body = addClickabelURLToMail( $body, $clickableURL );
            }

            $body .= "<p> This email was automatically generated and sent on " .
                humanReadableDate( 'now' ) .
                ". If this is mistake, please write to acadoffice@ncbs.res.in.</p>";

            // Add PI to cc list.
            $cclist = $email[ 'cc' ];
            $pi = getPIOrHost( $speaker );
            if( $pi )
                $cclist .= ",$pi";

            echo printInfo( "Sending reminder to $to " );
            sendHTMLEmail( $body, $subject, $to, $cclist );
        }
    }
}

?>
