<?php
require_once BASEPATH.'autoload.php';
echo userHTML( );

$faculty = getFaculty( );
$speakers = getTableEntries( 'speakers' );
$logins = getTableEntries( 'logins' );

$speakersMap = array( );
foreach( $speakers as $visitor )
    if( strlen( $visitor[ 'email' ] ) > 0 )
        $speakersMap[ $visitor[ 'email' ] ] = $visitor;

// This must not be a key => value array else autocomplete won't work. Or have
// any null value,
$speakersIds = array( );
foreach( $speakers as $x )
    if( $x[ 'email' ] )
        $speakersIds[] = $x[ 'email' ];

$facultyForAutoComplete = array_map( function( $x ) { return loginToText( $x ); }, $faculty );
$loginsForAutoComplete = array_map( function( $x ) { return loginToText( $x ); }, $logins );

?>

<script type="text/javascript" charset="utf-8">
// Autocomplete speaker.
$( function() {
    var speakersDict = <?php echo json_encode( $speakersMap ) ?>;

    var host = <?php echo json_encode( $facultyForAutoComplete ); ?>;
    var logins = <?php echo json_encode( $loginsForAutoComplete ); ?>;

    // These emails must not be key value array.
    var emails = <?php echo json_encode( $speakersIds ); ?>;

    $( "#talks_host" ).autocomplete( { source : host });
    $( "#talks_host" ).attr( "placeholder", "email of host" );

    $( "#talks_coordinator" ).autocomplete( { source : logins.concat( host ) });
    $( "#talks_coordinator" ).attr( "placeholder", "email of coordinator" );


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
$speaker = array(
    'first_name' => '', 'middle_name' => '', 'last_name' => '', 'email' => ''
    , 'department' => '', 'institute' => '', 'title' => '', 'id' => ''
    , 'designation' => '', 'homepage' => ''
    );

$talk = array( 'created_by' => whoAmI(), 'created_on' => dbDateTime('now'));

// Show speaker image here.

// Form to upload a picture

echo '<form method="post" enctype="multipart/form-data"
        action="' . site_url( 'user/register_talk_submit' ) . '">';

echo "<h2>Speaker details</h2>";

echo printInfo( "Email id of speaker is desirable but not required.  ");

echo '<table><tr>';
echo '<td class="db_table_fieldname">Speaker picture</td><td>';
echo '<input type="file" name="picture" id="picture" value="" />';
echo '</td></tr></table>';

echo dbTableToHTMLTable( 'speakers', $speaker
    , 'honorific,email,homepage,first_name:required,middle_name,last_name:required'
        . ',designation,department,institute:required'
    , ''
    );


echo "<h2>Talk information</h2>" ;
echo dbTableToHTMLTable( 'talks', $talk
    , 'class:required,host:required,coordinator,title:required,description:required'
    , ''
    , $hide = 'id,speaker,status'
    );

echo "<h2>Submit booking request</h2>" ;
echo printInfo( "
    <i class=\"fa fa-flag\"></i>
    I may not be able to book if there is already a pending
    booking request at your preferred venue/slot. However, I'll register your
    talk and you can book venue later by visiting <tt>Book for Public Event</tt> link
    in your HOME page.
    <br />
    ", false);

$venueSelect = venuesToHTMLSelect(null, false, "input_venue");
echo '<table class="editable" >';
echo '<tr><td class="db_table_fieldname">Venue</td> <td>' . $venueSelect . '</td></tr>';
echo '<tr>
        <td class="db_table_fieldname">date</td>
        <td><input id="input_date" name="date" class="datepicker" type=\"date\" ></td>
    </tr>';
echo '<tr>
        <td class="db_table_fieldname">start time</td>
        <td><input id="input_start_time" name="start_time" class="timepicker" type=\"time\" ></td>
    </tr>';
echo '<tr>
        <td class="db_table_fieldname">end time</td>
        <td><input id="input_end_time" name="end_time" class="timepicker" type=\"time\" ></td>
    </tr>';
echo "</table>";
echo '<button class="submit" title="Submit talk" name="response" value="submit">Register (and Book)</button>';
echo '</form>';
echo '<button class="show_as_link" id="input_check_availability" 
    onClick=checkAvailability(this)
    style="float:left">Check Availability</button>';
echo "<br/><br/>";

echo goBackToPageLink( 'user/home' );

?>

<!-- Javascript to query server when start_time is filled in -->
<script type="text/javascript" charset="utf-8">
function checkAvailability()
{
    var d = $("#input_date").val();
    var startTime = $("#input_start_time").val();
    var endTime = $("#input_end_time").val();
    var venue = $("#input_venue").val();
    if( ! (d && startTime && endTime && venue))
    {
        console.log( "Data is incomplete." );
        return false;
    }

    // Check if venue is available.
    console.log( "A " + d + ": " + startTime + ", " + endTime + " on venue " + venue);

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
