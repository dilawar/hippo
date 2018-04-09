<?php

include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'html2text.php';
include_once 'methods.php';
include_once './check_access_permissions.php';

if( ! (isIntranet() || isAuthenticated( ) ) )
{
    echo loginOrIntranet( );
    exit;
}

?>

<!-- Sweet alert -->
<script src="./node_modules/sweetalert2/dist/sweetalert2.all.min.js"></script>
<link rel="stylesheet" type="text/css" href="./node_modules/sweetalert2/dist/sweetalert.css">


<?php
/* get this semester and next semester courses */

$year = __get__( $_GET, 'year', getCurrentYear( ) );
$sem = __get__( $_GET, 'semester', getCurrentSemester( ) );

$slotCourses = array( );
$tileCourses = array( );
$runningCourses = getSemesterCourses( $year, $sem );

// Collect both metadata and other information in slotCourse array.
foreach( $runningCourses as $c )
{
    $cid = $c[ 'course_id' ];
    $course = getTableEntry( 'courses_metadata', 'id' , array('id' => $cid) );
    if( $course )
    {
        $slotId = $c[ 'slot' ];
        $tiles = getTableEntries( 'slots', 'groupid', "groupid='$slotId'" );
        $slotCourses[ $slotId ][ ] = array_merge( $c, $course );
        foreach( $tiles as $tile )
        {
            if( strpos( $c['ignore_tiles'], $tile[ 'id' ]) !== 0 )
            {
                $tileCourses[ $tile['id']][ ] = array_merge( $c, $course );
            }
        }
    }
}

$slotUpcomingCourses = array( );
$nextSem = getNextSemester( );
$upcomingCourses = getSemesterCourses( $nextSem[ 'year' ], $nextSem['semester'] );
foreach( $upcomingCourses as $c )
{
    $cid = $c[ 'course_id' ];
    $course = getTableEntry( 'courses_metadata', 'id' , array('id' => $cid) );
    if( $course )
    {
        $slotId = $c[ 'slot' ];
        $tiles = getTableEntries( 'slots', 'groupid', "groupid='$slotId'" );
        $slotUpcomingCourses[ $slotId ][ ] = array_merge( $c, $course );
        foreach( $tiles as $tile )
        {
            if( strpos( $c['ignore_tiles'], $tile[ 'id' ]) !== 0 )
            {
                $tileCourses[ $tile['id']][ ] = array_merge( $c, $course );
            }
        }
    }
}


$tileCoursesJSON = json_encode( $tileCourses );

?>

<script type="text/javascript" charset="utf-8">
function showCourseInfo( x )
{
    swal({
        title : x.title
        , html : "<div align=\"left\">" + x.value + "</div>"
    });
}


function showRunningCourse( x )
{
    var slotId = x.value;
    var courses = <?php echo $tileCoursesJSON; ?>;
    var runningCourses = courses[ slotId ];
    var title;
    var runningCoursesTxt;

    if( runningCourses && runningCourses.length > 0 )
    {
        runningCoursesTxt = runningCourses.map(
            function(x, index) { return (1 + index) + '. ' + x.name
            + ' at ' + x.venue ; }
        ).join( "<br>");

        title = "Following courses are running in slot " + slotId;
    }
    else
    {
        title = "No course is running on slot " + slotId;
        runningCoursesTxt = "";
    }

    swal({
        title : title
        , html : runningCoursesTxt
        , type : "info"
        });
}
</script>



<?php

echo alertUser(
    "Click on tile <button class=\"invisible\" disabled>1A</button> etc to see the
    list of courses running at this time.");

$table = slotTable(  );
echo $table;

/* Select year and semester */

echo ' <br /> ';
echo horizontalLine( );
$autumnSelected = '';
$springSelected = '';
if( $sem == 'AUTUMN' )
    $autumnSelected = 'selected';
else
    $springSelected = 'selected';

// Show select semester/year.
$form = selectYearSemesterForm( $year, $sem );
echo $form;

/* Enrollment table. */
echo "<h1>Courses in " . __ucwords__( $sem) . ", $year semester</h1>";

$showEnrollText = 'Show Enrollement';
echo '<div style="font-size:small">
    <table border="1"> <tr>
        <td> <i class="fa fa-flag-o fa-2x"></i>
        To enroll, visit <a class="clickable" href="user_manages_courses.php">My Courses</a>
        link in your home page after login. </td>
    </tr>
    <tr>
        <td>
            <i class="fa fa-flag-checkered fa-2x"></i>
            Registration on <tt>Hippo</tt> is mandatory;
            <a href="https://moodle.ncbs.res.in" target="_blank">MOODLE</a> registration
            is independent!
        </td>
    </tr>
    </table></div>';
echo '<br />';

/**
    * @name Show the courses.
    * @{ */
/**  @} */


$header = '<tr><th>Course/Instructors</th><th>Schedule</th><th>Slot/Venue</th><th>URL</th></tr>';


// Go over courses and populate the entrollment array.
$enrollments = array( );
ksort( $slotCourses );

$table = '<table border="1">';
foreach( $slotCourses as $slot => $courses )
{
    foreach( $courses as $c )
    {
        $cid = $c[ 'course_id' ];
        $courseTable = '<table class="show_course">';
        $courseTable .= $header;
        $courseTable .= "<tr>";
        $courseTable .= courseToHTMLRow( $c, $slot, $sem, $year, $enrollments );
        $courseTable .= '</tr>';
        $courseTable .= '</table>';

        $data = getEnrollmentTableAndEmails( $cid, $enrollments, 'info' );
        $enTable = $data[ 'html_table'];
        $allEmails = $data[ 'enrolled_emails' ];

        $tid = "show_hide_$cid";

        $regTable = '<table class=""></table>';
        if( count( $allEmails ) > 0 )
        {
            // Apend user email at the end of registration table.
            $mailtext = implode( ",", $allEmails );
            $enTable .= '<div>' .  mailto( $mailtext, 'Send email to all students' ) . "</div>";

            $regTable = '<table border="1">';
            $regTable .= '<tr>';
            $regTable .= '<td>
                <button class="show_as_link"
                    onclick="toggleShowHide( this, \'' . $tid . '\' )">Show Enrollments</button>
                </td>';
            $regTable .= '</tr>';
            $regTable .= "<tr><td id=\"$tid\" style=\"display:none\"> $enTable </td></tr>";
            $regTable .= '</table>';
        }

        $table .= '<table border="0" class="course">';
        $table .= "<tr> <td> $courseTable </td>";
        $table .= "<td> $regTable </td></tr>";
    }
}

$table .= "</table>";
echo '<div style="font-size:small">';
echo $table;
echo '</div>';


/*******************************************************************************
 * Upcoming courses.
 *******************************************************************************/
// Collect both metadata and other information in slotCourse array.


$newTab = '<table id="upcoming_courses" class="info">';
$newTab .= '<tr><th>Course <br> Instructors</th><th>Schedule</th><th>Slot Tiles</th><th>Venue</th>
    <th>Enrollments</th><th>URL</th> </tr>';

foreach( $slotUpcomingCourses as $slot => $ucs )
{
    foreach( $ucs as $uc )
    {
        $newTab .= '<tr>';
        $slot = $uc[ 'slot' ];
        $sem = getSemester( $uc[ 'end_date' ] );
        $year = getYear( $uc[ 'end_date' ] );
        $newTab .= courseToHTMLRow( $uc, $slot, $sem, $year, $upcomingEnrollments);
        $newTab .= '</tr>';
    }
}
$newTab .= '</table>';

// Show table.
if( count( $slotUpcomingCourses ) > 0 )
{
    echo '<h1>Upcoming courses</h1>';
    echo '<div style="font-size:small">';
    echo $newTab;
    echo '</div>';
}

echo '<br>';
echo closePage( );

?>
