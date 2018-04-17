<?php

require_once BASEPATH. 'autoload.php';

trait AWS 
{
    public function aws( string $arg = '', string $arg2 = '' )
    {
        if( strtolower($arg) == 'schedulingrequest' )
        {
            if( $arg2 == 'submit' )
            {
                // Submit the given request.
                $_POST[ 'speaker' ] = whoAmI();
                $login = whoAmI();

                // Check if preferences are available.
                $firstPref = __get__( $_POST, 'first_preference', '' );
                $secondPref = __get__( $_POST, 'second_preference', '' );
                $keys = 'id,speaker,reason,created_on';
                $updateKeys = 'created_on,reason';

                // check if dates are monday. If not assign next monday.
                $firstPref = nextMonday( $firstPref );
                $secondPref = nextMonday( $secondPref );

                if( $firstPref )
                {
                    $prefDate = dbDate( $firstPref );
                    if( strtotime( 'next monday' ) >= strtotime( $prefDate) )
                        echo printInfo( "I can not change the past without Time Machine. 
                        Ignoring " . humanReadableDate( $prefDate ) );
                    else
                    {
                        $upcomingAWSs = getTableEntries( 
                            'upcoming_aws', 'date', "date='$prefDate'" 
                        );
                        if( count( $upcomingAWSs ) == 3 )
                            echo printInfo( "Date $prefDate is not available. Ignoring ..." );
                        else
                        {
                            $keys .= ',first_preference';
                            $updateKeys .= ',first_preference';
                        }
                    }
                }

                if( $secondPref )
                {
                    $prefDate = dbDate( $secondPref );
                    if( strtotime( 'next monday' ) >= strtotime( $prefDate) )
                        echo printInfo( "I can not change the past without Time Machine. 
                        Ignoring " . humanReadableDate( $prefDate ) );
                    else
                    {
                        $upcomingAWSs = getTableEntries( 
                            'upcoming_aws', 'date', "date='$prefDate'" 
                        );

                        if( count( $upcomingAWSs ) == 3 )
                            echo printInfo( "Date $prefDate is not available. Ignoring ..." );
                        else
                        {
                            $keys .= ",second_preference";
                            $updateKeys .= ",second_preference";
                        }
                    }
                }

                $updateKeys .= ',status';
                $_POST[ 'status' ] = 'PENDING';
                $res = insertOrUpdateTable( 'aws_scheduling_request', $keys, $updateKeys, $_POST);

                if( $res )
                    $_POST[ 'id' ] = $res[ 'id' ];
                else
                    $sendEmail = false;

                // Create subject for email
                $subject = "Your preferences for AWS schedule has been recieved";

                $msg = "<p>Dear " . loginToText( $login ) . "</p>";
                $msg .= "<p>Your scheduling request has been logged. </p>";
                $msg .= arrayToVerticalTableHTML( $_POST, 'info', NULL, 'response' );

                $email = getLoginEmail( $login );
                sendHTMLEmail( $msg, $subject, $email, 'hippo@lists.ncbs.res.in' );
            }
            else if( $arg2 == 'delete' )
            {
                $table = getTableEntry( 'aws_scheduling_request', 'id', $_POST );
                if( $table )
                    $_POST = array_merge( $_POST, $table );

                $_POST[ 'status' ] = 'CANCELLED';
                $res = updateTable( 'aws_scheduling_request', 'id'
                            , 'status', $_POST );
                if( $res )
                    $subject = "You have cancelled your AWS preference";
                else
                    $sendEmail = false;
            }
            else if( $arg2 )
            {
                // Unknown action 2.
                echo "Unknown action $arg2";
            }

            // All action are done. Send user back to aws page.
            $this->template->load( 'header.php' );
            $this->template->load( 'user_aws' );
        }
        else
        {
            if( $arg )
                $this->session->set_flashdata( 'error', "Unknown action $arg" );

            $this->template->load( 'header.php' );
            $this->template->load( 'user_aws' );
        }
    }

}

?>
