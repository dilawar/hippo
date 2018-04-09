<?php

include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

echo "<h3>Manage pending requests</h3>";


// Second review pending requests for AWS modification.
$pendingRequests = getPendingAWSRequests( );
foreach( $pendingRequests as $req )
{
    $speaker = $req['speaker'];
    $date = $req['date'];
    $aws = getMyAwsOn( $speaker, $date );

    echo '<form method="post" action="admin_acad_manages_requests_submit.php">';
    echo arrayToVerticalTableHTML( $req, 'aws', ''
        , array( 'id', 'status', 'modified_on' ) 
    );

    echo '<table class="show_aws" border="0">
        <tr style="background:white">
            <td style="border:0px;min-width:50%;align:left;">
            <textarea rows="3" cols="80%" name="reason" 
                placeholder="Reason for rejection" >Reason for rejection</textarea>
            <button type="submit" name ="response" value="Reject">Reject</button>
            </td>
        </tr><tr>
            <td style="border:0px;max-width:50%;">
                <button type="submit" name ="response" value="Accept">Accept</button>
            </td>
        </tr>
    </table>';
    echo '<input type="hidden" name="request_id" value="' . $req['id'] . '" >';
    echo '<input type="hidden" name="speaker" value="' . $speaker . '" >';
    echo '<input type="hidden" name="date" value="' . $date . '" >';
    echo '</form>';
}

echo goBackToPageLink( "admin_acad.php", "Go back" );

?>
