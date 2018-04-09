<?php

include_once 'database.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';
include_once 'mail.php';

mustHaveAllOfTheseRoles( Array( 'ADMIN' ) );
echo userHTML( );

// Logic to handle post request here.
if( array_key_exists( 'Response', $_POST ) )
{
    if( $_POST[ 'Response' ] == 'Cancel' )
    {
        $_POST[ 'status' ] = 'CANCELLED';
        updateTable( 'emails', 'id', 'status', $_POST );
    }
    else if( $_POST[ 'Response' ] == 'SendNow' )
    {
        echo printInfo( "Sending email now" );
        $_POST[ 'last_tried_on' ] = dbDateTime( 'now' );
        $res = sendEmailById( $_POST[ 'id' ] ); 

        if( $res )
            $_POST[ 'status' ] = 'SENT';
        else
            $_POST[ 'status' ] = 'FAILED';

        updateTable( 'emails', 'id', 'status,last_tried_on', $_POST );
    }
}


$sentEmail = getEmailsByStatus( 'SENT' );
$pendingEmails = getEmailsByStatus( 'PENDING' );
$failedEmail = getEmailsByStatus( 'FAILED' );

echo "<h3>Emails statistics</h3>";
echo '<table class="show_info">
    <tr> <td>Sent emails</td><td>' . count( $sentEmail ) . '</td> </tr>
    <tr> <td>Pending emails</td><td>' . count( $pendingEmails) . '</td> </tr>
    <tr> <td>Failed emails</td><td>' . count( $failedEmail) . '</td> </tr>
    </table>';

$nonSentEmails = array_merge( $pendingEmails, $failedEmail );
if( count( $nonSentEmails ) > 0 )
    echo "<h3>Following emails are not sent yet </h3>";

echo '<form method="post" action="">';
foreach( $nonSentEmails as $email )
{
    echo dbTableToHTMLTable( 'emails', $email );
    echo '<table style="min-width:600px">';
    echo '<tr>';
    echo '<td>';
    echo '<button type="submit" name="Response" value="SendNow">Send Now</button>';
    echo '</td><td>';
    echo '<button type="submit" name="Response" value="Cancel">Cancel</button>';
    echo '</td></tr>';
    echo '</table>';
    echo '</br>';
}
echo '</form>';

echo goBackToPageLink( "admin.php", "Go back" );

?>

