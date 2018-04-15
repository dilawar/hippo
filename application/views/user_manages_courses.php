<?php

require_once BASEPATH . 'autoload.php';

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
            , array( 'student_id' => $_SESSION[ 'user' ], 'year' => $year
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
echo '<h1>Course enrollment</h1>';

$today = strtotime( 'today' );

// Running course this semester.
$courseMap = array( );
$options = array( );

$blockedCourses = array( );
foreach( $runningCourses as $c )
{
    $cstart = strtotime( $c[ 'start_date' ] );

    // Registration is allowed within 4 weeks.
    if( $today > strtotime( '-14 day', $cstart) && $today <= strtotime( '+14 day', $cstart ) )
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

// Get the list of valid courses.
echo noteWithFAIcon(
    "Courses will be visible in registration form from -2 weeks to +2 weeks from the
    <tt>start date</tt>."
    , "fa-bell"
    );

echo "<h2>Registration form</h2>";
$courseSelect = arrayToSelectList( 'course_id', $options, $courseMap );
$default = array( 'student_id' => $_SESSION[ 'user' ]
                , 'semester' => $sem
                , 'year' => $year
                , 'course_id' => $courseSelect
                );

// TODO: Custom table for registration.
$form = '<form method="post" action="user_manages_courses_action.php">';
$form .= dbTableToHTMLTable( 'course_registration'
            , $default
            , 'course_id:required,type'
            , 'Submit'
            , 'status,registered_on,last_modified_on,grade,grade_is_given_on'
        );
$form .= '</form>';
echo $form;

/* @name Show the registered courses. */
$tofilter = 'student_id';

$action = 'drop';
if( count( $myCourses ) > 0 )
{
    echo "<h1>You are registered for following courses for $sem $year</h1>";

    // Show user which slots have been blocked.
    echo alertUser(
        "You have registered for courses running on following slots: "
        . implode( ", ", $mySlots )
        . ". <br> All courses running these slots will not appear in your
        registration form."
        );
}

if( count( $myCourses ) > 0 )
{
    echo ' <br />';
    // Dropping policy
    echo noteWithFAIcon( " <strong>Policy for dropping courses </strong> <br />
        Upto 30 days from starting of course, you are free to drop a course.
        After that, you need to write to your course instructor and academic office.
        ", "fa-bell-o" );
}

$count = 0;
$table = '<table class="1">';
$table .= '<tr>';
foreach( $myCourses as $c )
{
    $action = 'drop';
    // Break at 3 courses.
    if( $count % 3 == 0 )
        $table .= '</tr><tr>';

    $table .= '<td>';
    $table .= '<form method="post" action="user_manages_courses_action.php">';

    $cid = $c[ 'course_id' ];
    $course = getTableEntry( 'courses_metadata', 'id', array( 'id' => $cid ) );
    if( ! $course )
        continue;

    // If more than 30 days have passed, do not allow dropping courses.
    if( strtotime( 'today' ) >
        strtotime( '+30 day',strtotime($runningCourses[ $cid][ 'start_date' ])))
        $action = '';

    // TODO: Don't show grades unless student has given feedback.
    $tofilter = 'student_id,registered_on,last_modified_on';
    if( strlen( $c[ 'grade' ] ) == 0 )
        $tofilter .= ',grade,grade_is_given_on';

    $table .= dbTableToHTMLTable( 'course_registration', $c, '', $action, $tofilter );
    $table .= '</form>';
    $table .= '</td>';

    $count += 1;
}
$table .= '</tr></table>';
echo $table;
echo ' <br /> ';

echo '<h1> My courses </h1>';

$user = whoAmI( );
$myAllCourses = getTableEntries( 'course_registration'
    , 'year, semester'
    , "student_id='$user' AND status='VALID'"
    );

$hide = 'student_id,status,last_modified_on';

if( count( $myAllCourses ) > 0 )
{
    $table = '<table class="info sorttable">';
    // echo arrayToTHRow( $myAllCourses[0], 'info', $hide );
    foreach( $myAllCourses as $course )
    {
        $cid = $course[ 'course_id' ];
        $cname = getCourseName( $cid );
        $course[ 'course_id' ] .= " <br /> $cname";
        $table .= arrayToRowHTML( $course, 'info', $hide );
    }
    $table .= "</table>";
    echo $table;
}
else
    echo printInfo( "I could not find any course." );

echo goBackToPageLink( "user/home", "Go back" );

?>
