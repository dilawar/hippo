<?php

/* We use this interface for booking venue. We may also come here from manage
 * talks page. If a user creates a talk and we come here for a booking; we use
 * the external_id _GET variable.
 */

require_once BASEPATH . 'autoload.php';

$ref = 'user';
if(isset($controller))
    $ref = $controller;

echo userHTML( );

$roundedTimeNow = round( time( ) / (15 * 60) ) * (15 * 60 );

$defaults = array(
    "date" => dbDate( strtotime( 'today' ) )
    , "start_time" => date( 'H:i', $roundedTimeNow )
    , "end_time" => date( 'H:i', $roundedTimeNow + 3600 )
    , "strength" => 10
    , "has_skype" => "NO"
    , "has_projector" => "NO"
    , "openair" => "NO"
    , "title" => ''
    );


/*
 * If external_id is set by controller then we are here to book a  talk.
 */
if( isset( $external_id ) )
{
    $expr = explode( ".", $external_id );
    $tableName = $expr[ 0 ];
    $id = $expr[ 1 ];
    $entry = getTableEntry( $tableName, 'id', array( "id" => $id ) );

    echo "<h1>Scheduling following talk </h1>";

    echo '<div style="font-size:small">';
    echo arrayToVerticalTableHTML( $entry, 'events', '', 'id,status,date,time,venue' );
    echo '</div>';

    $defaults[ 'title' ] = $entry[ 'title' ];
    $defaults[ 'class' ] = $entry[ 'class' ];
    $defaults[ 'is_public_event' ] = 'YES';
    $defaults[ 'speaker' ] = $entry[ 'speaker' ];
    $defaults[ 'class' ] = $entry[ 'class' ];

    // Update the title of booking request.
    $defaults[ 'title' ] = __ucwords__( $defaults[ 'class' ] ) . ' by '
            . $defaults[ 'speaker' ] . " on '" . $defaults[ 'title' ] . "'";

    // Description is just the title of the talk. Keep it short.
    $defaults[ 'description' ] = $defaults[ 'title' ];
}
else
{
    echo alertUser( '
        If your event requires email to be send out to academic community (e.g. 
        <strong><tt>TALK</tt>s, <tt>SEMINAR</tt>, <tt>THESIS SEMINAR</tt>s, <tt>LECTURE</tt>s</strong> ),
        <a href="' . site_url( "$ref/register_talk") . '"> 
            <i class="fa fa-spinner fa-spin"></i> click here</a>.'
            , false
        );
}

// Since we come back to this page again and again, we reuse the previous values
// provided by user.
foreach( $defaults as $key => $val )
    if( array_key_exists( $key, $_POST ) )
        $defaults[ $key ] = $_POST[ $key ];

$skypeYes = ''; $skypeNo = '';
if( $defaults[ 'has_skype' ] == 'YES' )
    $skypeYes = 'checked';
else
    $skypeNo = 'checked';

$projectorYes = ''; $projectorNo = '';
if( $defaults[ 'has_projector' ] == 'YES' )
    $projectorYes = 'checked';
else
    $projectorNo = 'checked';

$openAirNo = ''; $openAirYes = '';
if( $defaults[ 'openair' ] == 'YES' )
    $openAirYes = 'checked';
else
    $openAirNo = 'checked';


/* PAGE */
echo '<br />';
echo '<table style="min-width:300px;max-width:500px",border="0">';
echo '<form action="" method="post" >';
echo '
    <tr>
        <td>Date</td>
        <td><input  class="datepicker" name="date"
            value="' . $defaults[ 'date' ] . '" /> </td>
    </tr>
    <tr>
        <td>Start time </td>
        <td><input  class="timepicker" name="start_time"
            value="' . $defaults[ 'start_time'] . '" /> </td>
    </tr>
    <tr>
        <td>End time </td>
        <td><input  class="timepicker" name="end_time"
            value="' . $defaults[ 'end_time'] . '" /> </td>
    </tr>
    <tr>
        <td>Mininum seatings required? </td>
        <td><input type="text" name="strength"
            value="' . $defaults[ 'strength' ] . '" /> </td>
    </tr>
    <tr>
        <td>Do you need video-conference facility?</td>
        <td>
            <input type="radio" name="has_skype" value="NO" ' . $skypeNo . ' /> No
            <input type="radio" name="has_skype" value="YES" ' .$skypeYes . ' /> Yes
        </td>
    </tr>
    <tr>
        <td>Do you need a projector?</td>
        <td>
        <input type="radio" name="has_projector"
            value="NO" ' . $projectorNo . ' /> No
        <input type="radio" name="has_projector"
                value="YES" ' .$projectorYes . ' /> Yes
        </td>
    </tr>
    <tr>
        <td>Prefer open-air location?</td>
        <td>
            <input type="radio" name="openair" value="NO"' . $openAirNo . ' $/> No
            <input type="radio" name="openair" value="YES"' . $openAirYes . ' /> Yes
        </td>
    </tr>
    <tr>
        <td></td>
        <td style="text-align:right">
        <button title="Scan for venues"
            style="font-size:large" name="Response" value="scan">
                Show <br />available venues</button>
        </td>
    </tr>
    ';

echo '</form>';
echo '</table>';

$date = __get__( $_POST, 'date', dbDate(strtotime( 'today' )) );

// // Force this only if user is not admin.
// if( ! anyOfTheseRoles( array( 'BOOKMYVENUE_ADMIN', 'AWS_ADMIN' ) ) )
// {
//     if( strtotime( $date ) >= (strtotime( 'today' ) + 60 * 24 * 3600 ) )
//     {
//         echo alertUser( "You can not book more than 60 days in advance" );
//         exit;
//     }
// }

// Get list of public events on user request day and show them to him. So he can
// decides if some other timeslot should be used.
$publicEvents = getPublicEventsOnThisDay( $date );

echo "<div style=\"font-size:small;border:1px solid\">";
if( count( $publicEvents ) > 0 )
{
    echo printWarning( 
        "There are some public events on selected date. <br />
        You are advised not to book any academic event which might clash with any of 
        the following events.", false
    );

    $tohide = 'gid,eid,description,status,is_public_event,external_id'
              . ',calendar_id,calendar_event_id,last_modified_on,url' ;

    echo '<table class="show_events info">';
    echo arrayHeaderRow( $publicEvents[0], 'info', $tohide );
    foreach( $publicEvents as $event )
        echo arrayToRowHTML( $event, 'info', $tohide );
    echo '</table>';
}
echo "</div>";

/******************************************************************************
 * Get the list of labmeets and JC
 * ***************************************************************************/
$jcAndMeets = getLabmeetAndJC( );


if( array_key_exists( 'Response', $_POST ) && $_POST['Response'] == "scan" )
{
    $date = humanReadableDate( $_POST[ 'date' ] );
    if( strtotime($_POST['end_time']) < strtotime($_POST['start_time'])  )
    {
        echo printWarning( 
            'Event is ending before starting. This will violate causality. Fix 
            it now or I wont let you continue booking! ' 
        );
    }

    $startTime = humanReadableTime( $_POST[ 'start_time' ] );
    $endTime = humanReadableTime( $_POST[ 'end_time' ] );

    echo "<h2> Available venues on $date between $startTime & $endTime </h2>";

    $venues = getVenues( $sortby = 'name' );


    $table = '<table class="info">';
    foreach ($venues as $venue)
    {
        $venueId = $venue[ 'id' ];
        $date = dbDate( $_POST['date'] );
        $startTime = $_POST[ 'start_time' ];
        $endTime = $_POST[ 'end_time' ];

        $skip = false;
        $skipMsg = '<tt>' . $venueId . '</tt> does not meet your requirements: ';
        if( $venue[ 'strength' ] < $_POST[ 'strength' ] )
        {
            $msg = "Required strength=" . $_POST[ 'strength' ] .
                ' but venue strength=' . $venue[ 'strength' ] . '.';
            $skipMsg .= $msg;
            $skip = true;
        }

        // One can reduce a Kernaugh map here. The expression is A' + B where
        // A is request for skype variable and B is has_skype field of
        // venue. We take its negative and use continue.
        if( $_POST[ 'has_skype' ] == 'YES' && ! ($venue[ 'has_skype' ] == 'YES') )
        {
            $skipMsg .= " No conference facility. " ;
            $skip = true;
        }

        if( $_POST[ 'has_projector' ] == 'YES' && ! ($venue[ 'has_projector' ] == 'YES') )
        {
            $skipMsg .= " No projector. ";
            $skip = true;
        }


        // Similarly, openair.
        if( $_POST[ 'openair' ] == 'YES' && ! ($venue[ 'type' ] == 'OPEN AIR') )
        {
            // No need to display anything here.
            continue;
            $skip = true;
        }

        if( $skip )
        {
            $table .= '<tr><td colspan="2"><small>'
                   . colored( $skipMsg, 'grey' ) . '</small></td></tr>';
            continue;
        }

        /**
            * @name Now check if any request or booking is already made on this
            * slot/venue. If yes, then do not book.
        */
        $events = getEventsOnThisVenueBetweenTime(
            $venueId , $date , $startTime, $endTime
        );

        $reqs = getRequestsOnThisVenueBetweenTime(
            $venueId, $date, $startTime, $endTime
            );

        // merge requests and events are together.
        $all = array( );
        if( $events )
            $all = array_merge( $all, $events );
        if( $reqs )
            $all = array_merge( $all, $reqs );

        // If there is already any request or event on this venue, do not book.
        $ignore = 'is_public_event,url,description,status,gid,rid,'
            . 'external_id,modified_by,timestamp'
            . ',calendar_id,calendar_event_id,last_modified_on';

        $venueIsTaken = false;
        if( count( $all ) > 0 )
        {
            $tr = '<tr><td colspan="2">';
            $tr .= "<tt> Venue <font color=\"red\">"
                . strtoupper( $venue['id'])
                . " </font> has been taken by following booking request/event</tt>"
                ;

            $tr .= '<div style="font-size:x-small">';

            foreach( $all as $r )
                $tr .= arrayToTableHTML( $r, 'info', '', $ignore );

            $tr .= '</div>';
            $tr .= '</td></tr>';
            $table .= $tr;
            $venueIsTaken = true;
        }

        /*
         * Also check if a course is running on this slot/venue.
         */
        $clashingCourses = runningCoursesOnThisVenueSlot(
            $venue[ 'id' ], $date, $startTime, $endTime
        );

        if( $clashingCourses )
        {
            $tr = '<tr><td colspan="2">';
            $tr .= "<tt> Venue <font color=\"red\">"
                . strtoupper( $venue['id'])
                . " </font> has following course(s)</tt>"
                ;

            $tr .= '<div style="font-size:x-small">';

            $ignore = '';
            foreach( $clashingCourses as $id => $r )
                $tr .= arrayToTableHTML( $r, 'info', '', $ignore );

            $tr .= '</div>';
            $tr .= '</td></tr>';
            $table .= $tr;
            $venueIsTaken = true;

        }

        // Now construct a table and form
        // check if there is a labmeet or JC at this slot/venue.
        $jclabmeets = clashesOnThisVenueSlot(
                $date, $startTime, $endTime, $venueId, $jcAndMeets
            );

        $block = '<form method="post" action="' . site_url( "$ref/bookingrequest" ) . '">';
        $block .= '<div><tr>';
        if( count( $jclabmeets ) > 0 )
        {
            foreach( $jclabmeets as $jclabmeet )
            {
                $block .= '<div class="">';
                $block .= '<tr><td colspan="1">';
                $block .= '<font color=\"red\">ALERT: Though ' . $venue[ 'id' ]
                    . ' is available
                    , it is usually booked for following JC/Labmeet. Make sure to check
                    with booking party before you book this slot. They may book it
                    later. </font>';

                $block .= '<div style="font-size:x-small">';
                $block .= arrayToTableHTML( $jclabmeet, 'info', 'lightcyan', $ignore . ",date,eid");
                $block .= '</div>';
                $block .= '</td></tr>';
            }
        }


        // Create hidden fields from defaults. The description must be cleaned
        // otherwise it will be displayed on the screen.
        if(!isset($external_id))
            $external_id = 'SELF.-1';

        $block .= '<input type="hidden" name="title"
            value="' . $defaults['title' ] . '">';
        $block .= '<input type="hidden" name="description"
            value="' . __get__( $defaults, 'description', '')  . '">';
        $block .= '<input type="hidden" name="external_id"
            value="' . $external_id . '">';

        // Insert all information into form.
        $block .= '<input type="hidden" name="date" value="' . $defaults[ 'date' ] . '" >';

        $block .= '<input type="hidden"
            name="start_time" value="' . $defaults[ 'start_time' ] . '" >';
        $block .= '<input type="hidden"
            name="end_time" value="' . $defaults[ 'end_time' ] . '" >';
        $block .= '<input type="hidden"
            name="venue" value="' . $venue[ 'id' ] . '" >';
        $venueT = venueSummary( $venue );
        $block .= "<td>$venueT</td>";
        $block .= '<td> <button type="submit" title="Book this venue">Book</button></td>';
        $block .= '</tr></div>';
        $block .= '</form>';

        if( ! $venueIsTaken )
            $table .= $block;

    }
    $table .= '</table>';
    echo $table;
}


echo goBackToPageLink( "$ref/home", "Go back" );
echo "</body>";

?>
