<?php
include_once 'header.php';
include_once 'database.php';
include_once 'methods.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';

mustHaveAnyOfTheseRoles('BOOKMYVENUE_ADMIN' );
echo userHTML( );

/* Let user select venues and select the date to block them. */
echo ' <h1> Block venues on certain dates and times </h1> ';

$venues = getVenues( );
$venueSelect = venuesToHTMLSelect( $venues, true );
$classSelect = arrayToSelectList( 'class'
        , explode( ',', $dbChoices[ 'bookmyvenue_requests.class' ] )
        , array()
    );

$form = ' <form action="#" method="post" accept-charset="utf-8">';
$table = ' <table class="tasks">';
$table .= "<tr> <td> <strong>Select one or more venues </strong> </td><td> $venueSelect </td> </tr>";
$table .= '<tr><td>Pick dates</td>
            <td> <input type="text" name="dates" class="multidatespicker" value="" /></td></tr>';
$table .= '<tr> <td>Start Time</td>
            <td> <input type="text" name="start_time" class="timepicker" value="" /></td></tr>';
$table .= '<tr> <td>End Time</td>
            <td> <input type="text" name="end_time" class="timepicker" value="" /></td></tr>';
$table .= '<tr> <td>Reason for blocking</td> <td> <input type="text" name="reason" value="" /></td></tr>';
$table .= '<tr> <td>Type of event</td> <td>' . $classSelect .' </td></tr>';
$table .= '</table>';
$form .= $table;

$form .= ' <br />  <br />';
$form .= '<button class="submit" name="response" value="Block">Block Venues</button>';
$form .= '</form>';
echo $form;


// Now block the venue.
if( __get__( $_POST, 'response', '' ) == 'Block' )
{
    $venues = __get__( $_POST, 'venue' );

    $dates = __get__( $_POST, 'dates' );
    $dates = explode( ',', $dates );

    $startTime = __get__( $_POST, 'start_time' );
    $endTime = __get__( $_POST, 'end_time' );

    $gid = intval( getUniqueFieldValue( 'bookmyvenue_requests', 'gid' ) ) + 1;
    $rid = 0;
    foreach( $venues as $venue )
    {
        foreach( $dates as $date )
        {
            $date = dbDate( trim( $date ) );
            $title = __get__( $_POST, 'reason', '' );
            $class = __get__( $_POST, 'class', 'UNKNOWN' );

            if( strlen( $title ) < 8 )
            {
                echo printInfo( "Reason for blocking '$title' is too small.
                    At least 8 chars are required. Ignoring ..."
                );
                continue;
            }

            // We create a request and immediately approve it.
            $user = whoAmI( );
            $data = array(
                'gid' => $gid, 'rid' => $rid
                , 'date' => dbDate( $date )
                , 'start_time' => $startTime
                , 'end_time' => $endTime
                , 'venue' => $venue
                , 'title' => $title
                , 'class' => $class
                , 'description' => 'AUTO BOOKED BY Hippo'
                , 'created_by' => whoAmI( )
                , 'last_modified_on' => dbDateTime( 'now' )
            );

            $res = insertIntoTable( 'bookmyvenue_requests', array_keys( $data ), $data );
            $res = approveRequest( $gid, $rid );
            if( $res )
                echo printInfo( "Request $gid.$rid is approved." );

            $rid ++;
        }
    }
}

echo ' <br /> <br />';
echo goBackToPageLink( './bookmyvenue_admin.php', 'Go Back' );

?>
