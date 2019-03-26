<?php
require_once BASEPATH.'autoload.php';

echo userHTML( );

global $symbDelete;

$ref = $controller;
if( $ref == 'user' )
{
    // make sure that he is either admin of JC.
    if( ! isJCAdmin( whoAmI() ) )
    {
        echo flashMessage( "Only JC Admin can access this page." );
        redirect( "$ref/home" );
        exit;
    }
}

// For Javascript.
$faculty = getFaculty( );
$speakers = getTableEntries( 'speakers' );
$logins = getTableEntries( 'logins' );

// generate a key from speaker.
function speakerKey( $x )
{
    $key = nameArrayToText( $x );
    if( strlen( $x['email'] ) > 0 )
        $key .= ' (' . $x[ 'email' ] . ' )';
    return $key;
}

// Use speaker ID.
$speakersMap = array( );
foreach( $speakers as $x )
{
    if( intval($x[ 'id'] ) > 0 )
    {
        $speakersMap[ speakerKey($x) ] = $x;
        $speakersMap[ intval($x['id']) ] = $x;
    }
}

// This must not be a key => value array else autocomplete won't work. Or have
// any null value,
$speakerAutoCompleteKeys = array( );
foreach( $speakers as $x )
{
    // If id is zero, these speakers are very old and not supported anymore. You
    // CANNOT edit them.
    if( intval( $x[ 'id' ] ) == 0 )
        continue;
    $speakerAutoCompleteKeys[ ] = speakerKey( $x );
}

$faculty = array_map( function( $x ) { return loginToText( $x ); }, $faculty );
$logins = array_map( function( $x ) { return loginToText( $x ); }, $logins );

// Logic for POST requests.
$speaker = array(
    'first_name' => '', 'middle_name' => '', 'last_name' => '', 'email' => ''
    , 'department' => '', 'institute' => '', 'title' => '', 'id' => ''
    , 'designation' => '' , 'homepage' => ''
    );

$talk = [ 'created_by' => whoAmI(), 'created_on' => dbDateTime('now') ];


// Form to upload a picture

echo "<h1>Speaker details</h1>";

echo alertUser( "If you know the speaker id, use the id (its an interger value), 
    else I'll try to find the speaker." , false);

echo '<form method="post" action="">';
echo '<input id="speakers_id" name="id" type="text" value="" >';
echo '<button type="submit" name="response" value="show">Show details</button>';
echo '</form>';

// Show speaker image here.
// Show emage.
if( __get__( $_POST, 'id', '' ) )
{
    // Get the real speaker id for database table.
    $speaker = __get__($speakersMap, $_POST['id'], '');
    if( $speaker )
    {
        $picPath = getSpeakerPicturePath( $speaker['id'] );
        $html = '<table class="show_info" border="1"> <tr>';
        $html .= '<td>';
        $html .= showImage( $picPath );
        $html .= '</td>';
        $html .= '<td>';
        $html .= arrayToVerticalTableHTML( $speaker, 'info' );
        $html .= '</td>';
        $html .= '</tr></table>';
        echo $html;
    }
    else
        echo alertUser( "No speaker is found for entry : " . __get__($_POST, 'id', 'NA'));
}

echo '<h1>Edit speaker details</h1>';

echo printInfo(
    "Email id of speaker is very desirable but not neccessary. <br>
    It helps keeping database clean and makes autocompletion possible.
    "
    );

echo printInfo(
    "<strong>First name</strong> and <strong>institute</strong> are required
    fields.  ");

echo '<form method="post" enctype="multipart/form-data" action="' . site_url( "$ref/manages_speakers_action") . '">';

echo '<table><tr>';
echo '<td class="db_table_fieldname">Speaker picture</td><td>';
echo '<input type="file" name="picture" id="picture" value="" />';
echo '</td></tr></table>';

echo dbTableToHTMLTable( 'speakers', $speaker
    , 'honorific,email,homepage,first_name,middle_name,last_name'
        . ',department,institute,designation'
    , 'submit'
    );
echo '<button title="Delete this entry" type="submit" onclick="AreYouSure(this)"
    name="response" value="Delete">' . $symbDelete .
    '</button>';

echo '</form>';


echo "<br/><br/>";
echo goBackToPageLink( "$ref/home", 'Go back' );

?>

<script type="text/javascript" charset="utf-8">
// Autocomplete speaker.
$( function() {
    var speakersDict = <?php echo json_encode($speakersMap) ?>;

    var host = <?php echo json_encode( $faculty ); ?>;
    var logins = <?php echo json_encode( $logins ); ?>;

    var id;

    // Keys for autocompletion.
    var ids = <?php echo json_encode( $speakerAutoCompleteKeys ); ?>;

    // Once email is matching we need to fill other fields.
    $( "#speakers_id" ).autocomplete( { source : ids
        , focus : function( ) { return false; }
    }).on( 'autocompleteselect', function( e, ui ) {
            id = ui.item.value;
            $('#speakers_id').val( speakersDict[ id ]['id'] );
        }
    );

    $('#speakers_id').val( id );
    $( "#speakers_id" ).attr( "placeholder", "autocomplete" );
});
</script>

