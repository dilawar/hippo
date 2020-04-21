<?php
require_once BASEPATH.'autoload.php';
echo userHTML();

$user = $cUser;
$scheduledAWS = $cScheduledAWS;
$tempScheduleAWS = $cTempScheduleAWS;
$awsRequests = getAwsRequestsByUser($user);
$awses = getMyAws($user);
$eligible = isEligibleForAWS($user);

// AWS schedule.
if ($scheduledAWS && $eligible) {
    alertUser("<i class='fa fa-bell'></i>
            Your AWS date has been confirmed. It is on " .
        humanReadableDate($scheduledAWS['date']));

    $disabled = '';
    if ($scheduledAWS['acknowledged'] === 'YES') {
        echo "<div class='bg-info'>You've acknowledged your AWS schedule. </div>";
        $disabled = 'disabled';
    } else {
        echo "<div class='info'>
            By pressing this button, you are confirming your AWS schedule.
            Please contact academic office in case you want to change your schedule.";

        echo '<form method="post" action="' . site_url('user/aws_acknowledge') . "\">
            <button class='btn-secondary' $disabled name=\"acknowledged\" value=\"YES\">Acknowledge schedule</button>
            <input type=\"hidden\" name=\"id\" value=\"" . $scheduledAWS[ 'id' ] . "\" >
            </form>
            ";
        echo '</div>';
    }
} elseif ($eligible) {
    // // Here user can submit preferences.
    // $prefs = getTableEntry( 'aws_scheduling_request', 'speaker,status'
    //             , array( 'speaker' => $user
    //                 , 'status' => 'PENDING' ) );

    // $approved  = getTableEntry( 'aws_scheduling_request', 'speaker,status'
    //             , array( 'speaker' => $user
    //                 , 'status' => 'APPROVED' ) );

    // if( ! $prefs )
    // {
    //     echo printInfo(
    //         "You can tell me know your preferred dates. I will try my best to
    //         assign you on or very near to these dates (+/- 2 weeks). Make sure that
    //         you are available at these dates.
    //         "
    //         );

    //     echo '<form method="post" action="' . site_url( 'user/aws/schedulingrequest/create' ) . '">';
    //     echo '<button type="submit">Create preference</button>';
    //     echo '<input type="hidden" name="speaker" value="' . $user . '">';
    //     echo '</form>';
    // }
    // else if( $prefs[ 'status' ] == 'PENDING' )
    // {
    //     echo printInfo( "You preference for AWS schedule is pending. If you have
    //         changed your mind, cancel it. After approval, you wont be able to modify
    //         this request. "
    //         );

    //     echo '<form method="post" action="' . site_url( 'user/aws/schedulingrequest' ) . '">';
    //     echo dbTableToHTMLTable( 'aws_scheduling_request', $prefs, '', 'edit' );
    //     echo '<input type="hidden" name="created_on" value="' . dbDateTime( 'now' ) . '" >';
    //     echo '</form>';

    //     // Cancel goes directly to cancelling the request. Only non-approved
    //     // requests can be cancelled.
    //     echo '<form method="post" action="' . site_url( 'user/aws/schedulingrequest/delete') . '">';
    //     echo '<button onclick="AreYouSure(this)"
    //             name="response" title="Cancel this request"
    //             type="submit">  <i class="fa fa-trash "></i>
    //               </button>';
    //     echo '<input type="hidden" name="id" value="'. $prefs[ 'id' ].'">';
    //     echo '</form>';
    // }

    // if( $approved )
    // {
    //     echo '<strong>You already have a request below.
    //         Notice that request is only effective when its <tt>STATUS</tt> has
    //         changed to <tt>APPROVED</tt>. </strong>';

    //     // Form to revoke the approved preference.
    //     echo ' <form method="post" action="' . site_url( 'user/aws/schedulingrequest/revoke' ) . '">';
    //     echo arrayToTableHTML( $approved, 'info' );
    //     echo '<button name="response" value="delete_preference">Revoke</button> ';
    //     echo '<input type="hidden" name="id" value="' . $approved['id'] . '" />';
    //     echo '</form>';
    // }
}
?>

<?php if (! $eligible): ?>
<div class="alert alert-warning">
    Your name is no longer on the AWS roster.
    Usually a name is removed after one's <tt>PRESYNOPSIS SEMINARY</tt> 
    or <tt>THESIS SEMINAR</tt>. If this is mistake, please write to Academic Office.
</div>
<?php endif;?>

<?php if ($scheduledAWS): ?>
    <?php $editableTill = strtotime('-1 day', strtotime($scheduledAWS[ 'date' ]));?>
    <div class="card">
        <div class="card-header">
        Please fill-in details of your upcoming  AWS below.
         We will use this to generate the notification email
         and document. You can change it as many times as you like upto
         23:59 Hrs, <?=humanReadableDate($editableTill)?> 
         <small> (Note: We will not store the old version).</small>
            </div>
    </div>
    <?php $id = $scheduledAWS[ 'id' ]; ?>
    <div class="card-body p-2 m-3">
    <form method="post" action="<?=site_url('user/aws/update_upcoming_aws')?>">
    <?=arrayToVerticalTableHTML($scheduledAWS, 'aws', null, 'speaker,id')?>
    <button class="btn btn-secondary" name="response"
        title="Update this entry" value="update">Edit</button>
        <input type="hidden" name="id" value="<?=$id?>" />
    </form>
    </div>
<?php endif; ?>

<?php if (count($awsRequests) > 0): ?>
    <div class="h1">Update pending requests</div>
<?php endif; ?>
<?php foreach ($awsRequests as $awsr): ?>
    <?php $id = $awsr['id']; ?>
    <?= arrayToVerticalTableHTML($awsr, 'aa table table-sm table-secondary');?>
    <form method="post" action="<?=site_url('user/aws/edit_request_cancel')?>">
        <button class='btn btn-warning' name="response" value="cancel">Cancel</button>
        <input type="hidden" name="id" value="<?=$id ?>" />
        You can only <tt>Cancel</tt> this request. No editing allowed.
    </form>
<?php endforeach; ?>

<br />

<div class="card">
    <div class='card-header'>
        <i class="fa fa-leanpub"></i> I have learnt 'deeply' from previous AWSs
    </div>
    <div class='card-body'>
        I have been trained using Artificial Intelligence algorithms to write 
        AWS abstract. Ask me to write your AWS abstract by pressing the button 
        below (puny human)!

        <form action="" method="post" accept-charset="utf-8">
            <button class="btn btn-primary"
                    type="submit" name="response" value="write_my_aws">Write My AWS</button>
            <button class="btn btn-secondary"
                    type="submit" name="response" value="clean_up">Cleap up</button>
        </form>

        <?php if (__get__($_POST, 'response', '') === 'write_my_aws'): ?>
        <?php $cmd = FCPATH . '/scripts/write_aws.sh'; ?>
        <?=hippo_shell_exec($cmd, $awsText, $stderr)?>
        <?=$awsText?>
        <br>
        <p> I will only get better! </p>
        <?php endif; ?>
    </div>
</div>
<?=goBackToPageLink("user/home", "Go back")?>

<h1>Past Annual Work Seminar</h1>

<div class="alert-warning ">
    If any of your AWSs is missing from the list below, please emails details to
    <a href="mailto:officeacad@ncbs.res.in" target="_black">Academic Office</a>.
    <br />
    Your <tt>PRE SYNOPSIS SEMINARY</tt> may be 
    <a href=" <?= site_url("info/talks") ?> ">in list of the talks</a>.
</div>

<?php foreach ($awses as $aws): ?>
    <?php $id = $aws['id']; ?>
    <div class='py-3'>
        <form method="post" action="<?=site_url("user/aws/edit_request")?>">
            <?= awsToHTML($aws) ?>
            <button class="btn btn-primary"
                    title="Edit this entry" name="response" value="edit">Edit</button>
            <input type="hidden" name="id" value="<?=$id ?>" />
        </form>
    </div>
<?php endforeach; ?>

<?=goBackToPageLink("user/home", "Go back")?>
