<?php

trait AdminacadCourses 
{

    // VIEWS.
    public function courses( )
    {
        $this->load_adminacad_view( 'admin_acad_manages_current_courses' );
    }

    public function allcourses( )
    {
        $this->load_adminacad_view( 'admin_acad_manages_courses' );
    }

    public function slots()
    {
        $this->load_adminacad_view( 'admin_acad_manages_slots' );
    }

    public function jc()
    {
        $this->load_adminacad_view( 'admin_acad_manages_jc' );
    }

    public function jc_admins( )
    {
        $this->load_adminacad_view( 'admin_acad_manages_jc_admins' );
    }

    public function feedbackquestionnaire()
    {
        $this->load_adminacad_view( 'admin_acad_manages_course_questionnaire' );
    }

    function goBackToReferrer( $fallback )
    {
        // Send to referrer.
        if($this->agent->is_referral())
            redirect($this->agent->referrer());
        redirect( $fallback );
    }

    // ACTIONS.
    public function all_courses_action( $arg = '' )
    {
        // Instructor extras has to be reformatted.
        $extraInstTxt = '';
        if( is_array( __get__( $_POST, 'more_instructors', false) ) )
            $extraInstTxt = implode(',', $_POST[ 'more_instructors' ] );

        // Append to already existibg extra instructiors.
        if( __get__( $_POST, 'instructor_extras', '' ) && $extraInstTxt )
            if( $extraInstTxt )
                $_POST[ 'instructor_extras' ] .= ',' . $extraInstTxt;
        else
            if ( $extraInstTxt )
                $_POST[ 'instructor_extras' ] =  $extraInstTxt;

        if( $_POST['response'] == 'delete' )
        {
            // We may or may not get email here. Email will be null if autocomplete was 
            // used in previous page. In most cases, user is likely to use autocomplete 
            // feature.


            if( strlen($_POST[ 'id' ]) > 0 )
            {
                $res = deleteFromTable( 'courses_metadata', 'id', $_POST );
                if( $res )
                    echo flashMessage( "Successfully deleted entry" );
                else
                    echo printWarning( "Failed to delete speaker from database" );
            }
        }
        else if ( $_POST[ 'response' ] == 'Add' ) 
        {
            echo printInfo( "Adding a new course in current course list" );
            if( strlen( $_POST[ 'id' ] ) > 0 )
            {
                $res = insertIntoTable( 
                    'courses_metadata'
                    , 'id,name,credits,description' 
                        .  ',instructor_1,instructor_2,instructor_3'
                        . ',instructor_4,instructor_5,instructor_6,instructor_extras,comment'
                    , $_POST 
                    );

                if( ! $res )
                    echo printWarning( "Could not add course to list" );
                else
                    echo flashMessage( "Successfully added new course." );
            }
            else
                echo printWarning( "Course ID can not be empty!" );
            
        }
        else if ( $_POST[ 'response' ] == 'Update' ) 
        {
            $res = updateTable( 'courses_metadata'
                    , 'id'
                    , 'name,venue,credits,description' 
                        .  ',instructor_1,instructor_2,instructor_3'
                        . ',instructor_4,instructor_5,instructor_6,instructor_extras,comment'
                    , $_POST 
                    );

            if( $res )
                echo flashMessage( 'Updated course : ' . $_POST[ 'id' ] );
        }
        redirect( "adminacad/allcourses" );
    }

    // Current running courses.
    public function courses_action($arg = '')
    {
        $response = strtolower($_POST['response']);

        // If no venue is found, the leave it empty.
        if( ! __get__( $_POST, 'venue', '' ) )
            $_POST[ 'venue' ] = '';

        if( $response == 'do_nothing' )
        {
            flashMessage( "User said do nothing.");
            redirect( "adminacad/courses" );
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
            $required =  array( "start_date", "end_date" );
            foreach( $required as $k )
            {
                if( ! __get__($_POST, $k, '' ) )
                {
                    printErrorSevere( "Incomplete entry. $k is not found." );
                    redirect( 'adminacad/courses' );
                    return;
                }
            }

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

                printErrorSevere( $msg );
                redirect( "adminacad/courses" );
                return;
            }

            // No collision. Add or update now.
            if ( $response == 'add' )
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
                    }
                }
                else
                    $msg .= printWarning( "Could ID can not be empty" );
            }
            else if ( $response == 'update' )
            {
                $res = updateTable( 'courses', 'course_id', $updatable , $_POST );
                if( $res )
                {
                    $res = updateBookings( $_POST[ 'id' ] );
                    $msg .= printInfo( 'Updated running course ' . $_POST['course_id'] . '.' );
                    flashMessage( $msg );
                }
            }
            else
                printWarning( "Unknown task '$response'. " );

        }
        redirect( "adminacad/courses" );
        return;
    }

    public function slots_action( $arg = '' )
    {
        $response = strtolower($_POST['response']);
        if( $response == 'do_nothing' )
        {
            flashMessage( "User said do nothing.");
        }
        else if( $response == 'delete')
        {
            // We may or may not get email here. Email will be null if autocomplete was 
            // used in previous page. In most cases, user is likely to use autocomplete 
            // feature.
            if( strlen($_POST[ 'id' ]) > 0 )
                $res = deleteFromTable( 'slots', 'id', $_POST );
            if( $res )
                flashMessage( "Successfully deleted entry." );
            else
                printWarning( "Failed to delete slot from database." );
        }
        else   // update
        {
            // Get group id of slot.
            $gid = slotGroupId( $_POST['id'] );
            $_POST[ 'groupid' ] = $gid;

            $res = insertOrUpdateTable( 'slots'
                    , 'id,groupid,day,start_time,end_time'
                    , 'day,start_time,end_time'
                    , $_POST 
                );

            if( $res )
                flashMessage( 'Updated/Inserted slot.' );
        }
        redirect("adminacad/slots");
    }

    public function addquestion( )
    {
        $res = insertIntoTable( 'question_bank'
            , 'id,category,subcategory,question,choices,status,last_modified_on'
           ,  $_POST );
        if( ! $res )
            printWarning( "Failed to add question to database." );
        else
            flashMessage( "Successfully added question to database." );

        // Send to referrer.
        $this->goBackToReferrer( "adminacad/feedbackquestionnaire" ); 
    }

    public function deletequestion( $qid )
    {
        $res = deleteFromTable( 'question_bank', 'id', array( 'id' => $qid ) );
        if(! $res)
            printWarning( "Failed to deleted question from database." );
        else
            flashMessage( "Successfully deleted question." );

        $this->goBackToReferrer( "adminacad/feedbackquestionnaire" ); 
    }
}

?>
