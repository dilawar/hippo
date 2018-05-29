<?php

require_once BASEPATH. 'autoload.php';

trait Booking 
{
    public function bookingrequest( $arg = '' )
    {
        log_message( 'info', 'Creating booking requests' );
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'user_booking_request', $_POST );
    }

    // Function to show the user's booking.
    public function show_private( $arg = '' )
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load('user_show_requests');
    }

    public function show_public( $arg = '' )
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load('user_manages_public_events');
    }

    public function register_talk( $arg = '' )
    {
        $this->template->set( 'header', 'header.php' );
        $this->template->load('user_register_talk');
    }


    public function private_request_edit( $arg = '')
    {
        $gid = $_POST['gid'];
        $response = $_POST['response'];
        if( $response == 'delete' )
        {
            $res = deleteFromTable( 'bookmyvenue_requests', 'gid', $_POST );
            if( $res )
                flashMessage("Successfully deleted request id $gid");
            redirect( 'user/show_private');
        }
        else if( $response == 'edit')
        {
            $gid = $_POST['gid'];
            $this->template->load( 'header', 'header.php');
            $this->template->load( "booking_request_edit", $_POST );
        }
        else if( $response == 'Update' )
        {
            $editables = $_POST['editables'];
            $res = updateTable( 'bookmyvenue_requests', 'gid', $editables, $_POST );
            if( $res )
                flashMessage( "Successfully updated entry" );
            redirect( 'user/show_private');
        }
        else
        {
            flashMessage( "Not implemented yet $response." );
            redirect( 'user/show_private');
        }
    }

    public function public_event_edit( $arg = '')
    {
        $response = $_POST['response'];
        $gid = $_POST['gid'];
        $eid = $_POST['eid'];

        if( $response == 'delete' )
        {
            $_POST['status'] = 'CANCELLED';
            $res = updateTable('events', 'gid,eid', 'status', $_POST);
            if($res)
                flashMessage( "Cancelled event $gid.$eid.");
            redirect( 'user/show_public');
        }
        else if($response == 'DELETE GROUP')
        {
            $_POST['status'] = 'CANCELLED';
            $res = updateTable( 'events', 'gid', 'status', $_POST );
            if($res)
                flashMessage( "Successfully deleted group $gid.");
            redirect( 'user/show_public');
        }
        else
        {
            flashMessage( "Not implemented $response." );
            redirect( 'user/show_public');
        }
    }

    public function private_event_edit($arg='')
    {
        $response = $_POST['response'];
        $gid = $_POST['gid'];
        $eid = $_POST['eid'];

        if($response == 'DO NOTHING')
        {
            echo flashMessage("User selected DO NOTHING");
        }
        else if($response == 'DELETE EVENT')
        {
            $res = deleteFromTable( 'events', 'gid,eid', $_POST );
            if($res)
                flashMessage( "Successfully deleted $gid.$eid.");
        }
        else if( $response == 'DELETE GROUP')
        {
            $_POST['status'] = 'CANCELLED';
            $res = updateTable( 'events', 'gid', 'status', $_POST );
            if($res)
                flashMessage("Successfully cancelled $gid");
        }
        else
        {
            echo flashMessage("Unknown request $response.");
        }
        redirect('user/show_private');
    }

    public function bookingrequest_submit( $ref = '' )
    {
        $ref = __get__( $_POST, 'REFERER', 'user' );
        $msg = verifyRequest( $_POST );
        if( $msg == "OK" )
        {
            // Generate repeat pattern from days, week and month repeat patter. If we 
            // are coming here from quickbook.php, it may not be here.
            if( array_key_exists( 'day_pattern', $_POST ) )
            {
                // Only lab-meet and JC are allowed more than 12 months. For others its 
                // 6 months max.
                $nMonths = intval( __get__( $_POST, 'month_pattern', 6) );
                if( $_POST[ 'class' ] == 'LAB MEETING' || $_POST[ 'class' ] == 'JOURNAL CLUB MEETING' )
                    if( $nMonths > 12 )
                        $nMonths = 12;
                    else
                        if( $nMonths > 6 )
                            $nMonths = 6;

                $_POST[ 'month_pattern'] = "$nMonths";

                $repeatPat = constructRepeatPattern( 
                    $_POST['day_pattern'], $_POST['week_pattern'] , $_POST['month_pattern']
                );

                if( $repeatPat )
                    echo "<pre>Repeat pattern $repeatPat </pre>";

                $_POST['repeat_pat']  = $repeatPat;
            }

            $_POST['timestamp']  = dbDateTime( 'now' );
            $gid = submitRequest( $_POST );
            if( $gid )
            {
                $userInfo = getLoginInfo( $_SESSION[ 'user' ] );
                $userEmail = $userInfo[ 'email' ];
                $msg = initUserMsg( $_SESSION[ 'user' ] );
                $msg .= "<p>Your booking request id $gid has been created. </p>";
                $msg .= arrayToVerticalTableHTML( getRequestByGroupId( $gid )[0], 'request' );
                sendHTMLEmail( $msg
                    , "Your booking request (id-$gid) has been recieved"
                    , $userEmail 
                );

                // Send email to hippo@lists.ncbs.res.in 
                sendHTMLEmail( "<p>Details are following </p>" . $msg
                    , "A new booking request has been created by $userEmail"
                    , 'hippo@lists.ncbs.res.in'
                );

                echo flashMessage( "Your booking request has been submitted." );
                redirect("$ref/home");
            }
            else
            {
                echo printWarning( "Your request could not be submitted. Please notify the admin." );
                redirect("$ref/home");
            }
        }
        else
        {
            $msg1 = "There was an error in request.";
            $msg1 .= "<p>$msg</p>";
            $msg1 .= "<p>Complete entry is following.</p>";
            $msg1 .= arrayToVerticalTableHTML( $_POST, "request" );
            flashMessage( $msg1, 'warning' );
            redirect( "$ref/book" );
        }
    }


    public function register_talk_submit( )
    {
        /* ALL EVENTS GENERATED FROM THIS INTERFACE ARE SUITABLE FOR GOOGLE CALENDAR. */
        // Here I get both speaker and talk details. I need a function which can either 
        // insert of update the speaker table. Other to create a entry in talks table.
        // Sanity check 
        $msg = '';
        if(!($_POST['first_name'] && $_POST['institute'] && $_POST['title'] && $_POST['description']))
        {
            $msg = 'Incomplete entry. Required fields: First name, last name, 
                institute, title and description of talk.';
            $msg .= arrayToVerticalTableHTML( $_POST, 'info' );
            flashMessage( "Failed to register following entry. <br /> $msg ." );
            redirect( "user/register_talk" );
            return;
        }

        // Insert the speaker into table. if it already exists, just update.
        $speaker = addOrUpdateSpeaker( $_POST );
        $filepath = getSpeakerPicturePathById( $speaker[ 'id' ] );

        if( $_FILES[ 'picture' ] )
            uploadImage( $_FILES['picture'], $filepath );

        if( $speaker )  // Sepeaker is successfully updated. Move on.
        {
            // This entry may be used on public calendar. Putting email anywhere on 
            // public domain is allowed.
            $speakerText = loginToText( $speaker, $withEmail = false, $autofix = false );
            $_POST[ 'speaker' ] = $speakerText;
            $_POST[ 'speaker_id' ] = $speaker[ 'id' ];

            $res2 = addNewTalk( $_POST );
            if( $res2 )
            {
                $talkId = $res2[ 'id'];
                $msg .= "<p>Successfully registered your talk with id $talkId </p>";
                $startTime = $_POST[ 'start_time' ];
                $endTime = $_POST[ 'end_time' ];

                if( isBookingRequestValid( $_POST ) )
                {
                    $date = $_POST[ 'end_time' ];
                    $venue = $_POST[ 'venue' ];

                    if( $venue && $startTime && $endTime && $date )
                    {
                        /* Check if there is a conflict between required slot and already 
                         * booked events or requests. If no then book else redirect user to 
                         * a page where he can make better decisions.
                         */

                        $reqs = getRequestsOnThisVenueBetweenTime( $venue, $date
                            , $startTime, $endTime );
                        $events = getEventsOnThisVenueBetweenTime( $venue, $date
                            , $startTime, $endTime );
                        if( $reqs || $events )
                        {
                            $msg .= "<p>There is already an events on $venue on $date
                                between $startTime and $endTime. 
                                <br />
                                I am redirecting you to page where you can browse all venues 
                               and create suitable booking request.</p>";
                            $msg .= "<p>You can book a slot for this talk later" 
                                . " by visiting 'Manage my talks' link in your homepage. </p>";

                            printWarning( $msg );
                        }
                        else 
                        {
                            // Else create a request with external_id as talkId.
                            $external_id = getTalkExternalId( $res2 );
                            $_POST[ 'external_id' ] = $external_id;
                            $_POST[ 'is_public_event' ] = 'YES';

                            // Modify talk title for calendar.
                            $_POST[ 'title' ] = __ucwords__( $_POST['class'] ) . " by " . 
                                $_POST[ 'speaker' ] . ' on \'' . $_POST[ 'title' ] . "'";

                            $res = submitRequest( $_POST );
                            if($res)
                                $msg .= "<p>Successfully created booking request. </p>";
                            else
                                $msg .= printWarning( "Oh Snap! Failed to create booking request" );
                        }
                    }
                    else
                        $msg .= "<p>The booking request associtated with talk is invalid.</p>";
                }
            }
            else
                $msg .= printWarning( "Oh Snap! Failed to add your talk to database." );
        }
        else
            $msg .= printWarning( "Oh Snap! Failed to add speaker to database" );

        redirect( "user/register_talk");
    }
}

?>
