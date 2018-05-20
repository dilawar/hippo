<?php
require_once 'cron_jobs/helper.php';

if( trueOnGivenDayAndTime( 'this friday', '15:00' ) )
{
    /* Send out email to TCM members and faculty about upcoming AWS. */
    $awayFrom = strtotime( 'now' ) - strtotime( '15:00' );
    if( $awayFrom >= -1 && $awayFrom < 15 * 60 )
    {
        error_log( 'Try notifying TCM and PI about AWS' );
        $awses = getUpcomingAWSOnThisMonday( dbDate( 'next monday' ) );
        foreach( $awses as $aws )
        {
            $speaker = loginToText( $aws[ 'speaker' ] );
            $emails = array( );
            foreach( $aws as $key => $value )
                if( preg_match( '/tcm_member_\d|supervisor_\d/', $key ) )
                    if( strlen( $value )  > 1 )
                        $emails[ ] = $value;

            foreach( $emails as $email )
            {
                $recipient = findAnyoneWithEmail( $email );
                $name = arrayToName( $recipient );
                $email = emailFromTemplate( 'NOTIFY_SUPERVISOR_TCM_ABOUT_AWS'
                    , array( 'FACULTY' => $name, 'AWS_SPEAKER' => $speaker
                            , 'AWS_DATE' => humanReadableDate( $aws[ 'date' ] )
                            , 'AWS_DATE_DB' => $aws[ 'date' ]
                        )
                    );
                $subject = 'Annual Work Seminar of ' . $speaker;
                $to = $recipient[ 'email' ];
                $cc = $email[ 'cc' ];
                echo "Sending AWS notification $to </pre>";
                sendHTMLEmail( $email[ 'email_body' ], $subject, $to, $cc );
            }
        }
    }
}

?>
