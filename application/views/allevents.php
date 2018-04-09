<meta http-equiv="refresh" content="180">
<?php

include_once 'header.php';
include_once 'methods.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

// Show it only if accessed from intranet or user have logged in.
if( ! (isIntranet( ) || isAuthenticated( ) ) )
{
    echo printWarning( "To access this page, either use Intranet or log-in first" );
    echo closePage( );
    exit;
}

if( isAuthenticated( ) )
{
}

// This page displays all events on campus. Select all venues.
$venues = getVenues( $sortby = 'id' );
$venuesDict = array( );
foreach( $venues as $v )
    $venuesDict[ $v[ 'id' ] ] = $v;

$venuesIds = array_map( function( $v ) { return $v['id']; }, $venues );

$defaults = array( 'date' => dbDate( 'today' ));

if( array_key_exists( 'date', $_GET ) )
    $defaults[ 'date' ] = $_GET[ 'date' ];

echo '<form action="" method="get" accept-charset="utf-8">
    <table class="info">
    <tr>
        <td> <input  class="datepicker" name="date" value="' .
            $defaults[ 'date' ] . '" /> </td>
            <td> <button name="response">' . $symbScan . '</button> </td>
    </tr>
    </table>
    </form>';

$calendarDate = humanReadableDate( $defaults[ 'date' ] );
echo "<h2>Confirmed bookings on $calendarDate </h1>";

$events = getEventsOn( $defaults['date' ] );
$cancelled = getEventsOn( $defaults[ 'date' ], 'CANCELLED' );



/*
 * ******************************************************************************
 * Get requests are well.
 * *****************************************************************************
 */
$requests = getPendingRequestsOnThisDay( $defaults[ 'date' ] );
$count = 0;
$eventWidth = 200;
$maxEventsInLine = intval( 800 / $eventWidth );

$table = '<table width="250px">';
$table .= '<tr>';
foreach( $events as $ev )
{
    if( $count % $maxEventsInLine == 0 )
        $table .= "</tr><tr>";

    $now = strtotime( 'now' );
    $eventEnd = $ev[ 'date' ] . ' ' . $ev[ 'end_time' ];
    $eventStart = $ev[ 'date' ] . ' ' . $ev[ 'start_time' ];
    $eventEnd = strtotime( $eventEnd );
    $eventStart = strtotime( $eventStart );

    $background = 'lightyellow';
    if( isPublicEvent( $ev ) )
        $background = "yellow";


    $width = $eventWidth . "px";
    $table .= "<td style=\"background:$background;min-width:$width;border:1px dotted;\">";

    // Blink if the event is currently happening.
    if( $eventEnd >= $now && $eventStart <= $now )
        $table .= '<i class="fa fa-circle-o-notch fa-spin fa-2x"></i>' . eventToShortHTML( $ev );
    elseif( $eventEnd <= $now )    // This one is over.
        $table .= '<font color="gray">'
                        . eventToShortHTML( $ev ) . '</font>';
    else
        $table .= eventToShortHTML( $ev );

    $table .= "</td>";
    $count += 1;
}
$table .= '</tr>';
$table .= '</table>';
echo $table;
echo '<br />';

if( isAuthenticated( ) )
{
    echo '<a style="float:left;padding-left:500px"  class="clickable" href="quickbook.php">Create New Booking</a>';
    echo '<br />';
}

echo '<br />';

echo '<h2>Classes</h2>';
/*******************************************************************************
 * Get running courses.
 **/
$slots = getTableEntries( 'slots' );
$day = date( 'D', strtotime( $defaults[ 'date' ] ) );
$todaySlots = getSlotsAtThisDay( $day, $slots );

$count = 0;
$eventWidth = 150;
$maxEventsInLine = intval( 900 / $eventWidth );
echo '<table width="250px">';
echo '<tr>';
foreach( $todaySlots as $slot )
{
    $slotId = $slot[ 'id' ];
    $runningCourses = getRunningCoursesOnTheseSlotTiles( $defaults['date'], $slotId );
    foreach( $runningCourses as $cr )
    {
        $ev = array_merge( $cr, $slot );
        $ev[ 'class' ] = 'CLASS';
        $ev[ 'created_by' ] = 'Hippo';
        $ev[ 'timestamp' ] = 'NULL';
        $ev[ 'title' ] = getCourseName( $cr[ 'course_id' ] );

        if( $count % $maxEventsInLine == 0 )
            echo "</tr><tr>";

        $background = 'lightyellow';
        $width = $eventWidth . "px";
        echo "<td style=\"background:$background;min-width:$width;border:1px dotted;\">";
        echo eventToShortHTML( $ev );
        echo "</td>";
        $count += 1;
    }
}
echo '</tr>';
echo '</table>';
echo '</br>';

/*******************************************************************************
 * Pending requests
 */
if( count( $requests ) > 0 )
{
    echo "<h2>Pending approval </h2>";
    $count = 0;
    $eventWidth = 150;
    $maxEventsInLine = intval( 900 / $eventWidth );
    echo '<table width="250px">';
    echo '<tr>';
    foreach( $requests as $ev )
    {
        if( $count % $maxEventsInLine == 0 )
            echo "</tr><tr>";

        $background = 'lightyellow';
        $width = $eventWidth . "px";
        echo "<td style=\"background:$background;min-width:$width;border:1px dotted;\">";
        echo requestToShortHTML( $ev );
        echo "</td>";
        $count += 1;
    }
    echo '</tr>';
    echo '</table>';
    echo '</br>';
}

if( count( $cancelled ) > 0 )
{
    echo '<h2>Cancelled events</h2>';
    $count = 0;
    $eventWidth = 150;
    $maxEventsInLine = intval( 900 / $eventWidth );
    echo '<table width="250px">';
    echo '<tr>';
    foreach( $cancelled as $ev )
    {
        if( $count % $maxEventsInLine == 0 )
            echo "</tr><tr>";

        $background = 'lightyellow';
        $width = $eventWidth . "px";
        echo "<td style=\"background:$background;min-width:$width;border:1px dotted;\">";
        echo eventToShortHTML( $ev );
        echo "</td>";
        $count += 1;
    }
    echo '</tr>';
    echo '</table>';
    echo '</br>';
}

echo closePage( );

?>
