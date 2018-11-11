<?php
require_once BASEPATH . 'autoload.php';

// Local function.
function feedbackForm(string $year, string $sem, string $cid ) : array
{
    // DO NOT use ' to delimit the string; it wont work very well inside table.
    $numUnanswered = numQuestionsNotAnswered(whoAmI(), $year, $sem, $cid);
    $form =  "<form action='".site_url("user/givefeedback/$cid/$sem/$year")."' method='post'>";
    $form .= "<button style='float:right' name='response' value='submit'>Feeback ("
                . $numUnanswered . " unanswered.)</button>";
    $form .= "</form>";
    return ['html'=>$form, 'num_unanswered'=>$numUnanswered];
}

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
$myCourses = getMyCourses( $sem, $year, $user = whoAmI() );

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
            , array( 'student_id' => whoAmI(), 'year' => $year
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
$courseMap = array( );
$options = array( );
$blockedCourses = array( );
foreach( $runningCourses as $c )
{
    $cstart = strtotime( $c[ 'start_date' ] );

    // Registration is allowed within 3 weeks.
    if( $today <= (strtotime($cstart) + 3*7*24*3600) )
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
            $courseMap[ $cid ] = getCourseName( $cid ) .
                " (slot " . getCourseSlotTiles( $c ) . ")";
        }
    }
}


echo "<h2>Registration form</h2>";
$courseSelect = arrayToSelectList( 'course_id', $options, $courseMap );
$default = array( 'student_id' => whoAmI()
                , 'semester' => $sem
                , 'year' => $year
                , 'course_id' => $courseSelect
                );

// echo alertUser( "Any course running on already registered slot will not appear in your
    // registration form."
    // );
echo alertUser(
    "Courses will be visible in registration form upto 21 days from <tt>start date</tt>."
    , false
    );

echo '<form method="post" action="manage_course/register">';
echo dbTableToHTMLTable( 'course_registration'
    , $default
    , 'course_id:required,type:required'
    , 'Submit'
    , 'status,registered_on,last_modified_on,grade,grade_is_given_on'
    );
echo '</form>';

// Show user which slots have been blocked.


/**
    * @name Show the registered courses.
    * @{ */
/**  @} */

$tofilter = 'student_id';
$action = 'drop';


if(count($myCourses) > 0)
{
    echo ' <br />';
    // Dropping policy
    echo noteWithFAIcon( 
        colored("<strong>Policy for dropping courses </strong><br />", "blue") .
        "Upto 30 days from starting of course, you are free to drop a course.
        After that, you need to contact appropriate authority."
        , "fa-bell-o" );
}

$count = 0;

if(count($myCourses) > 0)
{
    echo "<h1>You are registered for following " . count($myCourses) . " courses for $sem $year</h1>";
}

echo '<div style="font-size:small">';
echo '<table border="1pt dotted blue">';
echo '<tr>';
foreach($myCourses as &$c)
{
    $action = 'drop';

    // Break at 3 courses.
    if( $count % 3 == 0 )
        echo '</tr><tr>';

    $cid = $c[ 'course_id' ];
    $course = getTableEntry( 'courses_metadata', 'id', array( 'id' => $cid ) );

    if(!$course)
        continue;

    // If more than 30 days have passed, do not allow dropping courses.
    $cstartDate = $runningCourses[$cid]['start_date'];
    if(strtotime('today') > (strtotime($cstartDate)+30*24*86400))
        $action = '';

    // TODO: Don't show grades unless student has given feedback.
    $tofilter = 'student_id,registered_on,last_modified_on';

    //// Show grade if it is available and user has given feedback.
    //if( __get__($c, 'grade', 'X' ) != 'X' )
    //{
    //    if($numUnanswered > 0 )
    //    {
    //        $c['grade'] = colored( "Grade is available.<br />
    //            Feedback is due. $numUnanswered unanswered.", 'darkred' 
    //        );
    //    }
    //}
    echo '<td>';

    // Show form.
    echo '<table><tr><td>';
    echo '<form method="post" action="'.site_url("user/manage_course/$action").'">';
    echo dbTableToHTMLTable( 'course_registration', $c, '', $action, $tofilter );
    echo '</form>';
    echo '</td>';

    // If feedback is not given for this course, display a button.
    $numUnanswered = 0;
    $res = feedbackForm($year, $sem, $cid, $numUnanswered );
    if( $res['num_unanswered']> 0 )
    {
        // Feeback form
        $sem = $c['semester'];
        $year = $c['year'];
        echo "<tr><td> " . $res['html'] . "</td></tr>";
    }
    else if( $numUnanswered == 0 )
    {
        // All questions have been answered
        $showFeedbackLink = "<a target='Feedback'  
            href='".site_url( "user/seefeedback/$cid/$sem/$year" ) . "'>Show Feedback</a>";
        echo "<tr><td colspan=2><strong>Feedback has been given. </strong> <br /> $showFeedbackLink
            </td></tr>";
    }
    echo '</table>';

    // Next col of all courses.
    echo '</td>';
    $count += 1;
}
echo '</table>';
echo '</div>';

echo ' <br /> ';
echo goBackToPageLink( "user/home", "Go back" );

echo '<h1>My courses</h1>';

$user = whoAmI( );
$myAllCourses = getTableEntries( 'course_registration'
    , 'year, semester'
    , "student_id='$user' AND status='VALID'"
    );

$hide = 'student_id,status,last_modified_on';

// Add feedback URL as well.
foreach( $myAllCourses as &$course )
{
    $cid = $course['course_id'];
    $res = feedbackForm( $year, $sem, $cid, $numUnanswered );
    $numUnanswered = $res['num_unanswered'];
    $course['Feedback'] = $res['html'];
}

if( count( $myAllCourses ) > 0 )
{
    $table = '<table class="info sorttable">';
    $table .= arrayToTHRow( $myAllCourses[0], 'info', $hide );
    foreach( $myAllCourses as $course )
    {
        $cid = $course[ 'course_id' ];

        if(__get__($course, 'grade', 'X') != 'X')
            if( in_array($cid, $noFeedback))
                $course['grade'] = colored('Feedback is due.', 'darkred');

        $cname = getCourseName( $cid );
        $course[ 'course_id' ] .= " <br /> $cname";
        $table .= arrayToRowHTML( $course, 'info', $hide );
    }
    $table .= "</table>";
    echo $table;
}
else
    echo printInfo( "I could not find any course belonging to '$user' in my database." );

echo goBackToPageLink( "user/home", "Go back" );

?>
