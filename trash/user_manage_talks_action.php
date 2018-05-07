<?php

include_once 'header.php';
include_once 'database.php';
include_once 'mail.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAnyOfTheseRoles( array( 'USER' ) );

echo userHTML( );

// Javascript.
$faculty = getFaculty( );
$speakers = getTableEntries( 'speakers' );
$logins = getTableEntries( 'logins' );

//var_dump( $speakers );

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

$faculty = array_map( function( $x ) { return loginToText( $x ); }, $faculty );
$logins = array_map( function( $x ) { return loginToText( $x ); }, $logins );

?>

<script type="text/javascript" charset="utf-8">
// Autocomplete speaker.
$( function() {
    var speakersDict = <?php echo json_encode( $speakersMap ) ?>;
    var host = <?php echo json_encode( $faculty ); ?>;
    var logins = <?php echo json_encode( $logins ); ?>;

    // These emails must not be key value array.
    var emails = <?php echo json_encode( $speakersIds ); ?>;
    //console.log( emails );

    $( "#talks_host" ).autocomplete( { source : host }); 
    $( "#talks_host" ).attr( "placeholder", "autocomplete" );

    $( "#talks_coordinator" ).autocomplete( { source : logins.concat( host ) }); 
    $( "#talks_coordinator" ).attr( "placeholder", "autocomplete" );


    // Once email is matching we need to fill other fields.
    $( "#speakers_email" ).autocomplete( { source : emails
        , focus : function( ) { return false; }
    }).on( 'autocompleteselect', function( e, ui ) 
        {
            var email = ui.item.value;
            $('#speakers_first_name').val( speakersDict[ email ]['first_name'] );
            $('#speakers_middle_name').val( speakersDict[ email ]['middle_name'] );
            $('#speakers_last_name').val( speakersDict[ email ]['last_name'] );
            $('#speakers_department').val( speakersDict[ email ]['department'] );
            $('#speakers_institute').val( speakersDict[ email ]['institute'] );
            $('#speakers_homepage').val( speakersDict[ email ]['homepage'] );
        }
    );
    $( "#speakers_email" ).attr( "placeholder", "autocomplete" );
});
</script>


<?php

if( ! $_POST[ 'response' ] )
{
    // Go back to previous page.
    goBack( );
    exit;
}
else if( $_POST[ 'response' ] == 'delete' )
{
    // Delete this entry from talks.
    $res = deleteFromTable( 'talks', 'id', $_POST );
    if( $res )
    {
        echo printInfo( 'Successfully deleted entry' );
        // Now cancel this talk in requests, if there is any.
        $res = updateTable( 
            'bookmyvenue_requests', 'external_id', 'modified_by,status,last_modified_on'
            , array( 'external_id' => "talks." . $_POST[ 'id' ] 
                    , 'status' => 'CANCELLED' 
                    , 'last_modified_on' => dbDateTime( 'now' )
                    , 'modified_by' => $_SESSION[ 'user' ]
                )
            );

        // Cancel confirmed event associated with this talk if any.
        $res = updateTable( 
            'events', 'external_id', 'modified_by,status,last_modified_on'
            , array( 'external_id' => "talks." . $_POST[ 'id' ] 
                        , 'status' => 'CANCELLED' 
                        , 'last_modified_on' => dbDateTime( 'now' )
                        , 'modified_by' => $_SESSION[ 'user' ]
                    )
            );
        
        goBack( "user_manage_talk.php", 1 );
        exit;
    }
    else
        echo printWarning( "Failed to delete the talk " );
}
else if( $_POST[ 'response' ] == 'DO_NOTHING' )
{
    echo printInfo( "User said NO!" );
    goBack( $default = 'user.php' );
    exit;
}
else if( $_POST[ 'response' ] == 'edit' )
{
    echo alertUser( "Here you can change the host, coordinator, title and 
                description of the talk." 
                );

    $id = $_POST[ 'id' ];
    $talk = getTableEntry( 'talks', 'id', $_POST );

    echo '<form method="post" action="user_manage_talks_action_update.php">';
    echo dbTableToHTMLTable('talks', $talk
        , 'class,coordinator,host,title,description'
        , 'submit');
    echo '</form>';
}
else if( $_POST[ 'response' ] == 'schedule' )
{
    // We are sending this to quickbook.php as GET request. Only external_id is 
    // sent to page.
    //var_dump( $_POST );
    $external_id = "talks." . $_POST[ 'id' ];
    $query = "&external_id=".$external_id;
    header( "Location: quickbook.php?" . $query );
    exit;
}

echo goBackToPageLink( "user_manage_talk.php", "Go back" );
exit;

?>
