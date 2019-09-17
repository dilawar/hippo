<?php
require_once BASEPATH . 'autoload.php';

/* IMP/NOTE: 
 * We use this interface for booking venue. We may also come here from manage
 * talks page. If a user creates a talk and we come here for a booking; we use
 * the external_id _GET variable. 
 *  
 *  Make sure to redirect to user manage talk or something similar page after
 *  booking.
 */


$ref = 'user';
if(isset($controller))
    $ref = $controller;
$goback = "$ref/home";

echo userHTML( );

$roundedTimeNow = round( time( ) / (15 * 60) ) * (15 * 60 );

$defaults = array(
    "date" => dbDate( strtotime( 'today' ) )
    , "start_time" => date( 'H:i', $roundedTimeNow )
    , "end_time" => date( 'H:i', $roundedTimeNow + 3600 )
    , "strength" => 7
    , "has_skype" => "NO"
    , "has_projector" => "NO"
    , "openair" => "NO"
    , "title" => ''
    );


/*
 * If external_id is set by controller then we are here to book a  talk.
 */
if(isset($external_id))
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
    $goback = "user/show_public";
}
else
{
    echo alertUser( 'If emails are be sent out to academic community (e.g. 
        <strong><tt>TALK</tt>s, <tt>SEMINAR</tt>, <tt>THESIS SEMINAR</tt>s, <tt>LECTURE</tt>s</strong> ),
        for your booking, please 
        <a href="' . site_url( "$ref/register_talk") . '"> 
            <i class="fa fa-spinner fa-spin fa-2w"></i>use this interface</a> to proceed.'
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

?>

<form action="" method="post" >
<table class="table-responsive table-condensed">
<tr>
    <td>Date</td>
    <td><input  class="datepicker" name="date"
    value=" <?= $defaults['date'] ?>" /> </td>
</tr>
<tr>
    <td>Start time </td>
    <td><input  class="timepicker" name="start_time"
    value="<?= $defaults['start_time'] ?>" /> </td>
</tr>
<tr>
    <td>End time </td>
    <td><input  class="timepicker" name="end_time"
    value="<?= $defaults['end_time'] ?>" /> </td>
</tr>
<tr>
    <td>Mininum seatings required? </td>
    <td><input type="text" name="strength"
    value=" <?= $defaults['strength'] ?>" /> </td>
</tr>
<tr>
    <td>Do you need video-conference facility?</td>
    <td>
        <input type="radio" name="has_skype" value="NO" <?= $skypeNo ?> /> No
        <input type="radio" name="has_skype" value="YES" <?= $skypeYes ?> /> Yes
    </td>
</tr>
<tr>
    <td>Do you need a projector?</td>
    <td>
    <input type="radio" name="has_projector" value="NO" <?= $projectorNo ?> /> No
    <input type="radio" name="has_projector" value="YES" <?= $projectorYes?> /> Yes
    </td>
</tr>
<tr>
    <td>Prefer open-air location?</td>
    <td>
        <input type="radio" name="openair" value="NO" <?= $openAirNo ?> /> No
        <input type="radio" name="openair" value="YES" <?= $openAirYes ?> /> Yes
    </td>
</tr>
</table>
<div class="d-flex justify-content-end">
<button title="Scan for venues" 
    class="btn btn-success btn-lg" 
    name="Response" value="scan"> Show Available Venues </button>
</div>
</form>

<?php
$date = __get__( $_POST, 'date', dbDate(strtotime( 'today' )) );

// Get list of public events on user request day and show them to him. So he can
// decides if some other timeslot should be used.
$publicEvents = getPublicEventsOnThisDay( $date );

if( count( $publicEvents ) > 0 )
{
    echo "<div class='alert alert-info'>";
    echo p("You are advised not to schedule any public event which might clash with the followings.");

    $tohide = 'gid,eid,class,description,status,is_public_event,external_id'
              . ',calendar_id,calendar_event_id,last_modified_on,url' ;

    echo '<table class="table-responsive table-bordered">';
    echo arrayHeaderRow( $publicEvents[0], 'info', $tohide );
    foreach( $publicEvents as $event )
        echo arrayToRowHTML( $event, 'info', $tohide );
    echo '</table>';
    echo "</div>";
}

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

    // Prepare list.
    $list = '<div class="list-group">';

    foreach ($venues as $venue)
    {
        // Store data in this array.
        $listData = ['reason' => ''];
        $listData['show_booking_button'] = true;
        $listData['venue'] = $venue;
        $listData['note'] = $venue['note_to_user'];
        $listData['quota'] = intval($venue['quota']);
        if($listData['quota'] > 0)
        {
            $listData['note'] .= colored(
                p("<i class='fa fa-frown-o'></i> This venue has quota limit of <tt> " .
                $listData['quota'] . " minutes per week</tt> for every user.")
            , 'red');
        }

        $venueId = $venue[ 'id' ];
        $date = dbDate( $_POST['date'] );
        $startTime = $_POST[ 'start_time' ];
        $endTime = $_POST[ 'end_time' ];

        $skip = false;
        $skipMsg = '<tt>' . $venueId . '</tt> does not meet your requirements: ';
        if( $venue[ 'strength' ] <= $_POST[ 'strength' ] )
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
            $skip = true;
            continue;
        }

        if( $skip )
        {
            $listData['reason'] = colored( $skipMsg, 'grey' );
            $listData['hide'] = $skip;

            continue;
        }

        /* check if admin has marked it reserved. */
        if( (! anyOfTheseRoles("BOOKMYVENUE_ADMIN,ADMIN,ACAD_ADMIN")) 
            && ('NO' === $venue['allow_booking_on_hippo']) 
        ){
            $listData['show_booking_button'] = false;
            $listData['reason'] = p("This venue is not available for common booking on Hippo!");
        }

        // Check if user has surpassed this quota.
        if($listData['quota'] > 0)
        {
            $usage = getWeeklyUsage(whoAmI(), $listData['venue']['id']);
            echo " USAGE $usage ";
            $listData['show_booking_button'] = false;
            $listData['reason'] .= p(
                "You have surpassed your quota on this venue " 
                . venueToShortText($listData['venue']) 
                . ". Your usage $usage mins, allowed "
                . $listData['quota'] . "."
            );
        }

        /**
            * @name Now check if any request or booking is already made on this
            * slot/venue. If yes, then do not book.
        */
        $events = getEventsOnThisVenueBetweenTime($venueId , $date , $startTime, $endTime);

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
        $ignore = 'is_public_event,url,description,gid,rid,eid,'
            . 'external_id,modified_by,timestamp,venue'
            . ',calendar_id,calendar_event_id,last_modified_on';

        $venueIsTaken = false;
        if( count( $all ) > 0 )
        {
            $msg = "Venue <font color=\"red\">"
                . strtoupper( $venue['id'])
                . " </font> has been taken by following booking request/event</tt>"
                ;

            foreach( $all as $r )
                $msg .= arrayToTableHTML( $r, 'info', '', $ignore, false);

            $listData['reason'] = $msg;
            $listData['show_booking_button'] = false;
        }

        /*
         * Also check if a course is running on this slot/venue.
         */
        $clashingCourses = runningCoursesOnThisVenueSlot(
            $venue[ 'id' ], $date, $startTime, $endTime
        );

        if( $clashingCourses )
        {
            $msg = "Venue <font color=\"red\">" . strtoupper( $venue['id']). " </font> 
                has following course(s)";

            $ignore = 'semester,year,max_registration,allow_deregistration_until,is_audit_allowed,note';
            foreach( $clashingCourses as $id => $r )
                $msg .= arrayToTableHTML( $r, 'info', '', $ignore, false);

            $listData['reason'] .= $msg;
            $listData['show_booking_button'] = false;
        }

        // check if there is a labmeet or JC at this slot/venue if venue is
        // still available.
        if( $listData['show_booking_button'])
        {
            $jclabmeets = clashesOnThisVenueSlot(
                    $date, $startTime, $endTime, $venueId, $jcAndMeets
                );

            if( count($jclabmeets) > 0 )
            {
                $note = '';
                foreach( $jclabmeets as $jclabmeet )
                {
                    $note .= '<font color=\"cyan\"> <i class="fa fa-exclamation-circle fa-2x"></i>
                         Although ' . $venue[ 'id' ]
                        . ' is available, it is usually booked for following JC/Labmeet. Make sure 
                        to check with booking party. They may book it later. </font>';

                    $note .= '<div class="alert alert-info" style="font-size:x-small">';
                    $note .= arrayToTableHTML( $jclabmeet, 'info', '', $ignore . ",date,eid", false);
                    $note .= '</div>';
                }
                // Append to note if any.
                $listData['note'] .= $note;
            }
        }

        // Create hidden fields from defaults. The description must be cleaned
        // otherwise it will be displayed on the screen.
        if(!isset($external_id))
            $external_id = 'SELF.-1';

        // Insert all information into form.
        $form = '<form method="post" action="' . site_url( "$ref/bookingrequest/$goback" ) . '">';
        $form .= '<input type="hidden" name="title"
            value="' . $defaults['title' ] . '">';
        $form .= '<input type="hidden" name="description"
            value="' . __get__( $defaults, 'description', '')  . '">';
        $form .= '<input type="hidden" name="external_id"
            value="' . $external_id . '">';

        $form .= '<input type="hidden" name="date" value="' . $defaults[ 'date' ] . '" >';

        $form .= '<input type="hidden"
            name="start_time" value="' . $defaults[ 'start_time' ] . '" >';
        $form .= '<input type="hidden"
            name="end_time" value="' . $defaults[ 'end_time' ] . '" >';
        $form .= '<input type="hidden"
            name="venue" value="' . $venue[ 'id' ] . '" >';
        $form .= '<button class="btn btn-primary" title="Book this venue">Book</button>';
        $form .= "";
        $form .= '</form>';

        // Now contruct a row.
        $row = "<div class='list-group-item'>";

        // Show booking button only when venue is safe to book.
        if( $listData['show_booking_button'] )
        {
            $row .= "<div class='h5'>" . venueSummary($listData['venue']) . '</div>';
            $row .= "<div class='d-flex justify-content-start'> $form </div>";
        }

        if(trim($listData['reason']))
            $row .= "<p class='list-group-item-text'>" .$listData['reason'] . "</p>";
        if(trim($listData['note']))
            $row .= "<p class='list-group-item-text'>" .$listData['note'] . "</p>";

        $row .= '</div>';

        $list .= $row;
    }
    $list .= "</div>";
    echo $list;
}


echo goBackToPageLink( "$goback", "Go back" );
echo "</body>";

?>
