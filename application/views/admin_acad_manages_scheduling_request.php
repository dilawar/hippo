<?php

require_once BASEPATH.'autoload.php';



// First, review request for upcoming AWS.
$schedulingReqs = getTableEntries( 'aws_scheduling_request', 'status'
    , "status='PENDING'" );

$ref = 'admin/acad';
if(isset($controller))
    $ref = $controller;

if( count( $schedulingReqs ) == 0 )
{
    echo printInfo( "No scheduling request. Its very quiet in here!" );
}
else
{
    echo "<h3>Manage pending requests</h3>";
    foreach( $schedulingReqs as $req )
    {
        echo '<form method="post" action="admin_acad_manages_scheduling_request_submit.php">';
        echo dbTableToHTMLTable( 'aws_scheduling_request', $req, '', '' );
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
    }
}

echo goBackToPageLink( 'adminacad/home', 'Go back' );

?>
