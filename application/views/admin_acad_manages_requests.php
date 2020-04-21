<?php

require_once BASEPATH.'autoload.php';

echo "<h1>Manging pending requests</h1>";

// Second review pending requests for AWS modification.
$pendingRequests = getPendingAWSRequests();
foreach ($pendingRequests as $req) {
    $speaker = $req['speaker'];
    $date = $req['date'];
    $aws = getMyAwsOn($speaker, $date);

    echo '<form method="post" action="'.site_url("adminacad/aws_edit_request_action") . '">';
    echo arrayToVerticalTableHTML($req, 'aws', '', array( 'id', 'status', 'modified_on'));

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

echo goBackToPageLink("adminacad/home", "Go back");
