<?php

require_once BASEPATH . 'autoload.php';

// Called from controller.

echo heading('Upcoming AWS', 1);

$dateMap = [];
foreach ($upcomingAWS as $aws) {
    $dateMap[$aws['date']][] = $aws;
}

ksort($dateMap);

$table = '<table class="table table-hover table-condensed">';
foreach ($dateMap as $date => $awses) {
    $table .= '<tr>';
    $table .= '<td>' . humanReadableDate($date) . '</td>';

    foreach ($awses as $aws) {
        $piOrHost = getPIOrHost($aws['speaker']);
        $table .= '<td>' . loginToHTML($aws['speaker'])
            . ' <br /><small><tt>' . $piOrHost . '</tt></small></td>';
    }

    for ($i = 0; $i < 4 - count($awses); ++$i) {
        $table .= '<td></td>';
    }

    $table .= '<td>' . venueToShortText($awses[0]['venue']) . '</td>';
    $table .= '</tr>';
}

$table .= '</table>';
echo $table;

echo heading('List of AWS Speakers', 1);

echo alertUser(
    'If your name is not on this list, please write to '
        . mailto('acadoffice@ncbs.res.in', 'Academic Office') . '.',
    false
);

$table = ' <table class="table table-hover sortable"> ';
$table .= '<tr> <th></th> <th>Name</th> <th>PI/HOST</th> <th>Specialization</th> </tr>';
$index = 0;
foreach ($speakers as $i => $speaker) {
    $login = $speaker['login'];
    $speaker = getLoginInfo($login);

    if (!trim($login)) {
        continue;
    }

    ++$index;
    $name = arrayToName($speaker);
    $pi = getPIOrHost($login);
    $specialization = getSpecialization($login, $pi);

    $row = '<tr>';
    $row = "<td>$index<td>$name ($login)</td> <td>$pi</td> <td>$specialization</td>";
    $row .= '</tr>';
    $table .= $row;
}
$table .= '</table>';
echo $table;

echo goBackToPageLink('info/aws', 'Go back');
