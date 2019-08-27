<?php
require_once BASEPATH.'autoload.php';

function showAlertTable( ) {
    $html = '<div class="row">
            <div class="col">
                <i class="fa fa-flag-o"></i>
                To enroll, visit <a class="btn btn-link" href="user_manages_courses.php">
                My Courses</a> link in your home page after login.
            </div>
            <div class="col">
                <i class="fa fa-flag-checkered fa-1x"></i> Registration on Hippo is mandatory; 
                <a class="btn btn-link" href="http://moodle.ncbs.res.in" target="_blank">MOODLE</a> 
                enrollment is independent of it!
            </div>
        </div>';
    return $html;
}

?>

<!-- Sweet alert -->
<script src="<?=base_url()?>/node_modules/sweetalert2/dist/sweetalert2.all.min.js"></script>
<link rel="stylesheet" type="text/css" 
    href="<?=site_url()?>/node_modules/sweetalert2/dist/sweetalert.css">

<?php

$slotCourses = array( );
$tileCourses = array( );
$runningCourses = $cRunningCourses;
$sem = $cSemester;
$year = $cYear;

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
    else
    {
        flashMessage( "No metadata is found for $cid " );
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
$table = slotTable();
?>

<div class="card m-1 p-1">
    <div class="card-header h2"> Slot Table </div> 
    <div class="card-body"> <?=$table ?> </div> 
    <div class="card-footer">
        Click on tiles such as <button class=\"tiles\" disabled>1A</button> 
        to see the courses running on this slot.
    </div>
</div>

<?php
/* Select year and semester */
$autumnSelected = '';
$springSelected = '';
if( $sem == 'AUTUMN' )
    $autumnSelected = 'selected';
else
    $springSelected = 'selected';

/* Enrollment table. */
// Show select semester/year.
$form = selectYearSemesterForm( $year, $sem );

$showEnrollText = 'Show Enrollement';
// Go over courses and populate the entrollment array.
$header = '<tr><th>Course/Instructors</th><th>Schedule</th><th>Slot/Venue</th><th>URL</th></tr>';
$enrollments = array( );
$html = '';

// This semester courses.
foreach( $slotCourses as $slot => $courses )
{
    $div = '<div class="container">';
    foreach( $courses as $c )
    {
        $cid = $c[ 'course_id' ];
        // Add header only to the first entry.
        $courseTable = '<table class="show_course">';
        // $courseTable .= "<caption> $cid </caption>";
        $courseTable .= "<tr>";

        // This function fills in $enrollments.
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
?>

<div class="card m-2 p-2">
    <div class="card-header"> <?=$form?> </div>
    <div class="card-body>
        <?= showAlertTable() ?>
        <?=$html?>
    </div>
</div>



<?php
/*******************************************************************************
 * Upcoming courses in next semester.
 *******************************************************************************/
// Collect both metadata and other information in slotCourse array.


$newTab = '<table id="upcoming_courses" class="info">';
$header = '<tr><th>Course <br> Instructors</th><th>Schedule</th>
    <th>Slot Tiles</th><th>Venue</th>
    <th>Enrollments</th><th>URL</th></tr>';

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

<script type="text/javascript" charset="utf-8">
function showCourseInfo( x )
{
    swal.fire({
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

    swal.fire({
        title : title
        , html : runningCoursesTxt
        , type : "info"
        });
}
</script>
