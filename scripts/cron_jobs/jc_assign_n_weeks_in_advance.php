<?php

require_once 'cron_jobs/helper.php';
require_once 'actions/jc_admin.php';


////////////////////////////////////////////////////////////////////////////
// JOURNAL CLUB

echo "<p>Scheduling JCs </p>";

$jcs = getActiveJCs( );
foreach( $jcs as $jc )
{
    $jcID = $jc[ 'id' ];

    $jcDay =  'This ' . $jc[ 'day' ];

    // Three weeks from JC day.
    $nWeeksFromjcDay = strtotime($jcDay) + 3 * 7 * 24 * 3600; 

    // At JC time.
    if( isNowEqualsGivenDayAndTime( $jcDay, dbTime( $jc['time'] ) ) )
    {
        echo printInfo( "JcID is $jcID with $jcDay." );
        error_log( "Scheduling for $jcID" );
        echo printInfo( "Scheduling for $jcID" );

        // check if there is anyone scheduled on nWeeksFromjcDay
        $schedule = getJCPresentations( $jcID, dbDate( $nWeeksFromjcDay ), '' );
        if( $schedule && count( $schedule ) > 0 )
        {
            error_log( "$jcID alreay have a schedule on " . humanReadableDate( $nWeeksFromjcDay ) );
            echo printInfo( "$jcID already have a schedule on " . humanReadableDate( $nWeeksFromjcDay) );
            continue;
        }
        else
        {
            error_log( "Finding speaker" );
            // Else find someone and assign.
            $presenter = pickPresenter( $jcID );
            if( $presenter )
            {
                $res = fixJCSchedule( $presenter
                        , array( 'date' => dbDate( $nWeeksFromjcDay ), 'jc_id' => $jcID )
                    );

                if( $res )
                    echo printInfo( "Success! " );
            }
            else
            {
                echo printWarning( "Failed to find a presenter" );
            }
        }
    }
}

?>
