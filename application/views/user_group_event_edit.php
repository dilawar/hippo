<?php

include_once BASEPATH . '/autoload.php';

echo printInfo('You are editing a confirmed booking. You can only modify 
    title, description, is public event, and class.
    To change venue, date or time you have to cancel this one and book again.
');

$events = getEventsByGroupId($gid);

// We only edit once request and all other in the same group should get
// modified accordingly.
$event = $events[0];
echo '<form method="post" action="' . site_url('user/private_event_edit') . '">';
echo dbTableToHTMLTable(
    'events',
    $event,
    'title,description,class,is_public_event'
);
echo '<input type="hidden" name="response" value="UPDATE GROUP" />';
echo '</form>';

echo goBackToPageLink('user');
