<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';

trait AdminAcad
{
    function index()
    {
        $this->home();
    }

    public function acad( $action = '' )
    {
        // If no action is selected, view admin page.
        if( ! $action )
        {
            $this->template->set( 'header', 'header.php' );
            $this->template->load( 'admin_acad.php' );
        }
        else
            $this->acad_action( $action );
    }

    public function acad_action( $action )
    {
        if( $action == 'manages_upcoming_aws' )
        {
            $this->template->set( 'header', 'header.php');
            $this->template->load( 'admin_acad_manages_upcoming_aws.php' );
        }
        elseif( $action == 'schedule_upcoming_aws' )
        {
            flashMessage( json_encode( $_POST ));
            $method = $_POST['method'];
            $ret = rescheduleAWS($method);
            if($ret)
                flashMessage("Failed to compute schedule.");
            else
                flashMessage('Sucessfully computed schedule.');

            redirect( 'admin/acad/manages_upcoming_aws');
        }
        elseif( $action == 'next_week_aws' )
        {
            $this->execute_aws_action( __get__($_POST,'response','') );
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
        {
            flashMessage( "$action is not implemented yet");
            redirect( 'admin/acad' );
        }
    }

    public function execute_aws_action($response)
    {
        if( ! $response)
        {
            flashMessage( 'Empty response from user.', 'warning');
            redirect( 'admin/acad/manages_upcoming_aws');
        }

        if( $response == 'Accept' or $response == 'Assign' )
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
            $this->template->set('header', 'header.php');
            $this->template->load( 'admin_acad_manages_upcoming_aws_reformat.php');
        }
        else if( $_POST[ 'response' ] == 'RemoveSpeaker' )
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

        else if( $_POST[ 'response' ] == 'delete' )
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
        else if( $_POST[ 'response' ] == "DO_NOTHING" )
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
