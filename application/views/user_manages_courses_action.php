<?php

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'USER' ) );

include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

echo userHTML( );

$sem = getCurrentSemester( );
$year = getCurrentYear( );

// User courses.
$myCourses = getMyCourses( $sem, $year, $user = $_SESSION[ 'user' ] );

// Running course this semester.
$runningCourses = getSemesterCourses( $year, $sem );

$courseMap = array( );
$options = array( );

foreach( $runningCourses as $c )
{
    $cid = $c[ 'course_id' ];
    $options[] = $cid ;
    $courseMap[ $cid ] = $cid . ' - ' . getCourseName( $cid );
}

$courseSelect = arrayToSelectList( 'courses', $options, $courseMap );

// Manage courses. Add into course_registration.

if( strtolower($_POST[ 'response' ]) == 'submit' )
{
    $_POST[ 'last_modified_on' ] = dbDateTime( 'now' );
    $_POST[ 'registered_on' ] = dbDateTime( 'now' );

    $res = insertIntoTable( 'course_registration'
                , 'student_id,semester,year,type,course_id,registered_on,last_modified_on'
                , $_POST );

    if( $res )
    {
        echo printInfo( "You are successfully registered for the course" );
        goBack( 'user_manages_courses.php', 1 );
        exit;
    }
    else
    {
        echo minionEmbarrassed( "Failed to register you for the course." );
        echo printWarning( "Most likely you are already registered for this course" );
    }

}
else if( $_POST[ 'response' ] == 'drop' )
{
    // Drop this course.
    echo printInfo( "Dropping course " . $_POST[ 'course_id' ] );

    $_POST[ 'student_id' ] = $_SESSION[ 'user' ];

    $res = deleteFromTable( 'course_registration'
                        , 'student_id,semester,year,course_id'
                        , $_POST );

    $course = getTableEntry( 'course_registration'
                        , 'student_id,semester,year,course_id'
                        , $_POST );

    if( ! $course )
        echo printInfo( "Successfully dropped course." );
    else
        echo minionEmbarrassed( "Failed to drop course" );
}
else
    echo alertUser( 'Unknown type of request ' . $_POST[ 'response' ] );

echo goBackToPageLink( "user_manages_courses.php", "Go back" );

?>
