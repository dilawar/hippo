<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH.'autoload.php';


class Adminbmv extends CI_Controller
{
    // Pure VIEWS
    function index()
    {
        $this->home();
    }

    public function loadview( $view, $data = null )
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load( $view, $data );
    }

    // Show user home.
    public function home()
    {
        $this->loadview( 'bookmyvenue_admin' );
    }

    public function review( )
    {
        $this->loadview( 'bookmyvenue_admin_request_review' );
    }


    public function venues( )
    {
        $this->loadview( 'bookmyvenue_admin_manages_venues.php' );
    }

    public function email_and_docs($arg = '')
    {
        $this->loadview( 'admin_acad_email_and_docs.php' );
    }

    // Set the controller which called it. Since this view can be called by acad
    // admin as well.
    public function manages_talks( $arg = '' )
    {
        $data = array( 'controller' => 'adminbmv' );
        $this->loadview( 'admin_manages_talks.php', $data );
    }

    // ACTIONS
    public function synchronize_calendar( )
    {
        $this->loadview( 'synchronize_calendar.php' );
        redirect( 'adminabmv/home' );
    }

    public function venues_action($arg='')
    {
        $response = __get__( $_POST, 'response', '' );
        if( $response == 'update' )
        {
            $res = updateTable( 
                    'venues'
                    , 'id'
                    , 'name,institute,building_name,floor,location,type,strength,' 
                        . 'distance_from_ncbs,has_projector,' 
                        . 'suitable_for_conference,has_skype'
                    , $_POST
                );
            if( $res )
                flashMessage( "Venue " . $_POST[ 'id' ] . ' is updated successful' );
            else
                flashMessage( 'Failed to update venue ' . $_POST[ 'id ' ] );
        }
        else if( $response == 'add new' ) 
        {
            if( strlen( $_POST[ 'id' ] ) < 2  )
                flashMessage( "The venue id is too short to be legal." );
            else
            {
                $res = insertIntoTable( 
                        'venues'
                        , 'id,name,institute,building_name,floor,location,type,strength,' 
                            . 'distance_from_ncbs,has_projector,' 
                            . 'suitable_for_conference,has_skype'
                        , $_POST
                    );

                if( $res )
                    flashMessage( "Venue " . $_POST[ 'id' ] . ' is successfully added.' );
                else
                    flashMessage( 'Failed to added venue ' . $_POST[ 'id ' ] );
            }
        }
        else if( $response == 'delete' ) 
        {
            $res = deleteFromTable( 'venues' , 'id' , $_POST);
            if( $res )
                flashMessage( "Venue " . $_POST[ 'id' ] . ' is successfully deleted.' );
            else
                flashMessage( 'Failed to added venue ' . $_POST[ 'id ' ] );
        }
        else if( $response == 'DO_NOTHING' ) 
        {
            flashMessage( "User said DO NOTHING. So going back!" );
            redirect( 'adminbmv/venues' );
        }
        else
            flashMessage( "Unknown command from user $response." );

        redirect('adminbmv/venues');
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

            flashMessage( 'Successfuly reviewed.' );
            redirect( 'adminbmv/home' );
        }
    }

    // MANAGES TALK
    public function deletetalk($id)
    {
        $response = $_POST['response'];
        if( $response == 'DO_NOTHING' )
        {
            flashMessage( "User cancelled.");
            redirect( 'adminbmv/manages_talks');
        }

        // Delete this entry from talks.
        $data = array( 'id' => $id );
        $res = deleteFromTable( 'talks', 'id', $data );
        if( $res )
        {
            flashMessage( 'Successfully deleted talk' );
            $success = true;
            $externalId = getTalkExternalId( $id );
            $events = getTableEntries( 'events'
                , 'external_id', "external_id='$externalId' AND status='VALID'" 
            );
            $requests = getTableEntries( 'bookmyvenue_requests'
                , 'external_id', "external_id='$externalId' AND status='PENDING'" 
            );
            foreach( $events as $e )
            {
                echo printInfo( "Cancelling associated booking." );
                echo arrayToTableHTML( $e, 'info' );
                $e[ 'status' ] = 'CANCELLED';
                // Now cancel this talk in requests, if there is any.
                $res = updateTable( 'events', 'external_id', 'status', $e );
            }

            foreach( $requests as $r )
            {
                echo printInfo( "Cancelling associated booking request " );
                echo arrayToTableHTML( $r, 'info' );

                $r[ 'status' ] = 'CANCELLED';
                $res = updateTable( 'bookmyvenue_requests', 'external_id', 'status', $r);
            }

            // /* VALIDATION: Check the bookings are deleted  */
            $events = getTableEntries( 'events'
                , 'external_id', "external_id='$externalId' AND status='VALID'"
            );
            $requests = getTableEntries( 'bookmyvenue_requests'
                , 'external_id', "external_id='$externalId' AND status='VALID'"
            );
            assert( ! $events );
            assert( ! $requests );
            flashMessage( "Successfully deleted related events/requests of talk id $id." );
        }
        else
            printWarning( "Failed to delete the talk." );

        redirect('adminbmv/manages_talks');
    }

    public function updatetalk( $id )
    {
        echo printInfo( "Here you can only change the host, class, title and description
            of the talk." );

        $data = array('id' => $id );
        $talk = getTableEntry( 'talks', 'id', $data );

        echo '<form method="post" action="admin_acad_manages_talks_action_update.php">';
        echo dbTableToHTMLTable('talks', $talk
            , 'class,coordinator,host,title,description'
            , 'submit');
        echo '</form>';
    }

    public function scheduletalk($id)
    {
        // We are sending this to quickbook.php as GET request. Only external_id is 
        // sent to page.

        $external_id = getTalkExternalId( $id );
        $query = "&external_id=".$external_id;

        $data = array( 
            'external_id' => $external_id
            , 'controller', 'adminbmv'
            );
        $this->loadview('user_book.php', $data );
    }
}

?>
