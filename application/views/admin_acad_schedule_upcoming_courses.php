<?php
include_once 'header.php';
include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN' ) );

include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

echo userHTML( );
$nextSem = getNextSemester( );
$year = $nextSem[ 'year' ];
$sem = $nextSem[ 'semester' ];

$upcomingCourses = getSemesterCourses( $year, $sem );
$runningCourseMapForAutoCompl = [];
foreach( $upcomingCourses as $x )
{
    $cid = $x['id'] . ': ' . getCourseName( $x['course_id'] );
    $runningCourseMapForAutoCompl[ $cid ] = $x['id'];
}
?>

<script type="text/javascript" charset="utf-8">

// Autocomplete running course. Append course name for better searching.
// Put the code in hidden input id.
$( function() {
    var courses = <?php echo json_encode( array_keys($runningCourseMapForAutoCompl) ); ?>;
    $( "#upcoming_course_schedule_course_id" ).autocomplete({ source : courses });
    $( "#upcoming_course_schedule_course_id" ).attr( "placeholder", "Type course code/name" );
    });
</script>

<?php

echo slotTable( );

echo "<h2>Add preferences for upcoming courses in $sem, $year </h2>";
$editable = 'course_id,slot,venue,weight';

$slotMap = getSlotMap( );
$lhs = getVenuesByType( 'LECTURE HALL' );

$venueSelectList = venuesToHTMLSelect( $lhs );
$slotSelectList = arrayToSelectList( 'slot', array_keys( $slotMap ) );

$action = 'Add';
$default = array( 'slot' => $slotSelectList, 'venue' => $venueSelectList );

// Form: Add new scheduling entry.
$form = '<form action="admin_acad_schedule_upcoming_courses_action.php"
            method="post" accept-charset="utf-8">';
$form .= dbTableToHTMLTable( 'upcoming_course_schedule'
            , $default, $editable, $action
        );
$form .= '</form>';
echo $form;

// Print the table of entries.
$tofilter = 'id,status';
$entries = getTableEntries( 'upcoming_course_schedule' );

// SORT the array for easy viewing.
usort( $entries
    , function( $a, $b) { return $a['course_id'] > $b['course_id']; }
);

if( count( $entries ) > 0 )
{
    echo '<h2>Current list of preferences</h2>';
    $table = '<table class="info">';
    $table .= arrayHeaderRow( $entries[0], 'info', $tofilter );
    foreach( $entries as $entry )
    {
        $cname = getCourseName( $entry[ 'course_id' ] );
        $entry['comment'] .= '<br>' . $cname;
        $table .= '<form action="admin_acad_schedule_upcoming_courses_action.php"
            method="post" accept-charset="utf-8">';
        $table .= '<tr>' . arrayToRowHTML( $entry, 'info', $tofilter, false, false );
        $table .= '<td><button name="response" value="Delete">Delete</button></td>';
        $table .= '<input type="hidden" name="id" value="' . $entry['id'] . '">';
        $table .= '</tr>';
        $table .= '</form>';
    }
    $table .= '</table>';
    echo $table;
}

echo '<form method="post" action="admin_acad_schedule_upcoming_courses_action.php">';
echo '<button name="response" value="schedule_courses">Compute Schedule</button>';
echo '</form>';

echo '<h1>Computed schedule</h1>';

$rows = getTableEntries( 'upcoming_course_schedule', '', "status='VALID'" );

$table = '<table class="info">';
$table .= '<tr><th>Slot</th><th>Venue</th><th>Course</th></tr>';
foreach( $rows as $row )
{
    if( ! trim($row['alloted_slot']) || ! trim($row[ 'alloted_venue' ]) )
        continue;

    $rowHTML = '<td>' . $row[ 'alloted_slot' ] . '</td><td>'
                . $row[ 'alloted_venue' ]
                . '</td><td>' . getCourseName( $row['course_id'] ) . '</td>';
    $table .= "<tr> $rowHTML </tr>";
}
$table .= '</table>';
echo $table;

?>
