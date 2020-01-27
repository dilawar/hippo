<?php
require_once BASEPATH.'autoload.php';
echo userHTML( );

$symbSubmit = '<i class="fa fa-check fa-1x"></i>';

// Referering controller.
$ref = "adminbmv";
if(isset($controller))
    $ref = $controller;

/* 
 * Admin select the class of emails she needs to prepare. We remain on the same 
 * page for these tasks.
 */

$default = array( "task" => "upcoming_aws", "date" => dbDate( 'this monday' ) );
$options = array( 
    'This day\'s events', 'This week AWS', 'This week events'
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
echo '<form method="post" action=""> 
    <select name="task" id="list_of_tasks">';

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
        echo printInfo( "No AWS is found for selected day.", true );
    else
    {
        foreach( $awses as $aws )
            $emailHtml .= awsToHTML( $aws, false );

        echo $emailHtml;
        $subject = " Annual Work Seminars on " . humanReadableDate($aws['date']);
        $macros = array( 
            'DATE' => humanReadableDate( $awses[0]['date'] ) 
            , 'TIME' => humanReadableTime( strtotime('4:00 pm') )
            , 'VENUE' => venueToShortText($awses[0]['venue'])
            ,  'EMAIL_BODY' => $emailHtml);

        $templ = emailFromTemplate( 'aws_template', $macros );
        $templ = htmlspecialchars( json_encode( $templ ) );
        echo '<form method="post" action="'.site_url("$ref/send_email").'">
            <button class="btn btn-primary">Send email</button>
            <input type="hidden" name="subject" value="'. $subject . '" >
            <input type="hidden" name="template" value="'. $templ . '" >
            </form>'
            ; 
    }
} // This week AWS is over here.
else if( $default[ 'task' ] == 'This week events' )
{
    $html = printInfo( "List of public events for the week starting " 
        . humanReadableDate( $default[ 'date' ] ) 
        );
    $events = getEventsBetween( $from = 'this monday', $duration = '+7 day' );

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
    $html .= "<br><br>";
    echo $html;

    // Generate email
    // getEmailTemplates
    $templ = emailFromTemplate( 'this_week_events'
        , array( "EMAIL_BODY" => $html ) 
        );
}
else if( $default[ 'task' ] == 'This day\'s events' )
{
    // List todays events.

    $templ = array( );

    // Get all ids on this day.
    $date = $default[ 'date' ];
    echo "<h3> Events on " . humanReadableDate( $date ) . " </h3>";
    $entries = getEventsOn( $date );
    foreach( $entries as $entry )
    {
        $html = '';
        if( ! __get__( $entry, 'external_id', '' ) )
            continue;

        $talkid = explode( '.', $entry[ 'external_id' ])[1];
        $talk = getTableEntry( 'talks', 'id', array( 'id' => $talkid ) );
        if( ! $talk )
            continue;

        $talkHTML = talkToHTML( $talk, false );

        $subject = __ucwords__( $talk[ 'class' ] ) . " by " . $talk['speaker'] . ' on ' .
            humanReadableDate( $entry[ 'date' ] );

        $hostInstitite = emailInstitute( $talk[ 'host' ] );

        $templ = emailFromTemplate( "this_event" 
            , array( 'EMAIL_BODY' => $talkHTML
                    , 'HOST_INSTITUTE' => strtoupper( $hostInstitite )
                ) 
            );

        $templ = htmlspecialchars( json_encode( $templ ) );
        echo '<form method="post" action="'.site_url("$ref/send_email").'">
                <button class="btn btn-primary">Send email</button>
                <input type="hidden" name="subject" value="'. $subject . '" >
                <input type="hidden" name="template" value="'. $templ . '" >
            </form>'
            ;
        $html .= $talkHTML;

        $html .= ' <br />';
        $html .= '<a target="_blank" class="float-right" href="'
            . site_url("user/downloadtalk/".$default['date']."/$talkid") . '">
            <i class="fa fa-download ">PDF</i></a>';
        echo $html;
    }
    echo ' <br />';
}

echo goBackToPageLink( "$ref/home", "Go back" );

?>
