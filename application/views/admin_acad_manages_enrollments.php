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
$taskSelected = __get__( $_POST, 'task', '' );

$runningCourses = array();

foreach( getSemesterCourses( $year, $sem ) as $c )
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


echo '<h1>Manage Enrollements</h1>';
echo alertUser( "Your are working with semester $sem-$year" );

echo '<form method="post" action="">'; echo
"<table>
    <tr>
        <th>Select courses</th>
        <th>Task</th>
    </tr>
    <tr>
        <td>" . $runningCoursesSelect . "</td>
        <td>" . $taskSelect . "</td>
        <td><button type=\"submit\">Submit</button>
    </tr>
    </table>";

echo '</form>';

// Handle request here.
$taskSelected = __get__( $_POST, 'task', '' );
$_POST[ 'semester' ] = $sem;
$_POST[ 'year' ] = $year;

$whereExpr = '';
if( __get__( $_POST, 'course_id', '' ) )
    $whereExpr = whereExpr( 'semester,year,course_id', $_POST  );

$enrollments = getTableEntries( 'course_registration' ,'student_id', $whereExpr);

if( $taskSelected == '' )
{
    echo printWarning( "No task has been selected yet!" );
}
else if( $_POST[ 'task' ] == 'Change enrollment' )
{
    echo "<h2>Changing enrollment</h2>";

    echo printInfo( "Press in button to change the status of ennrollment." );

    $types = getTableColumnTypes( 'course_registration', 'type' );

    $i = 0;
    echo '<table>';
    foreach( $enrollments as $enrol )
    {
        $i += 1;
        echo "<tr><td>$i</td><td>";
        echo '<form method="post"
            action="admin_acad_manages_enrollments_action.php">';

        echo arrayToTableHTML( $enrol, 'info', ''
            , 'last_modified_on,grade,grade_is_given_on,status' );

        $type = $enrol[ 'type' ];

        foreach( $types as $t )
        {
            if( $t == $type )
                continue ;

            echo '</td><td><button name="response" value="' . $t
                . '">'. $t . '</button>';
        }

        echo '</td></tr>';
        echo '<input type="hidden" name="year" value="' . $enrol[ 'year'] . '" >';
        echo '<input type="hidden" name="semester" value="' . $enrol['semester'] . '" >';
        echo '<input type="hidden" name="student_id" value="' . $enrol['student_id'] . '" >';
        echo '<input type="hidden" name="course_id" value="' . $enrol['course_id'] . '" >';
        echo '</form>';
    }
    echo '</table>';

}
else if( $_POST[ 'task' ] == 'Add enrollment' )
{
    $course = $_POST[ 'course_id' ] ;
    $cname = getCourseName( $course );

    echo "<h2> Adding new enrollments to $cname ($sem/$year) </h2>";
    $form = '<form method="post" action="admin_acad_manages_enrollments_action.php">';
    $form .= '<table>';
    $form .= '<tr><td>';
    $form .= '<textarea cols="30" rows="4" name="logins" placeholder="gabbar@ncbs.res.in kalia@instem.res.in"></textarea>';
    $form .= '</td><td>';
    $form .= arrayToSelectList( 'type', array( 'CREDIT', 'AUDIT' )
                , array( 'CREDIT' ,'AUDIT' ), false, 'CREDIT'  );
    // Add semester and year as hidden input.
    $form .= ' <input type="hidden" name="year" id="" value="' . $year . '" />';
    $form .= ' <input type="hidden" name="semester" id="" value="' . $sem . '" />';
    $form .= '</td><td>';
    $form .= '<button type="submit" name="response" value="enroll_new">Enroll</button>';
    $form .= '</td></tr>';
    $form .= '</table>';
    $form .= '<input type="hidden" name="course_id" value="' . $course .  '">';
    $form .= '</form>';
    echo $form;
}
else
    echo printInfo( "Unsupported task " . $_POST[ 'task' ] );


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

    $cname = getCourseName( $cid );
    echo "<h2> ($cid) $cname </h2>";

    // Create a form to add new registration.
    $table = ' <table border="0">';
    $table .= '<tr>
            <td> <textarea cols="40" rows="3" name="enrollments"
                placeholder="gabbar@ncbs.res.in:CREDIT&#10kalia@instem.res.in:AUDIT"></textarea> </td>
            <td> <button name="response" value="quick_enroll"
                title=\'Use "email:CREDIT" or "email:AUDIT" or "email:DROPPED" format.\'
                >Quick Enroll</button> </td>
        </tr>';
    $table .= '</table>';

    // Display form
    $form = '<div id="show_hide_div">';
    $form .= '<form action="admin_acad_manages_enrollments_action.php" method="post" accept-charset="utf-8">';
    $form .= $table;
    $form .= '<input type="hidden" name="course_id" value="' . $cid . '" />';
    $form .= '<input type="hidden" name="year" value="' . $year . '" />';
    $form .= '<input type="hidden" name="semester" value="' . $sem . '" />';
    $form .= '</form>';
    $form .= '</div>';
    echo $form;

    echo ' <br /> ';
    echo '<table class="tiles">';
    echo '<tr>';
    echo ' <strong>Enrollement Table</strong> ';
    foreach( $enrolls as $i => $e )
    {
        $student = $e[ 'student_id'];

        $dropForm = '
            <form action="admin_acad_manages_enrollments_action.php" method="post" accept-charset="utf-8">
                <button class="show_as_link" name="response" value="drop_course"
                    title="Drop course"> <i class="fa fa-tint fa-1x"></i>
                </button>
                <input type="hidden" name="course_id" id="" value="' . $cid . '" />
                <input type="hidden" name="year" value="' . $year . '" />
                <input type="hidden" name="semester" value="' . $sem . '" />
                <input type="hidden" name="student_id" value="' . $student . '" />
            </form>
        ';

        $sname = arrayToName( getLoginInfo( $student ) );
        $grade = $e[ 'grade' ];
        $type = $e[ 'type'];

        // If grade is assigned, you can't drop the course.
        if( $grade )
            $dropForm = '';

        echo "<td> $sname  ($student) <br /> $type <br /> $grade $dropForm </td>";
        if( ($i+1) % 4 == 0 )
            echo '</tr><tr>';
    }
    echo '</tr>';
    echo '</table>';
}

echo '<br />';
echo goBackToPageLink( 'admin_acad.php', 'Go back' );


?>
