<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';


class Adminbmv extends CI_Controller
{

    function index()
    {
        $this->home();
    }

    // Show user home.
    public function home()
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'bookmyvenue_admin' );
    }

    public function review( )
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'bookmyvenue_admin_request_review' );
    }

    // Views with action.
    public function request_review( )
    {
        $whatToDo = $_POST['response'];
        $isPublic = $_POST['isPublic'];

        // If admin is rejecting and have not given any confirmation, ask for it.
        if( $whatToDo == 'REJECT' )
        {
            // If no valid response is given, rejection of request is not possible.
            if( strlen( $_POST[ 'reason' ] ) < 5 )
            {
                flashMessage( "Before you can reject a request, you must provide
                    a valid reason (more than 5 characters long)" );
                redirect("adminbmv/home");
            }
        }

        // Else send email.
        $msg = "<p>Some changes have been made to your request. The update entry 
            is following. </p>";

        $msg .= '<table border="0">';
        $events = $_POST['events'];
        $userEmail = '';
        $eventGroupTitle = '';

        if( count( $events ) < 1 )
        {
            flashMessage( "I could not find an event.", 'warning');
            redirect("adminbmv/home");
        }
        else
        {
            $group = array( );

            foreach( $events as $event )
            {
                $event = explode( '.', $event );
                $gid = $event[0]; $rid = $event[1];

                // Get event info from gid and rid of event as passed to $_POST.
                $eventInfo = getRequestById( $gid, $rid );
                $userEmail = getLoginEmail(  $eventInfo[ 'created_by' ] );

                $eventText = eventToText( $eventInfo );
                array_push( $group, $eventInfo );

                $eventGroupTitle = $eventInfo[ 'title' ];

                try {

                    if( $whatToDo == 'APPROVE' )
                        $status = 'APPROVED';
                    else
                        $status = $whatToDo . 'ED';

                    $res = actOnRequest( $gid, $rid, $whatToDo );
                    $msg .= "<tr><td> $eventText </td><td>". $status ."</td></tr>";

                } catch ( Exception $e ) {
                    flashMessage( "Failed to update request: " . $e->getMessage( ),'warning');
                    redirect("adminbmv/home");
                }
                changeIfEventIsPublic( $gid, $rid, $isPublic );
            }

            $msg .= "</table>";

            // Append user email to front.
            $msg = "<p>Dear " . loginToText( $group[0]['created_by' ], true ) . '</p>' . $msg;

            // Name of the admin to append to the email.
            $admin = getLoginEmail( $_SESSION[ 'user' ] );

            if( $whatToDo == 'REJECT' && strlen( $_POST[ 'reason' ] ) > 5 )
            {
                $msg .= "<p>Following reason was given by $admin </p>";
                $msg .= $_POST[ 'reason' ];
            }

            error_log( "<pre> $msg </pre>" );

            $res = sendHTMLEmail( $msg
                , "Your booking request '$eventGroupTitle' has been $status"
                , $userEmail
                , 'hippo@lists.ncbs.res.in'
            );
        }
    }
}

?>
