<?php

require_once __DIR__ . '/methods.php';
require_once __DIR__ . '/tohtml.php';

function cancelThisEvent(array $ev)
{
    $ev['status'] = 'CANCELLED';
    return updateTable('events', 'gid,eid', 'status', $ev);
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Cancel given events.
    *
    * @Param $gid       gid of event.
    * @Param $eids      eids (csv) of events. If empty, get all.
    * @Param $by        Whom to blame.
    * @Param $reason    Why?
    *
    * @Returns      status and error message.
 */
/* ----------------------------------------------------------------------------*/
function cancelEvents(string $gid, string $eids, string $by='HIPPO', string $reason=""): array
{
    $ret = ['success'=>true, 'msg' => ''];

    if(! $reason)
        $reason = "No reason was given by admin. So rude!";

    // Get events to cancelled.
    $events = [];
    if( ! $eids )
        $events = getEventsByGroupId($gid, 'VALID', 'today');
    else
        foreach(explode(',', $eids) as $eid)
            $events[] = getEventsById($gid, $eid);

    // Cancel them all.
    foreach($events as $ev)
        cancelThisEvent($ev);

    $res['msg'] .= "Admin ($by) has successfully cancelled 
        " . count($events) . " events.";

    // Send email to 
    $to = getEmailById($events[0]['created_by']);
    $subject = "You confirmed booking(s) have been cancelled by Admin ($by)";

    $body = p("Dear " . loginToText($to));
    $body .= p("Following booked events have been cancelled. Following reason is given.");
    $body .= p($reason);
    $body .= p("If this is a mistake, you know whom to blame ;-).");

    foreach ($events as $ev) 
        $body .= eventToShortHTML($ev);

    if(! sendHTMLEmail($body, $subject, $to))
        $ret['msg'] .= 'Could not notify user.';

    return $ret;
}

?>
