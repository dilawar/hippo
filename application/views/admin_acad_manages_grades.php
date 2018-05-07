<?php
require_once BASEPATH.'autoload.php';
echo userHTML( );

$year = __get__( $_GET, 'year', getCurrentYear( ) );
$sem = __get__( $_GET, 'semester', getCurrentSemester( ) );

$springChecked = '';
$autumnChecked = '';
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

echo '<div class="important">';
echo "Selected semester $sem/$year";
echo selectYearSemesterForm( $year, $sem );
echo '</div>';

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
    echo alertUser( "Following courses are not graded yet: <br /> "
        . implode( ", ", $nonGradable ), false );

$runningCoursesSelect = arrayToSelectList( 'course_id'
            , array_keys( $runningCourses )
            , $runningCourses
            , false, $courseSelected
        );

echo '<div class="thick_border">';

echo '<h2>Quick grade</h2>';

echo '<form method="post" action="">';
echo "<table>
    <tr>
        <td>" . $runningCoursesSelect . "</td>
        <td><button type=\"submit\">Grade</button>
    </tr>
    </table>";
echo '</form>';

$_POST[ 'semester' ] = $sem;
$_POST[ 'year' ] = $year;
$whereExpr = '';
if( __get__( $_POST, 'course_id', '' ) )
    $whereExpr = whereExpr( 'semester,year,course_id', $_POST  );

$enrollments = getTableEntries( 'course_registration' ,'course_id, student_id', $whereExpr);

if( count( $enrollments ) > 0 )
{
    if( __get__($_POST, 'course_id', '' ) )
    {
        echo '<strong>Quick Grading</strong>';
        echo printNote( "Each line must contain <tt>student_email grade</tt> e.g.
            <tt> gabbar@ncbs.res.in,A+ </tt>" );

        $allForm = '<form method="post" action="'.site_url('adminacad/quickgrade').'">';
        $allForm .= '<table class="info">';
        $allForm .= '<tr><td>';
        $allForm .= ' <textarea rows="5" cols="35" name="grades_csv"
                        placeholder="gabbar@ncbs.res.in,A+ &#10kalia@ncbs.res.in,F"
                        value=""></textarea>';
        $allForm .= '</td><td>';
        $allForm .= '<button name="response" value="Assign All">Grade All</button>';
        $allForm .= '<input type="hidden" name="course_id" id="" value="' . $_POST[ 'course_id' ] . '" />';
        $allForm .= '<input type="hidden" name="year" id="" value="' . $year . '" />';
        $allForm .= '<input type="hidden" name="semester" id="" value="' . $sem . '" />';
        $allForm .= '</td></tr>';
        $allForm .= '</table>';
        $allForm .= '</form>';

        echo $allForm;
        echo ' <br /> ';
    }
}
echo '</div>';

echo ' <br /> ';
echo goBackToPageLink( 'adminacad/home', 'Go back' );

echo "<h2>Enrollments tables $sem/$year</h2>";
$enrolls = getTableEntries( 'course_registration'
        , 'course_id, student_id'
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

    echo '<div class="important">';
    echo "<large><strong>$cid: $cname </strong></large>";
    echo showEnrollmenTable( $enrolls );

    // Show update/edit grade button here.
    echo '<form action="'.site_url("adminacad/gradecourse/$year/$sem/$cid").'" method="post">';
    echo '<button class="show_as_link">Edit/Update Grades</button>';
    echo '</form>';
    echo '</div>';
    echo ' <br />';
}

echo '<br />';
echo goBackToPageLink( 'adminacad/home', 'Go back' );


?>
