<?php

require_once BASEPATH . 'autoload.php';

if (!isset($gid)) {
    $gid = $_POST['gid'];
}

if (!isset($goback)) {
    $goback = 'user/home';
}

$editable = ['title', 'description', 'vc_url:url', 'url'];

echo printInfo(
    'You can only change ' . implode(', ', $editable)
    . ' here. If you want to change other fields, you have to delete 
    this request a create a new one.'
);

$requests = getRequestByGroupId($gid);
// We only edit once request and all other in the same group should get
// modified accordingly.
$request = $requests[0];
echo '<form method="post" action="' . site_url("user/edit_booking_of_talk_submit/$goback") . '">';
echo dbTableToHTMLTable('bookmyvenue_requests', $request, $editable);
echo '</form>';

echo goBackToPageLink("$goback", 'Go back');
