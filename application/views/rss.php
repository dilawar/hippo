<?php
@include_once 'database.php';

function venueText( $venue )
{
    if( is_string( $venue ) )
        $venue = getVenueById( $venue );

    return $venue['name'] . ' ' . $venue['building_name'] . ', ' . $venue['location'];
}

// RSS feed.
function feedDate( $date )
{
    if( strtotime( $date ) == strtotime( 'today' ) )
        return 'Today';
    else if( strtotime( $date ) <= (strtotime( 'today' ) + 24 * 3600 ) )
        return 'Tomorrow';

    return humanReadableDate( $date );
}

function todayTomorrow( $date, $venue )
{
    if( strtotime( $date ) == strtotime( 'today' ) )
        return "Today, $venue";
    else if( strtotime( $date ) <= (strtotime( 'today' ) + 24 * 3600 ) )
        return "Tomorrow, $venue";

    return "$venue";
}

function sanitize( $title )
{
    $title = preg_replace('/[^A-Za-z0-9\-\ \'\",\<\>]/', '', $title);
    return $title;
}

function cmp( $a, $b )
{
    return strtotime( $a[ 'date' ] ) > strtotime( $b['date'] );
}

/* Courses today */
$todayCourses = array( );
$slots = getTableEntries( 'slots' );
$day = date( 'D', strtotime( 'today' ) );
$today = dbDate( 'today' );

$todaySlots = getSlotsAtThisDay( $day, $slots );
foreach( $todaySlots as $slot )
{
    $slotId = $slot[ 'id' ];
    $runningCourses = getRunningCoursesOnTheseSlotTiles( $today, $slotId );

    foreach( $runningCourses as $cr )
    {
        $ev = array_merge( $cr, $slot );
        $ev[ 'class' ] = 'CLASS';
        $ev[ 'created_by' ] = 'Hippo';
        $ev[ 'timestamp' ] = 'NULL';
        $ev[ 'title' ] = getCourseName( $cr[ 'course_id' ] );
        $ev[ 'date' ] = dbDate( 'today' );
        $todayCourses[ ] = $ev;
    }
}

// Today's event.
$events = getPublicEvents( 'today', 'VALID', 60 );
$events = array_merge( $events, $todayCourses );

// Sort events by date.
usort( $events, "cmp" );

$feed =  '<rss version="2.0"> <channel>';
$feed .= "<title>Events over next 60 days</title>";
$feed .= trim( "<link>" . appURL( ) . "</link>" );
$feed .= "<description>NCB events list</description>";

foreach( $events as $e )
{
    if( $e['date'] == dbDate( 'today' ) )
        if( strtotime( $e['end_time'] ) < strtotime( 'now' ) )
            continue;

    $eventXML = "<item>";

    $date =  feedDate( $e[ 'date' ] );

    if( strlen( $e[ 'title' ] ) < 2 )
        continue;

    $eventXML .= "<title>" . todayTomorrow( $e['date'], $e['venue'] ) . ' : ' . 
                    sanitize( $e[ 'title'] ) . "</title>";

    $eventXML .= "<link> https://ncbs.res.in/hippo/events.php?date=" . $e['date'] . 
                "</link>";

    $eventXML .= "<description>" 
                    .  feedDate( $e[ 'date' ] ) . ", " 
                    . humanReadableTime( $e['start_time' ] ) 
                    .  " to " . humanReadableTime( $e[ 'end_time' ] )
                    . ', ' . venueText( $e[ 'venue' ], false )
                    . "</description>";

    $eventXML .= "<pubDate> " . date( 'r', strtotime($e['date'] . ' ' . $e['start_time'] ) ) . "</pubDate>";
    $eventXML .= "</item>";

    $feed .= $eventXML;

}

$feed .= '</channel>';
$feed .= '</rss>';

// Display it now.
header( 'Content-Type: application/rss+xml' );
echo '<?xml version="1.0" encoding="UTF-8" ?>';
echo $feed;

?>

