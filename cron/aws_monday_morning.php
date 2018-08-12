<?php

function aws_monday_morning_cron()
{
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
}

function notifyAcadOfficeUnassignedSlot( )
{
    $thisMonday = dbDate( 'this monday');
    // Check for next 6 weeks.
    $table = '<table>';
    $table .= "<caption>Unassigned slots</caption>";
    $table .= '<tr><th>Date</th><th>Number of unassigned slots</th></tr>';

    $totalMissing = 0;
    for ($i = 0; $i <= 6; $i++) 
    {
        $weekDate = dbDate( strtotime( "+$i weeks", strtotime($thisMonday)) );
        if( isAWSHoliday( $weekDate ) )
            continue;

        echo " | This week is $weekDate <br /> ";
        $awses = getTentativeAWSSchedule( $weekDate );
        $nMissing = 3 - count($awses);
        if( $nMissing > 0 )
        {
            $table .= "<tr><td> $weekDate </td><td> $nMissing </td></tr>";
            $totalMissing += $nMissing;
        }
    }
    $table .= '</table>';

    if( $totalMissing == 0 )
        return;

    $email = emailFromTemplate( 'NOTIFY_ACADOFFICE_UNASSIGNED_SLOTS', [ 'TABLE' => $table ] );
    if( ! $email )
    {
        echo p("Could not find email template" );
        return;
    }

    sendHTMLEmail( $email['email_body']
        , "Some AWS slots are still not assgined"
        , $email['recipients'], $email['cc']
    );

}

?>
