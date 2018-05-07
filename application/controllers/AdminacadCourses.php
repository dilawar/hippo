<?php

trait AdminacadCourses 
{

    public function courses_action($arg = '')
    {
        $response = strtolower($_POST['response']);

        if( $response == 'do_nothing' )
        {
            flashMessage( "User said do nothing.");
            goBack( "adminacad/courses" );
        }
        else if( $response == 'delete' )
        {
            // We may or may not generate here. Email will be null if autocomplete was
            // used in previous page. In most cases, user is likely to use autocomplete
            // feature.
            if( strlen($_POST[ 'id' ]) > 0 )
            {
                $msg = '';
                $res = deleteFromTable( 'courses', 'id', $_POST );
                if( $res )
                {
                    deleteBookings( $_POST[ 'id' ] );
                    $msg .= "Successfully deleted entry";

                    // Remove all enrollments.
                    $year = getCurrentYear( );
                    $sem = getCurrentSemester( );

                    $res = deleteFromTable( 'course_registration', 'semester,year,course_id'
                        , array( 'year' => $year, 'semester' => $sem, 'course_id' => $_POST[ 'course_id'] )
                    );
                    if( $res )
                    {
                        $msg .= "Successfully removed  all enrollments. I have not notifiied the students.";
                        flashMessage($msg);
                        redirect( 'adminacad/courses' );
                        return;
                    }
                }
                echo printErrorSevere( "Failed to delete course from the" );
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

            $msg = '';

            $updatable = 'semester,year,start_date,end_date,slot,venue,note,url,ignore_tiles';
            if( count( $collisionCourses ) > 0 )
            {
                foreach( $collisionCourses as $cc )
                {
                    $msg .= "Following course is already assigned at this slot/venue";
                    $msg .= arrayToVerticalTableHTML( $cc, 'info' );
                    $msg .= '<br>';
                }

                echo printErrorSevere( $msg );
                redirect( "adminacad/courses" );
                return;
            }

            // No collision. Add or update now.
            if ( $response == 'Add' )
            {
                $msg .= "Adding a new course in current course list" ;
                if( strlen( $_POST[ 'course_id' ] ) > 0 )
                {
                    $id = getCourseInstanceId( $_POST[ 'course_id' ], $sem, $year );
                    $_POST[ 'id' ] = $id;
                    $res = insertIntoTable('courses',"id,course_id,$updatable", $_POST);
                    if( ! $res )
                        $msg .= printWarning( "Could not add course to list" );
                    else
                    {
                        $res = addCourseBookings( $_POST[ 'id' ] );
                        flashMessage("Successfully added course. Blocked venue as well.");
                        redirect( 'adminacad/courses' );
                        return ;
                    }
                }
                else
                    $msg .= printWarning( "Could ID can not be empty" );
            }
            else if ( $_POST[ 'response' ] == 'Update' )
            {
                $res = updateTable( 'courses', 'course_id', $updatable , $_POST );
                if( $res )
                {
                    $res = updateBookings( $_POST[ 'id' ] );
                    $msg .= printInfo( 'Updated course ' . $_POST['course_id'] . '.' );
                    flashMessage( $msg );
                    redirect('adminacad/courses');
                    return;
                }
            }
        }
    }
}

?>
