<?php

require_once BASEPATH. 'autoload.php';

trait AWS 
{
    public function aws( string $arg = '', string $arg2 = '' )
    {
        if( strtolower($arg) == 'schedulingrequest' )
        {
            if( $arg2 == 'create' )
            {
                $this->template->load( 'header.php' );
                $this->template->load( 'user_aws_scheduling_request' );
            }
            else if( $arg2 == 'submit' )
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
                $_POST['status'] = 'PENDING';
                $_POST['created_on'] = dbDateTime( 'now' );
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
                redirect( 'user/aws' );
            }
            else if( strtolower(trim($arg2)) == 'delete' )
            {
                // Cancel the scheduling request.
                $table = getTableEntry( 'aws_scheduling_request', 'id', $_POST );
                if( $table )
                    $_POST = array_merge( $_POST, $table );
                else
                    echo "No entry found";

                $_POST[ 'status' ] = 'CANCELLED';
                $res = updateTable( 'aws_scheduling_request', 'id', 'status', $_POST );
                if( $res )
                {
                    $subject = "You have cancelled your AWS preference";
                    $this->session->set_flashdata( 'success', "Successfully cancelled" );
                }
                redirect( 'user/aws' );
            }
            else if( $arg2 )
            {
                // Unknown action 2.
                echo "Unknown action $arg2";
            }
            else
            {
                // All action are done. Send user back to aws page.
                $this->template->load( 'header.php' );
                $this->template->load( 'user_aws' );
            }
        }
        else if( strtolower(trim($arg)) == 'update_upcoming_aws' )
        {
            $this->load_user_view( "user_aws_update_upcoming_aws.php" );
        }
        else
        {
            if( $arg )
                flashMessage( "Unnown action $arg", 'error' );

            $this->load_user_view( "user_aws" );
        }
    }

}

?>
