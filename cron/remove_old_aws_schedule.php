<?php

function remove_old_aws_scheduling_prefs()
{
    if( trueOnGivenDayAndTime( 'this monday', '9:00' ) )
    {
        // Also cleanup the AWS preferences.
        $today = dbDate( 'today' );
        echo p("Cleaning up old requests $today." );
        $prefs = getTableEntries( 'aws_scheduling_request'
            , 'id'
            , "first_preference < '$today' OR 'second_preference' "
        );

        echo "Found total " . count( $prefs );

        foreach( $prefs as $p )
        {
            // Since we don't have expired field.
            $p[ 'status' ] = 'CANCELLED';
            updateTable( 'aws_scheduling_request', 'id', 'status', $p );
        }
    }
}

?>
