<?php 
include_once( "header.php" );
include_once( "methods.php" );
include_once( "tohtml.php" );
include_once( "database.php" );
include_once 'display_content.php';
include_once "./check_access_permissions.php";

mustHaveAnyOfTheseRoles( array( 'USER' ));

echo alertUser( "This interface is deprecated.  Booking made using this interface 
    likely to clash with clases/JC/LAB MEETS. Use 'QuickBook' link provides above.
    <br />
    DO NOT USE IT."
    );

echo userHTML( );

if( isMobile( ) )
    echo alertUser( 
        "If you are on a mobile device, you may like another interface.
        <a href=\"quickbook.php\">TAKE ME THERE</a>"
        );

// There is a form on this page which will send us to this page again. Therefore 
// we need to keep $_POST variable to a sane state.
$venues = getVenues( $sortby = 'id' );
$venueNames = implode( ","
    , array_map( function( $x ) { return $x['id']; }, $venues )
    );


// Get the holiday on particular day. Write it infront of date to notify user.
$holidays = array( );
foreach( getTableEntries( 'holidays', 'date' ) as $holiday )
    $holidays[ $holiday['date'] ] = $holiday['description'];

// Construct a array to keep track of values. Since we are iterating over this 
// page many times.
$defaults = array( 
    'selected_dates' => dbDate( strtotime( 'today' ) )
    , 'selected_venues' => $venueNames
    // This trick to make sure we get 15 minutes block.
    , 'start_time' => date( 
        'H:i', floor( strtotime( 'now ' ) / (15 * 60 )) * (15 * 60) 
    )
    // It is essential that we stop at 11:59. After than we have 00:00. Which is 
    // a lower number than start time. We can not iterate over the difference.
    , 'end_time' => date( 'H:i'
        , min( strtotime('today midnight') - 60, strtotime( 'now' ) + 6 * 3600)
        )
    );

// Update these values by $_POST variable.
foreach( $_POST as $key => $val )
    if( array_key_exists( $key, $defaults ) )
    {
        // All entries in $defaults are CSV.
        if( is_array( $val ) )
            $val = implode( ",", $val );
        $defaults[ $key ] = $val;
    }

$selectedDates = explode( ",", $defaults['selected_dates'] );
$selectedVenues = explode( ",", $defaults[ 'selected_venues' ] );

$venueSelect = venuesToHTMLSelect( $venues, true
    , "selected_venues", $selectedVenues 
    );

echo "<form method=\"post\" action=\"\">
    <table>
    <tr>
    <th>
        Step 1: Pick dates (and optionally a time-range)
        <p class=\"note_to_user\">
        You can select multiple dates by clicking on popup calendar</p>
    </th>
    <th>
        Step 2: Select Venues
        <p class=\"note_to_user\">You can select multiple venues by holding 
            down Ctrl or Shift key</p>
    </th>
    </tr>
    <tr>
    <td>
        <input type=\"text\" class=\"multidatespicker\" name=\"selected_dates\" 
        value=\"" . $defaults[ 'selected_dates' ] . "\" >
        <br />
        <p>Explore time range </p>
        <input type=\"time\" value=\"" . $defaults[ 'start_time' ] . 
            "\" class=\"timepicker\" name=\"start_time\"> Start Time
        <br />
        <input type=\"time\" value=\"" . $defaults[ 'end_time' ] . 
                "\" class=\"timepicker\" name=\"end_time\"> End Time
    </td>
    <td> $venueSelect </td>
    </tr>
    <tr> <td></td>
        <td>
            <button  name=\"response\" value=\"submit\">Filter</button>
        </td>
    </tr>";

echo '</table>';
echo '</form>';


echo "<h3>Step 3: Press <button disabled>+</button> to book your venue. 
    Next, you'll be asked to fill details and thats it.
    </h3>";

echo "<table border=\"1\" style=\"table-layout:fixed;width:100%;\">
    <tr><td><button class=\"display_request\"></button>
        Someone has already created a booking request (pending approval). You 
        CANNOT book at this slot.
    </td>
    <td><button class=\"display_event\"></button>
        This slot has already been booked. You CANNOT book at this slot. </td>
    <td><button class=\"display_event_with_public_event\"></button>
        There is a public event at this slot at some other venue.
        This is just for your information. </td>
    </tr>
   </table>
       ";

// Now generate the range of dates.
foreach( $selectedDates as $date )
{
    $thisdate = humanReadableDate( strtotime( $date  ) );
    $thisday = nameOfTheDay( $thisdate );

    $holidayText = '';
    if( array_key_exists( $date, $holidays ) )
        $holidayText =  '<div style="float:right"> &#9786 ' . $holidays[ $date ] . '</div>';

    $html = "<h4 class=\"info\"> <font color=\"blue\">
        $thisdate $holidayText </font></h4>";

    // Now generate eventline for each venue.
    foreach( $selectedVenues as $venueid )
        $html .= eventLineHTML( 
                    $date, $venueid
                    , $defaults[ 'start_time' ] , $defaults[ 'end_time' ]
                );

    echo $html;
}

?>


