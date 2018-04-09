<?php
include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

//include_once 'check_access_permissions.php';
//mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN' ) );

echo userHTML( );

$res = getNextSemester( );
$sem = $res['semester'];
$year = $res[ 'year' ];

echo printInfo( "You are scheduling for semester: $sem, $year" );

$upcomingCourses = getSemesterCourses( $year, $sem );

foreach( $upcomingCourses as $course )
{
    echo arrayToTableHTML( $course, 'info' );
}


?>
