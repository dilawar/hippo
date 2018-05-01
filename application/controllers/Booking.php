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
        $this->template->load('user_show_events');
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

                echo flashMessage( "Your request has been submitted." );
                redirect("$ref/book");
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

    public function register_talk( $arg = '' )
    {
        if( $arg == 'submit' )
        {
            $msg = register_talk_and_optionally_book( $_POST );
            if( "OK" != $msg )
            {
                $msg .= arrayToVerticalTableHTML( $data, "request" );
                flashMessage( 'Successfully booked', 'error' );
                redirect( 'user/book' );
            }
            else
            {
                flashMessage( $msg, 'warning' );
                redirect( 'user/home' );
            }
        }
        else
        {
            $this->template->set( 'header', 'header.php' );
            $this->template->load('user_register_talk');
        }
    }
}

?>
