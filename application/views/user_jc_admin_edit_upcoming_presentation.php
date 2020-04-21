<?php
require_once BASEPATH.'autoload.php';
echo userHTML();

echo '<h1>Edit presentation entry</h1>';

echo printInfo("
    Consider adding <tt>URL</tt>. This should be the place people can find material related
    to this presentation e.g. link to github repo, slideshare, google-drive, dropbox etc. .
    ");

echo printNote("We do not keep backup for your entry!");

// If current user does not have the privileges, send her back to  home
// page.
if (! isJCAdmin(whoAmI())) {
    echo printWarning("How did you manage to come here? You don't have permission to access this page!");
    echo goBackToPageLink("user/home", "Go Home");
    exit;
}

$editables = 'title,description,url,time,venue,other_presenters';

// Edit only date when reschedule button is pressed.
if (__get__($_POST, 'response', '') == 'Reschdule') {
    $editables = 'date';
}

// If only id of the jc is given, create $_POST array by fetching the details.
if (isset($id) && $id) {
    echo p("We are given id of JC entry $id.");
    $_POST = getTableEntry('jc_presentations', 'id', [ 'id' => $id ]);
}

// get default parameters for this JC.
$jcInfo = getJCInfo($_POST['jc_id']);
$venues = getVenuesNames();
$venueSelect = arrayToSelectList('venue', $venues, array(), false, $jcInfo['venue']);
$_POST['venue'] = $venueSelect;

if (__get__($_POST, 'time', '00:00:00') == "00:00:00") {
    $_POST['time'] = $jcInfo['time'];
}

echo '<form action="'. site_url('user/jc_admin_edit_jc_submit') . '" 
    method="post" accept-charset="utf-8">';
echo dbTableToHTMLTable('jc_presentations', $_POST, $editables);
echo '</form>';

echo " <br /> <br /> ";
echo goBackToPageLink('user/jcadmin', 'Go Back');
