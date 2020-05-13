<?php
require_once BASEPATH.'extra/methods.php';
require_once BASEPATH.'database.php';
require_once BASEPATH.'extra/ICS.php';
require_once BASEPATH.'extra/linkify.php';

require_once FCPATH.'scripts/generate_pdf_aws.php';
require_once FCPATH.'scripts/generate_pdf_talk.php';

$useCKEditor = false;
global $symbUpdate;
global $symbDelete;

if( $useCKEditor )
    echo '<script src="https://cdn.ckeditor.com/4.6.2/standard/ckeditor.js"></script>';

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  This does nothing (now).
    *
    * @Param $html
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function purifyHTML( $html ) { 
    return $html;
}

function whereWhenHTML( $event )
{
    return humanReadableTime( $event['start_time'] ) . ', '
        . humanReadableDate( $event['date'] ) . ' | ' 
        . $event['venue' ] ;
}


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Create an email out of a talk event.
    *
    * @Param array
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function eventToEmail(array $event, array $talk = []): array
{
    /* PREPARE email template */
    $talkid = explode( '.', $event[ 'external_id' ])[1];
    if(! $talk)
        $talk = getTableEntry( 'talks', 'id', array( 'id' => $talkid ) );
    if( ! $talk )
        return [];

    $talkHTML = talkToHTML( $talk, false );
    $attachments = eventToICALFile($event);
    $subject = __ucwords__( $talk[ 'class' ] ) . " by " . $talk['speaker'] . ' on ' .
        humanReadableDate( $event[ 'date' ] );

    $hostInstitite = emailInstitute($talk['host'], $talk['host_extra']);
    $data = emailFromTemplate(
        "this_event" 
        , array( 'EMAIL_BODY' => $talkHTML
        , 'HOST_INSTITUTE' => strtoupper( $hostInstitite )
        ) 
    );
    $data['subject'] = $subject;
    $data['attachments'] = $attachments;
    return $data;
}

function talkToEmail(string $talkid)
{
    $talk = getTableEntry('talks', 'id,status'
        , ['id'=>$talkid, 'status'=>'VALID']);
    $event = getEventsOfTalkId($talkid);
    return eventToEmail($event, $talk);
}


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Convert a given where/when event to HTML.
    *
    * @Param $venue Given venue.
    * @Param $date  Given date.
    * @Param $time  Given time.
    *
    * @Returns  A html table.
    */
/* ----------------------------------------------------------------------------*/
function whereWhenTable( string $venue, string $date, string $time = '', $inothertable=false)
{
    $whereHTML = venueToText( $venue, false );
    $whenHTML = humanReadableDate( $date );
    if( $time )
        $whenHTML .= ' ' . humanReadableTime( $time );

    $table = "";
    if( ! $inothertable )
        $table .= "<table class='wherewhen'>";

    $table .= "<tr><td><strong>Where</strong></td><td>$whereHTML </td></tr>
        <tr><td><strong>When<strong></td> <td>$whenHTML</td></tr>";

    if( ! $inothertable )
        $table .= "</table>";
    return $table;
}


function alert( $msg )
{
    echo "<script type='text/javascript'>alert('$msg');</script>";
}

function p( string $msg, $class="") : string
{
    return "<p class=\"$class\"> $msg </p>";
}


function prompt( $msg )
{
    echo("<script type='text/javascript'> var answer = prompt('". $msg ."'); </script>");
    $answer = "<script type='text/javascript'> document.write(answer); </script>";
    return($answer);
}

function fontWithStyle( $msg, $style = "" )
{
    return "<font style=\"$style;\">$msg</font>";
}

function fixHTML( $html, bool $strip_tags = false ) : string
{
    if( ! $html )
        return '';

    $res = $html;
    if( $strip_tags )
        $res = strip_tags(  $res, '<br><p><a><strong><tt>' );
    // Replate all new line with space.
    $res = preg_replace( "/[\r\n]+/", ' ', $res );
    $res = str_replace( '<br />', ' ', $res );
    $res = str_replace( '<br/>', ' ', $res );
    $res = str_replace( '<br>', ' ', $res );
    return $res;
}

function rescale_inline_images( $html, $width = "60%")
{
    $dom = new DOMDocument( );
    $dom->loadHTML($html);
    $images = $dom->getElementsByTagName( 'img' );
    foreach( $images as $img )
    {
        $img->setAttribute( 'width', $width );
        $img->setAttribute( 'height', 'auto' );
        $img->setAttribute( 'align', 'right' );
    }

    return $dom->saveHTML();
}

function addToGoogleCalLink( array $event )
{
    $location = venueToText( $event[ 'venue' ] );
    $date = dateTimeToGOOGLE( $event[ 'date' ], $event[ 'start_time' ] )
                . '/' . dateTimeToGOOGLE( $event[ 'date' ], $event[ 'end_time' ] );

    $link = 'http://www.google.com/calendar/event?action=TEMPLATE';
    $link .= '&text=' . rawurlencode($event['title']);
    $link .= "&dates=" . $date;
    $link .= "&ctz=Asia/Kolkata";
    $link .= '&details=' . rawurlencode($event['description']);
    $link .= '&location=' . rawurlencode($location);

    $res = '<a href="'. $link . '" target="_blank" >';

    // Get inline image.
    $res .= inlineGoogleCalImage( FCPATH.'data/gc_button6.png' );
    $res .= '</a>';

    $res = '<div class="strip_from_md">' . $res . "</div>";
    return $res;
}

function showCourseFeedbackLink(string $year, string $sem, string $cid )
{
    return "<a target='Feedback' href='".site_url( "user/seefeedback/$cid/$sem/$year" )
        . "'>Show Feedback</a>";
}

// Local function.
function feedbackForm(string $year, string $sem, string $cid ) : array
{
    // DO NOT use ' to delimit the string; it wont work very well inside table.
    $numUnanswered = numQuestionsNotAnswered( whoAmI(), $year, $sem, $cid);
    $form =  "<form action='".site_url("user/givefeedback/$cid/$sem/$year")."' method='post'>";
    $form .= "<button style='float:right' name='response' value='submit'>Feeback ("
                . $numUnanswered . " unanswered.)</button>";
    $form .= "</form>";
    return ['html'=>$form, 'num_unanswered'=>$numUnanswered];
}


function coursesToHTMLTable(array $courses, array $runningCourses=null
    , bool $withFeedbackForm=false, $classes=''): array
{
    $htmlArr = [];

    // If runnung courses were not provided, fetch them.
    if(! $runningCourses)
        $runningCourses = getRunningCourses();

    if(count($courses) == 0)
        return $htmlArr;

    // Else add all tables.
    $table = "<table class='$classes'>";
    foreach($courses as &$c)
    {
        $action = 'drop';
        $cid = $c[ 'course_id' ];
        $course = getTableEntry( 'courses_metadata', 'id', array( 'id' => $cid ) );
        if(!$course)
            continue;

        // If feedback is not given for this course, display a button.
        $sem = $c['semester'];
        $year = $c['year'];

        // If more than 30 days have passed, do not allow dropping courses.
        if( __get__($runningCourses, $cid, ''))
        {
            // This course is not in running courses. So sad//
            $cstartDate = $runningCourses[$cid]['start_date'];
            if(strtotime('today') > (strtotime($cstartDate)+30*24*3600))
                $action = '';
        }

        // TODO: Don't show grades unless student has given feedback.
        $tofilter = 'student_id,registered_on,last_modified_on';

        // Show grade if it is available and user has given feedback.
        if( __get__($c, 'grade', 'X' ) != 'X' )
        {
            $numUnanswered = $feedRes['num_unanswered'];
            if($numUnanswered > 0 )
            {
                $c['grade'] = colored( "Grade is available.<br />
                    Feedback is due. $numUnanswered unanswered.", 'darkred' 
                );
            }
        }

        $table = '<table>';
        if($withFeedbackForm)
        {
            // Show form. Form is inside another table.
            $feedRes = feedbackForm($year, $sem, $cid );
            $table .= '<table><tr><td>';
            $table .= '<form method="post" action="'.site_url("user/manage_course/$action").'">';
            $table .= dbTableToHTMLTable( 'course_registration', $c, '', $action, $tofilter );
            $table .= '</form>';
            $table .= '</td>';

            if( $feedRes['num_unanswered']> 0 )
            {
                // Feeback form
                $table .= "<tr><td> " . $feedRes['html'] . "</td></tr>";
            }
            else
                $table .= "<tr><td colspan=2><strong>Feedback has been given. </strong> <br />"
                    .  showCourseFeedbackLink( $year, $sem, $cid ) . "</td></tr>";
            $table.= '</table>';
        }
        // Next col of all courses.
        $table .= '</table>';
        $htmlArr[] = $table;
    }
    return $htmlArr;
}


function bookingToHtml( array $booking, $equipmentMap=array() ) : string
{
    if( ! $equipmentMap )
    {
        $equipments = getTableEntries( 'equipments', 'id' );
        foreach( $equipments as $e )
            $equipmentMap[ $e['id'] ] = $e;
    }

    $eid = $booking['equipment_id'];
    $name = $equipmentMap[$eid]['name'];
    $by = $booking['booked_by'];

    $html =  "$name ($eid) by <strong>$by</strong><br />" . humanReadableShortDate($booking['date'])
        . '<br />' . humanReadableTime($booking['start_time']) 
        . ' to ' . humanReadableTime( $booking['end_time'] );

    return $html;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Convert event to ICS file.
    *
    * @Param $event
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function eventToICAL( array $event ) : string
{
    $ics = new ICS( $event );
    return $ics;
}

function eventToICALLink( $event )
{
    return '';

    $prop = array( );
    $prop[ 'dtstart' ] = $event[ 'date' ] . ' ' . $event[ 'start_time' ];
    $prop[ 'dtend' ] = $event[ 'date' ] . ' ' . $event[ 'end_time' ];
    $prop[ 'description' ] = substr( html2Markdown( $event[ 'description' ]), 0, 200 );
    $prop[ 'location' ] = venueToText( $event['venue'] );
    $prop[ 'summary' ] = $event[ 'title' ];

    $ical = eventToICAL( $prop );

    $filename = __DIR__ . '/_ical/' . $event[ 'gid' ] . $event[ 'eid' ] . '.ics';
    file_put_contents( $filename, $ical->to_string( ) );

    $link = '.';
    if( file_exists( $filename ) )
        $link = downloadTextFile( $filename
        , '<i class="fa fa-calendar"> <strong>iCal</strong></i>'
        , 'link_as_button'
    );

    return $link;
}


/**
    * @brief Generate SPEAKER HTML with homepage and link.
    *
    * @param $speaker
    *
    * @return
 */
function speakerToHTML( $speaker )
{
    if( ! $speaker )
    {
        return "Error: Speaker not found";
    }

    // Most likely speaker id.
    if( is_string( $speaker ) )
    {
        $speaker = getTableEntry( 'speakers', 'id', array( 'id' => $speaker ) );
        return speakerToHTML( $speaker );  // call recursively
    }

    // Get name of the speaker.
    $name = array( );
    foreach( explode( ',', 'honorific,first_name,middle_name,last_name' ) as $k )
        if( $speaker[ $k ] )
            array_push( $name, $speaker[ $k ] );

    $name = implode( ' ', $name );

    // Start preparing speaker HTML.
    $html = $name;

    // If there is url. create a clickable link.
    if( $speaker )
    {
        if( __get__( $speaker, 'homepage', '' ) )
            $html .=  '<br /><a target="_blank" href="' . $speaker['homepage'] . '">Homepage</a>';

        if( __get__( $speaker, 'designation', '' ) )
            $html .= "<br /><small><strong>" . $speaker[ 'designation' ] . "</strong></small>";

        if( $speaker[ 'department' ] )
            $html .= "<br />" . $speaker[ 'department' ];

        $html .= "<br />" . $speaker[ 'institute' ];
    }

    return $html;
}

/**
    * @brief Convert a speaker to HTML based on its ID. Make sure it is > 0.
    *
    * @param $id
    *
    * @return
 */
function speakerIdToHTML( $id )
{
    $speaker = getTableEntry( 'speakers', 'id', array( 'id' => $id ) );
    return speakerToHTML( $speaker );
}


/**
    * @brief Summary table for front page.
    *
    * @return
 */
function summaryTable( )
{
    global $db;
    $allAWS = getAllAWS( );
    $nspeakers = count( getAWSSpeakers( ) );
    $nAws = count( $allAWS );
    $awsThisYear = count( getAWSFromPast( date( 'Y-01-01' ) ) );
    $html = '<table class="summary">';
    //$html .= "
    //    <tr>
    //        <td>$nAws AWSs </td>
    //        <td> $awsThisYear AWSs so far this year </td>
    //    </tr>";
    $html .= "</table>";
    return $html;
}


function sanitiesForTinyMCE( $text )
{
    $text = preg_replace( "/\r\n|\r|\n/", " ", $text );
    $text = str_replace( "'", "\'", $text );
    $text = htmlspecialchars_decode( $text );
    return $text;
}

function prettify( $string )
{
    // Replace _ with space.
    $string = str_replace( "_", " ", $string );

    // Uppercase first char.
    $string = ucfirst( $string );
    return $string;
}


/**
    * @brief Convert requests to HTML form for review.
    *
    * @param $requests
    *
    * @return
 */
function requestsToHTMLReviewForm( $requests )
{
    $html = '<table>';
    foreach( $requests as $r )
    {
        $html .= '<tr><td>';
        // Hide some buttons to send information to next page.
        $html .= '<input type="hidden" name="gid" value="' . $r['gid'] . '" />';
        $html .= '<input type="hidden" name="rid" value="' . $r['rid'] . '" />';
        $html .= arrayToTableHTML( $r, 'events', '',  'status,modified_by,timestamp,url' );
        $html .= '</td>';
        $html .= '<td style="background:white">
                 <button name="response" value="Review">Review</button>
                 </td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    return $html;
}


// Return a short description of event. Don't force type on events, google 
// calednar API uses it but passes an object not an array.
function eventToText( array $event ) : string
{
    $html = 'By ' . $event['created_by'] . ', ';
    $html .= '';
    $html .= __get__( $event, 'title', '' );
    $html .= ' @' . $event['venue'] . ', ';
    $html .= humanReadableDate( $event['date'] );
    $html .= ', ' . humanReadableTime( $event['start_time'] )
             . ' to ' . humanReadableTime( $event['end_time'] ) ;
    return $html;
}

function eventToShortHTML( $event )
{
    $startT = date( 'H:i', strtotime( $event[ 'start_time' ] ) );
    $endT = date( 'H:i', strtotime( $event[ 'end_time' ] ) );
    $html = colored(  __get__( $event, 'title', '' ), 'FireBrick' ) . 
        ' (' . $event['class'] . ')</tt>';
    $html .= '<br>' . $startT . ' to ' . $endT;
    $html .= ' </tt> @ <strong>' . $event['venue'] . '</strong>, ';
    $html .= '</br><small>Booked by ' . mailto($event['created_by']) . '</small><br/>';
    return $html;
}

function requestToShortHTML( $request )
{
    $startT = date( 'H:i', strtotime( $request[ 'start_time' ] ) );
    $endT = date( 'H:i', strtotime( $request[ 'end_time' ] ) );
    $html =  colored( __get__( $request, 'title', '' ), 'FireBrick') 
        . ' (' . $request['class'] . ')';
    $html .= '<tt>(' . $request['gid'] . ')</tt>';
    $html .= '<br>' . $startT . ' to ' . $endT;
    $html .= ' </tt> @ <strong>' . $request['venue'] . '</strong>, ';
    $html .= '<br /><small>Requested by ' . mailto($request['created_by']) . '</small>';
    $html .= '<br /><small>Created on: ' . humanReadableDate( $request['timestamp']);
    return $html;
}


function eventSummaryHTML( $event, $talk = null) : string
{ 
    if( ! $talk || ! $event )
        return '';

    $date = humanReadableDate( $event[ 'date' ] );
    $startT = humanReadableTime( $event[ 'start_time' ] );
    $endT = humanReadableTime( $event[ 'end_time' ] );
    $time = "$startT to $endT";
    $venue = venueSummary( $event[ 'venue'] );

    if( $talk )
        $title = talkSummaryLine( $talk );
    else
        $title = $event[ 'title'];

    $html = "<h2>" . $title . "</h2>";
    $html .= '<table class="events">';

    if( $talk )
    {
        $speaker = $talk[ 'speaker' ];
        $html .= "<tr><td> Host </td><td>" . loginToHTML( __get__($talk, 'host','')) ."</td></tr>";
        $html .= "<tr><td> Coordinator </td><td>" .
                     loginToHTML( __get__($talk,'coordinator','') ) ."</td></tr>";
    }

    $html .= "<tr><td> Where </td><td>  $venue </td></tr>";
    $html .= "<tr><td> When </td><td>" . $date . ", " . $time . " </td></tr>";
    $html .= '</table>';

    // Add google and ical links.
    $html .=  addToGoogleCalLink( $event );

    return $html;
}

// Return a short description of event for main page.
function eventSummary( $event )
{
    $html = '<table class=\"event_summary\">';
    $html .= '<tr><td><small>WHEN</small></td><td>' .  date( 'l M d, Y', strtotime($event['date']));
    $html .= date('H:i', strtotime($event['start_time']))  . ' to ' .
             date( 'H:i', strtotime(  $event['end_time'])) . '</td></tr>';

    $html .= '<tr><td><small>WHERE</small></td><td>'.  $event['venue'] . "</td></tr>";
    $html .= '<tr><td><small>WHAT</small></td><td>' . $event['title']
             . "</td></tr>";
    $html .= "</table>";
    return $html;
}


function requestToText( $req )
{
    $html = 'By ' . $req['created_by'] . ', ';
    $html .= $req['title'];
    $html .= ' @' . $req['venue'] . ', ';
    $html .= $req['start_time'] . ' to ' . $req['end_time'];
    $html .= "; ";
    return $html;
}

// $day and $hour are used to check if at this day and hour  this venue is
// booked or have pending requests.
function hourToHTMLTable( $day, $hour, $venue, $section = 4 )
{
    //$tableName = "<font style=\"font-size:12px\">" . strtoupper($venue). "</font><br>";
    $tableTime = "<font style=\"font-size:12px\" >" . date('H:i', $hour) . "</font>";
    $html = "<table class=\"hourtable\">";
    $html .= "<tr><td colspan=\"$section\"> $tableTime </td></tr>";

    $html .= "<tr>";
    for( $i = 0; $i < $section; $i++)
    {
        $stepT = $i * 60 / $section;
        $segTime = strtotime( "+ $stepT minutes", $hour );
        $segDateTime = strtotime( $day . ' ' . date('H:i', $segTime ));

        // Check  for events at this venue. If non, then display + (addEvent)
        // button else show that this timeslot has been booked.

        $events = eventsAtThisVenue( $venue, $day, $segTime );
        $requests = requestsForThisVenue( $venue, $day, $segTime );

        // If there is a public event at this time, change the color of all
        // button at all venues. Thats clue to user that something else has been
        // approved at this time.
        $is_public_event = '';
        if( count( publicEvents( $day, $segTime ) ) > 0 )
            $is_public_event = '_with_public_event"';

        if( count( $events ) == 0 && count($requests) == 0)
        {

            // Add a form to trigger adding event purpose.
            $html .= '<form method="post" action="'.site_url('user/bookingrequest') . '">';
            $html .= "<td>";
            if( $segDateTime >= strtotime( 'now' ) )
                $html .= '<button class="add_event'.$is_public_event.'" name="add_event" value="'.$segTime.'">+</button>';
            else
                $html .= '<button class="add_event_past'.$is_public_event.'" name="add_event" value="'.$segTime.'" disabled></button>';

            $html .= "</td>";

            // And the hidden elements to carry the values to the action page.
            $html .= '<input type="hidden" name="start_time" value="'. dbTime($segTime) . '">';
            $html .= '<input type="hidden" name="date" value="'. $day . '">';
            $html .= '<input type="hidden" name="venue" value="'. $venue . '">';
            $html .= "</form>";
        }
        else
        {
            if( count( $events ) > 0 )
            {
                $msg = '';
                foreach( $events as $e )
                    $msg .= eventToText( $e );
                $html .= "<td><button class=\"display_event\"
                         value=\"$msg\" onclick=\"displayEvent(this)\"></button></td>";
            }
            elseif( count( $requests ) > 0 )
            {
                $msg = '';
                foreach( $requests as $r )
                    $msg .= requestToText( $r );
                $html .= "<td><button class=\"display_request\"
                         value=\"$msg\" onclick=\"displayRequest(this)\"></button></td>";
            }
        }
    }
    $html .= "</tr></table>";
    return $html;
}

// Convert a event into a nice looking html line.
function eventLineHTML( $date, $venueid, $start = '8:00', $end = '18:00' )
{
    $venue = getVenueById( $venueid );
    $venueText = venueSummary( $venueid );
    $html = '<table class="eventline">';
    $startDay = $start;
    $dt = 60;
    $html .= "<tr>";
    $html .= "<td><div style=\"width:100px\">$venueText</div></td>";
    $duration = ( strtotime( $end ) - strtotime( $start ) ) / 3600;
    for( $i = 0; $i < $duration; $i++ )
    {
        $stepT = $i * $dt;
        $segTime = strtotime( $startDay ) + 60 * $stepT;
        // Each hour has 15 minutes segment. For each segment hourToHTMLTable
        // create a block.
        $html .= "<td>" . hourToHTMLTable( $date, $segTime, $venueid, 4 ) . "</td>";
    }
    $html .= "</tr>";
    $html .= '</table>';
    return $html;
}

// Convert a event into a readonly event line.
function readOnlyEventLineHTML( $date, $venueid )
{
    $events = getEventsOnThisVenueOnThisday( $venueid, $date );
    $requests = getRequestsOnThisVenueOnThisday( $venueid, $date );

    $html = '';
    if( count( $events ) + count( $requests ) > 0 )
    {
        $html .= '<table class="show_calendar">';
        $html .= "<tr> <td> $venueid </td>";

        $html .= "<td> <table class=\"show_info\"><tr>";
        foreach( $requests as $req )
            $html .= '<td> Unapproved:<br>' . requestToText( $req ) . "</td>";

        foreach( $events as $event )
            $html .=  "<td>" . eventToText( $event ) . "</td>";
        $html .= "</tr></table>";

        $html .= "</td></tr>";
        $html .= '</table>';
    }
    return $html;
}

/**
    * @brief Convert each array to a single HTML row.
    *
    * @param $array
    * @param $tablename
    * @param $tobefilterd
    *
    * @return
 */
function arrayToRowHTML( $array, $tablename, $tobefilterd = ''
    , $linkify = true, $withtr=true )
{
    if( $withtr )
        $row = '<tr>';
    else
        $row = '';

    if( is_string( $tobefilterd ) )
        $tobefilterd = explode( ',', $tobefilterd );

    $keys = array_keys( $array );

    $toDisplay = Array();
    foreach( $keys as $k )
    {
        if( ! in_array( $k, $tobefilterd ) )
            $toDisplay[] = $array[ $k ];
    }

    foreach( $toDisplay as $v )
    {
        if( isStringAValidDate( $v ) )
            $v = humanReadableDate( $v );

        if( $linkify )
            $v = linkify( $v );

        $row .= "<td><div class=\"cell_content\">$v</div></td>";
    }

    if( $withtr )
        $row  .= "</tr>";
    else
        $row .= '';

    return $row;

}

function arraysToTable( $arrs, $with_index = false )
{
    $html = '<table>';

    foreach( $arrs as $i => $row )
    {
        $tr = '';

        if( $with_index )
            $tr .= "<td> $i </td>";

        if( is_string( $row ) )
            $tr .= "<td>" . $row . "</td>";
        else
            foreach( $row as $j => $val )
                $tr .= "<td>" . $val . "</td>";

        $html .= "<tr> $tr </tr>";
    }
    $html .= "</table>";
    return $html;
}

/**
    * @brief Convert an array to HTML header row. Only th fields are used.
    *
    * @param $array
    * @param $tablename
    * @param $tobefilterd
    *
    * @return
 */
function arrayHeaderRow( $array, $tablename, $tobefilterd = '', $sort_button = false )
{
    $hrow = '';
    $keys = array_keys( $array );
    $toDisplay = Array();
    $hrow .= "<tr>";

    if( is_string( $tobefilterd ) )
        $tobefilterd = explode( ',', $tobefilterd );

    foreach( $keys as $k )
    {
        if( ! in_array( $k, $tobefilterd ) )
        {
            $kval = prettify( $k );
            $label = strtoupper( $kval );
            $sortButton = '';
            if( $sort_button )
            {
                $sortButton = '<table class="sort_button"><tr>';
                $sortButton .= "<td><button class='sort' name='response' value='sort'>
                    <i class='fa fa-sort-asc'></i>
                    </button></td>";
                $sortButton .= "<td><button class='sort' name='response' value='sort'>
                    <i class='fa fa-sort-desc'></i>
                    </button></td>";
                $sortButton .= '<input type="hidden" name="key" value="' . $k . '" />';
                $sortButton .= '</tr></table>';
            }
            $hrow .= "<th class=\"db_table_fieldname\">$label $sortButton</th>";
        }
    }

    return $hrow;
}

function arrayToTHRow(array $array, string $tablename, $tobefilterd='', bool $sort_button=false) : string
{ 
    if( ! $array )
        return '';

    return arrayHeaderRow( $array, $tablename, $tobefilterd , $sort_button);
}

// Convert an array to HTML
function arrayToTableHTML(array $array, string $tablename, string $background=''
    , string $tobefilterd='', bool $header=true, string $class= '')
{
    if( $background )
        $background = "style=\"background:$background;\"";

    if( is_string( $tobefilterd ) )
        $tobefilterd = explode( ',', $tobefilterd );

    $table = "<table class=\"show_$tablename $class\" $background>";
    $keys = array_keys( $array );
    $toDisplay = Array();
    if( $header )
    {
        $table .= "<tr>";
        $table .= arrayToTHRow( $array, $tablename, $tobefilterd );
        $table .= "</tr>";
    }
    $table .= arrayToRowHTML( $array, $tablename, $tobefilterd );
    $table .= "</table>";
    return $table;
}

function arraysToCombinedTableHTML( $tables, $class, $hide = '', $id = 'downloadable' )
{
    $html = "<table id='$id' class='$class'>";
    $html .= arrayToTHRow( $tables[0], $class, $hide );
    foreach( $tables as $table )
        $html .= arrayToRowHTML( $table, $class, $hide );
    $html .= "</table>";
    return $html;
}

// Convert an array to HTML table (vertical)
function arrayToVerticalTableHTML( $array, $tablename
    , $background = NULL, $tobefilterd = ''
    , $with_hidden_input = false
)
{
    if( $background )
        $background = "style=\"background:$background;\"";
    else
        $background = '';

    if( is_string( $tobefilterd ) )
        $tobefilterd = explode( ",", $tobefilterd );

    $table = "<table class=\"show_$tablename\" $background>";
    $keys = array_keys( $array );
    $toDisplay = Array();
    foreach( $keys as $k )
    {
        if( $with_hidden_input )
        {
            // Create a hidden field just in case.
            $val = htmlspecialchars( __get__( $array, $k ) );
            $table .= '<input type="hidden" name="' . $k . '"  value="' . $val .'" />';
        }
        if( ! in_array( $k, $tobefilterd ) )
        {
            $table .= "<tr>";
            $kval = prettify( $k );
            $label = strtoupper( $kval );
            $table .= "<td class=\"db_table_fieldname\">$label</td>";

            // Escape some special chars speacial characters.
            $text = linkify( $array[ $k ] );

            $table .= "<td><div class=\"cell_content\">$text</div></td>";
            $table .= "</tr>";
        }
    }

    // Also set the content as div element which can be formatted using css
    $table .= "</table>";
    return $table;
}


function requestToHTML( $request )
{
    return arrayToTableHTML( $request, "request" );
}


function arrayToHtmlTableOfLogins( $logins )
{
    $table = '<table>';

    foreach( $logins as $i => $login )
    {
        $table .= "<tr><td> " . ($i + 1) . "</td><td>"
            . arrayToName( $login ) . "</td><td>" . $login['email']
            . "</td></tr>";

    }
    $table.= '</table>';
    return $table;
}

function userHTML() : string
{
    $user = whoAmI();
    $html = '<div class="user_float">';
    $html .= '<table class="user_float">';
    $html .= '<tr> <td><a href="' . site_url( '/user/info' ) .  '">
        <i class="fa fa-user-o"></i> Hi ' . $user.' </a> </td>';

    $html .= '<td><a href="' . site_url('/user/logout') . '">
        <i class="fa fa-sign-out"></i>SignOut</a></td>';

    $html .= '</tr><tr>';
    $html .= '<td><a href="' . site_url( '/user/book' ) . '">
        <i class="fa fa-hand-pointer-o"></i>QuickBook</a>';

    $html .= '<td><a href="' . site_url( 'user/home' ) . '">
            <i class="fa fa-home"></i>My Home</a></td>';
    $html .= '</tr>';
    $html .= '</table>';
    $html .= '</div>';
    return $html;
}

function venuesToHTMLCheck( $groupedVenues, $grouped )
{
    $html = '<table class="venues">';
    $html .= "<tr>";
    foreach( array_keys( $groupedVenues ) as $venueType )
        $html .= "<td> $venueType </td>";
    $html .= "</tr><tr>";
    foreach( array_values($groupedVenues) as  $venues )
        $html .= "<td> " . venuesToChekcButtons( $venues ) . "</td>";
    $html .= "</tr></table>";
    return $html;
}

function venueSummaryHTML( $venue ) : string
{
    if( is_string( $venue ) )
        $venue = getVenueById( $venue );

    $vname = trim($venue['name']);

    $html = '<table style="width:100%;table-layout:fixed;font-size:small"><tr>';
    $html .= "<td colspan=2>
        <font style='font-variant:small-caps;color:blue;font-size:x-large'>$vname</font>
        </td><td><tt>" . $venue[ 'type' ] . '</tt> </td><td>' .
        $venue['building_name'] . '</td><td>' . $venue['location'] . '</td>'
        ;
    $html .= '</tr></table>';
    return $html;
}

/**
    * @brief Convert a given venue to summary.
    *
    * @param $venue
    * @param ame
    *
    * @return 
 */
function venueSummary($venue, $withName=true)
{
    if(is_string($venue))
        $venue = getVenueById( $venue );

    $res = '';

    if($withName)
        $res = $venue['name'];

    if(strtoupper(__get__($venue, 'type', 'REMOTE VC')) !== 'REMOTE VC')
        $res .= " [" . $venue['type'] . "]";

    if( __get__($venue, 'institute', 'Virtual') !== 'Virtual')
        $res .= ", " . $venue['institute'];
    return $res;
}

function requestToEditableTableHTML( $request, $editables = Array( ) )
{
    $html = "<table class=\"request_show_edit\">";
    foreach( $request as $key => $value )
    {
        $editHTML = $value;
        if( in_array( $key, $editables ) )
        {
            $inType = "input";
            $props = "style=\"width:100%;\"";
            $text = "";
            if( $key == "description" )
            {
                $inType = "textarea";
                $props  = $props . " rows=\"4\"";
                $text = $value;
            }

            $editHTML = "<$inType $props name=\"$key\" value=\"$value\">$text</$inType>";
        }
        $html .= "<tr> <td>$key</td><td> $editHTML </td> </tr>";
    }
    $html .= "</table>";
    return $html;
}

/**
    * @brief Add tinyMCE editor.
    *
    * @param $id
    *
    * @return
    */
function editor_script( $id, $default = '' )
{
    $editor = "
        <script>
        tinymce.init( { selector : '#" . $id . "'
        , init_instance_callback: \"insert_content\"
        , plugins : [ 'image imagetools link paste code wordcount fullscreen table' ]
        , paste_as_text : true
        , height : 300
        , paste_data_images: true
        , cleanup : true
        , verify_html : false
        , cleanup_on_startup : false
        , toolbar1 : 'undo redo | insert | stylesheet | bold italic'
            + ' | alignleft aligncenter alignright alignjustify'
            + ' | bulllist numlist outdent indent | link image'
});
    </script>";

    return $editor;

}


/**
    * @brief Convert a database table schema to HTML table to user to
    * edit/update.
    *
    * @param $tablename Name of table (same as database)
    * @param $defaults Default values to pass to entries.
    * @param $editables These keys will be convert to appropriate input fields.
    * @param $button_val What value should be visible on 'response' button?
    * @param $hide These keys will be hidden to user.
    *
    * @return  An html table. You need to wrap it in a form.
 */
function dbTableToHTMLTable( string $tablename, array $defaults=[]
    , $editables = '', string $button_val = 'submit', string $hide = '', string $classes = '' ) 
{
    global $dbChoices;
    global $useCKEditor;

    $html = "<table class=\"editable_$tablename $classes\" id=\"$tablename\">";
    $schema = getTableSchema( $tablename );

    if( is_string( $editables ) )
        $editables = explode( ",", $editables );

    /*
     *  Editabale can have keyval:attribs format. Separate out the extra format
     *  from keys.
     */
    $attribMap = array( );
    $editableKeys = array( );
    foreach( $editables as $v )
    {
        $attr = explode( ":", $v );
        $editableKeys[] = $attr[0];
        if( count( $attr ) > 1 )
            $attribMap[ $attr[0] ] = array_slice( $attr, 1 );
    }

    if( is_string( $hide ) )
        $hide = explode( ",", $hide );

    // Sort the schema in the same order as editable.
    foreach( $schema as $col )
    {
        $keyName = $col['Field'];

        // If this entry is in $hide value, do not process it.
        if( in_array( $keyName, $hide ) )
            continue;

        $ctype = $col['Type'];

        // Add row to table
        $columnText = strtoupper( prettify( $keyName ) );

        // Update column text if 'required' is in attributes.
        $attribs = __get__($attribMap, $keyName, '');
        $required = false;
        if($attribs)
            if( in_array( 'required', $attribs ) )
                $required = true;

        if( $required )
            $columnText .=  '<blink><i class="fa fa-star"></i></blink>';

        $inputId = $tablename . "_" . $keyName;
        $html .= "<tr><td class=\"db_table_fieldname\" > $columnText </td>";

        $default = __get__( $defaults, $keyName, $col['Default'] );

        // DIRTY HACK: If value is already a html entity then don't use a input
        // tag. Currently only '<select></select> is supported
        if( preg_match( '/\<select.*?\>(.+?)\<\/select\>/', $default ) )
            $val = $default;
        else
        {
            $val = "<input class=\"editable\" name=\"$keyName\" type=\"text\"
                    value=\"$default\" id=\"$inputId\"
                   />";
        }

        // Genearte a select list.
        $match = Array( );
        if( preg_match( '/^varchar\((.*)\)$/', $ctype ) )
        {
            $classKey = $tablename . '.' . $keyName;
            if(isset($dbChoices[$classKey]))
            {
                $val = "<select name=\"$keyName\" class='dbchoices'>";
                $choices = getChoicesFromGlobalArray( $dbChoices, $classKey );
                foreach( $choices as $k => $v )
                {
                    $selected = '';
                    $v = str_replace( "'", "", $v );
                    if( $v == $default )
                        $selected = 'selected';
                    $val .= "<option value=\"$v\" $selected> $v </option>";
                }
                $val .= "</select>";
            }

        }
        elseif( preg_match( "/^enum\((.*)\)$/" , $ctype, $match ) )
        {
            $val = "<select name=\"$keyName\" class='enum'>";
            foreach( explode(",", $match[1] ) as $v )
            {
                $selected = '';
                $v = str_replace( "'", "", $v );
                if( $v == $default )
                    $selected = 'selected';
                $val .= "<option value=\"$v\" $selected> $v </option>";
            }

            $val .= "</select>";
        }

        // TODO generate a multiple select for SET typeclass.
        else if( preg_match( "/^set\((.*)\)$/", $ctype, $match ) )
        {
            $val = "<select class='set' multiple name=\"" . $keyName . '[]' . "\">";
            foreach( explode(",", $match[1] ) as $v )
            {
                $selected = '';
                $v = str_replace( "'", "", $v );
                // If it is set, there might be multiple values here. So check
                // in all of them.
                if( in_array($v, explode(',', $default) ) )
                    $selected = 'selected';
                $val .= "<option value=\"$v\" $selected> $v </option>";
            }
            $val .= "</select>";
        }
        // TEXT or MEDIUMTEXT
        else if( strpos( strtolower($ctype), 'text' ) !== false )
        {
            // NOTE: name and id should be same of ckeditor to work properly.
            // Sometimes we have two fileds with same name in two tables, thats
            // a sticky situation.

            $showValue =  sanitiesForTinyMCE( $default );
            $val = "<textarea class=\"editable\" \
                id=\"$inputId\" name=\"$keyName\" >" . $showValue . "</textarea>";

            // Either use CKEDITOR or tinymce.
            if( $useCKEditor )
                $val .= "<script> CKEDITOR.replace( '$inputId' ); </script>";
            else
            {
                $val .= editor_script( $inputId, $showValue );
            }
        }
        else if( strcasecmp( $ctype, 'date' ) == 0 )
            $val = "<input class=\"datepicker\" name=\"$keyName\" value=\"$default\" />";
        else if( strcasecmp( $ctype, 'datetime' ) == 0 )
            $val = "<input class=\"datetimepicker\" name=\"$keyName\" value=\"$default\" />";
        else if( strcasecmp( $ctype, 'time' ) == 0 )
            $val = "<input class=\"timepicker\" name=\"$keyName\" value=\"$default\" />";

        // If not in editables list, make field readonly.
        // When the value is readonly. Just send the value as hidden input and
        // display the default value.
        $readonly = True;
        if( in_array($keyName , $editableKeys ) )
            $readonly = False;

        if( $readonly )
        {
            // Escape only if it is a html.
            $hiddenValue = $default;
            $showValue = $default;
            if( isHTML( $default ) )
            {
                $hiddenValue = htmlspecialchars( $default );
                $showValue = sanitiesForTinyMCE( $default );
            }

            $val = "<input type=\"hidden\" id=\"$inputId\"
                    name=\"$keyName\" value=\"$hiddenValue\" >"
                    . $showValue . '</input>';
        }


        $html .= "<td>" . $val . "</td>";
        $html .= "</tr>";
    }

    // If some fields are editable then we need a submit button as well unless
    // user pass an empty value
    $buttonSym = ucfirst( $button_val );

    // Let JS add extra rows here.
    $html .= '<tr><td></td><td><table id="' . $tablename . '_extra_rows"> </table></td>';
    if( count($editableKeys) > 0 && strlen( $button_val ) > 0 )
    {
        $html .= "<tr style=\"background:white;\"><td></td><td>";
        $html .= "<button class='btn btn-primary' 
            style=\"float:right\" value=\"$button_val\"
            title=\"$button_val\" name=\"response\">" . $button_val . "</button>";
        $html .= "</td></tr>";
    }
    $html .= "</table>";
    return $html;
}

/**
    * @brief Deprecated: Convert an event to an editable table.
    *
    * @param $event
    * @param $editables
    *
    * @return
 */
function eventToEditableTableHTML( $event, $editables = Array( ) )
{
    $html = "<table class=\"request_show_edit\">";
    foreach( $event as $key => $value )
    {
        $editHTML = $value;
        if( in_array( $key, $editables ) )
        {
            $inType = "input";
            $props = "style=\"width:100%;\"";
            $text = "";
            if( $key == "description" )
            {
                $inType = "textarea";
                $props  = $props . " rows=\"4\"";
                $text = $value;
            }

            $editHTML = "<$inType $props name=\"$key\" value=\"$value\">$text</$inType>";
        }
        $html .= "<tr> <td>$key</td><td> $editHTML </td> </tr>";
    }
    $html .= "</table>";
    return $html;
}

/**
    * @brief Convert a array into select list.
    *
    * @param $name Name of the select list.
    * @param $options Options to populate.
    * @param $display Search fo text for each option here if not then prettify
    * the option and show to user.
    * @param $multiple_select If true then allow user to select multiple
    * entries.
    * @param $selected If not '' then select this one by default.
    * @param $header If false, don't show -- Select one -- etc.
    *
    * @return HTML <select>
 */
function arrayToSelectList( string $name, array $options
        , $display = array()
        , $multiple_select = false
        , $selected = ''
        , bool $header = true
    ) : string
{
    $html = '';
    if( ! $multiple_select )
    {
        $html .= "<select class=\"$name\" name=\"$name\">";
        if( $header )
            $html .= "<option selected value=\"\">-- Select one --</option>";
    }
    else
    {
        $html .= "<select class=\"$name\" multiple size=\"4\" name=\"$name\">";
        if( $header )
            $html .= "<option selected disabled>-- Select multiple --</option>";
    }

    foreach( $options as $option )
    {
        $selectText = "";

        if( $option == $selected )
            $selectText = " selected";

        $html .= "<option value=\"$option\" $selectText >"
                 .  __get__( $display, $option, prettify( $option ) )
                 . "</option>";
    }

    $html .= "</select>";
    return $html;
}

/**
    * @brief Convert login/speaker to text. First name + Middle name + Last name format.
    *
    * @param $login
    * @param $withEmail
    *
    * @return A string of length.
 */
function loginToText( $login, $withEmail = true, $autofix = true ) : string
{
    if( ! $login )
        return '';

    $email = '';

    // If only login name is give, query database to get the array. Otherwise
    // assume that an array has been given to use.
    // Find email in text. Sometimes people write the whole name with email. So 
    // stupid.
    if( is_string( $login ) )
    {
        if( __substr__( '@', $login) )
        {
            $email = extract_emails_from( $login );
            if( $email )
                $login = explode( '@', $email )[0];
        }
        if( strlen( trim($login) ) < 1 )
            return '';
        $user = getUserInfo( $login );
    }
    else if( is_array( $login ) )
    {
        $email = __get__( $login, 'email', '' );
        $user = $login;
    }
    else
        $user = $login;

    if( __get__( $user, 'first_name', '' ) == __get__( $user, 'last_name', ''))
    {
        if( $email )
        {
            $ldap = @getUserInfoFromLdap( $email );
            if( $ldap )
                $user = array_merge( $user, $ldap );
        }
    }

    if( is_bool( $user ) and is_string( $login ) )
        return $login;

    // Return first name + middle name + last name.
    $name = array( );
    foreach( explode( ',', 'first_name,middle_name,last_name' ) as $key )
    {
        if( array_key_exists( $key, $user ) )
            array_push( $name, $user[ $key ] );
    }

    if( is_array( $name ) )
        $text = implode( ' ', $name );

    if( $withEmail )
    {
        if( __get__( $user, 'email', '' ) )
            $text .= " (" . $user['email'] . ")";
    }

    if( $autofix )
        $text = fixName( $text );

    // If honorific exits in login/speaker; then prefix it.
    if( __get__( $user, 'honorific', '' ) )
        $text = trim( $user[ 'honorific' ] . ' ' . $text );

    return $text;
}

function loginToHTML( string $login, bool $withEmail = true ) : string
{
    if(! trim($login))
      return '';

    // If only login name is give, query database to get the array. Otherwise
    // assume that an array has been given to use.
    $login = getLoginID( $login );
    $user = getUserInfo( $login, true );

    if( ! $user )
        return $login;

    // Check if professor
    $prefix = '';
    if( __substr__('professor', __get__($user,'designation',''), true) )
        $prefix = 'Prof';

    // Return first name + middle name + last name.
    $text = fixName( arrayToName( $user ) );

    if( $prefix )
        $text = "$prefix $text";

    if( $withEmail )
    {
        if( array_key_exists( 'email', $user) && $user[ 'email' ] )
            $text = "<a href=\"mailto:" . $user['email'] . "\"> $text </a>";
    }

    if( strlen( trim($text) ) < 1 )
        return $login;

    return $text;
}

/**
    * @brief Get link from intranet.
    *
    * @param User login.
    *
    * @return
 */
function getIntranetLink( $login )
{
    $html = "<font style=\"font-size:x-small\"><a
            href=\"https://intranet.ncbs.res.in/people-search?name=$login\"
            target=\"_blank\">Profile on Intranet</a></font>"
            ;
    return $html;
}

/**
    * @brief Return a AWS table which is editable by user. When $default array
    * is present, use it to construct the table. Else query the AWS table.
    * Passing array is useful when AWS is coming from some other table such as
    * upcoming_aws etc.
    *
    * @return  A editable table with submit button.
 */
function editableAWSTable(int $awsId=-1, array $default=[], bool $withlogin=false)
{
    if( $awsId > 0 && ! $default )
        $default = getAwsById( $awsId );

    // Now create an entry
    $supervisors = getSupervisors( );
    $supervisorIds = Array( );
    $supervisorText = Array( );
    foreach( $supervisors as $supervisor )
    {
        array_push( $supervisorIds, $supervisor['email'] );
        $supervisorText[ $supervisor['email'] ] = $supervisor['first_name']
                .  ' ' . $supervisor[ 'last_name' ] ;
    }

    $html = "<table class=\"input\">";
    $text = sanitiesForTinyMCE( __get__( $default, 'abstract', '' ));

    if( $withlogin )
        $html .= '<tr>
                <td>Speaker Login ID</td> 
                <td><input type="text" class="long" name="speaker" value="" /></td>
             </tr>';
                
    $html .= '<tr>
                 <td>Title</td>
                 <td><input type="text" class="long" name="title" value="'
                 . __get__( $default, 'title', '') . '" /></td>
             </tr>
             <tr>
                 <td>Abstract </td>
                 <td><textarea class="editable" id="abstract" name="abstract">' .
                     $text . '</textarea> ' .  editor_script( 'abstract', $text ) . '</td> 
            </tr>';

    for( $i = 1; $i <= 2; $i++ )
    {
        $name = "supervisor_$i";
        $selected = __get__( $default, $name, "" );
        $html .= '
                 <tr>
                 <td>Supervisor ' . $i . '<br></td>
                 <td>' . arrayToSelectList(
                     $name, $supervisorIds , $supervisorText
                     , FALSE, $selected
                 );

        $html .= '</td> </tr>';
    }
    for( $i = 1; $i <= 4; $i++ )
    {
        $name = "tcm_member_$i";
        $selected = __get__( $default, $name, "" );
        $html .= '
                 <tr>
                 <td>Thesis Committee Member ' . $i . '<br></td>
                 <td>' . arrayToSelectList( $name, $supervisorIds , $supervisorText, FALSE, $selected)
                 . '</td>';
        $html .= '</tr>';

    }

    // Check if AWS is pre-synopsis seminar.
    $selected = __get__( $default, 'is_presynopsis_seminar', "NO" );
    $html .= '
             <tr>
             <td>Is Pre-synopsis Seminar? <br></td>
             <td>' . arrayToSelectList( 'is_presynopsis_seminar', array('YES', 'NO') , array(), false, $selected) . '</td>';
    $html .= '</tr>';


    $html .= '
             <tr>
             <td>Date</td>
             <td><input class="datepicker"  name="date" value="' .
             __get__($default, 'date', '' ) . '" readonly ></td>
             </tr>
             <tr>
             <td>Time</td>
             <td><input class="timepicker" name="time" value="16:00" readonly/></td>
             </tr>
             <tr>
             <td></td>
             <td>
             <input  name="awsid" type="hidden" value="' . $awsId . '"  />
             <button class="btn btn-primary submit" name="response" value="submit">Submit</button>
             </td>
             </tr>
             ';
    $html .= "</table>";
    return $html;

}

/**
    * @brief Initialize user message.
    *
    * @param $user Login id of user.
    *
    * @return First part of the message.
 */
function initUserMsg( $user = null )
{
    if( ! $user )
        $user = whoAmI();

    $msg = "<p> Dear " . loginToText( $user ) . "<p>";
    return $msg;
}

function dataURI( $filepath, $mime )
{
    $contents = file_get_contents($filepath);
    $base64   = base64_encode($contents);
    return ('data:' . $mime . ';base64,' . $base64);
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Pph replaces DOT and SPACE with _.
    *
    * @Param $email
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function encodeEmail(string $email): string
{
    $email = trim($email);
    $email = str_replace('.', '·', $email);
    // We don't have space in emails.
    // $email = str_replace(' ', 'SPACE', $email);
    return $email;

}

function decodeEmail(string $email): string
{
    return str_replace('·', '.', $email);
}

function __ucwords__( $text )
{
    return ucwords( strtolower( $text ) );
}


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Draw a horizontal line.
    *
    * @Param $width
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function horizontalLine( $width = "100%" )
{
    return "<div width=$width><hr width=$width align=left> </div>";
}

function awsToHTMLLarge($aws, $with_picture = true, array $links=[])
{
    $speaker = __ucwords__( loginToText( $aws[ 'speaker' ] , false ));
    $supervisors = array( 
        __ucwords__(loginToText( findAnyoneWithEmail( $aws[ 'supervisor_1' ] ), false ))
        ,  __ucwords__(loginToText( findAnyoneWithEmail( $aws[ 'supervisor_2' ] ), false ))
    );
    $supervisors = array_filter( $supervisors );
    $tcm = array( );
    array_push( $tcm
        , __ucwords__(loginToText( findAnyoneWithEmail( $aws[ 'tcm_member_1' ] ), false ))
        , __ucwords__(loginToText( findAnyoneWithEmail( $aws[ 'tcm_member_2' ] ), false ))
        , __ucwords__(loginToText( findAnyoneWithEmail( $aws[ 'tcm_member_3' ] ), false ))
        , __ucwords__(loginToText( findAnyoneWithEmail( $aws[ 'tcm_member_4' ] ), false ))
    );
    $tcm = array_filter( $tcm );
    $title = $aws[ 'title' ];
    if(__get__($aws, 'is_presynopsis_seminar', 'NO') === 'YES')
        $title = "(Presynopsis Seminar) $title";

    if( strlen( $title ) == 0 )
        $title = "Not disclosed yet.";

    $abstract = $aws[ 'abstract' ];
    if( strlen( $abstract ) == 0 )
        $abstract = "Not disclosed yet!";

    // Adding css inline screw up the email view. Dont do it.
    $user = $aws[ 'speaker' ];

    // Add a table only if there is a picture. Adding TD when there is no picture
    // screws up the formatting of emails.
    $pic = '';
    if( $with_picture )
    {
        $imgpath = getLoginPicturePath( $user );
        $pic = showImage( $imgpath, 'auto', '200px' );
    }

    $whereWhen = whereWhenTable( $aws['venue'], $aws['date'], $aws['time'], $inothertable=true);
    $left = '<table class="table table-sm">
                <tr>
                    <td colspan="2"><strong>' . $speaker .'</strong></td>
                </tr>
                <tr>
                    <td>' . smallCaps( 'Supervisors') . '</td>
                    <td>' . implode( ", ", $supervisors ) . '</td>
                 </tr>
                 <tr>
                     <td>' . smallCaps( 'Thesis Committee Members') . '</td>
                     <td>' . implode( ", ", $tcm) . '</td>
                 </tr>' . $whereWhen . '</table>';

    $infoTable = "<table><tr><td> $pic </td><td> $left </td></tr></table>";

    $abstract = rescale_inline_images( $abstract );

    // Add table.
    $html = '<div class="card">';
    $html .= "<h1 class='card-header' style='font-size:x-large'>$title</h1>";

    $right =  fixHTML( $abstract );
    $html .= "<div class='card-body'>";
    $html .= "<div> $infoTable </div> <div> $right </div>";
    foreach ($links as $link) 
        $html .= "<div class='card-link'>$link</div>";
    $html .= "</div>";
    $html .= '</div>';
    return $html;
}

/**
    * @brief NOTE: Must not have any decoration. Used in sending emails.
    * Squirrel mail html2text may not work properly.
    *
    * @param $aws
    * @param $with_picture
    *
    * @return
 */
function awsToHTML( $aws, $with_picture = false )
{
    $speaker = __ucwords__( loginToText( $aws[ 'speaker' ] , false ));

    $supervisors = array( 
        __ucwords__( loginToText( findAnyoneWithEmail( $aws[ 'supervisor_1' ] ), false ))
        ,  __ucwords__(loginToText( findAnyoneWithEmail( $aws[ 'supervisor_2' ] ), false ))
    );
    $supervisors = array_filter( $supervisors );

    $tcm = array( );
    array_push( $tcm
        , __ucwords__(loginToText( findAnyoneWithEmail( $aws[ 'tcm_member_1' ] ), false ))
        , __ucwords__(loginToText( findAnyoneWithEmail( $aws[ 'tcm_member_2' ] ), false ))
        ,  __ucwords__(loginToText( findAnyoneWithEmail( $aws[ 'tcm_member_3' ] ), false ))
        , __ucwords__(loginToText( findAnyoneWithEmail( $aws[ 'tcm_member_4' ] ), false ))
    );
    $tcm = array_filter( $tcm );

    $title = $aws[ 'title' ];
    if( strlen( $title ) == 0 )
        $title = "Not disclosed yet.";

    if( __get__( $aws, 'is_presynopsis_seminar', 'NO' ) === 'YES' )
        $title = '(Presynopsis Seminar)' . ' ' . $title;

    $abstract = $aws[ 'abstract' ];
    if( strlen( $abstract ) == 0 )
        $abstract = "Not disclosed yet!";

    // Adding css inline screw up the email view. Dont do it.
    $user = $aws[ 'speaker' ];

    // Add a table only if there is a picture. Adding TD when there is no picture
    // screws up the formatting of emails.
    $pic = '';
    if( $with_picture )
        $pic = getUserPicture( $user, 'hippo', '200px' );

    $extra = '<table class="table table-sm table-info">
            <tr>
                <td colspan="2"><strong>' . $speaker .'</strong></td>
            </tr>
            <tr>
                <td>' . smallCaps( 'Supervisors') . '</td>
                <td>' . implode( ", ", $supervisors ) . '</td>
             </tr>
             <tr>
                 <td>' . smallCaps( 'Thesis Committee Members') . '</td>
                 <td>' . implode( ", ", $tcm) . '</td>
             </tr>
             <tr>
                 <td>Where/When</td>
                 <td>'. venueToShortText($aws['venue']) . ', '
                     . humanReadableDate($aws['date']) . ' '
                     . humanReadableTime($aws['time']) . '</td>
             </tr>
             </table>';

    $abstract = rescale_inline_images( $abstract );

    // Add table.
    $html = "<div class='card'>";
    $html .= "<h1 class='card-header'>$title</h1>";
    $html .= "<div class='card-body'> $extra $abstract </div>";
    $html .= '</div>';
    return $html;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Given id or array of speaker, construct speaker name.
    *
    * @Param $speaker   Array or integer in spring format.
    * @Param $with_email  
    *
    * @Returns  Speaker name in plain text.
 */
/* ----------------------------------------------------------------------------*/
function speakerName($speaker, bool $with_email=false): string
{
    // NOTE: Do not use is_int here.
    if( is_numeric( $speaker ) )                        // Got an id.
        $speaker = getTableEntry('speakers', 'id' , ['id' => $speaker]);

    $name = __get__($speaker, 'honorific', '' );
    if( $name )
        $name .= ' ';

    $name .= __ucwords__( __get__($speaker, 'first_name', 'NA') );

    /* Middle name can be very long and make looks name look awkward */
    $mname = trim(__get__($speaker, 'middle_name'));
    if($mname && strtolower($mname) != 'na' && strtolower($mname) != 'notupdated')
    {
        // Just get initial from middle name.
        $name .= ' ' . __ucwords__($mname[0]);
    }

    $name .= ' ' . __ucwords__(__get__($speaker,'last_name', ''));

    if( $with_email )
    {
        $email = __get__( $speaker, 'email', '' );
        if( $email )
            $name .= " <$email>";
    }
    return $name;
}

function arrayToName( $arr, $with_email = false )
{
    return speakerName( $arr, $with_email );
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Convert talk to HTML.
    *
    * @Param $talk
    *   array: Talk data.
    * @Param $with_picture
    *   Show pictures or not.
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function talkToHTMLLarge( $talk, $with_picture=true, string $header='') : string
{
    $speakerId = intval($talk['speaker_id']);

    // If speaker id is > 0, then use it to fetch the entry. If not use the
    // speaker name. There was a design problem in the begining, some speakers
    // do not have unique id but only email. This has to be fixed.
    if( $speakerId > 0 )
    {
        $speakerArr = getTableEntry('speakers', 'id', array( 'id' => $speakerId ));
        $speakerName = speakerName( $speakerId );
    }
    else
    {
        $speakerArr = getSpeakerByName( $talk[ 'speaker' ] );
        $speakerName = speakerName( $speakerArr );
    }

    $coordinator = __get__($talk, 'coordinator', '');
    $hostEmail = $talk[ 'host' ];

    // Either NCBS or InSTEM.
    $hostInstitite = emailInstitute( $hostEmail );

    // Get its events for venue and date.
    $event = getEventsOfTalkId( $talk[ 'id' ] );
    $where = venueSummary( $event[ 'venue' ] );

    if(__get__($event, 'vc_url', '')) {
        $where .= anchor($event['vc_url'], "Remote URL");
    }

    $when = humanReadableDate( $event[ 'date' ] ) . ', ' .
            humanReadableTime( $event[ 'start_time'] );

    $title = '(' . __ucwords__($talk[ 'class' ]) . ') ' . $talk[ 'title' ];

    $pic = '';
    if($with_picture) {
        $imgpath = getSpeakerPicturePath($speakerId);
        $pic = showImage($imgpath, 'auto', '200px');
    }

    // Speaker info
    $speakerHMTL = speakerToHTML( $speakerArr );
    if( $speakerId > 0 )
        $speakerHMTL = speakerIdToHTML( $speakerId );

    // Hack: If talk is a THESIS SEMINAR then host is thesis advisor.
    $host = '<td>Host</td><td>' . loginToHTML( $talk['host'], false );
    if(__get__($talk, 'host_extra', ''))
        $host .= ' and ' . loginToHTML( $talk['host_extra'], false);
    $host .= '</td>';

    if( __substr__('THESIS SEMINAR', $talk['class'] ))
        $host = '<td>Supervisor</td><td>' . loginToHTML( $talk[ 'host' ], false ) . '</td>';

    // Calendar link.
    $googleCalLink = addToGoogleCalLink( $event );
    //$icalLink = eventToICALLink( $event );

    $right = '<table class="table table-sm table-borderless">';
    $right .= '<tr><td colspan="2"><big>' . $speakerHMTL . '</big></td></tr>
            <tr>' . $host . '</tr>
            <tr><td>When</td><td>' . $when . '</td></tr>
            <tr><td>Where</td><td> ' . $where . '</td></tr>';
    if(__get__($event, 'vc_url', ''))
        $right .= '<tr><td>VC Link</td><td> ' . anchor_popup($event['vc_url']) . '</td></tr>';

    $right .= '<tr><td>Coordinator</td><td>' . loginToHTML($coordinator, true) .'</td></tr>';
    $right .=  "<tr><td>$googleCalLink </td>";
    $right .= '<td><a target="_blank" href="'.site_url('info/talks') . '?date='. $event[ 'date' ]
                . '">Permanent link</a></td></tr>';
    $right .= "</table>";

    // Tbale with speaker and other info.
    $infoTable = '<table class="table table-responsive">';
    $infoTable .= "<tr><td colspan='2'> $pic </td>";
    $infoTable .= "<td> $right <td></tr>";
    $infoTable .= '</table>';
    $talkHTML = '<div class="">' . fixHTML( $talk['description'] ) . '</div>';

    // Final HTML.
    $html = "<details> 
        <summary>
            <div class='small font-weight-bold text-uppercase'> $header </div>
            <div class='h4'> $title </div> 
        </summary>";
    $html .= "<div> $infoTable </div> <div> $talkHTML </div>";
    $html .= "</details>";
    return $html;
}

/**
    * @brief Convert an event entry to HTML. This is suitable for sending html
    * emails. Prefer talkToHTMLLarge for displaying on hippo.
    *
    * @param $talk Talk/event entry.
    * @param DEPRECATED: $with_picture Fetch entry with picture.
    *
    * @return
 */
function talkToHTML( $talk, $with_picture = false )
{
    $speakerId = intval( $talk[ 'speaker_id' ] );
    // If speaker id is > 0, then use it to fetch the entry. If not use the
    // speaker name. There was a design problem in the begining, some speakers
    // do not have unique id but only email. This has to be fixed.
    if( $speakerId > 0 )
    {
        $speakerArr = getTableEntry( 'speakers', 'id', array( 'id' => $speakerId ) );
        $speakerName = speakerName( $speakerId );
    }
    else
    {
        $speakerArr = getSpeakerByName( $talk[ 'speaker' ] );
        $speakerName = speakerName( $speakerArr );
    }

    $coordinator = __get__( $talk, 'coordinator', '' );

    $hostEmail = $talk[ 'host' ];


    // Either NCBS or InSTEM.
    $hostInstitite = emailInstitute( $hostEmail );

    // Get its events for venue and date.
    $event = getEventsOfTalkId( $talk[ 'id' ] );

    $where = venueSummary( $event[ 'venue' ] );
    if(__get__($event, 'vc_url', '')) {
        $where .= "<br />" . anchor($event['vc_url'], $event['vc_url']);
    }


    $when = humanReadableDate( $event[ 'date' ] ) . ', ' .
            humanReadableTime( $event[ 'start_time'] );

    $title = '(' . __ucwords__($talk[ 'class' ]) . ') ' . $talk[ 'title' ];

    // Speaker info
    $speakerHMTL = speakerToHTML( $speakerArr );
    if( $speakerId > 0 )
        $speakerHMTL = speakerIdToHTML( $speakerId );

    // Hack: If talk is a THESIS SEMINAR then host is thesis advisor.
    $host = '<td>Host</td><td>' . loginToHTML( $talk['host'], true );
    if(__get__($talk, 'host_extra', ''))
        $host .= ' and ' . loginToHTML( $talk['host_extra'], true);
    $host .= '</td>';

    if( __substr__('THESIS SEMINAR', $talk['class'] ))
        $host = '<td>Supervisor</td><td>' . loginToHTML( $talk[ 'host' ], false ) . '</td>';

    // Calendar link.
    $googleCalLink = addToGoogleCalLink( $event );
    //$icalLink = eventToICALLink( $event );

    // Author information 
    $side = $speakerHMTL;
    $side .= '<br /> <br />';
    $side .= '<table class="">
            <tr>' . $host . '</tr>
            <tr><td>When</td><td>' . $when . '</td></tr>
            <tr><td>Where</td><td> ' . $where . '</td></tr>
            <tr><td>Coordinator</td><td>' . loginToHTML($coordinator, true) .'</td></tr>';
    $side .= '</table>';

    $html = "<h1> $title </h1>";
    $html .= $side;
    $html .= ' <br />';
    $html .= fixHTML( $talk['description'] );

    // Add the calendar links
    $html .=  $googleCalLink;
    $html .= '<a target="_blank" href="'.site_url('info/talks') . '?date=' . $event[ 'date' ]
                . '">Permanent link</a>';

    return $html;
}

function printableCharsOnly( $html )
{
    return preg_replace('/[\x00-\x1F\x7F]/u', '', $html );
}

function closePage( )
{
    return "<div><a href=\"javascript:window.close();\">Close Window</a></div>";
}

function awsPdfURL( $speaker, $date, $msg = 'Download PDF' )
{
    $ret = pdfFileOfAWS( $date, $speaker );
    return download_file( $ret['pdf'] );
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Generate a controller for downloading file.
    *
    * @Param $filepath
    * @Param $msg
    *
    * @Returns  Clickable link.
 */
/* ----------------------------------------------------------------------------*/
function download_file( $filepath, $msg = 'Download File' )
{
    // All of these files are in /tmp directory.
    if( file_exists( $filepath ) )
    {
        $filename = basename($filepath);
        $url = "<a class='download_link' href='". site_url("user/download/$filename")."'>$msg</a>";
    }
    else
        $url = "<a class='download_link' disabled>No downloadable data found.</a>";

    return $url;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Generate pdfs for talk on given date.
    *
    * @Param $date
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function pdfTalkLink( $date )
{
    $pdffile = generatePdfForTalk( $date );
    return download_file( $pdffile );
}

/**
    * @brief Download text file of given name. This file must exists in data
    * folder.
    *
    * @param $filename
    * @param $msg
    *
    * @return
 */
function downloadTextFile( $filepath, $msg = 'Download file', $class = 'download_link' )
{
    return download_file( $filepath );
}


/**
    * @brief Generate a two column table for user to fill-in.
    *
    * @return
 */
// <td>Repeat pattern for recurrent events <br> (optional) <br>
//     <p class="note_to_user"> Valid for maximum of 6 months </p>
//     </td>
function repeatPatternTable( )
{
    $html = '<table class="info" style="width:100%">';
    // Day row.
    $html .= '<tr>
                <th> Select days </th>
                <th> Select Weeks </th>
                <th> Number of months </th>
            </tr>';
    $html .= '<tr> '; 
    $html .= '<td>';
    $html .= arrayToMultiCheckbox( 'day_pattern', 'Mon,Tue,Wed,Thu,Fri,Sat,Sun' );
    $html .= '</td><td>';

    $html .= arrayToMultiCheckbox( 'week_pattern'
                        , 'All,First,Second,Third,Fourth,Fifth'
                        , $default ='All' 
                    );
    $html .= '</td>';
    $html .= '<td>
                <nobr>
                <strong>(<span style="white-space:nowrap" id="textInput">6</span>)</strong>
                <input type="range" name="month_pattern" 
                    id="rangeInput"
                    min="1" max="6" value="6" 
                    onchange="updateTextInput(this.value);" />
                </nobr>
                </td>
                ';
    $html .= '</tr>';
    $html .= "</table>";
    return $html;
}

function arrayToMultiCheckbox( string $name, string $values, string $default='' ) : string
{
    $values = explode( ',', $values );
    $default = explode( ',', $default );
    $name = $name . '[]';
    $html = "<select name='$name' size='6' multiple style='width:100px'>";
    foreach( $values as $i => $value )
    {
        $id = "$value-$i";
        $selected = '';
        if( in_array($value, $default) )
            $selected = 'selected';
        $html .= "<option value='$value' $selected>$value</option>";
    }
    $html .= "</multiple>";
    return $html;
}

/**
    * @brief Generate a email statement form given template id. Templte must
    * exits in a database table.
    *
    * Replaces @KEY@ by KEY value in options.
    *
    * @param $templateName
    * @param $options
    *
    * @return
 */
function emailFromTemplate( string $templateName, array $options ) : array
{
    $templ = getEmailTemplateById( $templateName );
    $desc = $templ['description'];

    if( ! $desc )
    {
        echo alertUser( "No template found with id: aws_template. I won't
                        be able to generate email"
                      );
        return null;
    }

    foreach( $options as $key => $value )
        $desc = str_replace( "@$key@", $value, $desc );

    $templ[ 'email_body' ] = $desc;
    return $templ;
}


function googleCaledarURL( )
{

    $url = "https://calendar.google.com/calendar/embed?";
    $url .= "src=d2jud2r7bsj0i820k0f6j702qo%40group.calendar.google.com";
    $url .= "&ctz=Asia/Calcutta";
    return $url;
}

function inlineImage( $picpath, $class='inline_image', $width='auto', $height='auto', $altText="" )
{
    if( ! file_exists( $picpath ) )
        $picpath = nullPicPath( );

    $html = '<img class="'.$class . '" width="' . $width
            . '" height="' . $height . '" src="'
            . dataURI( $picpath, 'image/jpg' ) . '" 
            alt="' . $altText . '"
            >';
    return $html;
}

/**
    * @brief Add Google image to talk inline.
    *
    * @param $picpath
    *
    * @return 
 */
function inlineGoogleCalImage(string $picpath) : string
{
    return inlineImage($picpath, 'inline_image', 'auto', 'auto', "Add to Google Calendar");
}

function showImage( $picpath, $height = 'auto', $width = 'auto' )
{
    return inlineImage( $picpath, $class = 'login_picture', $height, $width );
}



/**
    * @brief Display any image.
    *
    * @param $picpath
    * @param $height
    * @param $width
    *
    * @return
 */
function displayImage( $picpath, $height = 'auto', $width = 'auto', $usemap = '' )
{
    if( ! file_exists( $picpath ) )
        $picpath = nullPicPath( );

    $html = '<img width="' . $width . '" height="' . $height . '" src="'
            . dataURI( $picpath, 'image/jpg' ) . '" usemap="#' . $usemap . '"  >';
    return $html;
}


/**
    * @brief Return an empty image.
    *
    * @return
 */
function nullPicPath( $default = 'null' )
{
    $conf = getConf( );
    $datadir = $conf[ 'data' ]['user_imagedir'];
    return $datadir . "/$default.jpg";
}

function inlineImageOfSpeakerId( $id, $height = 'auto', $width = 'auto')
{
    $picPath = getSpeakerPicturePathById( $id );
    return showImage( $picPath, $height, $width );
}

function inlineImageOfSpeaker( $speaker, $height = 'auto', $width = 'auto')
{
    $picPath = getSpeakerPicturePath( $speaker );
    return showImage( $picPath, $height, $width );
}

/**
    * @brief Convert slots to a HTML table.
    *
    * @return
 */
function slotTable(string $width = "20px") : string
{
    $days = array( 'Mon', 'Tue', 'Wed', 'Thu', 'Fri' );
    $html = '<table class="table table-sm table-borderless">';

    // Generate columns. Each one is 15 min long. Starting from 9am to 6:00pm
    $maxNumCols = intval( ( 18 - 9 ) * 4 );

    // Check which slot is here.
    $slots = getTableEntries('slots' );
    // each day is row.
    foreach( $days as $day )
    {
        $html .= "<tr>";
        $html .= "<tr> <td>$day</td> ";

        $counter = 0;
        $i = 0;
        while( $i < $maxNumCols )
        {
            $slotTime = dbTime( strtotime( '9:00 am' . ' +' . ( $i * 15 ) . ' minute' ) );
            $slot = getSlotAtThisTime( $day, $slotTime, $slots );

            if( $slot )
            {
                $duration = strtotime( $slot[ 'end_time' ] )  -
                            strtotime( $slot[ 'start_time' ] );

                $text = humanReadableTime( $slot[ 'start_time' ] ) . '-' .
                        humanReadableTime(  $slot[ 'end_time' ] );

                $bgColor = 'lightblue';
                $id = $slot[ 'id' ];
                $gid = $slot[ 'groupid' ];

                // Invalid slot.
                if( ! is_numeric( $id[0] ) )
                    $bgColor = 'red';

                $ncols = intval( $duration / 60 / 15 ); // Each column is 15 minutes.

                $html .= "<td colspan=\"$ncols\">
                    <button class='btn-info btn-sm' onClick=\"showRunningCourse(this)\" 
                        value=\"$id\"><u>SLOT $id</u><br />$text</button> </td>";
                $i += $ncols;
            }
            else
            {
                $i += 1;
                $html .= ' <td> </td> ';
            }
        }
        $html .= "</tr>";
    }

    $html .= '</table>';

    return $html;

}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Make a course table.
    *
    * @Param $editable
    * @Param $with_form
    * @Param $class
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function coursesTable( $editable = false, $with_form = true, $class="" )
{
    $courses = getTableEntries( 'courses_metadata', 'name,id' );
    $html = "<table id='all_courses' class='info sortable exportable $class'>";
    $html .= '<th>ID</th> <th>Credit</th> <th>Name</th> <th> Description </th>
        <th> Instructors </th> <th></th> ';
    foreach( $courses as $c )
    {
        $instructors = array( );
        foreach( $c as $k => $v )
        if( $v && strpos( $k, 'instructor_') !== false )
            $instructors = array_merge($instructors, explode( ',', $v ) );

        $html .= "<tr>";
        $html .= "<td>" . $c[ 'id' ] . "</td>";
        $html .= "<td>" . $c[ 'credits' ] . "</td>";
        $html .= "<td>" . $c[ 'name' ] . "</td>";
        $html .= "<td><div class=\"cell_content\">" . $c[ 'description' ] . "</div></td>";
        $html .= "<td><div class=\"cell_content\">" . implode('<br>', $instructors)
            . "</div></td>";

        if( $editable )
        {
            if( $with_form )
                $html .= '<form action="#editcourse" method="post" accept-charset="utf-8">';

            $html .= '<td> <button name="response" class="btn btn-secondary btn-small" 
                value="Edit">Edit</button>';
            $html .= '<input type="hidden" name="id" value="' . $c['id'] . '">';
            $html .= '</td>';

            if( $with_form )
                $html .= '</form>';
        }

        $html .= "</tr>";

    }
    $html .= '</table>';
    return $html;
}

/**
    * @brief Create a select list with default value selected.
    *
    * @param $default
    *
    * @return
 */
function gradeSelect( $name, $default = 'X' )
{
    if( strlen( $default ) == 0 )
        $default = 'X';

    $select = arrayToSelectList(
            $name
            , array( 'A+', 'A', 'B+', 'B', 'C+', 'C', 'F', 'X' )
            , array( ), false, $default, false
        );
    return $select;
}

function talkSummaryLine( $talk )
{

    $title = __ucwords__( $talk[ 'title' ] );
    $msg = __ucwords__ ( $talk['class'] ) . ' by ';
    $msg .= $talk[ 'speaker' ];
    $msg .= " : '$title'";
    return $msg;
}

function preferenceToHtml( $request )
{

    $html = '<div class="aws_preference"><br />User Preferences: <br />';
    $prefs = array( );
    if( $request[ 'first_preference' ] )
        $prefs[ ] =  humanReadableDate( $request[ 'first_preference' ] );
    if( $request[ 'second_preference' ] )
        $prefs[ ] = humanReadableDate( $request[ 'second_preference' ] );

    $html .= implode( ' <br /> ', $prefs );
    $html .= '<br />' . $request['status'];
    $html .= '</div>';
    return $html;
}

/**
    * @brief Reutrn colored text.
    *
    * @param $txt
    * @param $color
    *
    * @return
 */
function colored( $txt, $color = 'black' )
{
    return "<font color=\"$color\">$txt</font>";
}


function getCourseShortInfoText( $course )
{
    if( is_string( $course ) )
        $course = getCourseById( $course );

    $text = $course[ 'name' ];
    return $text;
}

function getCourseInstructorsList($c, string $year='', string $sem=''):array
{
    if(is_string($c))
        $c =  getCourseById($c);

    $instructors = array( );
    foreach( $c as $k => $v )
    {
        if( contains( 'instructor_', $k ) )
            foreach( explode( ",", $v) as $i )
            {
                if( $i )
                {
                    $name = arrayToName( findAnyoneWithEmail( $i ) );
                    $instructors[] = [$v, $name];
                }
            }
    }
    return $instructors;
}

function getCourseInstructors( string $c, string $year = '', string $sem='' )
{
    $res = [];
    $list = getCourseInstructorsList($c, $year, $sem);
    foreach($list as $inst)
    {
        $v = $inst[0];
        $name = $inst[1];
        $res[] = "<a id=\"emaillink\" href=\"mailto:$v\" target=\"_top\"> $name </a>";
    }
    return ['html'=>implode('<br />', $res), 'data' => $list];
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  For a give course id, get the email and name of instructors in
    * an map.
    *
    * @Param $cid course id 
    * @Param $year year (not used)
    * @Param $semester semester (not use)
    *
    * @Returns  A map containing instructor email (as key) and html info (as
    * value).
 */
/* ----------------------------------------------------------------------------*/
function getCourseInstructorsEmails( string $cid, string $year = '', string $semester = '' ) : array 
{
    $c =  getCourseById( $cid );
    $instructors = array( );
    foreach( $c as $k => $v )
    {
        if( contains( 'instructor_', $k ) )
            foreach( explode( ",", $v) as $i )
            {
                if( $i )
                {
                    $name = arrayToName( findAnyoneWithEmail( $i ) );
                    $instructors[$i] = "<a id=\"emaillink\" href=\"mailto:$v\" target=\"_top\">
                        $name </a>";
                }
            }
    }
    return $instructors;
}

function smallCaps( $text )
{
    return "<font style=\"font-variant:small-caps\"> $text </font>";
}


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Course to HTML row.
    *
    * @Param $c
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function courseToHTMLRow( array $c, string $slot, string $sem, string $year
    , array &$enrollments ) : string
{
    if( ! __get__( $c, 'id', '' ) )
    {
        flashMessage( 'Empty of invalid course.' );
        return 'Empty of invalid course: ' . json_encode( $c );
    }

    $cid = $c['course_id'];

    $whereExpr = "status='VALID' AND year='$year' AND semester='$sem' AND course_id='$cid' AND type!='DROPPED'";

    $registrations = getTableEntries(
        'course_registration', 'student_id', $whereExpr
    );

    $enrollments[ $cid ] = $registrations;

    $cinfo = $c[ 'description' ];
    $cname = $c[ 'name' ];
    $cr = $c[ 'credits' ];

    $note = '';
    if( $c[ 'note' ] )
        $note = colored( '* ' . $c[ 'note' ], 'brown' );

    if( intval($c['max_registration']) > 0 )
        $note .= p(colored( 'Maximum registrations allowed: ' . $c[ 'max_registration' ], 'brown' ));

    if( $c['is_audit_allowed']=='NO')
        $note .= p(colored( 'No <tt>AUDIT</tt>.', 'brown'));

    $cinfo = "<p><strong>Credits: $cr </strong></p>" . $cinfo;
    $cinfo = base64_encode( $cinfo );

    $schedule = humanReadableDate( $c[ 'start_date' ] ) . '<br /> to <br />'
        . humanReadableDate( $c[ 'end_date' ] );

    $slotInfo = getCourseSlotTiles( $c, $slot );
    $instructors = getCourseInstructors( $cid );
    $venue = $c[ 'venue' ];
    $nReg = count( $registrations );

    if( $note )
        $note = "<blink><i class='fa fa-bell-o faa-ring faa-slow animated fa-2x'></blink></i> $note";

    $row = '<td><font style="font-variant:small-caps; font-size:large">' . $cname . '</font>
            <button id="$cid" onclick="showCourseInfo(this)"
                class="show_as_link" value="' . $cinfo . '"
                title="' . $cname . '" > <i class="fa fa-2x fa-info-circle"></i>
            </button>
        <br />' . $instructors['html'] . " <br /> $note " . '</td>
        <td>' .  $schedule . '</td>
        <td>' . "$slotInfo <br /><strong> $venue </strong> </td>";

    // If url is found, put it in page.
    if( __get__( $c, 'url', '' ) )
    {
        $text = '';
        $url = $c['url'];
        $row .= '<td><a target="_blank" href="' . $c['url'] . '">' 
            . inlineImage( FCPATH .'./data/Moodle-Icon-1024-corners.png','inline-image', 30) 
            .'</i></a></td>';
    }
    else
        $row .= '<td></td>';

    return $row;
}

function getReferer( )
{
    return $_SERVER['HTTP_REFERER'];
}

function getRefShort( $ifsamegoto = 'user/home' )
{
    if( $_SERVER['HTTP_REFERER'] == $_SERVER['PHP_SELF'])
        return site_url( $ifsamegoto ) ;

    $shortUrl = str_replace( site_url(), '', $_SERVER['HTTP_REFERER'] );

    // Remove leading / if any
    $shortUrl = ltrim( $shortUrl, '/' );
    return $shortUrl;
}

function piSpecializationHTML( $pi, $specialization, $prefix = 'PI/HOST:' )
{
    $pi = "<tt>$pi</tt>";
    return "$specialization <br />$prefix $pi";
}

function goBackToPageLink($url, $title="Go back") : string
{
    $html = '<div class="btn btn-link">';
    $html .= goBackToPageLinkInline( $url, $title );
    $html .= '</div>';
    return $html;
}

function goBackToPageLinkInline(string $url, string $title="Go back" ) : string
{
    $html = '<a  class="fa fa-step-backward fa-2x my-2"
        href="' . site_url($url) . '"> '.$title .'</a>';
    return $html;
}


function goBackInline( )
{
    $url = __get__( $_SERVER, 'HTTP_REFERER', site_url('user/home'));
    $html = '<a href="' . $url . '"> 
            <i class="fa fa-step-backward fa-2x"></i>
            <font color="blue" size="5">Go back</font>
            </a>';
    return $html;
}


/**
    * @brief Go back to referer page.
    *
    * @param $defaultPage
    *
    * @return
 */
function goBack( $default = '', $delay = 0 )
{
    if( ! $default )
        $url = __get__( $_SERVER, 'HTTP_REFERER', site_url('user/home'));
    else
        $url = $default;
    goToPage( $url, $delay );
}

function presentationToHTML( $presentation )
{
    $html = __get__( $presentation, 'description', '' );

    if( ! trim($html) )
        $html .= '<p>Not disclosed yet</p>';

    // Add URL and PRESENTATION URL in table.
    $html .= ' <br /> ';
    $html .= '<table class="info">';
    $html .= '<tr><td>URL(s)</td><td>'
                .  linkify( $presentation['url'] ) . '</td></tr>';
    $html .= '<tr><td>Presentation URL</td><td>'
                .  linkify( $presentation['presentation_url'] ) . '</td></tr>';
    $html .= '</table>';

    $jcId = $presentation[ 'jc_id'];
    $jcInfo = getJCInfo( $jcId );

    $presentation['venue' ] = $jcInfo['venue'];
    $presentation['start_time' ] = $jcInfo['time'];
    $presentation['end_time' ] = dbTime( strtotime($jcInfo['time'] ) + 3600 );
    $presentation[ 'title' ] = "$jcId | '" . $presentation[ 'title' ] . "' by " .
        arrayToName( getLoginInfo( $presentation[ 'presenter' ] ) );

    $html .= '<br />';
    $html .= "<div class='addtogooglecal'> " . addToGoogleCalLink( $presentation ) . "</div>";

    return $html;
}

function getPresentationTitle( $presentation )
{
    $title = __get__( $presentation, 'title', '' );
    if( ! $title )
        $title = 'Not disclosed yet';

    return $title;
}

function getJCPresenters( array $jc ) : string
{
    $presenter = getLoginInfo( $jc[ 'presenter' ], true, true);
    $pName = arrayToName( $presenter );
    $others = array_map( function ($x) { return arrayToName(getLoginInfo($x));}
                        , splitAtCommonDelimeters($jc['other_presenters'])
                );
    $otherNames = implode( ' and ', $others );
    // Only when there are some more presenters.
    if( strlen(trim($jc['other_presenters'])) > 0)
        $pName .= " and $otherNames";
    return $pName;
}

function jcToHTML( $jc, $sticker = false )
{
    $jcInfo = getJCInfo( $jc[ 'jc_id' ] );
    $whereWhen = whereWhenTable( $jc['venue'], $jc['date'], $jc['time'] );
    $pName = getJCPresenters( $jc );

    if( ! $sticker )
    {
        $html = '<h3>' . $jc['jc_id'] . ' | ' . $jc['title'] . '</h3>';
        $html .= "<strong> $pName </strong>";
        $html .= $whereWhen;
        $html .= presentationToHTML( $jc );
    }
    else
    {
        $html = $jc['jc_id'] . ' | ' . $jc['title'] ;
        $html .= ' <br />';
        $html .= " <br /> <strong> $pName </strong>";
        $html .= $whereWhen;
    }
    return $html;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Remove sensitive data from the row.
    *
    * @Param $arr
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function removeSensitiveInfomation( $arr )
{
    $id = $arr[ 'id' ];
    $res = array( 'id' => $id );

    if( __substr__( 'password', $id, true ) )
        $res[ 'value' ] = 'Not Displayed';
    else if( __substr__( 'secret', $id, true ) )
        $res[ 'value' ] = 'Not Displayed';
    else
        $res[ 'value' ] = $arr['value'];

    return $res;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Remove sensitive data from the table.
    *
    * @Param $configs
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function showConfigTableHTML( $configs = null )
{
    if( ! $configs )
        $configs = getTableEntries( 'config' );

    $html = '<table class="info">';
    foreach( $configs as $config )
    {
        $config = removeSensitiveInfomation( $config );
        $html .= arrayToRowHTML( $config, 'info', '', false );
    }
    $html .= '</table>';
    return $html;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Reutrn a clickable URL for a given query id.
    *
    * @Param $qid
    * @Param $msg
    *
    * @Returns
 */
/* ----------------------------------------------------------------------------*/
function queryToClickableURL( $qid, $msg = 'Click here' )
{
    $url = appURL( ) . '/execute.php?id=' . $qid;
    return '<p>' . $msg . ': <a
        style="border:1px solid;border-radius:5px;background-color:#cc0000;padding:10px 10px 10px 10px;"
        href="' . $url . '" target="_blank">' . $url . '</a> </p>';
}

function queryHashToClickableURL($hash, $msg = "Click here")
{
    $url = site_url() . '/confirm/' . $hash;
    return '<p>' . $msg . ': <a
        style="border:1px solid;border-radius:5px;background-color:#cc0000;padding:10px 10px 10px 10px;"
        href="' . $url . '" target="_blank">' . $url . '</a> </p>';
}

function addClickabelURLToMail( $html, $clickable )
{
    $html .= ' <br />';
    $html .= $clickable;
    $html .= ' <br />';
    return $html;
}

function awsAssignmentForm( $date = null, $small = false )
{
    $form = '<form method="post" action="'.site_url("adminacad/assignaws").'">';

    $class = '';
    if( ! $small )
        $class = 'standout';

    $form .= "<table class=\"$class\" >";
    if( ! $date )
    {
        $form .= '<tr><td> 
            <input class="datepicker"  name="date" value="" placeholder="Select monday" >
            </td>';
    }
    else
    {
        $form .= '<input type="hidden"  name="date" value="' . $date . '" >';
        $form .= '<tr>';
    }

    $form .= '<td> <input class="autocomplete_speaker" name="speaker" 
        placeholder="Login id" /></td>';

    if( $small )
        $form .= '</tr><tr><td><button name="response" value="Assign">Assign</button> </td></tr>';
    else
    {
        $form .= '<td> <button name="response" value="Assign">Assign</button> </td>';
        $form .= '</tr>';
    }
    $form .= '</table></form>';
    return $form;
}

function getEnrollmentTableAndEmails( $cid, $enrollments, $table_class='info' )
{
    $courseName = getCourseName( $cid );
    $rows = [ ];

    $allEmails = array( );

    foreach( __get__($enrollments, $cid, array()) as $r )
    {
        $studentId = $r[ 'student_id' ];
        if( ! $studentId )
            continue;

        $info = getUserInfo( explode('@', $studentId)[0], true);
        if( ! __get__($info, 'email', '') )
        {
            $info['email'] = 'Email not found.'
                . '<br/>'
                . 'Most likely, your LDAP profile is incomplete. Please contact IT dept.'
                ;
        }

        if( ! __get__($info, 'first_name', '') )
            $info['first_name'] = $r['student_id'];

        if( $info )
        {
            $row = '';
            $row .= '<td>' . loginToText( $info, false) . '</td>';
            $row.= '<td><tt>' . mailto( $info[ 'email' ] ) . '</tt></td>';
            $row .= '<td>' . $r[ 'type' ] . "</td>";
            $row .= '<td>' . $r[ 'registered_on' ] . "</td>";
            $rows[ $info[ 'first_name'] ] = $row;
            $allEmails[ ] = $info[ 'email'];
        }
        else
        {
            $row = '';
            $row .= "<td> $studentId 
                <br />
                <small>Error fetching details from LDAP. Please contact IT.</small>
                </td>";
            $row.= "<td><tt>$studentId</tt></td>";
            $row .= '<td>' . $r[ 'type' ] . "</td>";
            $row .= '<td>' . $r[ 'registered_on' ] . "</td>";
            $rows[ $studentId ] = $row;
            // $allEmails[ ] = $info[ 'email'];
        }
    }

    ksort( $rows );
    $count = 0;

    // Construct enrollment table.
    $table = '<table id="show_enrollmenents_' . $cid . '" class="' . $table_class . ' sortable">';
    $table .= '<tr> <th></th> <th>Name</th> <th>Email</th> <th>Type</th> <th>Registation Time</th>  </tr>';
    foreach( $rows as $fname => $row )
    {
        $count ++;
        $table .= "<tr><td>$count</td>" . $row . '</tr>';
    }
    $table .= '</table>';
    return array( 'html_table' => $table, 'enrolled_emails' => $allEmails );
}

function selectYearSemesterForm($defaultYear='', $defaultSem='', $endpoint="info/courses")
{
    global $symbUpdate;
    $years = range(intval(getCurrentYear( )) + 1, 2010);
    $yearSelect = arrayToSelectList('year', $years, array(), false, $defaultYear);
    $semSelect = arrayToSelectList('semester', array( 'SPRING', 'AUTUMN' ), array(), false, $defaultSem);
    $form = '<form action="'.site_url("$endpoint").'" method="get">'; 
    $form .= "<table><tr> <td> $yearSelect </td><td> $semSelect </td>";
    $form .= "<td><button class='show_as_link'> Show Courses </button></td>";
    $form .= "</tr></table>";
    $form .= '</form>';
    return $form;
}

function showEnrollmenTable( $enrolls, $tdintr=4)
{
    $table = '<table class="enrollments">';
    $table .= '<tr>';
    foreach( $enrolls as $i => $e )
    {
        $index = $i + 1;
        $student = $e[ 'student_id'];
        $sname = arrayToName( getLoginInfo( $student ) );
        $grade = __get__($e, 'grade', 'NA');
        $type = $e[ 'type'];
        $table .= "<td><tt>$index.</tt> $student ($sname)<br /> $type (Grade: " .colored($grade,'blue').")</td>";
        if( ($index) % $tdintr == 0 )
            $table .= '</tr><tr>';

    }
    $table .= '</tr>';
    $table .= '</table>';
    return $table;
}

function questionBankBySubcategoryToTable( $subcategory, $questions, $controller )
{
    global $symbDelete;
    // $table = '<table class="info">';
    // $table = '<tr>';
    $table = '';
    $table .= "<tr><td colspan=3><strong> $subcategory </strong></td></tr>";
    foreach( $questions as $j => $q )
    {
        $qid = $q['id'];

        $table .= '<tr>';
        // $table .= '<td>' . (1+$j) .'</td>';
        $table .= arrayToRowHTML( $q, 'info', 'id,category,subcategory,status,last_modified_on', true, false );

        // Add forms to delete or modify the question.
        $table .= '<td>';
        $table .= '<form action="'.site_url("$controller/deletequestion/$qid"). '" method="post">';
        $table .= '<button type="submit" title="Delete this question">' . $symbDelete . '</button>';
        $table .= '</form>';
        $table .= '</td>';

        $table .= '</tr>';
    }
    // $table .= '</table>';
    return $table;

}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Convert question bank array to html table.
    *
    * @Param $qmap. Dictionary or associated array where subcategory is key and  
    *       Array to question as value.
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function questionBankByCategoryToTable( $qmap, $controller )
{

    $html = '';
    foreach( $qmap as $subcategory => $questions )
        $html .= questionBankBySubcategoryToTable( $subcategory, $questions, $controller );
    return $html;

}

function courseFeedbackQuestions($category, $questions, $controller)
{
    $html = questionBankBySubcategoryToTable( $category, $questions, $controller );
    return $html;

}

function csvToRadio(string $csv, string $name, string $default='', string $disabled='') : string
{
    // NOTE: Don't use character '.' in the field name becase '.' are replaced by '_'
    // Therefore we replace '.' with +dot+.
    $name = str_replace( '.', '+dot+', $name );
    $csvarray = explode( ',', $csv );
    $html = '';
    $options = array();
    foreach( $csvarray as $i => $opt )
    {
        $extra = '';
        if( $default == $opt )
            $extra .= ' checked';

        $idKey = "$name-id$i";
        // NOTE: Field 'for' of label should match the field 'id' of input.
        $row = "<input type='radio' value='$opt' name='$name' '$disabled' id='$idKey' $extra /> ";
        $row .= "<label for='$idKey' class='poll'>$opt</label>";
        $options[] = $row;
    }

    $html .= implode(' ', $options);
    return $html;
}

function instructorSpecificQuestion( string $year, string $semester
    , string $cid, array $q, array $instructors, int $nochangeafater=1
) : string
{
    $qid = $q['id'];

    $row = '<tr>';
    $row .= "<td><i class='fa fa-2x fa-question-circle-o'></i> " . html2text($q['question']);
    foreach( $instructors as $email => $instructor )
    {
        $responses = getInstructorSpecificFeedback( $year, $semester, $cid, $email );

        // Show instrcutor name at the top of question only if the
        // question was instructor specific. This is a bit wiered way
        // for designing this interface.
        $oldres = '';
        $defaultVal = '';
        $extra = '';

        if( __get__($responses, $qid, null) )
        {
            $oldres = $responses[$qid];
            $defaultVal = $oldres['response'];
            if( (strtotime('now') - strtotime($oldres['timestamp'])) > $nochangeafater * 24 *3600 )
                $extra = 'disabled';
        }

        $choices = trim($q['choices']);
        $name = "qid=".$q['id']."&instructor=$email";
        if( ! $choices )
            $options = '<textarea cols=50 rows=5 name="$name" ' . " $extra " . 
                '>'.$defaultVal.'</textarea>';
        else
            $options = csvToRadio( $choices, $name, $defaultVal, $extra );

        $row .= '<div class="radio">'. $instructor . '<br/>'. $options .'</div>';
    }
    $row .= '<br/></td></tr>';
    return $row;
}

function courseSpecificQuestion( string $year, string $semester, string $course_id
    , array $q, int $nochangeafater )
{
    $row = '<tr>';
    $row .= '<td> <i class="fa fa-question-circle-o fa-2x"></i> ' . html2text($q['question']);
    $qid = $q['id'];
    // $row .= '<td style="width:100%">';
    $oldres = '';
    $defaultVal = '';
    $extra = '';
    $responses = getCourseSpecificFeedback( $year, $semester, $course_id );
    if( __get__($responses, $qid, [] ) )
    {
        $oldres = $responses[$qid];
        if( $oldres )
            $defaultVal = $oldres['response'];
        if( (strtotime('now') - strtotime($oldres['timestamp'])) > $nochangeafater * 24 *3600 )
            $extra = 'disabled';
    }

    $choices = trim($q['choices']);
    if( ! $choices )
        $options = '<textarea cols=50 rows=5 name="qid='.$qid .'"' . " $extra " .'>'.$defaultVal.'</textarea>';
    else
        $options = csvToRadio($choices, "qid=$qid", $defaultVal, $extra);

    $row .= '<div class="radio">' . $options .'</div>';
    return $row;
}


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Convert questions to a poll form. 
    *
    * @Param $questions
    * @Param $responses
    * @Param $nochangeafater Don't allow changing response after this many days.
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function courseFeedbackForm( string $year, string $semester, string $course_id
    , array $questions, array $instructors, $nochangeafater=1
)
{
    $html = '';
    foreach( $questions as $cat => $qs )
    {
        $html .= '<div>';
        $html .= "<h2>$cat</h2>";

        $table = '<table class="poll">';
        foreach( $qs as $q )
        {
            // question is instructor specific or course specific.
            $type = $q['type'];
            $foreachInstructor = false;
            if( $type == 'INSTRUCTOR SPECIFIC')
                $table .= instructorSpecificQuestion($year, $semester, $course_id, $q
                                , $instructors, $nochangeafater
                            );
            else
                $table .= courseSpecificQuestion($year, $semester, $course_id
                                , $q, $nochangeafater
                            );
        }
        $table .= '</table>';
        $html .= $table;
        $html .= '</div>';
    }
    return $html;
}


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Create upload-link. A except sheet can be uploaded to database
    * table.
    *
    * @Param $tablename
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function uploadToDbTableLink( string $tablename, string $unique_key, string $arg = '' ) : string
{
    $to = "user/upload_to_db/$tablename/$unique_key";
    if($arg)
        $to .= "/$arg";

    $html = '<form action="'.site_url( "$to") .'" method="post" enctype="multipart/form-data">';
    $html .= '<table class="upload"><tr>';
    $html .= "<caption>First download data as spreadsheet, edit it and upload it. You can also 
        use the form given below to add/edit one entry at a time.</caption>";
    $html .= '<td>';
    $html .= '<input type="file" name="spreadsheet" value="" accept=".xlsx, .xls, .csv, .odt"/>';
    $html .= '</td><td>';
    $html .= "<button type='submit'>Upload to $tablename</button>";
    $html .= '</td></tr>';
    $html .= '</table>';
    $html .= '</form>';
    return $html;
}

function editHTML( $buttonVal )
{
    $html = "<h2>$buttonVal course</h3>";

    $html .= '<form method="post" action="'.site_url('adminacad/all_courses_action').'">';
    $html .= dbTableToHTMLTable( 'courses_metadata', $course
            , 'id,credits:required,name:required,description,'
                . 'instructor_1:required,instructor_2,instructor_3'
                . ',instructor_4,instructor_5,instructor_6,instructor_extras'
                . ',comment'
            , $buttonVal
            );


    $html .= '<button title="Delete this entry" type="submit" onclick="AreYouSure(this)"
        name="response" value="Delete">' . $symbDelete .
        '</button>';
    $html .= '<button id="addMoreInstructors">Add more instructors</button>';
    $html .= '</form>';
    return $html;
}

function getAWSSupervisorsHTML( array $aws ) : string 
{
    $supervisors = [];
    for( $i = 1; $i <= 2; $i++ )
    {
        $su = __get__( $aws, "supervisor_$i", "" );
        if($su)
            $supervisors[] = loginToHTML($su);
    }
    return implode( ", ", $supervisors);
}

function getLoginHTML($login)
{
    $info = getLoginInfo($login);
    if( ! $info)
        return $login . " (<tt>LDAP info unavailable.</tt>)";
    return trim(arrayToName($info, true));
}

function getAWSTcmHTML( array $aws ) : string 
{
    $tcms = [];
    for( $i = 1; $i <= 4; $i++ )
    {
        $su = __get__( $aws, "tcm_member_$i", "" );
        if($su)
            $tcms[] = loginToHTML($su);
    }
    return implode( ", ", $tcms);
}

function eventToICALString(array $event) :  string
{
    $vCalendar = new \Eluceo\iCal\Component\Calendar('https://ncbs.res.in/hippo');
    $vEvent = new \Eluceo\iCal\Component\Event();
    $startDateTime = new DateTime($event['date'] . ' ' . $event['start_time']);
    $endDateTime = new DateTime($event['date'] . ' ' . $event['end_time']);

    $vEvent->setDtStart($startDateTime);
    $vEvent->setDtEnd($endDateTime);
    $vEvent->setSummary($event['title']);

    if(__get__($event, 'vc_url', ''))
        $vEvent->setUrl($event['vc_url']);

    $vCalendar->addComponent($vEvent);
    return $vCalendar->render();
}

function eventToICALFile(array $event): string
{
    $icalStr = eventToICALString($event);
    $date = $event['date'];
    $icalFile = sys_get_temp_dir() . '/EVENT_'.$date.'.ics';
    file_put_contents($icalFile, $icalStr);
    return $icalFile;
}

?>
