<?php

include_once BASEPATH.'autoload.php';
echo userHTML();

echo '<h1>Edit presentation entry</h1>';

echo printNote("
    Consider adding <tt>URL</tt>. This is the place user can find material related
    to this presentation e.g. link to github repo, slideshare, drive etc..
    ");
echo printNote("We do not keep backup for your entry!");
$editables = 'title,description,url,presentation_url';

echo '<form action="'.site_url("user/jc_update_action") .'" method="post" accept-charset="utf-8">';
echo dbTableToHTMLTable('jc_presentations', $_POST, $editables, 'Save');
echo '</form>';

$res = updateTable('jc_presentations', 'id', 'title,description,url,presentation_url', $_POST);

echo ' <br />';
echo goBackToPageLink('user/jc', 'Go Back');
