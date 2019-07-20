<?php

require_once BASEPATH. 'autoload.php';
require_once BASEPATH. 'extra/booking_methods.php';

trait Booking 
{
    public function bookingrequest( $controller = 'user', $method = 'home' )
    {
        if( __get__( $_POST, 'venue', '' ) )
        {
            $_POST[ 'goback'] = "$controller/$method";
            $this->load_user_view( 'user_booking_request', $_POST );
            return;
        }
        else 
        {
            flashMessage( "Invlid use of page." );
            redirect( 'user/book' );
        }
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

    public function book_talk( $talkid )
    {
        $external_id = "talks." . $talkid;
        $data = array('external_id' => $external_id );
        $this->load_user_view( 'user_book', $data );
    }

    public function edit_booking_of_talk( $gid )
    {
        $data = array( 'gid' => $gid, 'response' => 'edit');
        $data['goback'] = "user/show_public";
        $this->load_user_view( 'user_show_requests_edit', $data );
    }


    public function private_request_edit($controller='user', $action = 'show_private')
    {
        $goback = "$controller/$action";

        $gid = $_POST['gid'];
        $response = $_POST['response'];
        if( $response == 'delete' )
        {
            $res = deleteFromTable( 'bookmyvenue_requests', 'gid', $_POST );
            if( $res )
                flashMessage("Successfully deleted request id $gid");
        }
        else if( $response == 'edit')
        {
            $gid = $_POST['gid'];
            $this->template->load( 'header', 'header.php');
            $this->template->load( "booking_request_edit", $_POST );
            return;
        }
        else if( $response == 'Update' )
        {
            $editables = $_POST['editables'];
            $res = updateTable( 'bookmyvenue_requests', 'gid', $editables, $_POST );
            if( $res )
                flashMessage( "Successfully updated entry" );
        }
        else
            flashMessage( "Not implemented yet $response." );

        redirect( $goback );
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

    public function bookingrequest_submit( $controller = 'user', $method = 'home' )
    {
        $goback = "$controller/$method";

        $msg = verifyRequest( $_POST );
        if( $msg == "OK" )
        {
            // Generate repeat pattern from days, week and month repeat patter. If we 
            // are coming here from quickbook.php, it may not be here.
            if( __get__( $_POST, 'day_pattern', array() ))
            {
                $repeatPat = constructRepeatPattern( 
                    implode( ',', $_POST['day_pattern']), 
                    implode( ',', $_POST['week_pattern']),
                    $_POST['month_pattern']
                );

                $_POST['repeat_pat']  = $repeatPat;

            }

            $_POST['timestamp']  = dbDateTime( 'now' );
            $gid = submitRequest( $_POST );
            if( $gid )
            {
                $userInfo = getLoginInfo( whoAmI() );
                $userEmail = $userInfo[ 'email' ];

                $data = [ 'USER' => loginToText( whoAmI() ) ];
                $rgroup = getRequestByGroupId( $gid );
                $data[ 'BOOKING_REQUEST']= arrayToVerticalTableHTML($rgroup[0], 'request');

                // add the recurrent pattern to table recurrent_pattern.
                $patData = [ 'id' => getUniqueID( 'recurrent_pattern')
                            , 'request_gid' => $gid
                            , 'pattern' => $repeatPat
                        ];
                $res = insertIntoTable( 'recurrent_pattern', 'id,request_gid,pattern', $patData );

                if( count( $rgroup ) > 0 )
                {
                    $data[ 'NUMBER_OF_REQUESTS'] = count( $rgroup );
                    $subject = "Your booking request (id-$gid) has been recieved";
                    $template = emailFromTemplate( 'BOOKING_NOTIFICATION', $data );

                    $body = $template[ 'email_body'];

                    // Now check if some bookings are not made.
                    $res = areThereAMissingRequestsAssociatedWithThisGID( $gid );
                    if( $res[ 'are_some_missing'] )
                    {
                        $body = printWarning( "I failed to book on some dates." );
                        $body .= $res[ 'html'];
                        $body .= $warn;
                    }

                    sendHTMLEmail( $body, $subject, $userEmail, $template['cc'] );
                    echo flashMessage( "Your booking request has been submitted." );
                }
                else
                {
                    flashMessage( "Something went wrong. No event could be generated OR
                        I could not register your booking request. 
                        Please write to hippo@lists.ncbs.res.in if this is my mistake!" 
                    );
                }
            }
            else
            {
                echo printWarning( "Something went wrong. No event could be generated OR
                    I could not register your booking request. 
                    Please write to hippo@lists.ncbs.res.in if this is my mistake!" 
                );
            }
        }
        else // $msg was on 'OK'
        {
            $msg1 = "There was an error in request. <br /> $msg";
            flashMessage( $msg1, 'warning' );

            // Send use back to page.
            $this->load_user_view( "user_booking_request", $_POST );
            return;
        }
        redirect( "$goback" );
    }

    public function manage_talks_action()
    {
        $action = strtolower( trim($_POST['response']));
        if( $action== 'delete' )
        {
            // Delete this entry from talks.
            $res = deleteFromTable( 'talks', 'id', $_POST );
            if( $res )
            {
                flashMessage( 'Successfully deleted entry' );
                // Now cancel this talk in requests, if there is any.
                $res = updateTable( 
                    'bookmyvenue_requests', 'external_id', 'modified_by,status,last_modified_on'
                    , array( 'external_id' => "talks." . $_POST[ 'id' ] 
                            , 'status' => 'CANCELLED' 
                            , 'last_modified_on' => dbDateTime( 'now' )
                            , 'modified_by' => whoAmI()
                        )
                    );

                // Cancel confirmed event associated with this talk if any.
                $res = updateTable( 
                    'events', 'external_id', 'status,last_modified_on'
                    , array( 'external_id' => "talks." . $_POST[ 'id' ] 
                                , 'status' => 'CANCELLED' 
                                , 'last_modified_on' => dbDateTime( 'now' )
                            )
                    );
                redirect( "user/show_public" );
                return;
            }
            else
                echo printWarning( "Failed to delete the talk " );
        }
        else if( $action == 'DO_NOTHING' )
        {
            echo printInfo( "User said NO!" );
            redirect( 'user/show_public');
        }
        else if( $action == 'edit' )
        {
            // Here we need to load quick-book interface but with talk-id.
            // Use _GET method.
            $this->load_user_view( 'user_manage_talks_action_update', $_POST );
            return;
        }
        else if( $action == 'update' )
        {
            $msg = '';
            $res = updateTable( 'talks', 'id'
                        , 'class,host,coordinator,title,description'
                        , $_POST 
                    );

            if( $res )
            {
                $msg .= printInfo( 'Successfully updated entry' );
                // Now update the related event as wel.
                $event = getEventsOfTalkId( $_POST[ 'id' ] );
                $tableName = 'events';
                if( ! $event )
                {
                    $event = getBookingRequestOfTalkId( $_POST[ 'id' ] );
                    $tableName = 'bookmyvenue_requests';
                }

                if( $event )
                {
                    $res = updateTable( $tableName, 'external_id'
                                    , 'title,description'
                                    , array( 'external_id' => "talks." . $_POST[ 'id' ] 
                                        , 'title' => talkToEventTitle( $_POST )
                                        , 'description' => $_POST[ 'description' ]
                                    )
                        );

                    if( $res )
                        flashMessage( "Successfully updated associtated event" );
                }
            }

            redirect( 'user/show_public' );
        }
        else if( $action == 'schedule' )
        {
            $tid = $_POST['id'];
            redirect( "user/book_talk/$tid" );
            return;
        }
        else if($action == 'submit' )
        {
            $res = updateTable( 'talks', 'id'
                        , 'class,host,coordinator,title,description'
                        , $_POST 
                    );

            if( $res )
            {
                echo printInfo( 'Successfully updated entry' );
                // Now update the related event as wel.
                $event = getEventsOfTalkId( $_POST[ 'id' ] );
                $tableName = 'events';
                if( ! $event )
                {
                    $event = getBookingRequestOfTalkId( $_POST[ 'id' ] );
                    $tableName = 'bookmyvenue_requests';
                }

                if( $event )
                {
                    $res = updateTable( $tableName, 'external_id'
                                    , 'title,description'
                                    , array( 'external_id' => "talks." . $_POST[ 'id' ] 
                                        , 'title' => talkToEventTitle( $_POST )
                                        , 'description' => $_POST[ 'description' ]
                                    )
                        );

                    if( $res )
                        echo printInfo( "Successfully updated associtated event" );
                }

                redirect('user/show_public');
                return;
            }
            else
                echo printWarning( "Failed to update the talk " );
        }
        else
            flashMessage( "Unknown operation $action." );
            
        redirect( "user/show_public");
    }


    public function register_talk_submit( )
    {
        /* ALL EVENTS GENERATED FROM THIS INTERFACE ARE SUITABLE FOR GOOGLE CALENDAR. */
        // Here I get both speaker and talk details. I need a function which can either 
        // insert of update the speaker table. Other to create a entry in talks table.
        // Sanity check 
        $msg = '';
        $missing = findIncompleteEntries( $_POST, 'first_name,institute,title,description');
        if( $missing )
        {
            $msg .= p( "Incomplete required fields: $missing." );
            $msg .= arrayToVerticalTableHTML( $_POST, 'info' );
            flashMessage( "Failed to register your talk. <br />
                You submitted following information:<br /> $msg" 
                );
            $this->load_user_view( "user_register_talk", $_POST );
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
                $startTime = $_POST[ 'start_time' ];
                $endTime = $_POST[ 'end_time' ];

                if( isBookingRequestValid( $_POST ) )
                {
                    $date = $_POST[ 'date' ];
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
                                <br />";
                            $msg .= "<p>You can book a slot for this talk later" 
                                . " by visiting <tt>'Manage my public events'</tt> link in 
                                your homepage. </p>";
                            echo printWarning( $msg );
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
                                $msg .= p( "Oh Snap! Failed to create booking request" );
                        }
                    }
                    else
                        $msg .= p( "The booking request associtated with talk is invalid.") 
                                . arrayToTableHTML( $_POST, 'info' );
                }
            }
            else
                $msg .= minionEmbarrassed( "Failed to add your talk to database." );
        }
        else
            $msg .= minionEmbarrassed( "Failed to add speaker to database." );

        //$msg = p( "If something unexpected has happened, curse my creator and write to my
        //           current maintainers. You can seek help by writing to hippo@lists.ncbs.res.in.
        //           It helps to attach screenshots."
        //        );
        echo flashMessage( $msg );
        redirect( "user/show_public");
    }

    public function delete_booking_of_talk( $gid )
    {
        // deleting $talkid
        $_POST[ 'gid' ] = $gid;
        $_POST[ 'status' ] = 'CANCELLED';
        $res = updateTable( 'bookmyvenue_requests', 'gid', 'status', $_POST ); 
        if( $res )
            flashMessage( "Successfully removed booking request for $talkid.");
        redirect( 'user/show_public' );
    }

    public function edit_booking_of_talk_submit( $controller='user', $action = 'show_public' )
    {
        $goback = "$controller/$action";
        $res = updateTable( "bookmyvenue_requests", "gid,rid", "title,description", $_POST );
        if( $res )
            flashMessage( "Successfully updated booking request." );
        redirect( $goback);
    }
}

?>
