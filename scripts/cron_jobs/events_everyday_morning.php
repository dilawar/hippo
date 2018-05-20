<?php

require_once 'cron_jobs/helper.php';

/*
 * Task 2. Send today's event every day at 8am.
 */
if( trueOnGivenDayAndTime( 'today', '8:00' ) )
{
    $today = dbDate( 'today' );
    error_log( "8am. Event for today" );
    $todaysEvents = getPublicEventsOnThisDay( $today );
    $nTalks = 0;
    if( count( $todaysEvents ) > 0 )
    {
        foreach( $todaysEvents as $event )
        {
            $external_id = $event[ 'external_id' ];

            // External id has the format TALKS.TALK_ID
            $talkid = explode( '.', $external_id );
            if( count( $talkid ) == 2 )
            {
                $data = array( 'id' => $talkid[1] );
                $talk = getTableEntry( 'talks', 'id', $data );
                if( $talk )
                {
                    $html = talkToHTML( $talk );
                    $nTalks += 1;

                    // Now prepare an email to sent to mailing list.
                    $macros = array( 'EMAIL_BODY' => $html, 'DATE' => $today );
                    $subject = "Today (" . humanReadableShortDate( $today ) . "): " ;
                    $subject .= talkToShortEventTitle( $talk );

                    $template = emailFromTemplate( 'todays_events', $macros );

                    if( array_key_exists( 'email_body', $template ) && $template[ 'email_body' ] )
                    {
                        // Send it out.
                        $to = $template[ 'recipients' ];
                        $ccs = $template[ 'cc' ];
                        $msg = $template[ 'email_body' ];
                        $attachment = '';
                        $res = sendHTMLEmail( $msg, $subject, $to, $ccs, $attachment );
                        if( $res )
                            echo printInfo( "Email sent successfully" );
                    }
                }
            }
        }
        ob_flush( );
    }
    else
        error_log( "No event found on day " . $today );
}


?>
