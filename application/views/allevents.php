<meta http-equiv="refresh" content="180">
<?php
require_once BASEPATH . 'autoload.php';

// This page displays all events on campus. Select all venues.
$venuesDict = array( );
foreach ($venues as $v) {
    $venuesDict[$v['id']] = $v;
}

$venuesIds = array_map(function ($v) {
    return $v['id'];
}, $venues);

$defaults = array('date' => dbDate($date));

echo '
<div class="float-right">
<form action="'. site_url("info/booking"). '" method="post" accept-charset="utf-8">
     <input  class="datepicker" name="date" value="'. $date.'" />
            <button class="btn btn-primary" name="response"> Select Date </button>
    </form>
</div>
<br />
<br />
';

$calendarDate = humanReadableDate($date);

echo heading("Confirmed bookings on $calendarDate", 3);

/*
 * REQUESTS is sent from controller.
 */
$count = 0;
$eventWidth = 200;
$maxEventsInLine = 4;

$table = '<table class="table table-responsive">';
$table .= '<tr>';
foreach ($events as $ev) {
    if ($count % $maxEventsInLine == 0) {
        $table .= "</tr><tr>";
    }

    $now = strtotime('now');
    $eventEnd = $ev[ 'date' ] . ' ' . $ev[ 'end_time' ];
    $eventStart = $ev[ 'date' ] . ' ' . $ev[ 'start_time' ];
    $eventEnd = strtotime($eventEnd);
    $eventStart = strtotime($eventStart);

    $background = 'lightyellow';
    if (isPublicEvent($ev)) {
        $background = "yellow";
    }


    // $width = $eventWidth . "px";
    $table .= "<td class=\"infonote\" style=\"background:$background;\">";

    // Blink if the event is currently happening.
    if ($eventEnd >= $now && $eventStart <= $now) {
        $table .= '<i class="fa fa-circle-o-notch fa-spin fa-2x"></i>' . eventToShortHTML($ev);
    } elseif ($eventEnd <= $now) {    // This one is over.
        $table .= '<font color="gray">'
                        . eventToShortHTML($ev) . '</font>';
    } else {
        $table .= eventToShortHTML($ev);
    }

    $table .= "</td>";
    $count += 1;
}
$table .= '</tr>';
$table .= '</table>';
echo $table;
echo '<br />';

if (isAuthenticated()) {
    echo '<a style="float:right;lign:left;"  class="clickable" 
        href="' . site_url('user/book') . '">Create New Booking</a>';
    echo '<br />';
}

echo '<br />';

/*******************************************************************************
 * Get running courses.
 **/
$count = 0;
$eventWidth = 150;
if (count($slots) > 0) {
    echo heading("Classes", 3);
}

echo '<table class="table table-responsive">';
echo '<tr>';
foreach ($slots as $slot) {
    $slotId = $slot[ 'id' ];
    $runningCourses = getRunningCoursesOnTheseSlotTiles($defaults['date'], $slotId);
    foreach ($runningCourses as $cr) {
        $ev = array_merge($cr, $slot);
        $ev[ 'class' ] = 'CLASS';
        $ev[ 'created_by' ] = 'Hippo';
        $ev[ 'timestamp' ] = 'NULL';
        $ev[ 'title' ] = getCourseName($cr[ 'course_id' ]);

        $count += 1;
        if ($count % $maxEventsInLine == 0) {
            echo "</tr><tr>";
        }

        $background = 'lightyellow';
        $width = $eventWidth . "px";
        echo "<td class=\"infonote\" style=\"background:$background;\">";
        echo eventToShortHTML($ev);
        echo "</td>";
    }
}
echo '</tr>';
echo '</table>';
echo '</br>';

/*******************************************************************************
 * Pending requests
 */
if (count($requests) > 0) {
    echo "<h2>Pending approval</h2>";
    $count = 0;
    $eventWidth = 150;
    $maxEventsInLine = intval(900 / $eventWidth);
    echo '<table class="td_as_tile">';
    echo '<tr>';
    foreach ($requests as $ev) {
        $count += 1;
        if ($count % $maxEventsInLine == 0) {
            echo "</tr><tr>";
        }

        $background = 'lightyellow';
        $width = $eventWidth . "px";
        echo "<td class=\"infonote\" style=\"background:$background;\">";
        echo requestToShortHTML($ev);
        echo "</td>";
    }
    echo '</tr>';
    echo '</table>';
    echo '</br>';
}

if (count($cancelled) > 0) {
    echo '<h2>Cancelled events</h2>';
    $count = 0;
    echo '<table class="td_as_tile">';
    echo '<tr>';
    foreach ($cancelled as $ev) {
        $count += 1;
        if ($count % $maxEventsInLine == 0) {
            echo "</tr><tr>";
        }

        $background = 'lightyellow';
        $width = $eventWidth . "px";
        echo "<td class=\"infonote\" style=\"background:$background;\">";
        echo eventToShortHTML($ev);
        echo "</td>";
    }
    echo '</tr>';
    echo '</table>';
    echo '</br>';
}

echo closePage();

?>
