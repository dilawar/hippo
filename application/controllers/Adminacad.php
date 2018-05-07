<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';
require_once __DIR__.'/AdminacadCourses.php';
require_once __DIR__.'/AdminacadJC.php';

class Adminacad extends CI_Controller
{
    use AdminacadCourses;
    use AdminacadJC;

    public function load_adminacad_view( $view, $data = array() )
    {
        $data['controller'] = 'adminacad';
        $this->template->set( 'header', 'header.php' );
        $this->template->load( $view, $data );
    }

    // VIEWS ONLY.
    public function index()
    {
        $this->load_adminacad_view( 'admin_acad.php' );
    }

    public function home()
    {
        $this->index();
    }

    public function upcoming_aws( $arg = '' )
    {
        $this->load_adminacad_view( 'admin_acad_manages_upcoming_aws' );
    }

    public function scheduling_request( )
    {
        $this->load_adminacad_view( 'admin_acad_manages_scheduling_request');
    }

    public function add_aws_entry( )
    {
        $this->load_adminacad_view( 'admin_acad_add_aws_entry' );
    }

    public function requests( )
    {
        $this->load_adminacad_view( "admin_acad_manages_requests" );
    }

    public function email_and_docs( )
    {
        $this->load_adminacad_view( "admin_acad_email_and_docs" );
    }

    public function enrollments()
    {
        $this->load_adminacad_view( 'admin_acad_manages_enrollments' );
    }

    public function grades( )
    {
        $this->load_adminacad_view('admin_acad_manages_grades');
    }

    public function gradecourse( $year, $sem, $course_id )
    {
        $data = array('course_id' => $course_id, 'year' => $year, 'semester' => $sem);
        $this->load_adminacad_view('admin_acad_grade_course.php', $data );
    }

    // VIEWS are here. Actions are in AdminacadCourses
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

    // ACTION.
    public function next_week_aws_action( )
    {
        $this->execute_aws_action( $_POST['response'], 'upcoming_aws' );
    }

    public function updateaws($arg = '')
    {
        $response = strtolower($_POST['response']);
        if($response == 'do_nothing')
        {
            flashMessage( 'User cancelled last action.' );
            redirect( 'adminacad/home' );
        }
        elseif($response == 'delete' )
        {
            $id = $_POST['id'];
            $res = deleteFromTable( 'annual_work_seminars', 'id', $_POST );
            if( $res )
                flashMessage( "Successfully deleted AWS entry $id" );
        }
        elseif($response == 'update' )
        {
            $id = $_POST['id'];
            $res = updateTable( 'annual_work_seminars', 'id', 'is_presynopsis_seminar,title,abstract', $_POST );
            if( $res )
                flashMessage( "Updated successfully AWS entry id $id." );
        }
        elseif($response == 'edit' )
        {
            // This is a view.
            $this->load_adminacad_view("admin_acad_edit_aws", $_POST );
            return;
        }
        else
            flashMessage( "Request $response is not supported yet." );

        redirect( 'adminacad/home' );
    }

    public function update_aws_speaker($arg = '')
    {
        $this->load_adminacad_view( 'admin_acad_update_user' );
    }

    // VIEWS WITH ACTION.
    function schedule_upcoming_aws( $arg = '' )
    {
        flashMessage( json_encode( $_POST ));
        $method = $_POST['method'];
        $ret = rescheduleAWS($method);
        if($ret)
            flashMessage("Failed to compute schedule.");
        else
            flashMessage('Sucessfully computed schedule.');
        redirect( 'adminacad/upcoming_aws');
    }

    public function update_user( )
    {
        $toUpdate = 'title,joined_on,eligible_for_aws,status,pi_or_host';
        $res = updateTable( 'logins', 'login', $toUpdate, $_POST );
        if( $res )
        {
            $login = $_POST[ 'login' ];
            flashMessage( "Successfully updated profile. " );

            // Get previous status of student.
            $msg = initUserMsg( $login );
            $msg .= "<p>Your Hippo profile has been updated by Academic Admin.</p>";
            $msg .= "<p>You current profile is following </p>";
            $msg .= arrayToVerticalTableHTML(
                        getTableEntry( 'logins', 'login', array( 'login' => $login ) )
                        , 'profile'
                    );

            $msg .= "<p>If there is any mistake, please contact academic office. You can
                    also update your profile after login to Hippo
                    </p>";

            $subject = "Your Hippo profile has been updated by admin";
            $to = getLoginEmail( $login );
            $cc = 'hippo@lists.ncbs.res.in';
            sendHTMLEmail( $msg, $subject, $to, $cc );
            // Rerun the scheduling script every time a change is made.
            rescheduleAWS( );
            redirect( 'adminacad/home');
        }
    }

    public function update_aws_entry( )
    {
        $res = updateTable( 'upcoming_aws', 'id', 'abstract,title,is_presynopsis_seminar', $_POST );
        if( $res )
            flashMessage( "Successfully updated abstract of upcoming AWS entry" );
        else
            flashMessage( "I could not update title/abstract.", 'warning' );

        redirect( 'adminacad/upcoming_aws');
    }


    public function assignaws( )
    {
        $speaker = explode( '@', $_POST[ 'speaker' ] )[0];
        $date = $_POST[ 'date' ];
        if(  $speaker && getLoginInfo( $speaker ) && strtotime( $date ) > strtotime( '-7 day' ) )
        {
            $aws = getUpcomingAWSOfSpeaker( $speaker );
            if( $aws )
                flashMessage( "$speaker already has AWS scheduled. Doing nothing." );
            else
            {
                $awsID = acceptScheduleOfAWS( $speaker, $date );
                if( $awsID > 0 )
                {
                    flashMessage( "Successfully assigned" );
                    if( $response == 'Assign' )
                        rescheduleAWS( );

                    // Send email to user.
                    $res = notifyUserAboutUpcomingAWS( $_POST[ 'speaker' ], $_POST[ 'date' ], $awsID );
                    if(! $res )
                        flashMessage( "Failed to send email to user" );
                }
                else
                    flashMessage( "Invalid entry. Probably date ('$date') is in past." );
            }
        }
        else
            printWarning( "Invalid speaker '$speaker' or date '$date' 
                is in past.  Could not assign AWS.");

        redirect( "adminacad/upcoming_aws" );
    }

    public function execute_aws_action($response, $ref = 'upcoming_aws' )
    {
        if( ! $response)
        {
            flashMessage( 'Empty response from user.', 'warning');
            redirect("adminacad/$ref");
        }

        else if( $response == 'format_abstract' )
        {
            $this->load_adminacad_view( 'admin_acad_manages_upcoming_aws_reformat.php');
        }
        else if( $response == 'removespeaker' )
        {
            $speaker = $_POST['speaker'];
            assert( $speaker );
            $res = removeAWSSpeakerFromList( $_POST[ 'speaker' ] );
            if( $res )
            {
                rescheduleAWS( );
                flashMessage( "Successfully removed $speaker" );
            }
            else
                flashMessage( "Could not remove $speaker.", "warning");

            redirect( "adminacad/$ref" );
        }
        else if( $response == 'delete' )
        {
            $speaker = $_POST['speaker'];
            $date = $_POST['date'];
            $res = clearUpcomingAWS( $speaker, $date );
            if( $res )
            {
                flashMessage( "Successfully cleared upcoming AWS of $speaker on $date." );

                $admin = whoAmI();
                // Notify the hippo list.
                $msg = "<p>Hello " . loginToHTML( $_POST[ 'speaker' ] ) . "</p>";
                $msg .= "<p>
                    Your upcoming AWS schedule has been removed by Hippo admin ($admin).
                     If this is a  mistake, please write to acadoffice@ncbs.res.in
                    as soon as possible.
                    </p>
                    <p> The AWS schedule which is removed is the following </p>
                    ";

                $data = array( );

                $data[ 'speaker' ] = $_POST[ 'speaker' ];
                $data[ 'date' ] = $_POST[ 'date' ];

                $msg .= arrayToVerticalTableHTML( $data, 'info' );

                sendHTMLEmail( $msg
                    , "Your AWS schedule has been removed from upcoming AWS list"
                    , $to = getLoginEmail( $_POST[ 'speaker' ] )
                    , $cclist = "acadoffice@ncbs.res.in,hippo@lists.ncbs.res.in"
                );
                redirect( "adminacad/$ref");
            }
        }
        else if( $response == "do_nothing" )
        {
            flashMessage( "User cancelled the previous operation.");
            redirect( "adminacad/$ref");
        }
        else
        {
            flashMessage( "Not yet implemented $response.");
            redirect( "adminacad/$ref");
        }
    }

    // Courses 
    public function change_enrollement( )
    {
        $response = strtolower($_POST['response']);
        $user = $_POST[ 'student_id' ];
        $course = $_POST[ 'course_id' ];
        $sem = $_POST[ 'semester' ];
        $year = $_POST[ 'year' ];

        $_POST['status'] = 'VALID';

        if( $response == 'drop' )
            $_POST['status'] = 'DROPPED';
        elseif($response == 'audit')
            $_POST['type'] = 'AUDIT';
        elseif($response == 'credit')
            $_POST['type'] = 'CREDIT';

        $res = updateTable( 'course_registration'
            , 'student_id,course_id,year,semester', 'status,type', $_POST
            );

        if( $res )
        {
            if($response == 'drop')
                flashMessage("Successfully dropped  $user from $course $sem/$year." );
            else
                flashMessage("Successfully changes registration type $user, $course ($sem/$year) to $response.");
        }
        else
            printWarning( "Failed to execute your wish!" );

        redirect( 'adminacad/enrollments' );
    }

    public function quickenroll( )
    {
        $enrolls = explode( PHP_EOL, $_POST[ 'enrollments' ] );
        foreach( $enrolls as $i => $en )
        {
            $l = splitAtCommonDelimeters( $en, ':' );

            $email = $l[0];
            if( count( $l ) < 2 )
            {
                printWarning( "Partial information in <tt>$en</tt>. Missing CREDIT/AUDIT info. 
                    Assuming <tt>CREDIT</tt>." 
                    );
                $etype = 'CREDIT';
            }
            else
                $etype = $l[1];


            if( ! in_array( $etype, array( 'AUDIT', 'CREDIT' ) ) )
            {
                echo printWarning( "Unknown registration type: '$etype'. Ignoring ..." );
                continue;
            }

            $login = getLoginByEmail( $email );
            if( ! $login )
            {
                echo printWarning( "No valid login found for $email. Ignoring ... " );
                continue;
            }

            $data = array( );
            $data[ 'registered_on' ] = dbDateTime( 'now' );
            $data[ 'last_modified_on' ] = dbDateTime( 'now' );
            $data[ 'student_id' ] = $login;
            $data[ 'type' ] = $etype;
            $data[ 'status' ] = 'VALID';
            $courseId = $_POST[ 'course_id' ];
            $data = array_merge( $_POST, $data );

            try {
                $res = insertOrUpdateTable( 'course_registration'
                    , 'student_id,course_id,year,semester'
                    , 'student_id,course_id,status,type,year,semester,registered_on,last_modified_on'
                    , $data
                );
            } catch (Exception $e) {
                echo printWarning( "failed to update table. Error was " . $e->getMessage( ) );
                continue;
            }

            if( $res )
                flashMessage( "Successfully enrolled $login to $courseId with type $etype." );
            else
                echo printWarning( "Failed to enroll $login to $courseId." );
        }

        redirect( 'adminacad/enrollments' );
    }

    // Grades
    public function quickgrade( )
    {
        $year = $_POST[ 'year' ];
        $sem = $_POST[ 'semester' ];

        $regs = array_map(
            function( $x ) { return $x['student_id']; }
                , getCourseRegistrations( $_POST[ 'course_id' ], intval($year), $sem )
            );

        $gradeCSV = explode( PHP_EOL, $_POST[ 'grades_csv' ]);
        $gradeMap = array( );

        $msg = '';
        foreach( $gradeCSV as $i => $csv )
        {
            $l = splitAtCommonDelimeters( $csv );
            $login = $l[0];
            $grade = $l[1];

            if(__substr__('@', $login))
            {
                $data = findAnyoneWithEmail($login);
                if($data)
                    $login = $data['login'];
                else
                    $login = '';
            }

            if( ! $login )
            {
                $msg .= "No valid user found with login/email <tt>$login</tt>. Ignoring...<br />";
                continue;
            }

            if( ! in_array( $login, $regs ) )
            {
                $msg .= "<tt>$login</tt> has not registered for this course. Ignoring... <br />";
                continue;
            }

            // Else assign grade.
            $data = array( 'student_id' => $login, 'grade' => $grade );
            $data = array_merge( $_POST, $data );
            $res = updateTable( 'course_registration'
                , 'student_id,semester,year,course_id'
                , 'grade,grade_is_given_on'
                , $data
            );

            if( $res )
                $msg .= "Successfully assigned $grade for $login. <br /> ";
            else
                $msg .= "Could not assign grade for $login. <br /> ";
        }
        flashMessage( $msg );
        redirect('adminacad/grades');
    }

    public function  gradecourse_submit( )
    {
        $student = $_POST[ 'student_id' ];

        $year = $_POST['year'];
        $semester = $_POST['semester'];
        $courseid = $_POST['course_id'];


        $_POST[ 'grade_is_given_on' ] = dbdatetime( 'now' );
        $_POST[ 'grade' ] = $_POST[ $student ];

        $res = updatetable( 'course_registration'
            , 'student_id,semester,year,course_id'
            , 'grade,grade_is_given_on'
            , $_POST
        );

        if( $res )
            echo printinfo( "successfully assigned grade for " . $student );
        else
            echo alertuser( "could not assign grade for " . $student );

        // Go to view.
        redirect( "adminacad/gradecourse/$year/$semester/$courseid");
    }

}

?>
