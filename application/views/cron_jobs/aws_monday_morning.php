<?php
require_once 'cron_jobs/helper.php';

if( trueOnGivenDayAndTime( 'this monday', '10:00 am' ) )
{
    error_log( "Monday 10amm. Notify about AWS" );
    echo printInfo( "Today is Monday. Send out emails for AWS" );
    $thisMonday = dbDate( strtotime( 'this monday' ) );
    $subject = 'Today\'s AWS (' . humanReadableDate( $thisMonday) . ') by ';
    $res = generateAWSEmail( $thisMonday );
    $to = 'academic@lists.ncbs.res.in';

    if( $res[ 'speakers' ] )
    {
        echo printInfo( "Sending mail about today's AWS" );
        $subject .= implode( ', ', $res[ 'speakers'] );

        $mail = $res[ 'email' ]['email_body'];

        $pdffile = $res[ 'pdffile' ];
        $cclist = $res[ 'email']['cc' ];
        $to = $res[ 'email']['recipients'];

        error_log( "Sending to $to, $cclist with subject $subject" );
        echo( "Sending to $to, $cclist with subject $subject" );
        $ret = sendHTMLEmail( $mail, $subject, $to, $cclist, $pdffile );
        ob_flush( );
    }
    else
    {
        // There is no AWS this monday.
        $subject = 'No Annual Work Seminar today : ' .
            humanReadableDate( $nextMonday );
        $mail = $res[ 'email' ]['email_body'];
        sendHTMLEmail( $mail, $subject, $to, $res['email']['cc'] );
    }
}

?>
