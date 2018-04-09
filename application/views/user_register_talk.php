<?php

require_once 'header.php';
require_once 'database.php';
require_once 'tohtml.php';
require_once 'methods.php';
require_once 'check_access_permissions.php';

mustHaveAnyOfTheseRoles( array( 'USER' ) );

echo userHTML( );

// Javascript.
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

    console.log( host );

    // These emails must not be key value array.
    var emails = <?php echo json_encode( $speakersIds ); ?>;

    $( "#talks_host" ).autocomplete( { source : host });
    $( "#talks_host" ).attr( "placeholder", "email of speaker" );

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

$talk = array( 'created_by' => $_SESSION[ 'user' ]
            , 'created_on' => dbDateTime( 'now' )
        );

// Show speaker image here.

// Form to upload a picture

echo '<form method="post" enctype="multipart/form-data"
        action="user_register_talk_action.php">';

echo "<h2>Speaker details</h2>";
echo printInfo( "Email id of speaker is desirable but not required.  ");

echo alertUser(
    "<strong>First name</strong> and <strong>institute</strong> are required
    fields.  ");

echo '<table><tr>';
echo '<td class="db_table_fieldname">Speaker picture</td><td>';
echo '<input type="file" name="picture" id="picture" value="" />';
echo '</td></tr></table>';

echo dbTableToHTMLTable( 'speakers', $speaker
    , 'honorific,email,homepage,first_name:required,middle_name,last_name'
        . ',designation,department,institute:required'
    , ''
    );


echo "<h2>Talk information</h2>" ;
echo dbTableToHTMLTable( 'talks', $talk
    , 'class,host,coordinator,title,description'
    , ''
    , $hide = 'id,speaker,status'
    );

echo "<h2>Submit booking request</h2>" ;
echo alertUser( "
    <i class=\"fa fa-flag\"></i>
    I may not be able to book if there is already a pending
    booking request at your preferred venue/slot. However, I'll register your
    talk and you can book venue later by visiting <strong>Manage my talks</strong> link
    in your HOME page.
    <br />
    "
    );

$venueSelect = venuesToHTMLSelect( );
echo "<table class=\"editable\" >";
echo '<tr>
        <td class="db_table_fieldname">Venue</td> <td>' . $venueSelect . '</td>
    </tr>';
echo '<tr>
        <td class="db_table_fieldname">date</td>
        <td><input name="date" class="datepicker" type=\"date\" ></td>
    </tr>';
echo '<tr>
        <td class="db_table_fieldname">start time</td>
        <td><input name="start_time" class="timepicker" type=\"time\" ></td>
    </tr>';
echo '<tr>
        <td class="db_table_fieldname">end time</td>
        <td><input name="end_time" class="timepicker" type=\"time\" ></td>
    </tr>';
echo "</table>";
echo '<button class="submit" title="Submit talk" name="response" value="submit">Register (and Book)</button>';
echo '</form>';

echo "<br/><br/>";
echo goBackToPageLink( 'user.php', 'Go back' );

?>
