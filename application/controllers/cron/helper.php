<?php

function trueOnGivenDayAndTime( $day, $time )
{
    $now = strtotime( 'today' );
    if( $now != strtotime( $day ) )
        return false;

    $away = strtotime( 'now' ) - strtotime( "$time" );
    if( $away >= -1 && $away < 15 * 60 )
        return true;

    return false;
}

function isNowEqualsGivenDayAndTime( $day, $time )
{
    return trueOnGivenDayAndTime( $day, $time );

}

?>
