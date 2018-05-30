<?php 

require_once BASEPATH .'autoload.php';

echo userHTML( );

$ref = 'user';
if(isset($controller))
    $ref = $controller;

if(!isset($goback))
    $goback = "$ref/home";

echo '<div class="important">
    <ul>
        <li> Set <tt>IS PUBLIC EVENT</tt>  to <tt>YES</tt> if you want your event to appear 
        on NCBS\' google-calendar. <br />
        </li>
        <li>
            Pick proper <tt>CLASS</tt> for your booking. Your request will be rejected if it is 
               filed under wrong <tt>CLASS</tt>.
        </li>
    </ul>
    </div>
    ';

$venues = getVenues( $sortby = 'total_events' );

if(! isset($date))
{
    echo printErrorSevere( "No valid day is selected. Please go back and select a valid date.", false );
    echo goBack( );
    echo goBackToPageLink( "user/home", "Go Home" );
    exit;
}

$day = nameOfTheDay( $date ); 
$events = getEvents( $date );
$dbDate = dbDate( $date );

// Generate options here.
$venue = __get__( $_POST, 'venue', '' );
$venue = trim( $venue );

if( $venue )
    $venueHTML = "<input name=\"venue\" type=\"text\" value=\"$venue\" readonly>";
else
    $venueHTML = venuesToHTMLSelect( $venues );

$startTime = dbTime( $_POST[ 'start_time' ] );

// This is END time of event. It may come from user from ./quickbook.php or use 
// default of 1 hrs in future.
$defaultEndTime = __get__( $_POST, 'end_time'
    , date( 'H:i', strtotime( $startTime ) + 60*60 )
    );

$date = __get__( $_POST, 'date', '' );
$title = __get__( $_POST, 'title', '' );
$description = __get__( $_POST, 'description', '' );

$labmeetOrJCs = labmeetOrJCOnThisVenueSlot( $date, $startTime, $defaultEndTime, $venue );

if( count( $labmeetOrJCs ) > 0 )
{
    foreach( $labmeetOrJCs as $labmeetOrJC )
    {
        $ignore = 'is_public_event,url,description,status,gid,rid,'
            . 'external_id,modified_by,timestamp'
            . ',calendar_id,calendar_event_id,last_modified_on';

        echo printWarning( "<font color=\"red\">
            ATTN: Following Journal Club or Labmeet usually happens at this 
            slot.  DO NOT book here unless you are sure that following event WILL not
            happen. 
            </font>" );

        echo '<small>';
        echo arrayToTableHTML( $labmeetOrJC, 'info', '', $ignore );
        echo '</small>';
        echo '<br><br>';
    }
}

echo ' <h2>Fill-in details</h2> ';

$default = array( 'created_by' => whoAmI() );
$default[ 'end_time' ] = $defaultEndTime;
$default = array_merge( $default, $_POST );

// If external_id is given then this needs to go into request table. This is 
// used to fetch event data from external table. The format of this field if 
// TABLENAME.ID. 'SELF.-1' means the there is not external dependency.
if(!isset($external_id))
    $external_id = 'SELF.-1';
else
    $default['is_public_event'] = true;

echo '<form method="post" action="' . site_url("user/bookingrequest_submit/$goback") . '">';
echo dbTableToHTMLTable( 'bookmyvenue_requests'
        , $default
        , 'class,title,description,url,is_public_event,end_time' 
        , ''
        , $hide = 'gid,rid,modified_by,timestamp,status'
        );

echo '<input type="hidden" name="external_id" value="' . $external_id . '" >';

// Lets keep the referer in the $_POST.
echo '<input type="hidden" name="REFERER" value="' . $ref . '" >';

// I need to add repeat pattern here.
echo "<br />";
echo repeatPatternTable( 'repeat_pat' );
echo '<br />';
echo submitButton( 'Submit' );
echo '</form>';
echo '<br /><br />';

echo goBackToPageLink( "$goback", "Go back" );
