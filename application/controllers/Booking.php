<?php

require_once BASEPATH. 'autoload.php';

trait Booking 
{
    public function bookingrequest( $arg = '' )
    {
        log_message( 'info', 'Creating booking requests' );
        $this->template->set( 'header', 'header.php' );
        $this->template->load( 'user_booking_request' );
    }

    public function bookingrequest_submit( $arg = '' )
    {
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
                redirect('user/book' );
            }
            else
            {
                echo printWarning( 
                    "Your request could not be submitted. Please notify the admin." 
                );
                redirect('user/home');
            }
        }
        else
        {
            $msg1 = "There was an error in request.";
            $msg1 .= "<p>$msg</p>";
            $msg1 .= "<p>Complete entry is following</p>";
            $msg1 .= arrayToVerticalTableHTML( $_POST, "request" );
            flashMessage( $msg1 );
            redirect( 'user/book' );
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
                $this->session->set_flashdata( 'error', $msg );
                redirect( 'user/register_talk' );
            }
            else
                redirect( 'user/home' );
        }
        else
        {
            $this->template->set( 'header', 'header.php' );
            $this->template->load('user_register_talk');
        }
    }

}

?>
