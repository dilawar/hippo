<?php
require_once BASEPATH.'autoload.php';
echo userHTML( );

$ref = 'adminacad';
if(isset($controller))
    $ref = $controller;

// If controller has set the year and semester and _GET also
// have year and semester then _GET gets the priority.
$yearGet = __get__( $_GET, 'year', '' );
$semesterGet = __get__( $_GET, 'semester', '' );

// Otherwise use the controller version. This logic is bit contrived but one can
// follow it.
if( isset( $_GET['year'] ) )
    $year = $_GET['year'];
else if( ! isset($year) )
    $year = getCurrentYear();

if( isset( $_GET['semester'] ) )
    $semester = $_GET['semester'];
else if( ! isset($semester) )
    $semester = getCurrentSemester();

$springChecked = ''; $autumnChecked = '';
if( $semester == 'SPRING' )
{
    $springChecked = 'checked';
    $autumnChecked = '';
}
else
{
    $autumnChecked = 'checked';
    $springChecked = '';
}

echo '<div class="important">';
echo "<strong>Selected semester $semester/$year.</strong>";
echo selectYearSemesterForm( $year, $semester );
echo '</div>';


// Select semester and year here.

// Get the pervious value, else set them to empty.
$courseSelected = __get__( $_POST, 'course_id', '' );
$taskSelected = __get__( $_POST, 'task', '' );

$runningCourses = array();

foreach( getSemesterCourses( $year, $semester ) as $c )
    $runningCourses[ $c[ 'course_id' ] ] = $c;

$runningCoursesSelect = arrayToSelectList(
            'course_id'
            , array_keys( $runningCourses ), array( )
            , false, $courseSelected
        );

$taskSelect = arrayToSelectList( 'task'
                , array( 'Add enrollment', 'Change enrollment' )
                , array( ), false, $taskSelected
        );

//echo ' <br /> <br />';
//echo '<form method="post" action="">'; echo
//    "<table>
//        <tr>
//            <th>Select courses</th>
//            <th>Task</th>
//        </tr>
//        <tr>
//            <td>" . $runningCoursesSelect . "</td>
//            <td>" . $taskSelect . "</td>
//            <td><button type=\"submit\">Submit</button>
//        </tr>
//    </table>";
//
//echo '</form>';

// Handle request here.
$taskSelected = __get__( $_POST, 'task', '' );
$_POST[ 'semester' ] = $semester;
$_POST[ 'year' ] = $year;

$whereExpr = '';
if( __get__( $_POST, 'course_id', '' ) )
    $whereExpr = whereExpr( 'semester,year,course_id', $_POST  );

$enrollments = getTableEntries( 'course_registration' ,'student_id', $whereExpr);


$enrolls = getTableEntries( 'course_registration', 'course_id, student_id'
                            , "status='VALID' AND year='$year' AND semester='$semester'"
                        );
$courseMap = array( );

foreach( $enrolls as $e )
    $courseMap[$e['course_id']][] = $e;

// Show the quick action and enrollment information here.
echo "<h1>Enrollments for $semester/$year</h1>";
foreach( $courseMap as $cid => $enrolls )
{

    if( ! $cid )
        continue;

    echo '<div style="border:1px dotted lightblue">';

    $cname = getCourseName( $cid );
    echo "<h2>($cid) $cname </h2>";
    // Create a form to add new registration.
    $table = ' <table border="0">';
    $table .= '<tr>
            <td> <textarea cols="30" rows="2" name="enrollments"
                placeholder="gabbar@ncbs.res.in:CREDIT&#10kalia@instem.res.in:AUDIT"></textarea> </td>
            <td> <button name="response" value="quickenroll"
                title=\'Use "email:CREDIT" or "email:AUDIT" or "email:DROPPED" format.\' 
                >Quick Enroll</button> </td>
        </tr>';
    $table .= '</table>';

    // Display form
    $form = '<div id="show_hide_div">';
    $form .= '<form action="' . site_url('adminacad/quickenroll') . '" method="post" accept-charset="utf-8">';
    $form .= $table;
    $form .= '<input type="hidden" name="course_id" value="' . $cid . '" />';
    $form .= '<input type="hidden" name="year" value="' . $year . '" />';
    $form .= '<input type="hidden" name="semester" value="' . $semester . '" />';
    $form .= '</form>';
    $form .= '</div>';
    echo $form;

    echo ' <br /> ';

    echo '<table class="enrollments">';
    echo '<tr>';
    $numEnrollments = count( $enrolls );
    echo " <strong>Current enrollements: $numEnrollments</strong> ";
    foreach( $enrolls as $i => $e )
    {
        $index = $i + 1;
        $student = $e[ 'student_id'];
        $dropForm = '<form action="'.site_url("adminacad/change_enrollement").'" method="post" >';

        // Change type of enrollment.
        $otherEnrollmentTypes = array( 'CREDIT', 'AUDIT', 'DROP' );
        foreach( $otherEnrollmentTypes as $other )
        {
            $style = '';
            if( $e['type'] == $other )
                $style = 'disabled=true class="current_enrollment_type"';

            $dropForm .= "<button name='response' value='$other' 
                $style title='$other course'>" . strtoupper($other) . '</button>';
        }

        $dropForm .= '<input type="hidden" name="course_id" id="" value="' . $cid . '" />
                <input type="hidden" name="year" value="' . $year . '" />
                <input type="hidden" name="semester" value="' . $semester . '" />
                <input type="hidden" name="student_id" value="' . $student . '" />
            </form>';

        $sname = arrayToName( getLoginInfo( $student ), true );
        $grade = $e[ 'grade' ];
        $type = $e[ 'type'];

        // If grade is assigned, you can't drop the course.
        if( $grade )
            $dropForm = '';

        echo "<td> <tt>$index.</tt> $student<br />$sname <br />$grade <br />$dropForm</td>";
        if( ($i+1) % 5 == 0 )
            echo '</tr><tr>';
    }
    echo '</tr>';
    echo '</table>';
    echo '</div>';
    echo '<br />';
}

echo '<br />';
echo goBackToPageLink( "$ref/home", 'Go back' );


?>
