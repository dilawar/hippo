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
function bookmyVenueAdminTaskTable()
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


echo userHTML();
echo bookmyVenueAdminTaskTable();

echo '<h1>Pending requests</h1>';
$reqGrouped = getPendingRequestsGroupedByGID();
echo p("Total " . count($reqGrouped) . " requests are pending.");

$html = '<div>';
$html .= '<input type="text" id="filter_requests" placeholder="Type to filter">';
$html .= '<table id="pending_requests" class="info sortable exportable">';
$tohide = 'last_modified_on,status,modified_by,timestamp,url,external_id,gid,rid';

// Using reference is missing the last event. Surprising! So we create a new
// array and add.
$requests = array();
foreach ($reqGrouped as $r) {
    $r['metadata'] = '(' . $r['gid'].'.'.$r['rid'] .')'
        . ' <br />' . $r['timestamp']
        . ' <br />' . $r['status'];
    $requests[] = $r;
}

$html .= arrayToTHRow($requests[0], 'request', $tohide);
foreach ($requests as $r) {
    // If request date has passed, ignore it.
    if (strtotime($r[ 'date' ]) < strtotime('-2 day')) {
        // TODO: Do not show requests which are more than 1 days old. Their status
        // remains PENDING all the time. Dont know what to do such
        // unapproved/expired requests.
        $res = changeRequestStatus($r['gid'], $r['rid'], 'EXPIRED');
        if ($res) {
            $rid = $r['gid'] . '-' . $r['rid'];
            $msg = p("Following request is expired because no one acted on it.");
            $msg .= arrayToTableHTML($r, 'info');
            $subject = "Request id  $rid is expired.";
            $to = getLoginEmail($r['created_by']);
            $cclist = 'hippo@lists.ncbs.res.in';
            sendHTMLEmail($msg, $subject, $to, $cclist);
        }
        continue;
    }

    // If a request is coming from talk or in near future
    $color = '';
    if (strtotime($r['date']) <= strtotime('tomorrow') + 3600*24*5) {
        $color = 'lightblue';
    }

    // If it is talk use yellow.
    if (__substr__('talks.', __get__($r, 'external_id', ''))) {
        $color = 'yellow';
    }

    $html .= "<tr style=\"background-color:$color\">";
    $html .= '<form action="'.site_url('adminbmv/review'). '" method="post">';
    // Hide some buttons to send information to next page.
    $html .= '<input type="hidden" name="gid" value="' . $r['gid'] . '" />';
    $html .= '<input type="hidden" name="rid" value="' . $r['rid'] . '" />';
    $html .= arrayToRowHTML($r, 'request', $tohide, false, false);
    $html .= '<td style="background:white"><button name="response" 
                class="btn btn-primaty"
                value="Review" title="Review request"> ' .  $symbReview . '</button>';
    $html .= '</form>';

    // Another form to quickly approve. Visible only if there are not many
    // subrequests.
    if (getNumberOfRequetsInGroup($r['gid']) == 1) {
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
echo goBackToPageLink("adminbmv/home", "Go home");


echo '<h1>Upcoming (approved) events in next 4 weeks </h1>';

// Let admin search.
echo '<input id="filter_events" placeholder="Type to filter"></input>';

if (__get__($_POST, 'response', '') == 'search') {
    $query = trim($_POST[ 'query' ]);
    if (trim($query)) {
        $day = dbDate('yesterday');
        $events = getTableEntries(
            'events',
            'date',
            "status='VALID' AND date >= '$day' AND
                (created_by='$query' OR title LIKE '%$query%')"
        );
    } else {
        $events = getEventsBetween('today', '+2 week');
    }
} else {
    $events = getEventsBetween('today', '+2 week');
}


if (count($events) > 0) {
    $html = '<div style="font-size:small;">';
    $event = $events[0];

    $html .= "<table id=\"approved_events\" class=\"info sortable exportable\">";
    $tofilter = 'eid,calendar_id,calendar_event_id' .
        ',external_id,gid,last_modified_on,status,url';

    // Add extra field to create one last row.
    $html .= arrayHeaderRow($event, 'event', $tofilter);

    foreach ($events as $event) {
        // Today's event if they are passed, don't display them.
        if ($event[ 'date' ] == dbDate('today') && $event[ 'start_time'] < dbTime('now')) {
            continue;
        }

        $gid = $event['gid'];
        $eid = $event['eid'];
        $html .= "<tr>";
        $html .= arrayToRowHTML($event, 'event', $tofilter, false, false);

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

echo goBackToPageLink("adminbmv/home", "Go home");
?>

<!-- This should be copy pasted -->
<script src="<?=base_url()?>./node_modules/xlsx/dist/xlsx.core.min.js"></script>
<script src="<?=base_url()?>./node_modules/file-saverjs/FileSaver.min.js"></script>
<script src="<?=base_url()?>./node_modules/tableexport/dist/js/tableexport.min.js"></script>
<script type="text/javascript" charset="utf-8">
TableExport(document.getElementsByClassName("exportable"));
</script>

<!-- Filter request -->
<script type="text/javascript" charset="utf-8">
var $rowsRequest = $('#pending_requests tr');
$('#filter_requests').keyup(function() {
    var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();

    $rowsRequest.show().filter(function() {
        var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
        return !~text.indexOf(val);
    }).hide();
});
</script>

<script type="text/javascript" charset="utf-8">
var $rowsEvent = $('#approved_events tr');
$('#filter_events').keyup(function() {
    var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();
    $rowsEvent.show().filter(function() {
        var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
        return !~text.indexOf(val);
    }).hide();
});
</script>
