<?php
require_once BASEPATH.'autoload.php';

function showAlertTable( )
{
    $html = '<div class="">
        <table> <tr>
            <td><i class="fa fa-flag-o fa-2x"></i>
                To enroll, visit <a class="clickable" href="user_manages_courses.php">My Courses</a>
                link in your home page after login.
            </td>
        </tr>
        <tr>
            <td>
                <i class="fa fa-flag-checkered fa-2x"></i>
                Registration on Hippo is mandatory; 
                <a href="http://moodle.ncbs.res.in" target="_blank">MOODLE</a> enrollment
                is independent of it!
            </td>
        </tr>
        </table></div>';
    return $html;
}

?>

<!-- Sweet alert -->
<script src="<?=base_url()?>/node_modules/sweetalert2/dist/sweetalert2.all.min.js"></script>
<link rel="stylesheet" type="text/css" 
    href="<?=site_url()?>/node_modules/sweetalert2/dist/sweetalert.css">

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
            if( strpos( $c['ignore_tiles'], $tile[ 'id' ]) !== 0 )
                $tileCourses[ $tile['id']][ ] = array_merge( $c, $course );
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
            if( strpos( $c['ignore_tiles'], $tile[ 'id' ]) !== 0 )
                $tileCourses[ $tile['id']][ ] = array_merge( $c, $course );
    }
}

$tileCoursesJSON = json_encode( $tileCourses );
?>

<script type="text/javascript" charset="utf-8">
function showCourseInfo( x )
{
    swal({
        title : x.title
        , html : "<div align=\"left\">" + atob(x.value) + "</div>"
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


$table = slotTable();
echo p("Click on tiles such as <button class=\"tiles\" disabled>1A</button> to see the
    courses running on this slot.");
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

/* Enrollment table. */

// Show select semester/year.
$form = selectYearSemesterForm( $year, $sem );
echo $form;
$showEnrollText = 'Show Enrollement';

echo showAlertTable( );
echo ' <br />';

// Go over courses and populate the entrollment array.
$header = '<tr><th>Course/Instructors</th><th>Schedule</th><th>Slot/Venue</th><th>URL</th></tr>';
$enrollments = array( );
$html = '';

foreach( $slotCourses as $slot => $courses )
{
    $div = '<div class="">';
    foreach( $courses as $c )
    {
        $cid = $c[ 'course_id' ];
        // Add header only to the first entry.
        $courseTable = '<table class="show_course">';
        // $courseTable .= "<caption> $cid </caption>";
        $courseTable .= "<tr>";
        $courseTable .= courseToHTMLRow( $c, $slot, $sem, $year, $enrollments );
        $courseTable .= '</tr>';
        $courseTable .= '</table>';

        $data = getEnrollmentTableAndEmails( $cid, $enrollments, 'info exportable' );
        $enTable = $data[ 'html_table'];
        $allEmails = $data[ 'enrolled_emails' ];

        $tid = "show_hide_$cid";

        if( count( $allEmails ) > 0 )
        {
            // Apend user email at the end of registration table.
            $mailtext = implode( ",", $allEmails );
            $enTable .= '<div>' .  mailto( $mailtext, 'Send email to all students' ) . "</div>";

            $regTable = '<table style="width:100%;">';
            $regTable .= '<tr>';
            $regTable .= '<td><button class="show_as_link"
                        onclick="toggleShowHide( this, \'' . $tid . '\' )">Show Enrollments</button>
                    </td>';
            $regTable .= '</tr>';
            $regTable .= "<tr><td width='100%' id=\"$tid\" style=\"display:none\"> $enTable </td></tr>";
            $regTable .= '</table>';
        }
        else
            $regTable = '<table></table>';

        $div .= $courseTable;
        $div .= $regTable;
        $div .= '</div>';
    }
    $html .= $div;
}

echo $html;


/*******************************************************************************
 * Upcoming courses in next semester.
 *******************************************************************************/
// Collect both metadata and other information in slotCourse array.


$newTab = '<table id="upcoming_courses" class="info">';
$header = '<tr><th>Course <br> Instructors</th><th>Schedule</th><th>Slot Tiles</th><th>Venue</th>
    <th>Enrollments</th><th>URL</th> </tr>';

foreach( $slotUpcomingCourses as $slot => $ucs )
{
    foreach( $ucs as $i => $uc )
    {
        if($i == 0)
            $newTab .= $header;

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

<script src="<?=base_url()?>./node_modules/xlsx/dist/xlsx.core.min.js"></script>
<script src="<?=base_url()?>./node_modules/file-saverjs/FileSaver.min.js"></script>
<script src="<?=base_url()?>./node_modules/tableexport/dist/js/tableexport.min.js"></script>
<script type="text/javascript" charset="utf-8">
TableExport(document.getElementsByClassName("exportable"));
</script>
