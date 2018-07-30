<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';
require_once __DIR__.'/AdminacadCourses.php';
require_once __DIR__.'/AdminacadJC.php';
require_once __DIR__.'/AdminSharedFunc.php';

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

    public function aws_edit_requests( )
    {
        $this->load_adminacad_view( "admin_acad_manages_requests" );
    }

    public function email_and_docs( )
    {
        $this->load_adminacad_view( "admin_acad_email_and_docs" );
    }

    public function enrollments( $year = '', $semester = '' )
    {
        $data = array();
        if( $year )
            $data['year'] = $year;
        if( $semester )
            $data['semester'] = $semester;

        $this->load_adminacad_view( 'admin_acad_manages_enrollments', $data );
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

    public function manages_talks()
    {
        $this->load_adminacad_view( 'admin_manages_talks' );
    }

    public function manages_speakers( )
    {
        $this->load_adminacad_view( 'admin_acad_manages_speakers' );
    }

    public function edittalk( $id )
    {
        $this->load_adminacad_view( 'admin_manages_talk_update', [ 'talkid' => $id ] );
    }

    public function deletetalk( $id )
    {
        printWarning( 
            "Sorry but you are not allowed to delete talk. Only Bookmyvenue Admin can do that." 
            );
        redirect( "adminacad/manages_talks" );
    }

    public function scheduletalk( $id )
    {
        printWarning( 
            "Sorry but you are not allowed to schedule talk. Only Bookmyvenue Admin can do that." 
            );
        redirect( "adminacad/manages_talks" );
    }

    public function send_email( )
    {
        $this->load_adminacad_view( 'admin_acad_send_email' );
    }

    public function update_upcoming_aws( )
    {
        $this->load_adminacad_view( "admin_acad_update_upcoming_aws" );
    }

    public function aws_speakers( )
    {
        $this->load_adminacad_view( "admin_acad_aws_speakers" );
    }

    public function summary_user_wise( )
    {
        $this->load_adminacad_view( "admin_acad_summary_user_wise" );
    }

    public function summary_date_wise( )
    {
        $this->load_adminacad_view( "admin_acad_summary_date_wise" );
    }

    // ACTION.
    public function next_week_aws_action( )
    {
        $this->execute_aws_action( $_POST['response'], 'upcoming_aws' );
    }

    public function upcoming_aws_action()
    {
        $this->execute_aws_action( $_POST['response'] );
    }

    public function updateaws($arg = '')
    {
        $response = strtolower($_POST['response']);
        if($response == 'do_nothing')
        {
            flashMessage( 'User cancelled last action.' );
            redirect( 'adminacad/upcoming_aws' );
            return;
        }
        elseif($response == 'delete' )
        {
            $id = $_POST['id'];
            $res = deleteFromTable( 'annual_work_seminars', 'id', $_POST );
            if( $res )
                flashMessage( "Successfully deleted AWS entry $id" );

            redirect( "adminaws/upcoming_aws");
            return;
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
            flashMessage("Failed to compute schedule. Request method $method.");
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
            redirect("adminacad/$ref");
            return;
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
            return;
        }
        else if( $response == 'delete' ) 
        {
            $reason = __get__( $_POST, 'reason', '' );
            if( strlen( trim($reason)) < 8 )
            {
                printErrorSevere( "I did not remove this AWS because reason you gave 
                    was NOT at least 8 chracter long." );
                redirect( "adminacad/upcoming_aws" );
                return;
            }

            $speaker = $_POST['speaker'];
            $date = $_POST['date'];
            $res = clearUpcomingAWS( $speaker, $date );
            $piOrHost = getPIOrHost( $speaker );

            if( $res )
            {
                flashMessage( "Successfully cleared upcoming AWS of $speaker on $date." );
                $admin = whoAmI();
                // Notify the hippo list.
                $msg = "<p>Hello " . loginToHTML( $_POST[ 'speaker' ] ) . "</p>";
                $msg .= "<p>
                    Your upcoming AWS schedule has been removed by Hippo admin ($admin).
                     If this is a  mistake, please write to acadoffice@ncbs.res.in
                    immediately.
                    </p>
                    <p> The AWS schedule which is removed is the following </p>
                    ";
                
                $msg .= p( "Following reason was given by admin." );
                $msg .= p( $reason );

                $data = array( );

                $data[ 'speaker' ] = $_POST[ 'speaker' ];
                $data[ 'date' ] = $_POST[ 'date' ];

                $msg .= arrayToVerticalTableHTML( $data, 'info' );

                $cclist = "acadoffice@ncbs.res.in,hippo@lists.ncbs.res.in";
                if($piOrHost)
                    $cclist .= ",$piOrHost";

                sendHTMLEmail( $msg
                        , "Your ($speaker) AWS schedule has been removed from upcoming AWSs"
                        , $to = getLoginEmail( $_POST[ 'speaker' ] )
                        , $cclist 
                    );
                redirect( "adminacad/$ref");
                return;
            }
        }
        else if( $response == "do_nothing" )
        {
            flashMessage( "User cancelled the previous operation.");
            redirect( "adminacad/$ref");
            return;
        }
        else
        {
            flashMessage( "Not yet implemented $response.");
            redirect( "adminacad/$ref");
            return;
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

        $year = $_POST['year'];
        $semester = $_POST['semester'];
        redirect( "adminacad/enrollments/$year/$semester" );
    }

    public function quickenroll( )
    {
        $enrolls = explode( PHP_EOL, $_POST[ 'enrollments' ] );

        $warnMsg = '';
        foreach( $enrolls as $i => $en )
        {
            $l = splitAt( $en, ':' );
            $email = $l[0];

            // By default, its credit.
            $etype = 'CREDIT';
            if( count( $l ) == 2 )
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

            $res = null;
            try {
                $res = insertOrUpdateTable( 'course_registration'
                    , 'student_id,course_id,year,semester'
                    , 'student_id,course_id,status,type,year,semester,registered_on,last_modified_on'
                    , $data
                );
            } catch (Exception $e) {
                $warnMsg .= p( "failed to update table. Error was " . $e->getMessage( ) );
                continue;
            }

            if( $warn )
                echo printWarning( "System generated following warning: <br /> $warn");

            if( $res )
                flashMessage( "Successfully enrolled $login to $courseId with type $etype." );
            else
            {
                echo printWarning( "Failed to enroll $email/$login to $courseId." );
                if( $warnMsg )
                {
                    $warnMsg = p( 'Following was reported by system.' );
                    echo printWarning( $warnMsg );
                }
            }
        }

        $year = $data['year'];
        $semester = $data['semester'];
        redirect( "adminacad/enrollments/$year/$semester" );
    }

    // Scheduling request.
    public function scheduling_request_submit( )
    {

        // Start preparing email.
        if( ! $_POST )
        {
            redirect( 'adminacad/scheduling_request' );
            return;
        }

        $speaker = $_POST[ 'speaker' ];
        $speakerInfo = getUserInfo( $speaker );
        $user = loginToText( $speaker );

        $msg = '<p>Dear ' . $user . ' </p>';

        if( $_POST[ 'response' ] == 'Reject' )
        {
            if( strlen( $_POST[ 'reason' ]) < 8 )
            {
                echo printWarning( "
                    Empty reason or reason too short (less than 8 characters).
                    A request can not rejected without a proper reason.
                    You must enter a valid reason."
                );
                
                $this->load_adminacad_view( 'adminacad/scheduling_request' );
                return;
            }

            $rid = $_POST[ 'id' ];

            $res = updateTable( 
                'aws_scheduling_request', 'id' , 'status'
                , array( 'id' => $rid, 'status' => 'REJECTED' )
            );

            if( $res )
            {
                echo flashMessage( "This request has been rejected." );
                // Now notify user.
                $msg .= p("Your preference for AWS dates has been rejected.");
                $msg .= p("Reason: " . $_POST[ 'reason' ] );
                $msg .= p("Feel free to drop an email to hippo@lists.ncbs.res.in for
                            further clarification. Please mention your request id in email."
                        );

                // Get the latest request.
                $req = getTableEntry('aws_scheduling_request', 'id', array('id' => $rid));
                $msg .= arrayToVerticalTableHTML( $req, "request" );

                sendHTMLEmail( $msg
                    , "Your preference for AWS dates (id:". $rid . ") has been rejected"
                    , $speakerInfo[ 'email' ]
                    , 'hippo@lists.ncbs.res.in'
                );

                flashMessage( "Successfully reviewed the request." );
                $this->load_adminacad_view( 'adminacad/scheduling_request' );
                return;
            }
        }
        elseif( $_POST['response'] == 'Accept' )
        {
            $rid = $_POST[ 'id' ];
            $req = getTableEntry( 'aws_scheduling_request', 'id', array( 'id' => $rid ));
            $req['status'] = 'APPROVED';

            $res = updateTable( 'aws_scheduling_request', 'id', 'status', $req );
            if( $res )
            {
                // Now recompute the schedule.
                rescheduleAWS( );

                $user = loginToText( $speaker );
                $msg .= "<p>
                    Your AWS scheduling preferences has been approved.
                    <br>
                    I will try to schedule your AWS on or very near to these dates but it 
                    can not be guaranteed especially when there are multiple scheduling 
                    requests on nearby slots.  
                    <br>
                    The preferece you sent are below.
                    </p>";

                // Get the latest request.
                $req = getTableEntry(
                    'aws_scheduling_request', 'id', array( 'id' => $rid )
                );
                $msg .= arrayToVerticalTableHTML( $req, "request" );

                sendHTMLEmail( $msg
                    , "Your AWS preference dates (id:$rid) have been approved"
                    , $speakerInfo['email' ]
                    , 'hippo@lists.ncbs.res.in'
                );

                flashMessage( "Successfully rejected the request." );
                redirect( 'adminacad/scheduling_request' );
                return;
            }
            else
                echo printWarning( "Could not update the AWS table." );
        }
        else
            echo printWarning( "Unknown request " . $_POST[ 'response' ] );

        redirect( 'adminacad/scheduling_request' );
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

    public function update_talk_action( )
    {
        admin_update_talk( $_POST );
        redirect( 'adminacad/manages_talks' );
    }

    public function send_email_action()
    {
        $res = admin_send_email( $_POST );
        if($res['error'])
            printWarning( p("Failed to send email.") . $res['error'] );
        else
            flashMessage( $res['message'] );

        redirect( 'adminacad/manages_talks' );
    }

    public function manages_speakers_action( )
    {
        $res = admin_update_speaker( $_POST );

        if( $res['error'] )
            printWarning( $res['error'] );
        else
            flashMessage( $res['message'] );

        redirect( "adminacad/manages_speakers");
    }

    public function aws_edit_request_action( )
    {

        // Start preparing email.
        $speaker = $_POST[ 'speaker' ];
        $speakerInfo = getUserInfo( $speaker );
        $rid = $_POST[ 'request_id' ];
        $user = loginToText( $speaker );

        $msg = '<p>Dear ' . $user . ' </p>';

        if( $_POST[ 'response' ] == 'Reject' )
        {
            if( strlen( $_POST[ 'reason' ]) < 8 )
            {
                echo printWarning( "
                    Empty reason or reason too short (less than 8 characters).
                    A request can not rejected without a proper reason.
                    You must enter a valid reason."
                );
                redirect( 'adminacad/aws_edit_requests' );
                return;
            }

            $res = updateTable( 
                'aws_requests', 'id' , 'status'
                , array( 'id' => $rid, 'status' => 'REJECTED' )
            );

            if( $res )
            {
                echo printInfo( "This request has been rejected" );
                // Now notify user.
                $msg .= "<p>Your AWS add/edit request has been rejected </p>";
                $msg .= "<p>Reason: " . $_POST[ 'reason' ] . "</p>";
                $msg .= "<p>Feel free to drop an email to hippo@lists.ncbs.res.in for
                    further clarification. Please mention your request id in email.
                    </p>";

                // Get the latest request.
                $req = getAwsRequestById( $rid );
                $msg .= arrayToVerticalTableHTML( $req, "request" );

                sendHTMLEmail( $msg
                        , "Your AWS edit request (id:". $rid . ") has been rejected"
                        , $speakerInfo[ 'email' ]
                    );

                redirect( "adminacad/aws_edit_requests" );
                return;
            }
        }
        elseif( $_POST['response'] == 'Accept' )
        {
            $date = $_POST[ 'date' ];
            $aws = getMyAwsOn( $speaker, $date );
            $req = getAwsRequestById( $rid );

            $req[ 'is_presynopsis_seminar' ] = __get__( $_POST, 'is_presynopsis_seminar', 'NO' );

            $res = updateTable( 'annual_work_seminars'
                    , 'speaker,date' 
                    , array( 'abstract'
                        , 'title'
                        , 'is_presynopsis_seminar'
                        , 'supervisor_1', 'supervisor_2'
                        , 'tcm_member_1', 'tcm_member_2', 'tcm_member_3', 'tcm_member_4' 
                        )
                    , $req
                    );

            if( $res )
            {
                $res = updateTable( 
                    'aws_requests', 'id', 'status'
                    , array( 'id' => $rid, 'status' => 'APPROVED' ) 
                );

                if( $res )
                {
                    $user = loginToText( $speaker );
                    $msg .= "<p>
                        Your edit to your AWS entry has been approved. 
                        The updated entry is following:
                        </p>";

                    // Get the latest request.
                    $req = getAwsRequestById( $rid );
                    $msg .= arrayToVerticalTableHTML( $req, "request" );
                    sendHTMLEmail( $msg
                        , "Your AWS edit request (id:$rid) has been approved"
                        , $speakerInfo['email' ]
                    );
                    
                    redirect( 'adminacad/aws_edit_requests' );
                    return;
                }
            }
        }
        echo printWarning( "Unknown request " . $_POST[ 'response' ] );
        redirect( 'adminacad/aws_edit_requests');
    }


    public function update_upcoming_aws_submit(  )
    {

        $res = updateTable( 'upcoming_aws', 'id'
                    , 'supervisor_1,supervisor_2,tcm_member_1,tcm_member_2,tcm_member_3' 
                        .  ',tcm_member_4,title,abstract'
                    , $_POST
                );

        if( $res )
            flashMessage( "Successfully updated AWS entry." );

        redirect( 'adminacad' );
    }

    // Add a speaker to PI/HOST.
    public function aws_speakers_action( )
    {
        // Show only this user.
        $login = $_POST[ 'login' ];
        $pi = $_POST[ 'pi_or_host' ];
        if( $login )
        {
            $res = updateTable( 'logins', 'login', 'pi_or_host', $_POST );
            if( $res )
                echo flashMessage( "Successfully updated/added $login to $pi." );
        }
        redirect( "adminacad" );
    }

}

?>
