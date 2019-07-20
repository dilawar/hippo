<meta http-equiv="refresh" content="180">
<?php
require_once BASEPATH . 'autoload.php';

// This page displays all events on campus. Select all venues.
$venues = getVenues( $sortby = 'id' );
$venuesDict = array( );
foreach( $venues as $v )
    $venuesDict[$v['id']] = $v;

$venuesIds = array_map( function( $v ) { return $v['id']; }, $venues );

$defaults = array( 'date' => dbDate( 'today' ));

if( array_key_exists( 'date', $_GET ) )
    $defaults[ 'date' ] = $_GET[ 'date' ];

echo '<form action="" method="get" accept-charset="utf-8">
    <table class="info">
    <tr>
        <td> <input  class="datepicker" name="date" value="' .
            $defaults[ 'date' ] . '" /> </td>
            <td> <button name="response"> Select Date</i>
            </button> </td>
    </tr>
    </table>
    </form>
    ';

$calendarDate = humanReadableDate( $defaults[ 'date' ] );

echo "<h2>Confirmed bookings for $calendarDate.</h2>";

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
$maxEventsInLine = 4;

$table = '<table>';
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


    // $width = $eventWidth . "px";
    $table .= "<td class=\"infonote\" style=\"background:$background;\">";

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
    echo '<a style="float:right;lign:left;"  class="clickable" 
        href="' . site_url('user/book') . '">Create New Booking</a>';
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
echo '<table>';
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

        $count += 1;
        if( $count % $maxEventsInLine == 0 )
            echo "</tr><tr>";

        $background = 'lightyellow';
        $width = $eventWidth . "px";
        echo "<td class=\"infonote\" style=\"background:$background;\">";
        echo eventToShortHTML( $ev );
        echo "</td>";
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
    echo "<h2>Pending approval</h2>";
    $count = 0;
    $eventWidth = 150;
    $maxEventsInLine = intval( 900 / $eventWidth );
    echo '<table class="td_as_tile">';
    echo '<tr>';
    foreach( $requests as $ev )
    {
        $count += 1;
        if( $count % $maxEventsInLine == 0 )
            echo "</tr><tr>";

        $background = 'lightyellow';
        $width = $eventWidth . "px";
        echo "<td class=\"infonote\" style=\"background:$background;\">";
        echo requestToShortHTML( $ev );
        echo "</td>";
    }
    echo '</tr>';
    echo '</table>';
    echo '</br>';
}

if( count( $cancelled ) > 0 )
{
    echo '<h2>Cancelled events</h2>';
    $count = 0;
    echo '<table class="td_as_tile">';
    echo '<tr>';
    foreach( $cancelled as $ev )
    {
        $count += 1;
        if( $count % $maxEventsInLine == 0 )
            echo "</tr><tr>";

        $background = 'lightyellow';
        $width = $eventWidth . "px";
        echo "<td class=\"infonote\" style=\"background:$background;\">";
        echo eventToShortHTML( $ev );
        echo "</td>";
    }
    echo '</tr>';
    echo '</table>';
    echo '</br>';
}

echo closePage( );

?>
