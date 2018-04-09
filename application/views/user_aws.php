<?php

include_once( "header.php" );
include_once( "methods.php" );
include_once( 'tohtml.php' );
include_once( "check_access_permissions.php" );

mustHaveAnyOfTheseRoles( Array( 'USER' ) );

echo userHTML( );

// AWS schedule.
echo '<div style="border:1px dotted">';
$scheduledAWS = scheduledAWSInFuture( $_SESSION['user'] );
$tempScheduleAWS = temporaryAwsSchedule( $_SESSION[ 'user' ] );
if( $scheduledAWS )
{
    echo alertUser( "
        <font color=\"blue\">&#x2620 Your AWS date has been confirmed. It is on " .
        humanReadableDate( $scheduledAWS[ 'date' ] ) . '.</font>'
    );

    $disabled = '';
    if( $scheduledAWS[ 'acknowledged' ] == 'YES' )
    {
        echo "You've acknowledged your AWS schedule.";
        $disabled = 'disabled';
    }
    else
    {
    echo printInfo(
        "By pressing this button, you are confirming your AWS schedule.
        If you have already acknowledged, this button will be disabled.
        Please contact academic office in case you want to change your schedule.
        " );
    }

    echo "
        <form method=\"post\" action=\"user_acknowledge_aws_schedule_action.php\">
        <button $disabled name=\"acknowledged\" value=\"YES\">Acknowledge schedule</button>
        <input type=\"hidden\" name=\"id\" value=\"" . $scheduledAWS[ 'id' ] . "\" >
        </form>
        ";

}
else
{

    // Here user can submit preferences.
    $prefs = getTableEntry( 'aws_scheduling_request', 'speaker,status'
                , array( 'speaker' => $_SESSION[ 'user' ]
                    , 'status' => 'PENDING' ) );

    $approved  = getTableEntry( 'aws_scheduling_request', 'speaker,status'
                , array( 'speaker' => $_SESSION[ 'user' ]
                    , 'status' => 'APPROVED' ) );

    if( ! $prefs )
    {
        echo printInfo(
            "You can tell me know your preferred dates. I will try my best to
            assign you on or very near to these dates (+/- 2 weeks). Make sure that
            you are available at these dates.
            "
            );

        echo '<form method="post" action="user_aws_scheduling_request.php">';
        echo '<button type="submit">Create preference</button>';
        echo '<input type="hidden" name="speaker" value="' . $_SESSION[ 'user' ] . '">';
        echo '</form>';
    }
    else if( $prefs[ 'status' ] == 'PENDING' )
    {
        echo printInfo( "You preference for AWS schedule is pending. If you have
            changed your mind, cancel it. After approval, you wont be able to modify
            this request. We usually wait for two days before approving.
            " );

        echo '<form method="post" action="user_aws_scheduling_request.php">';
        echo dbTableToHTMLTable( 'aws_scheduling_request', $prefs, '', 'edit' );
        echo '<input type="hidden" name="created_on"
                value="' . dbDateTime( 'now' ) . '" >';
        echo '</form>';
        // Cancel goes directly to cancelling the request. Only non-approved
        // requests can be cancelled.
        echo '<form method="post" action="user_aws_scheduling_request_submit.php">';
        echo '<button onclick="AreYouSure(this)"
                name="response" title="Cancel this request"
                type="submit">' . $symbCancel . '</button>';
        echo '<input type="hidden" name="id" value="'. $prefs[ 'id' ].'">';
        echo '</form>';
    }

    if( $approved )
    {
        echo '<strong>You already have a request below.
            Notice that request is only effective when its <tt>STATUS</tt> has
            changed to <tt>APPROVED</tt>. </strong>';

        // Form to revoke the approved preference.
        echo ' <form method="post" action="user_revoke_aws_preference.php">';
        echo arrayToTableHTML( $approved, 'info' );
        echo '<button name="response" value="delete_preference">Revoke</button> ';
        echo '<input type="hidden" name="id" value="' . $approved['id'] . '" />';
        echo '</form>';


    }

}
echo '</div>';


if( $scheduledAWS )
{

    $editableTill = strtotime( '-1 day', strtotime( $scheduledAWS[ 'date' ] ) );
    echo printInfo( 'Please fill-in details of your upcoming  AWS below.
         We will use this to generate the notification email
         and document. You can change it as many times as you like upto
         23:59 Hrs, ' . humanReadableDate( $editableTill ) . '
         <small> (Note: We will not store the old version).</small>
         ' );
    $id = $scheduledAWS[ 'id' ];
    echo "<form method=\"post\" action=\"user_aws_update_upcoming_aws.php\">";
    echo arrayToVerticalTableHTML( $scheduledAWS, 'aws', NULL
        , Array( 'speaker', 'id' ));
    echo "<button class=\"submit\" name=\"response\"
        title=\"Update this entry\" value=\"update\">Edit</button>";
    echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />";
    echo "</form>";
}

$awsRequests = getAwsRequestsByUser( $_SESSION['user'] );
if( count( $awsRequests ) > 0 )
    echo "<h3>Update pending requests</h3>";

foreach( $awsRequests as $awsr )
{
    $id = $awsr['id'];
    echo "<form method=\"post\" action=\"user_aws_request.php\">";
    echo arrayToVerticalTableHTML( $awsr, 'aws' );
    echo "<button name=\"response\" value=\"edit\">Edit</button>";
    echo "<button name=\"response\" value=\"cancel\">Cancel</button>";
    echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />";
    echo "</form>";
}

echo ' <br /> <br />';
echo goBackToPageLink( "user.php", "Go back" );

echo '<h2><i class="fa fa-leanpub"></i> I have learnt \'deeply\' from previous AWSs</h2>';

echo "<p>I have been trained using Artificial Intelligence algorithms to write
    AWS abstract. Ask me to write your AWS abstract by pressing the button below (puny human)!</p>
    ";
echo ' <form action="" method="post" accept-charset="utf-8">
        <button type="submit" name="response" value="write_my_aws">Write My AWS</button>
        <button type="submit" name="response" value="clean_up">Cleap up</button>
    </form>';

if( __get__( $_POST, 'response', '' ) == 'write_my_aws' )
{
    $cmd = __DIR__ . '/write_aws_using_ai.py';
    hippo_shell_exec( $cmd, $awsText, $stderr );
    echo "<p> $awsText </p>";
    echo "<br>";
    echo "<p> I will only get better! </p>";
}



echo '<br>';
echo goBackToPageLink( "user.php", "Go back" );
echo "<br />";

echo "<h1>Past Annual Work Seminar</h1>";

echo "<table>";
echo '<tr><td>
    If you notice your AWS entry is missing from the list below, please emails details to
    <a href="mailto:hippo@lists.ncbs.res.in" target="_black">hippo@lists.ncbs.res.in</a>
    ';
echo "</td></tr>";
echo "</table>";
echo "<br/>";

$awses = getMyAws( $_SESSION['user'] );

foreach( $awses as $aws )
{
    $id = $aws['id'];
    echo "<div>";
    // One can submit an edit request to AWS.
    echo "<form method=\"post\" action=\"user_aws_edit_request.php\">";
    echo arrayToVerticalTableHTML( $aws, 'aws', NULL
    , Array( 'speaker', 'id' ));
    echo "<button title=\"Edit this entry\"
            name=\"response\" value=\"edit\">" . $symbEdit . "</button>";
    echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />";
    echo "</form>";
    echo "<br /><br />";
    echo "</div>";
    echo awsPdfURL( $aws['speaker' ], $aws[ 'date' ] );
}

echo goBackToPageLink( "user.php", "Go back" );

?>
