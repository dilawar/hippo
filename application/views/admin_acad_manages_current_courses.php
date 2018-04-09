<?php

include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN' ) );

echo userHTML( );

$sem = getCurrentSemester( );
$year = getCurrentYear( );

$action = 'Add';

// Get the list of all courses. Admin will be asked to insert a course into
// database.
$allCourses = getTableEntries( 'courses_metadata', 'name' );
$coursesId = array_map( function( $x ) { return $x['id']; }, $allCourses );
asort( $coursesId );

$slotMap = getSlotMap( );

$coursesMap = array( );
foreach( $allCourses as $c )
    $coursesMap[ $c[ 'id' ] ] = $c[ 'name' ];

$courseIdsSelect = arrayToSelectList( 'course_id', $coursesId, $coursesMap );
$venues = getTableEntries( 'venues', '', "type='LECTURE HALL'" );
$venueSelect = venuesToHTMLSelect( $venues );
$slotSelect = arrayToSelectList( 'slot', array_keys($slotMap), $slotMap );


// Running course for this semester.
$nextSem = getNextSemester( );
$runningCourses = getSemesterCourses( $year, $sem );
$nextSemCourses = getSemesterCourses( $nextSem[ 'year' ], $nextSem[ 'semester' ] );

$runningCourses = array_merge( $runningCourses, $nextSemCourses );

// Auto-complete for JS.
$runningCourseMapForAutoCompl = [ ];
foreach( $runningCourses as $x )
    $runningCourseMapForAutoCompl[ $x['id'] . ': '
        . getCourseName( $x[ 'course_id' ] ) ] = $x['id'];

// Array to hold runnig course.
$default = array(
    'venue' => $venueSelect
    , 'semester' => $sem
    , 'year' => $year
    , 'course_id' => $courseIdsSelect
);

// running course returned from autocomplete has extra information. Use the map
// to add another parameter in $_POST 'running_course_id' which is used to get
// the real course id.
if( $_POST && array_key_exists( 'running_course', $_POST ) )
{
    $_POST[ 'running_course_id' ] = $runningCourseMapForAutoCompl[ $_POST[ 'running_course' ] ];
    $runningCourse = getTableEntry(
        'courses', 'id'
        , array( 'id' =>  $_POST[ 'running_course_id' ] )
    );
    if( $runningCourse )
        $default = array_merge( $default, $runningCourse );
    $action = 'Edit';
}

$runningCoursesHTML = "<h1>Running courses in $sem, $year</h1>";
$runningCoursesHTML .= '<table class="info sortable">';
$tobefilterd = 'id,semester,year';
$runningCoursesHTML .= arrayHeaderRow( $runningCourses[0], 'info', $tobefilterd );
foreach( $runningCourses as $course )
{
    $cname = getCourseName( $course[ 'course_id'] );
    $course[ 'course_id' ] = '<strong>'. $course['course_id'] . '</strong><br> ' . $cname;

    if( isCourseActive( $course ) )
        $course[ 'course_id' ] = "<blink> $symbBell </blink>" . $course[ 'course_id' ];

    $runningCoursesHTML .= '<tr>';
    $runningCoursesHTML .= arrayToRowHTML( $course, 'aws', $tobefilterd, true, false );
    $runningCoursesHTML .=  '<td>
        <form action="#" method="post" accept-charset="utf-8">
        <button type="submit" value="Edit">Edit</button>
        <input type="hidden" name="running_course" value="' .
            $course[ 'id' ] . ': ' . $cname .  '" />
        </form>
        </td>';
    $runningCoursesHTML .= '</tr>';
}
$runningCoursesHTML .= '</table>';
echo $runningCoursesHTML;


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Ask user which course to edit/update.
 */
/* ----------------------------------------------------------------------------*/
echo "</br>";

if( $action == 'Add' )
{
    echo "<h1>Add a course to running courses list</h1>";
}
else
    echo "<h1>Edit following course </h1>";

echo '<form method="post" action="admin_acad_manages_current_courses_action.php">';
$default[ 'slot' ] = $slotSelect;
$default[ 'venue' ] = $venueSelect;
$default[ 'semester' ] = $sem;

if( __get__( $_POST, 'running_course', '') )
{
    $action = 'Update';
    $course = getTableEntry( 'courses', 'id', array( 'id' => $_POST[ 'running_course_id' ]) );
    $default[ 'semester' ] = $sem;

    // Select the already assigned venue.
    $venueSelect = venuesToHTMLSelect( $venues, false, 'venue', array( $course[ 'venue' ] ) );
    $default[ 'venue' ] = $venueSelect;

    // We show all venues and slots because some combination of (venue,slot) may
    // be available. When updating the course we check for it. It can be fixed
    // by adding a javascript but for now lets admin feel the pain.
    $slotSelect = arrayToSelectList( 'slot'
            , array_keys($slotMap), $slotMap
            , false, $course['slot']
        );
    $default[ 'slot' ] = $slotSelect;
}
else
    $action = 'Add';

echo dbTableToHTMLTable( 'courses'
    , $default
    , 'start_date,end_date,slot,venue,note,url,ignore_tiles,course_id', $action, ''
    );

/* If we are updating, we might also like to remove the entry. This button also
 * appears. Admin can remove the course schedule.
 */
if( $action == 'Update' )
    echo '<button name="response" onclick="AreYouSure(this)"
        title="Remove this course from running courses."
        >' .
            $symbDelete . '</button>';

echo '</form>';


echo "<br/><br/>";
echo goBackToPageLink( 'admin_acad.php', 'Go back' );

?>
