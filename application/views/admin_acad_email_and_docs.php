<?php

include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'html2text.php';
include_once 'check_access_permissions.php';

mustHaveAnyOfTheseRoles( array('AWS_ADMIN', 'BOOKMYVENUE_ADMIN' ) );

echo userHTML( );

?>

<script type="text/javascript" charset="utf-8">
function ShowPlainEmail( button )
{
    var win = window.open('plain_email');
    win.document.write( "<pre>" + button.value + "</pre>" );
    win.select( );
}
</script>

<script type="text/javascript">
$(document).ready( function( ) {

});
</script>

<?php

/* 
 * Admin select the class of emails she needs to prepare. We remain on the same 
 * page for these tasks.
 */

$default = array( "task" => "upcoming_aws", "date" => dbDate( 'this monday' ) );
$options = array( 
    'Today\'s events', 'This week AWS', 'This week events'
    );

// Logic to keep the previous selected entry selected.
if( array_key_exists( 'response', $_POST ) )
{
    foreach( $_POST as  $k => $v )
    {
        $default[ $k ] = $v;
        if( $k == 'task' )
            $default[ $_POST[ $k ] ] = 'selected';
    }
}

// Construct user interface.
echo '<form method="post" action=""> <select name="task" id="list_of_tasks">';
foreach( $options as $val )
    echo "<option value=\"$val\" " . __get__( $default, $val, '') . 
        "> $val </option>";
echo '
    </select>
    <input class="datepicker" placeholder = "Select date" 
        title="Select date" name="date" value="' . $default[ 'date' ] . '" > 
    <button type="submit" name="response" title="select">' . $symbSubmit . '</button>
    </form>
    ';

// Fill subject of potential email here.
$subject = '';
$templ = null;

if( $default[ 'task' ] == 'This week AWS' )
{
    $whichDay = $default[ 'date' ];
    $awses = getTableEntries( 'annual_work_seminars', 'date' , "date='$whichDay'" );
    $upcoming = getTableEntries( 'upcoming_aws', 'date' , "date='$whichDay'" );
    $awses = array_merge( $awses, $upcoming );
    $emailHtml = '';

    $filename = "AWS_" . $whichDay;
    if( count( $awses ) < 1 )
    {
        echo printInfo( "No AWS has been confirmed for this day" );
    }
    else
    {
        foreach( $awses as $aws )
        {

            echo awsToHTML( $aws, $with_picture = true );
            $emailHtml .= awsToHTML( $aws, false );
            // Link to pdf file.
            echo awsPdfURL( $aws[ 'speaker' ], $aws[ 'date' ] );
            $filename .= '_' . $aws[ 'speaker' ];
        }

        $filename .= '.txt';

        $macros = array( 
            'DATE' => humanReadableDate( $awses[0]['date'] ) 
            , 'TIME' => humanReadableTime( strtotime('4:00 pm') )
            ,  'EMAIL_BODY' => $emailHtml
            );

        $templ = emailFromTemplate( 'aws_template', $macros );
        $md = html2Markdown( $templ[ 'email_body' ] );

        // Save the file and let the admin download it.
        file_put_contents( __DIR__ . "/data/$filename", $md);
        echo "<br><br>";
        echo '<table style="width:500px;border:1px solid"><tr><td>';
        echo downloadTextFile( $filename, 'Download email' );
        echo "</td><td>";
        echo awsPdfURL( '', $whichDay, 'All AWS PDF' );
        echo "</td></tr>";
        echo '</table>';
    }

} // This week AWS is over here.
else if( $default[ 'task' ] == 'This week events' )
{
    $html = printInfo( "List of public events for the week starting " 
        . humanReadableDate( $default[ 'date' ] ) 
        );
    $events = getEventsBeteen( $from = 'this monday', $duration = '+7 day' );

    foreach( $events as $event )
    {
        // We just need the summary of every event here.
        //$html .= eventSummaryHTML( $event );
        
        if( $event[ 'is_public_event' ] == 'NO' )
            continue;

        $externalId = $event[ 'external_id'];
        if( ! $externalId )
            continue;

        $id = explode( '.', $externalId)[1];
        if( intval( $id ) < 0 )
            continue;

        $talk = getTableEntry( 'talks', 'id', array( 'id' => $id ) );

        // We just need the summary of every event here.
        $html .= eventSummaryHTML( $event, $talk );
        $html .= "<br>";
    }

    echo $html;

    $html .= "<br><br>";

    // Generate email
    // getEmailTemplates
    $templ = emailFromTemplate( 'this_week_events'
        , array( "EMAIL_BODY" => $html ) 
        );
    $md = html2Markdown( $templ[ 'email_body'] );
    $emailFileName = 'Events_Of_Week_' .$default[ 'date' ] . '.txt';

    // Save the content of email to a file and generate a link to show to 
    // user.
    saveDownloadableFile( $emailFileName, $md );
    echo downloadTextFile( $emailFileName, 'Download email' );
}
else if( $default[ 'task' ] == 'Today\'s events' )
{
    // List todays events.

    $templ = array( );

    // Get all ids on this day.
    $date = $default[ 'date' ];
    echo "<h3> Events on " . humanReadableDate( $date ) . " </h3>";
    $entries = getEventsOn( $date );
    $html = '';
    foreach( $entries as $entry )
    {
        if( $entry[ 'is_public_event' ] == 'NO' )
            continue;

        if( ! array_key_exists( 'external_id', $entry ) )
            continue;

        if( ! $entry[ 'external_id' ] )
            continue;

        $talkid = explode( '.', $entry[ 'external_id' ])[1];
        $talk = getTableEntry( 'talks', 'id', array( 'id' => $talkid ) );
        if( ! $talk )
            continue;

        echo talkToHTML( $talk, true );

        $talkHTML = talkToHTML( $talk, false );

        $subject = __ucwords__( $talk[ 'class' ] ) . " by " . $talk['speaker'] . ' on ' .
            humanReadableDate( $entry[ 'date' ] );

        $hostInstitite = emailInstitute( $talk[ 'host' ] );

        $templ = emailFromTemplate(
            "this_event" 
            , array( 'EMAIL_BODY' => $talkHTML
                    , 'HOST_INSTITUTE' => strtoupper( $hostInstitite )
                ) 
            );

        $templ = htmlspecialchars( json_encode( $templ ) );
        echo '
            <form method="post" action="admin_acad_send_email.php">
                <button type="submit">Send email</button>
                <input type="hidden" name="subject" value="'. $subject . '" >
                <input type="hidden" name="template" value="'. $templ . '" >
            </form>'
            ;

        $html .= $talkHTML;
    }

    $md = html2Markdown( $html, $strip_inline_image = true );


    $emailFileName = 'EVENT_' . $default['date'] . '.txt';

    saveDownloadableFile( $emailFileName, $md );
    echo downloadTextFile( $emailFileName, 'Download email' );

    echo '<br>';
    // Link to pdf file.
    echo '<a target="_blank" href="generate_pdf_talk.php?date=' 
            . $default[ 'date' ] . '">Download pdf</a>';


    echo '<br><br>';

}

echo goBackToPageLink( "admin_acad.php", "Go back" );

?>
