<?php
require_once BASEPATH.'autoload.php';
echo userHTML( );

// In config/constants.php
global $symbDelete;
global $symbBell;


// Prefer $_GET over controller.
$year = $year ?? getCurrentYear();
$year = $_GET['year'] ?? $year;
$semester = $semester ?? getCurrentSemester();
$semester = $_GET['semester'] ?? $semester;

echo '<div style="display:flex; justify-content: flex-end">';
echo selectYearSemesterForm($year, $semester, '/adminacad/courses');
echo '</div>';

$action = 'Add';

// Get the list of all courses. Admin will be asked to insert a course into
// database.
$allCourses = getTableEntries( 'courses_metadata', 'name' );
$coursesId = array_map( function( $x ) { return $x['id']; }, $allCourses );
// asort( $coursesId );

$slotMap = getSlotMap( );

$coursesMap = array( );
foreach( $allCourses as $c )
    $coursesMap[ $c[ 'id' ] ] = $c[ 'name' ];

$courseIdsSelect = arrayToSelectList( 'course_id', $coursesId, $coursesMap );
$venues = getTableEntries( 'venues', '', "type='LECTURE HALL'" );
$venueSelect = venuesToHTMLSelect( $venues );
$slotSelect = arrayToSelectList( 'slot', array_keys($slotMap), $slotMap );

// Running course for the next semester which might overlap with this one. 
$runningCourses = getSemesterCourses( $year, $semester);

// This is bit wiered but leave it like this. Sometimes user might select a
// semester which is already a next semester (on new year eve). This is a bit
// odd here but I forgot why I am doing it here. If it is not hurting anyone,
// leave it as it is.
$nextSem = getNextSemester( );
$nextSemCourses = [];
if($nextSem['year'] != $year && $nextSem['semester'] != $semester)
    $nextSemCourses = getSemesterCourses( $nextSem[ 'year' ], $nextSem[ 'semester' ] );

$runningCoursesWithoutInsts = array_merge( $runningCourses, $nextSemCourses );

$runningCourses = array( );
// Attach instructors to the running courses as well.
foreach( $runningCoursesWithoutInsts as $course )
{
   $cid = $course[ 'course_id'];
   $instHTML = getCourseInstructors( $cid )['html'];
   $course['instructors'] = $instHTML;
   $runningCourses[] = $course;
}

// Auto-complete for JS.
$runningCourseMapForAutoCompl = [ ];
foreach( $runningCourses as $x )
    $runningCourseMapForAutoCompl[ $x['id'] . ': '
        . getCourseName( $x[ 'course_id' ] ) ] = $x['id'];

// Array to hold runnig course.
$default = array(
    'venue' => $venueSelect
    , 'semester' => $semester
    , 'year' => $year
    , 'course_id' => $courseIdsSelect
);

// running course returned from autocomplete has extra information. Use the map
// to add another parameter in $_POST 'running_course_id' which is used to get
// the real course id.
if( $_POST && array_key_exists( 'running_course', $_POST ) )
{
    $_POST[ 'running_course_id' ] = $runningCourseMapForAutoCompl[ $_POST[ 'running_course' ] ];

    $runningCourse = getTableEntry( 'courses', 'id'
        , array( 'id' =>  $_POST[ 'running_course_id' ] )
    );


    if( $runningCourse )
        $default = array_merge( $default, $runningCourse );
    $action = 'Edit';
}

// echo p( "To edit details of a course " .
    // goBackToPageLinkInline( "adminacad/allcourses" , "click here." )
// );

$runningCoursesHTML  = "<h1>Courses running in $year/$semester </h1>";
$runningCoursesHTML .= '<table class="sortable table table-striped">';
if( count($runningCourses) > 0 )
{
    $tobefilterd = 'id,year,semester';
    $runningCoursesHTML .= arrayHeaderRow( $runningCourses[0], 'info', $tobefilterd );
    foreach( $runningCourses as $course )
    {
        $courseID = $course[ 'course_id'];
        // $course['year/semester'] = $course['year'] . '/' . $course['semester'];

        $cname = getCourseName( $course[ 'course_id'] );

        $course['course_id'] = '<strong>'
            . '<a target="_blank" href="' . site_url('adminacad/allcourses?id='.$courseID.'#editcourse') . '">' 
            . '<i class="fa fa-pencil"></i> </a>' 
            . $course['course_id'] 
            . '</strong><br> ' . $cname;

        if(isCourseActive($course))
            $course['course_id'] .= "<blink>$symbBell</blink>";

        $runningCoursesHTML .= '<tr>';
        $runningCoursesHTML .= arrayToRowHTML($course, 'aws', $tobefilterd, true, false);
        $runningCoursesHTML .=  '<td>
            <form action="#edit_current_course" method="post">
                <button class="btn btn-secondary" 
                    type="submit" value="Edit">Edit Course</button>
                <input type="hidden" name="running_course" value="' 
                    .  $course[ 'id' ] . ': ' . $cname .  '" />
            </form></td>';
        $runningCoursesHTML .= '</tr>';
    }
    $runningCoursesHTML .= '</table>';
    echo '<div style="font-size:small">';
    echo $runningCoursesHTML;
    echo '</div>';
}
else
    echo p( "No courses found for this semester: $semester/$year." );


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Ask user which course to edit/update.
 */
/* ----------------------------------------------------------------------------*/
echo "</br>";
if( $action == 'Add' )
{
    echo "<h1>Add a course to running courses list</h1>";
    echo p( "<i class='fa fa-info-circle fa-2x'></i>
        If a course is not found drop-down menu, " 
        . goBackToPageLinkInline( "adminacad/allcourses" , "click here" ) 
        . " to add a new course. You will have to come back here again." 
        );
}
else
    echo "<h1 id='edit_current_course'>Edit following course</h1>";

echo '<form method="post" action="'.site_url('adminacad/courses_action') .'">';
$default[ 'slot' ] = $slotSelect;
$default[ 'venue' ] = $venueSelect;
$default[ 'semester' ] = $semester;
$default[ 'is_audit_allowed' ] = "YES";
$default[ 'max_registration' ] = -1;

if( __get__( $_POST, 'running_course', '') )
{
    $action = 'Update';
    $course = getTableEntry( 'courses', 'id', array( 'id' => $_POST[ 'running_course_id' ]) );
    $default[ 'semester' ] = $semester;

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
    $default['max_registration'] = $course['max_registration'];
    $default['is_audit_allowed'] = $course['is_audit_allowed'];
    $default['allow_deregistration_until'] = dbDate( strtotime($course['start_date'])+30*86400);
}
else
    $action = 'Add';

$editable = 'start_date,end_date,slot,venue,note,url,max_registration';
$editable .= ',allow_deregistration_until,is_audit_allowed,ignore_tiles,course_id';
echo dbTableToHTMLTable('courses', $default, $editable, $action, '');

/* If we are updating, we might also like to remove the entry. This button also
 * appears. Admin can remove the course schedule.
 */
if( $action == 'Update' )
    echo '<button name="response" onclick="AreYouSure(this)"
        title="Remove this course from running courses."
        >' .
            $symbDelete . '</button>';

echo '</form>';

echo "<br/>";
echo goBackToPageLink( 'adminacad/home', 'Go back' );

?>
