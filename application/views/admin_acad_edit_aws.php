<?php
require_once BASEPATH . 'autoload.php';
echo userHTML();

echo ' <h1>You are updating following AWS entry</h1>';

$aws = getTableEntry('annual_work_seminars', 'id', $_POST);
$editable = 'is_presynopsis_seminar,title,abstract';
echo ' <form action="' . site_url('adminacad/updateaws/submit') . '" method="post"> ';
echo dbTableToHTMLTable('annual_work_seminars', $aws, $editable, 'Update');
echo '</form>';

echo goBackToPageLink('adminacad/home', 'Go Back');

?>

