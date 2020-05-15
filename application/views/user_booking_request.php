<?php
require_once BASEPATH . 'autoload.php';
echo userHTML();

$ref = 'user';
if (isset($controller)) {
    $ref = $controller;
}

if (!isset($goback)) {
    $goback = "$ref/home";
}

$venues = getVenues($sortby = 'total_events');

?>

<div class="card-body">
    <ul>
        <li> Set <tt>IS PUBLIC EVENT</tt>  to <tt>YES</tt> if you want your event to appear 
        on NCBS\' google-calendar. <br />
        </li>
        <li>
            Pick proper <tt>CLASS</tt> for your booking. Your request may be rejected if it is 
               filed under wrong <tt>CLASS</tt>.
        </li>
    </ul>
</div>

<?php

if (!isset($date)) {
    echo printErrorSevere('No valid day is selected. Please go back and select a valid date.');
    echo goBack();
    exit;
}

$day = nameOfTheDay($date);
$events = getEvents($date);

// Generate options here.
$venue = __get__($_POST, 'venue', '');
$venue = trim($venue);

if ($venue) {
    $venueHTML = "<input name=\"venue\" type=\"text\" value=\"$venue\" readonly>";
} else {
    $venueHTML = venuesToHTMLSelect($venues);
}

$startTime = dbTime($_POST['start_time']);

// This is END time of event. It may have come from user from user_book.php,
// default of 1 hrs in future from the start_time of event..
$defaultEndTime = __get__(
    $_POST,
    'end_time',
    date('H:i', strtotime($startTime) + 60 * 60)
);

$date = __get__($_POST, 'date', '');
$title = __get__($_POST, 'title', '');
$description = __get__($_POST, 'description', '');

$labmeetOrJCs = labmeetOrJCOnThisVenueSlot($date, $startTime, $defaultEndTime, $venue);

if (count($labmeetOrJCs) > 0) {
    foreach ($labmeetOrJCs as $labmeetOrJC) {
        $ignore = 'is_public_event,url,description,status,gid,rid,'
            . 'external_id,modified_by,timestamp'
            . ',calendar_id,calendar_event_id,last_modified_on';

        echo p('<font color="darkblue">
            <i class="fa fa-flag fa-2x"></i> Following Journal Club or Labmeet usually happens at this 
            slot.  DO NOT book here unless you are sure that following event WILL not
            happen. 
            </font>');

        echo '<small>';
        echo arrayToTableHTML($labmeetOrJC, 'info', '', $ignore);
        echo '</small>';
        echo '<br><br>';
    }
}

echo heading('Booking details', 2);

$default = ['created_by' => whoAmI()];
$default['end_time'] = $defaultEndTime;
$default = array_merge($default, $_POST);

// If external_id is given then this needs to go into request table. This is
// used to fetch event data from external table. The format of this field if
// TABLENAME.ID. 'SELF.-1' means the there is not external dependency.
if (!isset($external_id)) {
    $external_id = 'SELF.-1';
}

if (__substr__('talks.', $external_id, true)) {
    $default['is_public_event'] = 'YES';
}

$default['last_modified_on'] = dbDateTime('now');

echo '<form method="post" action="' . site_url("user/bookingrequest_submit/$goback") . '">';
echo dbTableToHTMLTable(
    'bookmyvenue_requests',
    $default,
    'class:required,title:required,description,vc_url,url,is_public_event,end_time:required',
    '',
    $hide = 'gid,rid,modified_by,timestamp,status',
    'table table-sm'
);

echo '<input type="hidden" name="external_id" value="' . $external_id . '" >';

// Lets keep the referer in the $_POST.
echo '<input type="hidden" name="REFERER" value="' . $ref . '" >';

// I need to add repeat pattern here.
echo '<br />';
echo '<h3>Recurrent Event? (optional)</h3>';
echo printNote('Leave me alone if your booking is for a single event.');
echo repeatPatternTable();
echo '<br />';
echo '<button class="btn btn-primary pull-right" 
            name="response" value="submit">Submit</button>';
echo '</form>';
echo '<br /><br />';

echo goBackToPageLink($goback, 'Go back');
?>

<script type="text/javascript" charset="utf-8">
    $("input#bookmyvenue_requests_title").attr( "placeholder", "Minimum 8 characters");
</script>

<script>
function updateTextInput(val) {
    document.getElementById('textInput').innerHTML = val; 
}
</script>

