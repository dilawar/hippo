<?php

require_once BASEPATH . 'autoload.php';
echo userHTML();

if (__get__($_POST, 'id', null)) {
    echo '<h1>Reschedule presentation request</h1>';
    $editables = 'date';
    $entry = getTableEntry('jc_requests', 'id', $_POST);
    echo '<form action="' . site_url('user/jc_admin_reschedule_submit') . '" method="post">';
    echo dbTableToHTMLTable('jc_requests', $entry, $editables);
    echo '</form>';
    echo ' <br />';
} else {
    echo printInfo('Invalid request ID.');
}

echo ' <br /> <br /> <br />';
echo goBackToPageLink('user/jcadmin', 'Go Back');
