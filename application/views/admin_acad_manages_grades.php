<?php

include_once 'header.php';
include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN' ) );

include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

echo userHTML( );


$year = __get__( $_GET, 'year', getCurrentYear( ) );
$sem = __get__( $_GET, 'semester', getCurrentSemester( ) );

$springChecked = ''; $autumnChecked = '';
if( $sem == 'SPRING' )
{
    $springChecked = 'checked';
    $autumnChecked = '';
}
else
{
    $autumnChecked = 'checked';
    $springChecked = '';
}

echo selectYearSemesterForm( $year, $sem );

echo  noteWithFAIcon( "Selected semester $sem/$year", 'fa-bell-o' );

// Select semester and year here.

// Get the pervious value, else set them to empty.
$courseSelected = __get__( $_POST, 'course_id', '' );
$taskSelected = 'Grade';

$runningCourses = array();

$nonGradable = array( );
foreach( getSemesterCourses( $year, $sem ) as $c )
{
    $cid = $c[ 'course_id' ];
    $endDate = strtotime( $c['end_date'] );

    if( $endDate < strtotime( 'today' ) + 7 * 24 * 3600 )
        $runningCourses[ $cid ] = getCourseName( $cid );
    else
        $nonGradable[] = $cid;
}

if( count( $nonGradable ) > 0 )
    echo printInfo( "Following courses can not be graded yet: <br /> "
        . implode( ", ", $nonGradable ) );

$runningCoursesSelect = arrayToSelectList( 'course_id'
            , array_keys( $runningCourses )
            , $runningCourses
            , false, $courseSelected
        );

echo ' <br /> ';
echo ' <h1>Manage Grades</h1> ';
echo '<form method="post" action="">';
echo "<table>
    <tr>
        <td>" . $runningCoursesSelect . "</td>
        <td><button type=\"submit\">Submit</button>
    </tr>
    </table>";
echo '</form>';

$_POST[ 'semester' ] = $sem;
$_POST[ 'year' ] = $year;
$whereExpr = '';
if( __get__( $_POST, 'course_id', '' ) )
    $whereExpr = whereExpr( 'semester,year,course_id', $_POST  );

$enrollments = getTableEntries( 'course_registration' ,'student_id', $whereExpr);

if( count( $enrollments ) > 0 )
{
    if( ! __get__($_POST, 'course_id', '' ) )
        echo alertUser( "No course is selected" );
    else
    {
        echo alertUser( "Grading for course " . $_POST[ 'course_id' ] );

        echo '<h2>Quick Grading</h2>';
        echo printNote( "Each line must contain <tt>student_email grade</tt> e.g.
            <tt> gabbar@ncbs.res.in,A+ </tt>" );

        $allForm = '<form method="post" action="admin_acad_manages_grades_action.php">';
        $allForm .= '<table class="show_info">';
        $allForm .= '<tr><td>';
        $allForm .= ' <textarea rows="5" cols="50" name="grades_csv"
            placeholder="gabbar@ncbs.res.in,A+ &#10kalia@ncbs.res.in,F"
            value=""></textarea>';
        $allForm .= '</td><td>';
        $allForm .= '<button class="submit" name="response" value="Assign All">Assign All</button>';
        $allForm .= '<input type="hidden" name="course_id" id="" value="' . $_POST[ 'course_id' ] . '" />';
        $allForm .= '<input type="hidden" name="year" id="" value="' . $year . '" />';
        $allForm .= '<input type="hidden" name="semester" id="" value="' . $sem . '" />';
        $allForm .= '</td></tr>';
        $allForm .= '</table>';
        $allForm .= '</form>';

        echo $allForm;
        echo ' <br /> ';

        $hide = 'registered_on,last_modified_on,status,grade_is_given_on';
        $table = '<table class="info sortable">';

        $ids = array( );                    /* Collect all student ids.  */
        $grades = array( );
        $allGradesHTML = '';                // Add all grades to table.

        $table .= arrayToTHRow( $enrollments[0], 'info', $hide );
        foreach( $enrollments as $enrol )
        {
            $ids[ ] =  $enrol[ 'student_id' ];

            $table .= '<tr>';
            $table .= '<form action="admin_acad_manages_grades_action.php" method="post" accept-charset="utf-8">';
            $table .= arrayToRowHTML( $enrol, 'info', $hide, true, false );
            $table .= "<td>" . gradeSelect( $enrol['student_id'], $enrol[ 'grade' ] ) . "</td>";
            $table .= "<td> <button name='response' value='Assign One'>Assign</button> </td>";

            $table .= '<input type="hidden" name="student_id" value="' . $enrol['student_id'] . '" >';
            $table .= '<input type="hidden" name="year" value="' . $enrol[ 'year'] . '" >';
            $table .= '<input type="hidden" name="semester" value="' . $enrol['semester'] . '" >';
            $table .= '<input type="hidden" name="course_id" value="' . $enrol['course_id'] . '" >';
            $table .= '</form>';

            $table .= '</tr>';
        }

        $table .= '<input type="hidden" name="student_ids" value="' . implode(',', $ids) . '" >';
        $table .= '</table>';
        echo $table;

    }
}

echo ' <br /> ';
echo goBackToPageLink( 'admin_acad.php', 'Go back' );

echo "<h1>All enrollments for $sem/$year</h1>";
$enrolls = getTableEntries( 'course_registration', 'course_id'
        , "status='VALID' AND year='$year' AND semester='$sem'"
    );
$courseMap = array( );
foreach( $enrolls as $e )
    $courseMap[$e['course_id']][] = $e;

foreach( $courseMap as $cid => $enrolls )
{
    if( ! $cid )
        continue;

    sortByKey( $enrolls, 'student_id' );

    $cname = getCourseName( $cid );
    echo "<h2>$cid: $cname </h2>";
    echo '<table class="tiles">';
    echo '<tr>';
    foreach( $enrolls as $i => $e )
    {
        $student = $e[ 'student_id'];
        $sname = arrayToName( getLoginInfo( $student ) );
        $grade = $e[ 'grade' ];
        $type = $e[ 'type'];
        echo "<td> $sname <br /> $type <br /> $grade </td>";
        if( ($i+1) % 5 == 0 )
            echo '</tr><tr>';

    }
    echo '</tr>';
    echo '</table>';
}

echo '<br />';
echo goBackToPageLink( 'admin_acad.php', 'Go back' );


?>
