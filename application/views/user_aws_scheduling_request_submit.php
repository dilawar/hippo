<?php 

include_once 'header.php';
include_once 'database.php';
include_once 'mail.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAnyOfTheseRoles( array( 'USER' ) );
// Logic to add your preference.

$sendEmail = true;

// Speaker is the current user.
$_POST[ 'speaker' ] = $_SESSION[ 'user' ];
$login = $_SESSION[ 'user' ];

if( $_POST[ 'response' ] == 'submit' )
{
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
    $res = insertOrUpdateTable(
                'aws_scheduling_request', $keys, $updateKeys, $_POST
            );

    if( $res )
    {
        // Store id, it is needed to send email.
        $_POST[ 'id' ] = $res[ 'id' ];
        echo printInfo( "I have recorded your preferences." );
    }
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
else if( $_POST[ 'response' ] == 'delete' )
{
    $table = getTableEntry( 'aws_scheduling_request', 'id', $_POST );
    if( $table )
        $_POST = array_merge( $_POST, $table );
    $_POST[ 'status' ] = 'CANCELLED';

    $res = updateTable( 'aws_scheduling_request', 'id'
                , 'status', $_POST );
    if( $res )
    {
        echo printInfo( "Sucessfully cancelled your request" );
        $subject = "You have cancelled your AWS preference";
    }
    else
        $sendEmail = false;
}

echo goBackToPageLink( "user_aws.php", "Go back" );

?>
