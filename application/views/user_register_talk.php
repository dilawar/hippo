<?php
require_once BASEPATH . 'autoload.php';
echo userHTML();

$faculty = getFaculty();
$speakers = getTableEntries('speakers');
$logins = getTableEntries('logins');

$speakersMap = [];
foreach ($speakers as $visitor) {
    if (strlen($visitor['email']) > 0) {
        $speakersMap[$visitor['email']] = $visitor;
    }
}

// This must not be a key => value array else autocomplete won't work. Or have
// any null value,
$speakersIds = [];
foreach ($speakers as $x) {
    if ($x['email']) {
        $speakersIds[] = $x['email'];
    }
}

$facultyForAutoComplete = array_map(
    function ($x) {
        return loginToText($x);
    }, $faculty
);
$loginsForAutoComplete = array_map(
    function ($x) {
        return loginToText($x);
    }, $logins
);

?>

<script type="text/javascript" charset="utf-8">
// Autocomplete speaker.
$( function() {
    var speakersDict = <?php echo json_encode($speakersMap); ?>;

    var host = <?php echo json_encode($facultyForAutoComplete); ?>;
    var logins = <?php echo json_encode($loginsForAutoComplete); ?>;

    // These emails must not be key value array.
    var emails = <?php echo json_encode($speakersIds); ?>;

    $( "#talks_host" ).autocomplete( { source : host });
    $( "#talks_host" ).attr( "placeholder", "email of host" );

    $( "#talks_coordinator" ).autocomplete( { source : logins.concat( host ) });
    $( "#talks_coordinator" ).attr( "placeholder", "email of coordinator" );
    $( "#talks_host_extra" ).attr( "placeholder", "email of the other host (optional)" );


    // Once email is matching we need to fill other fields.
    $( "#speakers_email" ).autocomplete( { source : emails
        , focus : function( ) { return false; }
    }).on( 'autocompleteselect', function( e, ui )
        {
            var email = ui.item.value;
            $('#speakers_first_name').val( speakersDict[ email ]['first_name'] );
            $('#speakers_middle_name').val( speakersDict[ email ]['middle_name'] );
            $('#speakers_last_name').val( speakersDict[ email ]['last_name'] );
            $('#speakers_designation').val( speakersDict[ email ]['designation'] );
            $('#speakers_department').val( speakersDict[ email ]['department'] );
            $('#speakers_institute').val( speakersDict[ email ]['institute'] );
            $('#speakers_homepage').val( speakersDict[ email ]['homepage'] );
            $('#speakers_id').val( speakersDict[ email ]['id'] );
            $('#talks_speaker_id').val( speakersDict[ email ]['id'] );
        }
    );
    $( "#speakers_email" ).attr( "placeholder", "email" );
});
</script>

<?php

// Logic for POST requests.
$speaker = $cFormData ?? [
    'first_name' => '', 'middle_name' => '', 'last_name' => ''
    , 'email' => '', 'department' => '', 'institute' => '', 'title' => ''
    , 'id' => '', 'designation' => '', 'homepage' => '',
    ];

$talk = $cFormData ?? ['created_by' => whoAmI(), 'created_on' => dbDateTime('now')];

// Show speaker image here.
// Form to upload a picture
echo '<form method="post" enctype="multipart/form-data"
        onsubmit="return validateTalk()"
        action="' . site_url('user/register_talk_submit') . '">';

echo '<h2>Speaker details</h2>';

echo printInfo('Email id of speaker is desirable but not required.  ');

echo '<table><tr>';
echo '<td class="db_table_fieldname">Speaker picture</td><td>';
echo '<input type="file" name="picture" id="picture" value="" />';
echo '</td></tr></table>';

echo dbTableToHTMLTable(
    'speakers',
    $speaker,
    'honorific,email,homepage,first_name:required,middle_name,last_name:required'
        . ',designation,department,institute:required',
    ''
);

echo '<h2>Talk details</h2>';
echo dbTableToHTMLTable(
    'talks',
    $talk,
    'class:required,host:required,host_extra,coordinator,title:required,description:required',
    '',
    $hide = 'id,speaker,status'
);

echo '<h2>Submit booking request</h2>';
echo printInfo(
    "
    <i class=\"fa fa-flag\"></i>
    I may not be able to book if there is already a pending
    booking request at your preferred venue/slot. However, I'll register your
    talk and you can book venue later by visiting <tt>Book for Public Event</tt> link
    in your HOME page.
    <br />
    ", false
);
echo printNote(
    'For ZOOM/Jitsi and other video conferencing, select <tt>Remote VC</tt>
    option'
);

$venueSelect = venuesToHTMLSelect(null, false, 'venue');

echo '<table class="table table-sm editable" >';
echo '<tr><td class="db_table_fieldname">Venue</td> <td>' . $venueSelect . '</td></tr>';
echo '<tr><td class="db_table_fieldname">VC URL</td>
    <td> <input class="form-control input-sm" 
        type="url" name="vc_url" id="vc_url" 
        placeholder="URL for video conferencing (optional)" /> 
    </td>
    </tr>';
echo '<tr>
        <td class="db_table_fieldname">date</td>
        <td><input id="input_date" name="date" class="datepicker" type=\"date\" ></td>
    </tr>';
echo '<tr>
        <td class="db_table_fieldname">start time</td>
        <td><input id="input_start_time" name="start_time" 
        class="timepicker time" type=\"time\" ></td>
    </tr>';
echo '<tr>
        <td class="db_table_fieldname">end time</td>
        <td><input id="input_end_time" name="end_time" class="timepicker" type=\"time\" ></td>
    </tr>';
echo '</table>';
echo '<button class="submit btn btn-primary" 
    title="Submit talk" name="response" 
    value="submit">Register (and Book)</button>';
echo '</form>';
echo
'<button class="show_as_link btn btn-secondary" 
    id="input_check_availability" 
    onClick=checkAvailability(this)
    style="float:left">Check Availability</button>';
echo '<br/><br/>';

echo goBackToPageLink('user/home');

?>

<!-- replace HOST with HOST/PI -->
<script type="text/javascript" charset="utf-8">
$(document).ready( function() {
    var hostTD = $('.db_table_fieldname').filter( function() {
        return this.textContent.trim() === 'HOST'
        });
    hostTD[0].innerHTML = "PI or " + hostTD[0].innerHTML;
});
    
</script>

<!-- Javascript to query server when start_time is filled in -->
<script type="text/javascript" charset="utf-8">
function checkAvailability()
{
    var d = $("#input_date").val();
    var startTime = $("#input_start_time").val();
    var endTime = $("#input_end_time").val();
    var venue = $("#venue").val();
    if( ! (d && startTime && endTime && venue))
    {
        console.log( "Data is incomplete." );
        return false;
    }

    // Check if venue is available.
    $.ajax({
        type : "POST",
        url : "<?php echo base_url(); ?>" + "index.php/ajax/user_data_submit",
        dataType: "json",
        data: { "venue" : venue, "date" : d, "start_time" : startTime
                    , "end_time" : endTime, "function" : "isVenueAvailable" },
        success: function(r) {
            if( parseInt(r) == 0 )
                alert("Venue is available on " + d + " and between " + startTime 
                + " and " + endTime + ".");
            else
                alert( "Venue is not available." );
        }
    });
}
</script>

<!-- Don't enable button till class is unknown -->
<script>
function validateTalk( )
{
    var classTD = $('td select[name=class]')[0];
    var classVal = classTD.value;
    if( classVal == "UNKNOWN" )
    {
        // Disable Register button.
        alert( "Please select a suitable 'CLASS' for this event. Currently selected 'UNKNOWN'" );
        return false;
    }
    return true;
}
</script>
