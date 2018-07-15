<?php

require_once BASEPATH.'autoload.php';

// Some symbols.
global $symbEdit;
global $symbCancel;
global $symbDelete;
global $symbAccept;

echo userHTML( );

$allSpeakers = array_map( function( $x ) { return $x['login']; }, getAWSSpeakers( ) );
$upcomingAWSs = getUpcomingAWS( );

$alreadyHaveAWS = array_map( function( $x ) { return $x['speaker']; }, $upcomingAWSs );
$speakers = array_values( array_diff( $allSpeakers, $alreadyHaveAWS ) );

?>

<!-- Script to autocomplete user -->
<script type="text/javascript" charset="utf-8">
$(function() {
    var speakers = <?php echo json_encode( $speakers ); ?>;
    $( ".autocomplete_speaker" ).autocomplete( { source : speakers });
});
</script>

<?php

$upcomingAWSs = getUpcomingAWS( );

$upcomingAwsNextWeek = array( );
foreach( $upcomingAWSs as $aws )
    if( strtotime( $aws['date'] ) - strtotime( 'today' )  < 7 * 24 * 3600 )
        array_push( $upcomingAwsNextWeek, $aws );

echo '<h1>Annual Work Seminar for upcoming week</h1>';

if( count( $upcomingAwsNextWeek ) < 1 )
    echo alertUser( "No AWS found for upcoming week.", false );
else
{
    $table = '<div style="font-size:small">';
    foreach( $upcomingAwsNextWeek as $upcomingAWS )
    {
        $table .= '<form action="'.site_url('adminacad/next_week_aws_action').'" method="post" >';
        $table .= '<table>';
        $table .= '<tr><td>';
        $table .= arrayToVerticalTableHTML($upcomingAWS, 'aws', '', array( 'id', 'status', 'comment' ));
        $table .= '<input type="hidden", name="date" , value="' .  $upcomingAWS[ 'date' ] . '"/>';
        $table .= '<input type="hidden", name="speaker" , value="' . $upcomingAWS[ 'speaker' ] . '"/>';
        $table .= '<input type="hidden", name="id" , value="' . $upcomingAWS[ 'id' ] . '"/>';
        $table .= '</td><td>';
        $table .= '<button name="response" title="Edit/format the abstract" value="format_abstract">' . $symbEdit . '</button>';
        // $table .= '<br>';
        //$table .= '<button onclick="AreYouSure(this)" name="response"
        //       . title="Remove this entry from schedule" value="delete">' . $symbCancel . '</button>';
        $table .= '</td></tr>';
        $table .= '</table>';
        $table .= '</form>';
    }

    $table .= '</div>';
    echo $table;
}

echo "<h1>Upcoming approved AWSs</h1>";

echo '<div style="font-size:small">';
echo awsAssignmentForm( );
echo "</div>";

echo '<br /><br />';


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Group AWS by date here. Once we have them grouped, later
    * processing is much easier.
 */
/* ----------------------------------------------------------------------------*/
$awsThisWeek = 0;
$awsGroupedByDate = array( );
foreach( $upcomingAWSs as $aws )
    $awsGroupedByDate[ $aws['date'] ][] = $aws;


/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Show upcoming schedule. Show a table.
 */
/* ----------------------------------------------------------------------------*/
$table = '<table class="infolarge">';
foreach( $awsGroupedByDate as $groupDate => $awses )
{
    $awsThisWeek = count( $awses );

    $table .= '<tr>';

    // $table .= '<td style="font-size:large" colspan=3>' . humanReadableDate( $groupDate, $with_day = false ) . '</td>';
    // $table .= ' </tr><tr>';

    // Show AWSes
    foreach( $awses as $countAWS => $aws )
    {
        $table .= '<td>';

        // Each speaker can be a table as well.
        $speakerTable = '<table class="sticker" border=0> <tr> ';

        $speakerHTML = smallCaps( loginToText( $aws['speaker'], $withEmail = false ) .
            ' (' .  $aws['speaker'] . ')' );

        // Check if user has requested AWS schedule and has it been approved.
        $request = getSchedulingRequests( $aws['speaker'] );

        if( $request )
            $speakerHTML .= '<br />' . preferenceToHtml( $request );

        $speakerTable .= '<td>' . $speakerHTML . ' <br /><strong>' 
            . humanReadableDate($aws['date'], false) . '</strong></td>';

        $pi = getPIOrHost( $aws[ 'speaker' ] );
        $specialization = getSpecialization( $aws[ 'speaker' ], $pi );
        $awsID = $aws['id'];

        // Speaker PI if any.
        $speakerTable .=  '<td>' . piSpecializationHTML( $pi, $specialization ) . '</td>';

        $form = '<form action="'.site_url("adminacad/upcoming_aws_action"). '" method="post">';
        $form .= '<input type="hidden", name="date", value="' . $aws[ 'date' ] . '"/>';
        $form .= '<input type="hidden", name="speaker", value="' . $aws[ 'speaker' ] . '"/>';
        // Create a hidden form which gets active when someone clicks on delete
        // this entry.
        $divId = 'delete_aws_form_' . $awsID;
        $delete = '<div id="' . $divId . '" style="display:none;">';
        $delete .= '<textarea name="reason" cols="20" rows="5" 
                        placeholder="reason for removing (at least 8 chars). Email is also sent to PI."
                    ></textarea>';
        $delete .= '<button name="response" onclick="AreYouSure(this)"
                    title="Delete this entry" >' . $symbDelete . '</button>';
        $delete .= '</div>';
        $form .= "<a onclick=\"toggleShowHide( this, '$divId', 'Delete This Entry')\">Remove this entry</a> $delete";
        $form .= '</form>';

        $speakerTable .= '<td>' . $form . '</td>';
        $speakerTable .= '</td>';
        $speakerTable .=  '</tr></table>';

        $table .= $speakerTable;

        if( $aws[ 'acknowledged' ] == 'NO'  )
            $table .= "<blink><p class=\"note_to_user\">Acknowledged: " 
                . $aws[ 'acknowledged' ] . "</p></blink>";
    }
    

    if( $awsThisWeek < 3 )
        $table .= '<td>' . awsAssignmentForm( dbDate( $groupDate ), true ) . '</td>';

    $table .= '</tr>';
}
$table .= '</table>';
echo $table;


echo goBackToPageLink( "adminacad/home", "Go back" );

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Temporary schedule.
 */
/* ----------------------------------------------------------------------------*/
echo "<h1>Temporary assignments</h1>";

echo printInfo("Three methods are available for scheduling AWS. First one is default.");

$methodTable  = "<form method=\"post\" action=\"".site_url('adminacad/schedule_upcoming_aws'). "\">";
$methodTable .= ' <table> ';
$methodTable .= '<tr><td>';
$methodTable .= '<button name="method" value="reschedule_default">
    <strong>Recompute (DEFAULT)</strong></button>';
$methodTable .= "</td><td>";
$methodTable .= '<button name="method" value="reschedule_group_greedy">Recompute (GroupAndGreedily)</button>';
$methodTable .= "</td><td>";
$methodTable .= '<button name="method" value="reschedule">Recompute (DoNotGroupAWS)</button>';
$methodTable .= "</td></tr>";
$methodTable .= '</table>';
$methodTable .= "</form>";
echo $methodTable;


$schedule = getTentativeAWSSchedule( );
$scheduleMap = array( );
foreach( $schedule as $sch )
    $scheduleMap[ $sch['date'] ][ ] = $sch;

$header = "<tr>
    <th>Speaker</th><th>Scheduled On</th><th>Last AWS on</th><th># Day</th><th>#AWS</th>
    </tr>";

echo '<br>';

// This is used to group slots.
$weekDate = $schedule[0]['date'];

$csvdata = array( "Speaker,Scheduled on,Last AWS on,Days since last AWS,Total AWS so far" );

$allDates = array( );
foreach( $scheduleMap as $date => $schedule )
{
    $allDates[ ] = $date;

    // check if this date is one week away from previous date.
    if( count( $allDates ) > 0 )
    {
        $prevDate = end( $allDates );
        $noAWSWeeks =  (strtotime( $date ) -  strtotime( $prevDate )) / 7 / 24 / 3600;
        for( $i = $noAWSWeeks - 1; $i > 0; $i-- )
        {
            $nextDate = humanReadableDate( strtotime( $date ) - $i*7*24*3600 );
            echo printWarning( "No AWS is scheduled for '$nextDate'." );
        }
    }
    
    // Show table.
    $table = '<table class="show_schedule">';
    foreach( $schedule as $i => $upcomingAWS )
    {
        $table .= '<tr>';
        $csvLine = '';

        $speaker = $upcomingAWS[ 'speaker' ];
        $speakerInfo = getLoginInfo( $speaker );

        $pastAWSes = getAwsOfSpeaker( $speaker );

        // Get PI/HOST and speaker specialization.
        $pi = getPIOrHost( $speaker );
        $specialization = getSpecialization( $speaker, $pi );

        // This user may have not given any AWS in the past. We consider their
        // joining date as last AWS date.
        if( count( $pastAWSes ) > 0 )
        {
            $lastAws = $pastAWSes[0];
            $lastAwsDate = $lastAws[ 'date' ];
        }
        else
            $lastAwsDate = date( 'Y-m-d', strtotime($speakerInfo[ 'joined_on']));

        $nSecs = strtotime( $upcomingAWS['date'] ) - strtotime( $lastAwsDate );
        $nDays = $nSecs / (3600 * 24 );
        $speakerInfo = loginToText( $speaker, false ) . " ($speaker)";
        $csvLine .= loginToText( $speaker, true ) . ',';

        $table .= "<tr><td>";
        $table .= '<font style="font-size:large">' . $speakerInfo . '</font>';

        // Add PI and specialization info.
        $table .= '<br />' . piSpecializationHTML( $pi, $specialization );

        $intranetLink = getIntranetLink( $speaker );

        $table .= "<br /> $intranetLink ";
        $table .= '<form action="'.site_url('adminacad/action').'" method="post">
            <input type="hidden" name="speaker" value="' . $speaker . '" />
            <button name="response" class="show_as_link" value="RemoveSpeaker" 
                title="Remove this speaker from AWS speaker list" >
                <i class="fa fa-trash fa-x"></i> </button>';
        $table .= '</form>';

        // Check if user has requested AWS schedule and has it been approved.
        $request = getTableEntry(
            'aws_scheduling_request'
            , 'speaker,status'
            , array( 'status' => 'APPROVED', 'speaker' => $upcomingAWS[ 'speaker' ])
        );

        // If user request for rescheduling was approved, print it here.
        if( $request )
            $table .= preferenceToHtml( $request );

        $table .= "</td><td>";
        $table .= fontWithStyle( humanReadableDate( $upcomingAWS[ 'date' ] ), 'font-size:large' );

        $csvLine .= $upcomingAWS['date'] . ',';


        $csvLine .= $lastAwsDate . ',';

        $info = '<table class="info">';
        if( count( $pastAWSes) == 0 )
            $info .= "<tr><td>Joining Date</td><td> $lastAwsDate </td></tr>";
        else
            $info .= "<tr><td>Last AWS on</td><td> $lastAwsDate </td></td>";

        $info .= "<tr><td>Days since last AWS</td><td> $nDays </td></td>";
        $info .= "<tr><td>Number of past AWSs</td><td>" . count( $pastAWSes ) 
            . " </td></tr>";
        $info .= '</table>';

        $csvLine .= $nDays;

        $table .= $info;
        $table .= "</td>";

        // Create a form to approve the schedule.
        $table .= '<form method="post" action="'.site_url('adminacad/action').'">';
        $table .= '<input type="hidden" name="speaker" value="' . $speaker . '" >';
        $table .= '<input type="hidden" name="date" value="' . $upcomingAWS['date'] . '" >';
        $table .= '<td style="background:white;border:0px;">
            <button name="response" title="Confirm this slot"
            value="Accept" >' . $symbAccept . '</button>
            </td>';
        $table .= "</tr>";
        $table .= '</form>';

        array_push( $csvdata, $csvLine );

        $table .= '</tr>';
    }
    $table .= '</table>';

    // show table.
    echo $table;
    echo '<br />';
}

$csvText = implode( "\n", $csvdata );

$upcomingAWSScheduleFile = FCPATH.'/temp/upcoming_aws_schedule.csv';

$res = saveDataFile( $upcomingAWSScheduleFile, $csvText );

if( $res )
    echo downloadTextFile(  $upcomingAWSScheduleFile, "Download schedule" );

echo '<br><br>';
echo goBackToPageLink( "adminacad/home", "Go back" );

?>


