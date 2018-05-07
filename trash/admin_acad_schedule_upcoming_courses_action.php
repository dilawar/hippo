<?php

include_once 'methods.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAnyOfTheseRoles( array( "AWS_ADMIN" ) );

/* POST */
if( __get__( $_POST, 'response', '' ) == 'Add' )
{
    $cid = __get__( $_POST, 'course_id', 0 );
    if( strlen( trim($cid) ) > 0 )
    {
        echo printInfo( "Adding preference for $cid" );
        $_POST[ 'course_id' ] = getCourseCode( $_POST[ 'course_id' ] );
        insertOrUpdateTable( 'upcoming_course_schedule'
                , 'weight,course_id,slot,venue'
                , 'course_id,slot,venue,weight,comment'
                , $_POST
            );
        goToPage( "admin_acad_schedule_upcoming_courses.php", 1 );
        exit;
    }
}
else if( __get__( $_POST, 'response', '' ) == 'Delete' )
{
    if( __get__( $_POST, 'id', 0 ) > 0 )
    {
        $_POST[ 'status' ] = 'DELETED';
        updateTable( 'upcoming_course_schedule', 'id,status', $_POST );
    }
    goToPage( "admin_acad_schedule_upcoming_courses.php", 1);
    exit;
}
else if( __get__( $_POST, 'response', '' ) == 'schedule_courses' )
{
    echo printInfo( "Computing best schedule" );
    $res = shell_exec( __DIR__ . '/compute_course_schedule.py' );
    echo printInfo( $res );
    goToPage( "admin_acad_schedule_upcoming_courses.php", 1);
    exit;
}
else
    echo printInfo( "Unknown request " . $_POST[ 'response' ] );

echo goBackToPageLink( 'admin_acad_schedule_upcoming_courses.php', 'Go back' );

?>
