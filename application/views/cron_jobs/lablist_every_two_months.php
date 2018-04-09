<?php

require_once 'cron_jobs/helper.php';

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Every two months, first Saturday, 10a.m.; notify each faculty
    * about their AWS candidates.
 */
/* ----------------------------------------------------------------------------*/
$intMonth = intval( date( 'm', strtotime( 'today' ) ) );
echo $intMonth;

// Nothing to do on odd months.
if( $intMonth % 2 == 0 )
{
    $year = getCurrentYear( );
    $month = date( 'M', strtotime( 'today' ));
    if( trueOnGivenDayAndTime( 'first Saturday', '10:00 am' ) )
    {
        error_log( "First saturday of even month. Update PIs about AWS list" );
        $speakers = getAWSSpeakers( );
        $facultyMap = array( );
        foreach( $speakers as $speaker )
        {
            $login = $speaker[ 'login' ];
            $pi = getPIOrHost( $login );
            if( $pi )
                $facultyMap[ $pi ] =  __get__($facultyMap, $pi, '' ) . ',' . $login;
        }

        // Now print the names.
        foreach( $facultyMap as $fac => $speakers )
        {
            if( count( $speakers ) < 1 )
                continue;

            $table = '<table border="1">';
            foreach( explode( ",", $speakers ) as $login )
            {
                if( ! trim( $login ) )
                    continue;

                $speaker = loginToHTML( $login, true );
                $table .= " <tr> <td>$speaker</td> </tr>";
            }
            $table .= "</table>";

            $faculty = arrayToName( findAnyoneWithEmail( $fac ) );
            $email = emailFromTemplate( 'NOTIFY_SUPERVISOR_AWS_CANDIDATES'
                , array( 'FACULTY' => $faculty, 'LIST_OF_AWS_SPEAKERS' => $table
                , 'TIMESTAMP' => dbDateTime( 'now' ) )
            );

            $body = $email[ 'email_body' ];
            $cc = $email[ 'cc' ];
            $subject = 'List of AWS speakers from your lab';
            $to = $fac;
            sendHTMLEmail( $body, $subject, $to, $cc );
        }
    }
}

?>
