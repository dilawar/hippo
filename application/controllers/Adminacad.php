<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';

class Adminacad extends CI_Controller
{
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

        // Now real stuff.
        redirect( 'adminacad/home' );
    }

    public function action( $task = '' )
    {
        // If no action is selected, view admin page.
        if( ! $task )
        {
            $this->load_adminacad_view( 'admin_acad' );
        }
        elseif( $task == 'manages_upcoming_aws' )
        {
        }
        elseif($task == 'manages_enrollments')
        {
            $this->load_adminacad_view( 'admin_acad_manages_enrollments' );
        }
        else
        {
            flashMessage( "Not implemented yet: $task" );
            redirect('adminacad');
        }
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
}

?>
