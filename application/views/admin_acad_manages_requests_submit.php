<?php

include_once 'header.php';
include_once 'check_access_permissions.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'mail.php';

mustHaveAllOfTheseRoles( array( "AWS_ADMIN" ) );

echo userHTML( );

// Start preparing email.
$speaker = $_POST[ 'speaker' ];
$speakerInfo = getUserInfo( $speaker );
$rid = $_POST[ 'request_id' ];
$user = loginToText( $speaker );

$msg = '<p>Dear ' . $user . ' </p>';

if( $_POST[ 'response' ] == 'Reject' )
{
    if( strlen( $_POST[ 'reason' ]) < 8 )
    {
        echo printWarning( "
            Empty reason or reason too short (less than 8 characters).
            A request can not rejected without a proper reason.
            You must enter a valid reason."
        );
        echo goBackToPageLink( "admin_acad_manages_requests.php", "Go back" );
        exit;
    }

    $res = updateTable( 
        'aws_requests', 'id' , 'status'
        , array( 'id' => $rid, 'status' => 'REJECTED' )
    );

    if( $res )
    {
        echo printInfo( "This request has been rejected" );
        // Now notify user.
        $msg .= "<p>Your AWS add/edit request has been rejected </p>";
        $msg .= "<p>Reason: " . $_POST[ 'reason' ] . "</p>";
        $msg .= "<p>Feel free to drop an email to hippo@lists.ncbs.res.in for
            further clarification. Please mention your request id in email.
            </p>";

        // Get the latest request.
        $req = getAwsRequestById( $rid );
        $msg .= arrayToVerticalTableHTML( $req, "request" );

        sendHTMLEmail( $msg
            , "Your AWS edit request (id:". $rid . ") has been rejected"
            , $speakerInfo[ 'email' ]
        );

        goToPage( "admin_acad_manages_requests.php", 1 );
        exit;
    }
}
elseif( $_POST['response'] == 'Accept' )
{
    $date = $_POST[ 'date' ];
    $aws = getMyAwsOn( $speaker, $date );
    $req = getAwsRequestById( $rid );

    $req[ 'is_presynopsis_seminar' ] = __get__( $_POST, 'is_presynopsis_seminar', 'NO' );

    $res = updateTable( 'annual_work_seminars'
            , 'speaker,date' 
            , array( 'abstract'
                , 'title'
                , 'is_presynopsis_seminar'
                , 'supervisor_1', 'supervisor_2'
                , 'tcm_member_1', 'tcm_member_2', 'tcm_member_3', 'tcm_member_4' 
                )
            , $req
            );

    if( $res )
    {
        $res = updateTable( 
            'aws_requests', 'id', 'status'
            , array( 'id' => $rid, 'status' => 'APPROVED' ) 
        );

        if( $res )
        {
            $user = loginToText( $speaker );
            $msg .= "<p>
                Your edit to your AWS entry has been approved. 
                The updated entry is following:
                </p>";

            // Get the latest request.
            $req = getAwsRequestById( $rid );
            $msg .= arrayToVerticalTableHTML( $req, "request" );

            sendHTMLEmail( $msg
                , "Your AWS edit request (id:$rid) has been approved"
                , $speakerInfo['email' ]
            );
            
            echo goToPage( 'admin_acad_manages_requests.php', 1 );
            exit;
        }
    }
    else
        echo printWarning( "Could not update the AWS table" );
}
else
{
    echo printWarning( "Unknown request " . $_POST[ 'response' ] );
}

echo goBackToPageLink( "admin_acad_manages_requests.php", "Go back" );

?>
