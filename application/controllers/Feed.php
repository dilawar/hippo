<?php
// require_once BASEPATH.'database.php';

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

function todayTomorrowWeek( $date )
{
    $today = strtotime( 'today' );
    $thisMonday = strtotime( 'Monday this week' ); // If today is monday then return it else last monday.
    if( strtotime( $date ) == $today )
        return "Today | ";
    else if( strtotime( $date ) <= ($today + 24 * 3600 ) )
        return "Tomorrow | ";
    else if( strtotime( $date ) - $thisMonday < 7 * 24 * 3600 )
        return "This Week | ";

    return "";
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


class Feed extends CI_Controller 
{

    public function rss( )
    {

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

        $feed =  '<rss version="2.0"><channel>';
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

            $eventXML .= "<title>".todayTomorrowWeek($e['date']).sanitize($e['title'])
                ." @" .$e['venue'] . "</title>";

            $eventXML .= "<link> https://ncbs.res.in/hippo/events.php?date=" . $e['date'] . 
                        "</link>";

            $eventXML .= "<description>" 
                            .  feedDate( $e[ 'date' ] ) . ", " 
                            . humanReadableTime( $e['start_time' ] ) 
                            .  " to " . humanReadableTime( $e[ 'end_time' ] )
                            . ', ' . venueText( $e[ 'venue' ], false )
                            . "</description>";

            // This is an abuse of RSS protocol.
            $eventXML .= "<pubDate> " . date( 'r', strtotime($e['date'] . ' ' . $e['start_time'] ) ) . "</pubDate>";
            $eventXML .= "</item>";

            $feed .= $eventXML;

        }

        $feed .= '</channel>';
        $feed .= '</rss>';
        file_put_contents( "/tmp/rss.xml", $feed );

        $this->output->set_content_type('application/rss+xml' );
        $this->output->set_output( $feed );
        //$this->load->view( 'rss', array( 'feed' => $feed ), true );
    }
}

?>
