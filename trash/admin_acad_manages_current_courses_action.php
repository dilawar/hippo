<?php

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN', 'ADMIN' ) );

include_once 'methods.php';
include_once 'database.php';
include_once 'tohtml.php';


if( $_POST['response'] == 'DO_NOTHING' )
{
    echo printInfo( "User said do nothing.");
    goBack( "admin_acad_manages_current_courses.php", 0 );
    exit;
}
else if( $_POST['response'] == 'delete' )
{
    // We may or may not get email here. Email will be null if autocomplete was
    // used in previous page. In most cases, user is likely to use autocomplete
    // feature.
    if( strlen($_POST[ 'id' ]) > 0 )
    {
        $res = deleteFromTable( 'courses', 'id', $_POST );
        if( $res )
        {
            deleteBookings( $_POST[ 'id' ] );
            echo printInfo( "Successfully deleted entry" );

            // Remove all enrollments.
            $year = getCurrentYear( );
            $sem = getCurrentSemester( );

            $res = deleteFromTable( 'course_registration', 'semester,year,course_id'
                , array( 'year' => $year, 'semester' => $sem, 'course_id' => $_POST[ 'course_id'] )
                );
            if( $res )
            {
                printInfo( "Successfully removed enrollments." );
                goBack( 'admin_acad_manages_current_courses.php', 2 );
                exit;
            }
        }
        else
            echo minionEmbarrassed( "Failed to delete course from the" );
    }
}
else // Add or Update here.
{
    $_POST[ 'semester' ] = getSemester(  $_POST[ 'end_date' ] );
    $_POST[ 'year' ] = getYear( $_POST[ 'end_date' ]  );

    // Check if any other course is running on this venue/slot between given
    // dates.
    $startDate = $_POST[ 'start_date' ];
    $endDate = $_POST[ 'end_date' ];

    $sem = getSemester( $endDate );
    $year = getYear( $endDate );

    $_POST[ 'semester' ] = $sem;
    $_POST[ 'year' ] = $year;

    $coursesAtThisVenue = getCoursesAtThisVenueSlotBetweenDates(
        $_POST[ 'venue' ], $_POST[ 'slot' ], $startDate, $endDate
    );

    $collisionCourses = array_filter(
            $coursesAtThisVenue
            , function( $c ) { return $c['course_id'] != $_POST[ 'course_id' ]; }
            );

    $updatable = 'semester,year,start_date,end_date,slot,venue,note,url,ignore_tiles';
    if( count( $collisionCourses ) > 0 )
    {
        foreach( $collisionCourses as $cc )
        {
            echo printWarning( "Following course is already assigned at this slot/venue" );
            echo arrayToVerticalTableHTML( $cc, 'info' );
            echo '<br>';
        }
        echo goBackToPageLink( "admin_acad_manages_current_courses.php" );
        exit;
    }

    // No collision. Add or update now.
    if ( $_POST[ 'response' ] == 'Add' )
    {
        echo printInfo( "Adding a new course in current course list" );

        if( strlen( $_POST[ 'course_id' ] ) > 0 )
        {
            $id = getCourseInstanceId( $_POST[ 'course_id' ], $sem, $year );
            $_POST[ 'id' ] = $id;

            $res = insertIntoTable('courses',"id,course_id,$updatable", $_POST);

            if( ! $res )
                echo printWarning( "Could not add course to list" );
            else
            {
                $res = addCourseBookings( $_POST[ 'id' ] );
                goBack( 'admin_acad_manages_current_courses.php', 2 );
                exit;
            }
        }
        else
            echo printWarning( "Could ID can not be empty" );
    }
    else if ( $_POST[ 'response' ] == 'Update' )
    {
        $res = updateTable( 'courses', 'course_id', $updatable , $_POST );
        if( $res )
        {
            $res = updateBookings( $_POST[ 'id' ] );
            echo printInfo( 'Updated course' );
            goBack( 'admin_acad_manages_current_courses.php', 2);
            exit;
        }
    }
}

echo goBackToPageLink( 'admin_acad_manages_current_courses.php', 'Go back' );


?>
