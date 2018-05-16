<?php

require_once BASEPATH.'autoload.php';

global $symbReview;
global $symbEdit;
global $symbThumbsUp;

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Geneate task table for bookmyvenue admin.
    *
    * @Returns
    */
/* ----------------------------------------------------------------------------*/
function bookmyVenueAdminTaskTable( )
{
    $html = '<table class="admin">
        <tr>
            <td>
                <a class="clickable" href="'.site_url('user/bmv_browse').'">OLD BOOKING INTERFACE</a>
                <br /> You can browse all venues and see the pending requests and approved events.
            </td>
            <td>
                <i class="fa fa-calendar fa-2x"></i> 
                <a class="clickable" href="'.site_url('adminbmv/synchronize_calendar').'">
                Synchronize public calendar </a>
                <br />
               <strong>Make sure you are logged-in using correct google account </strong>
                </strong>
            </td>
        </tr>
        <tr>
            <td>
                <a class="clickable" href="'.site_url('adminbmv/venues').'"> Manage venues </a>
                <br /> Add/Update/Delete venues
            </td>
            <td>
                <i class="fa fa-share fa-2x"></i> 
                <a  class="clickable" href="'.site_url('adminbmv/email_and_docs').'">Send emails </a>
                <br />Send emails manually (and generate documents)
            </td>
        </tr>
        <tr>
            <td>
                <i class="fa fa-comments-o fa-2x"></i>
                <a class="clickable" href="'.site_url('adminbmv/manages_talks') .'">Manage talks/seminar</a>
                <br /> <br />
                <i class="fa fa-search fa-1x"></i>
                <a href="'.site_url('adminbmv/browse_talks') .'">Browse previous talks</a>
            </td>
            <td>
                <i class="fa fa-users fa-2x"></i>
                <a class="clickable" href="'.site_url('adminbmv/manages_speakers'). '">Manage speakers
            </td>
        </tr>
        <tr>
            <td>
                <i class="fa fa-ban fa-2x"></i>
                <a class="clickable" href="'.site_url('adminbmv/block_venues').'">Block venues</a>
                <br />Block venues on certain days/times.
            </td>
        </tr>
        </table>' ;

    return $html;
}


echo userHTML( );
echo bookmyVenueAdminTaskTable( );

echo '<h1>Pending requests</h1>';


$requests = getPendingRequestsGroupedByGID( );

$html = '<div style="font-size:small">';
$html .= '<table class="info">';

$tohide = 'last_modified_on,status,modified_by,timestamp,url,external_id,gid,rid';
foreach( $requests as &$r )
{
    $r['metadata'] = '<small>(' . $r['gid'].'.'.$r['rid'] .')'
        . ' <br />' . $r['timestamp'] 
        . ' <br />' . $r['status']
        . '</small>';

}

$html .= arrayToTHRow( $requests[0], 'request', $tohide );
foreach( $requests as $r )
{
    // If request date has passed, ignore it.
    if( strtotime( $r[ 'date' ] ) < strtotime( '-2 days' ) )
    {
        // TODO: Do not show requests which are more than 1 days old. Their status
        // remains PENDING all the time. Dont know what to do such
        // unapproved/expired requests.
        changeRequestStatus($r['gid'], $r['rid'], 'EXPIRED');
        continue;
    }

    // If a request is coming from talk, use different background.
    $color = '';
    if( strpos( $r[ 'external_id'], 'talks.' ) !== false )
        $color = 'lightyellow';

    $html .= "<tr style='background-color:$color'>";
    // $html .= '<td>';
    $html .= '<form action="'.site_url('adminbmv/review'). '" method="post">';
    // Hide some buttons to send information to next page.
    $html .= '<input type="hidden" name="gid" value="' . $r['gid'] . '" />';
    $html .= '<input type="hidden" name="rid" value="' . $r['rid'] . '" />';
    $html .= arrayToRowHTML( $r,'request', $tohide, false, false );
    $html .= '<td style="background:white"><button name="response" 
            value="Review" title="Review request"> ' .  $symbReview . '</button>';
    $html .= '</form>';
    // $html .= '</td>';

    // Another form to quickly approve. Visible only if there are not many
    // subrequests.
    if( getNumberOfRequetsInGroup( $r['gid'] ) == 1 )
    {
        // $html .= '<td style="background:white">';
        $html .= '<form action="'.site_url('adminbmv/approve'). '" method="post">';
        $html .= '<input type="hidden" name="gid" value="' . $r['gid'] . '" />';
        $html .= '<input type="hidden" name="rid" value="' . $r['rid'] . '" />';
        $html .= '<button title="One click approve. Careful!"> ' .  $symbThumbsUp . '</button>';
        $html .= '</form>';
    }
    // $html .= '</td>';
    $html .= '</tr>';
}
$html .= '</table>';
$html .= "</div>";
echo $html;
echo goBackToPageLink( "adminbmv/home", "Go home" );


echo '<h1>Upcoming (approved) events in next 4 weeks </h1>';


// Let admin search.
echo '<form action="" method="post" accept-charset="utf-8">
    <input name="query" value="" placeholder="Search using creator or title"></input>
    <button type="submit" name="response" value="search">Search</button>
</form>';

if( __get__( $_POST, 'response', '' ) == 'search' )
{
    $query = trim( $_POST[ 'query' ] );
    if( trim( $query ) )
    {
        $day = dbDate( 'yesterday' );
        $events = getTableEntries( 'events', 'date'
            , "status='VALID' AND date >= '$day' AND
                (created_by='$query' OR title LIKE '%$query%')"
        );
    }
    else
        $events = getEventsBeteen( 'today', '+2 week' );
}
else
    $events = getEventsBeteen( 'today', '+2 week' );


if( count( $events ) > 0 )
{
    $html = '<div style="font-size:small;">';
    $event = $events[0];

    $html .= "<table class=\"info\">";
    $tofilter = 'eid,calendar_id,calendar_event_id' .
        ',external_id,gid,last_modified_on,status,url';

    // Add extra field to create one last row.
    $html .= arrayHeaderRow( $event, 'event', $tofilter );

    foreach( $events as $event )
    {
        // Today's event if they are passed, don't display them.
        if( $event[ 'date' ] == dbDate( 'today' ) && $event[ 'start_time'] < dbTime( 'now' ) )
            continue;

        $gid = $event['gid'];
        $eid = $event['eid'];
        $html .= "<tr>";
        $html .= arrayToRowHTML( $event, 'event', $tofilter, false, false );

        $html .= "<td>";
        $html .= '<form method="post" action="'.site_url('adminbmv/edit').'">';
        $html .= "<td> <button title=\"Edit this entry\"  name=\"response\"
                    value=\"edit\">" . $symbEdit .  "</button></td>";
        $html .= "<input name=\"gid\" type=\"hidden\" value=\"$gid\" />";
        $html .= "<input name=\"eid\" type=\"hidden\" value=\"$eid\" />";
        $html .= "</form>";
        $html .= "</tr>";
    }

    $html .= "</table>";
    $html .= "</div>";
    echo $html;
}

echo goBackToPageLink( "adminbmv/home", "Go home" );

?>
