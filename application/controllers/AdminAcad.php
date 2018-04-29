<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';

trait AdminAcad
{
    public function load_adminacad_view( $view, $data = array() )
    {
        $data['controller'] = 'admin/acad';
        $this->template->set( 'header', 'header.php' );
        $this->template->load( $view, $data );
    }

    public function index()
    {
        $this->home();
    }

    // VIEWS ONLY.
    public function acad( $task = '' )
    {
        // If no action is selected, view admin page.
        if( ! $task )
        {
            $this->load_adminacad_view( 'admin_acad.php' );
        }
        elseif( $task == 'manages_upcoming_aws' )
        {
            $this->load_adminacad_view( 'admin_acad_manages_upcoming_aws.php' );
        }
        elseif($task == 'manages_enrollments')
        {
            $this->load_adminacad_view( 'admin_acad_manages_enrollments.php' );
        }
        else
        {
            flashMessage( "Not implemented yet: $task" );
            redirect('admin/acad');
        }
    }

    // VIEWS WITH ACTION.
    public function acad_action( $action )
    {
        if( $action == 'schedule_upcoming_aws' )
        {
            flashMessage( json_encode( $_POST ));
            $method = $_POST['method'];
            $ret = rescheduleAWS($method);
            if($ret)
                flashMessage("Failed to compute schedule.");
            else
                flashMessage('Sucessfully computed schedule.');
            redirect( 'admin/acad');
        }
        elseif( $action == 'post' )
        {
            $this->execute_aws_action( strtolower(__get__($_POST,'response','')));
        }
        elseif( $action == 'update_aws_entry' )
        {
            $res = updateTable( 'upcoming_aws', 'id', 'abstract,title,is_presynopsis_seminar', $_POST );
            if( $res )
                flashMessage( "Successfully updated abstract of upcoming AWS entry" );
            else
                flashMessage( "I could not update title/abstract.", 'warning' );

            redirect( 'admin/acad/manages_upcoming_aws');
        }
        else
            $this->execute_aws_action(strtolower($action));
    }

    public function execute_aws_action($response)
    {
        if( ! $response)
        {
            flashMessage( 'Empty response from user.', 'warning');
            redirect( 'admin/acad/manages_upcoming_aws');
        }

        if( $response == 'accept' or $response == 'assign' )
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

            redirect( "admin/acad/manages_upcoming_aws" );
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

            redirect( "admin/acad/manages_upcoming_aws" );
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
                redirect( "admin/acad/manages_upcoming_aws");
            }
        }
        else if( $response == "do_nothing" )
        {
            flashMessage( "User cancelled the previous operation.");
            redirect( "admin/acad/manages_upcoming_aws");
        }
        else
        {
            flashMessage( "Not yet implemented $response.");
            redirect( "admin/acad/manages_upcoming_aws");
        }
    }

}

?>
