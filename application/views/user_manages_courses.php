<?php
require_once BASEPATH . 'autoload.php';
$me = whoAmI();
echo userHTML( );
$sem = getCurrentSemester( );
$year = getCurrentYear( );
$runningCourses = array( );
$semCourses = getSemesterCourses( $year, $sem );
foreach( $semCourses as $rc )
{
    $cid = $rc[ 'course_id' ];
    $rc[ 'name' ] = getCourseName( $cid );
    $rc[ 'slot_tiles' ] = getCourseSlotTiles( $rc );
    $runningCourses[ $cid ] = $rc;
}
// User courses and slots.
$myCourses = getMyCourses( $sem, $year, $user = $me );
$mySlots = array( );
foreach( $myCourses as $c )
{
    // Get the running courses.  In rare case, use may have enrolled in course
    // which is not running anymore.
    $course = __get__( $runningCourses, $c['course_id'], null );
    if( $course )
        $mySlots[ ] = $runningCourses[ $c[ 'course_id' ] ]['slot'];
    else
    {
        // This course is no longer running. Drop it.
        updateTable(
            'course_registration'
            , 'student_id,year,semester,course_id'
            , 'status'
            , array( 'student_id' => $me, 'year' => $year
                , 'semester' => $sem, 'course_id' => $c[ 'course_id' ]
                , 'status' => 'INVALID'
            )
        );
    }
}
$mySlots = array_unique( $mySlots );

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Course enrollment.
    * Check each course date. If course is starting at date x, then start the
    * coruse x-7 days and let people register till x+7 days.
 */
/* ----------------------------------------------------------------------------*/
$today = strtotime( 'today' );

// Running course this semester.
$courseMap = [];
$options = [];
$blockedCourses = [];
foreach( $runningCourses as $c )
{
    $cstart = strtotime( $c[ 'start_date' ] );

    // Registration is allowed within 3 weeks.
    if( ($cstart + 21*24*3600) > $today )
    {
        // Ignore any course which is colliding with any registered course.
        $cid = $c[ 'course_id' ];
        $cname = getCourseName( $cid );
        $slot = $c[ 'slot' ];
        if( in_array( $slot, $mySlots ) )
        {
            $blockedCourses[ $cid ] = $cname;
            continue;
        }

        if( $cid )
        {
            $options[] = $cid ;
            $courseInfoHTML = ((strlen($cname) > 32 )?substr($cname, 0, 30) . '... ' : $cname) .
                " (Slot " . $c['slot'] . "/" . $c['venue'] . ")";
            $courseMap[$cid] = $courseInfoHTML;
        }
    }
}
$courseSelect = arrayToSelectList( 'course_id', $options, $courseMap );
$default = ['student_id' => $me, 'semester' => $sem, 'year' => $year, 'course_id' => $courseSelect];
$myCourseTables = coursesToHTMLTable($myCourses, $runningCourses, $withFeedbackForm = true);
?>

<div class="card m-2 p-2">
<div class="card-header h1">Registration form</div>
<div class="card-body">
<small>A course will be visible in registration form 
upto 21 days from its starting date.
Some courses may not allowed <tt>AUDIT</tt>. Some courses may put a ceiling on the 
number of enrollments. See the table at the end of page.

<?php if(count($courseMap)>0): ?>
    <form method="post" action="manage_course/register">
    <?= dbTableToHTMLTable( 'course_registration'
        , $default
        , 'course_id:required,type:required'
        , 'Submit'
        , 'status,registered_on,last_modified_on,grade,grade_is_given_on')?>
    </form>
<?php else: ?>
    <div class="text">
    Time limit for registration have passed for all courses. Please write to academic office.
    </div>
<?php endif; ?>
</div>
</div>

<?php
$tofilter = 'student_id';
$action = 'drop';
?>

<div class="card p-2 m-2">
    <div class="card-header h2">
        You are registered for following course(s) in <?=$sem?>-<?=$year?>
    </div>

    <div class="card-body">
        <p class="text text-sm m-2">
            <i class="fa fa-info-circle"></i>
            Courses with status <tt>WAITLIST</tt> does not count. You are in <tt>WAITLIST</tt> 
            because this course has an upper limit on number of students allowed. 
            If enough people drop the course, <tt>WAITLIST</tt> will 
            automatically change to <tt>CREDIT/AUDIT</tt>. You can always write to 
            Academic Office for clarification/update.
        </p>
        <div class="m-2">
            <strong>Policy for dropping courses:</strong>
            Upto 30 days from starting of course, you are free to drop a course using 
            Hippo. After that, you need to contact Academic Office authority.
        </div>
        <!-- table of courses -->
        <?php if(count($myCourses) > 0): ?>
            <div class="row">
            <?php foreach($myCourseTables as $table): ?>
                <div class="col"><?= $table ?></div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            No course found.
        <?php endif; ?>
    </div>
</div> 
<?=goBackToPageLink( "user/home", "Go back" )?>

<!-- Summary of runnung courses. -->
<?php
// Course table.
$table = '<table class="info small">';
$header = '<tr><th>Course Name</th><th>Schedule</th>' .
    '<th>Slot/Venue</th>' .
    '<th>Auditing allowed?</th><th>Max Enrollments</th>' .
    '<th>Enrollments</th><th>Notes</th></tr>';
$table .= $header;
foreach( $runningCourses as $cid => $course )
{
    $cinfo = getCourseById( $cid );
    $cname = $cinfo['name'];

    $timeline = dbDate( $course['start_date'] ) . ' to ' . dbDate( $course['end_date']);
    $whereWhen =  'Slot ' . $course['slot'] . '<br />' . $course['venue'];
    $isAudit = ($course['is_audit_allowed'] == 'YES')?'YES':colored('NO', 'blue');
    $maxAllowed = ($course['max_registration'] > 0)?$course['max_registration']:'Unlimited';
    $numEnrollments = count(getCourseRegistrations( $cid, $course['year'], $course['semester'] ));
    $note = $course['note'];

    $row = '<tr>';
    $row .= "<td> $cname </td>";
    $row .= "<td> $timeline </td>";
    $row .= "<td> $whereWhen </td>";
    $row .= "<td> $isAudit </td>";
    $row .= "<td> $maxAllowed </td>";
    $row .= "<td> $numEnrollments </td>";
    $row .= "<td style='min-width:300px'><small> $note </small></td>";
    $row .= '</tr>';

    $table .=  $row;
}
$table .= '</table>';
?>
<div class="card">
<div class="card-header h2">Summary of current running courses</div>
<div class="card-body"> <?=$table ?> </div>
</div>
<?=goBackToPageLink( "user/home", "Go back" )?>

<!-- MY all courses. -->
<div class="card">
<div class="card-header h2">My courses</div>

<?php
$myAllCourses = getTableEntries( 'course_registration'
    , 'year, semester'
    , "student_id='$me' AND status='VALID'"
    );

// Add feedback URL as well.
$myCoursesWithFeedback = array();
foreach($myAllCourses as $course)
{
    $cid = $course['course_id'];
    $cname = getCourseName( $cid );
    $course = array_insert_after('course_id', $course, 'course_name', $cname);

    // year and semster are course semester.
    $year = $course['year'];
    $sem = $course['semester'];
    $res = feedbackForm( $year, $sem, $cid );
    if( $res['num_unanswered']  > 0 )
        $course['Feedback'] = $res['html'];
    else
        $course['Feedback'] = showCourseFeedbackLink($year, $sem, $cid);
    $myCoursesWithFeedback[] = $course;
}

if(count($myCoursesWithFeedback ) > 0 )
{
    $hide = 'student_id,status,last_modified_on';
    $table = '<table class="info sorttable w-auto">';
    $table .= arrayToTHRow( $myCoursesWithFeedback[0], 'info', $hide );
    foreach( $myCoursesWithFeedback as $course )
    {
        $cid = $course[ 'course_id' ];
        $table .= arrayToRowHTML( $course, 'info', $hide );
    }
    $table .= "</table>";
    echo $table;
}
else
    echo printInfo( "I could not find any course belonging to '$user' in my database." );
echo '</div>';

echo goBackToPageLink( "user/home", "Go back" );

?>

<!-- Modify form for user -->
<script type="text/javascript" charset="utf-8">
$(document).ready( function() {
    $('#course_registration tbody tr td select[name="type"] option[value="DROPPED"]').remove();
});
</script>
