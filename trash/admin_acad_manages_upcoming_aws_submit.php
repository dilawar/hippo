<?php

include_once 'database.php';
include_once 'methods.php';
include_once "check_access_permissions.php";
include_once 'tohtml.php';
include_once 'mail.php';

mustHaveAllOfTheseRoles( array( 'AWS_ADMIN' ) );


if( __substr__( "reschedule", $_POST['response'] ) )
{
    $err = rescheduleAWS( $_POST[ 'response' ] );
    if( ! $err )
    {
        goToPage( 'admin_acad_manages_upcoming_aws.php', 1);
        exit;
    }
}

else if( $_POST[ 'response' ] == 'Accept' or $_POST[ 'response' ] == 'Assign' )
{
    $speaker = explode( '@', $_POST[ 'speaker' ] )[0];
    $date = $_POST[ 'date' ];
    if(  $speaker && getLoginInfo( $speaker ) && strtotime( $date ) > strtotime( '-7 day' ) )
    {
        $aws = getUpcomingAWSOfSpeaker( $speaker );
        if( $aws )
        {
            echo printWarning( "$speaker already has AWS scheduled. Doing nothing." );
            echo arrayToVerticalTableHTML( $aws, 'aws' );
        }
        else
        {
            $awsID = acceptScheduleOfAWS( $speaker, $date );
            if( $awsID > 0 )
            {
                echo printInfo( "Successfully assigned" );

                // When accepting the computed schedule, we don't want to run the
                // rescheduling algo.
                if( $_POST[ 'response' ] == 'Assign' )
                    rescheduleAWS( );

                // Send email to user.
                $res = notifyUserAboutUpcomingAWS( $_POST[ 'speaker' ], $_POST[ 'date' ], $awsID );
                if( $res )
                {
                    goToPage( "admin_acad_manages_upcoming_aws.php", 1 );
                    exit;
                }
                else
                    echo printWarning( "Failed to send email to user" );
            }
            else
                echo printWarning( "Invalid entry. Probably date ('$date') is in past." );
        }
    }
    else
        echo printWarning( "Invalid speaker '$speaker' or date '$date' is in past.
                Could not assign AWS."
            );
}
else if( $_POST[ 'response' ] == 'format_abstract' )
{
    // Update the user entry
    echo printInfo( "Admin is allowed to reformat the entry. That is why only
        abstract can be modified here" );

    $aws = getTableEntry( 'upcoming_aws', "speaker,date", $_POST );
    if( ! $aws )
        echo alertUser( "Nothing to update" );
    else
    {
        echo '<form method="post" action="admin_acad_manages_upcoming_aws_reformat.php">';
        echo dbTableToHTMLTable( 'upcoming_aws', $aws, 'abstract,is_presynopsis_seminar' );
        echo '</form>';
    }
}
else if( $_POST[ 'response' ] == 'RemoveSpeaker' )
{
    $res = removeAWSSpeakerFromList( $_POST[ 'speaker' ] );
    // And reschedule AWS entry.
    rescheduleAWS( );
    goToPage( "admin_acad_manages_upcoming_aws.php", 1 );
    exit(1);
}

else if( $_POST[ 'response' ] == 'delete' )
{
    $res = clearUpcomingAWS( $_POST[ 'speaker'], $_POST[ 'date' ] );
    if( $res )
    {
        rescheduleAWS( );
        echo printInfo( "Successfully cleared upcoming AWS" );

        $admin = $_SESSION[ 'user' ];

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
        goToPage( "admin_acad_manages_upcoming_aws.php", 1 );
        exit;
    }
}
else if( $_POST[ 'response' ] == "DO_NOTHING" )
{
    echo printInfo( "User cancelled the previous action" );
    goBack( );
    exit;
}
else
{
    echo printWarning( "To Do " . $_POST[ 'response' ] );
}

echo goBackToPageLink( "admin_acad_manages_upcoming_aws.php", "Go back" );

?>
