<?php

require_once BASEPATH . 'autoload.php';

echo userHTML();

global $dbChoices;

$ref = 'adminbmv';
if (isset($controller)) {
    $ref = $controller;
}

/* Let user select venues and select the date to block them. */
echo '<h1>Block venues on certain dates and times </h1>';

echo p("Note that venues are immediately blocked. They won't go for approval.");

$venues = getVenues();
$venueSelect = venuesToHTMLSelect($venues, true);
$classSelect = arrayToSelectList(
    'class',
    explode(',', $dbChoices['bookmyvenue_requests.class']),
    []
);

$form = '<form action="' . site_url("$ref/block_venue_submit") . '" method="post" accept-charset="utf-8">';
$table = '<table class="tasks">';
$table .= "<tr> <td> <strong>Select one or more venues </strong> </td><td> $venueSelect </td> </tr>";
$table .= '<tr><td>Date range</td>
            <td> <input type="text" name="start_date" class="datepicker" value="" /> 
             to <input type="text" name="end_date" class="datepicker" value="" /></td></tr>';
$table .= '<tr> <td>Start Time</td>
            <td> <input type="text" name="start_time" class="timepicker" value="" /></td></tr>';
$table .= '<tr> <td>End Time</td>
            <td> <input type="text" name="end_time" class="timepicker" value="" /></td></tr>';
$table .= '<tr> <td>Reason for blocking</td> <td> <input type="text" name="reason" value="" /></td></tr>';
$table .= '<tr> <td>Type of event</td> <td>' . $classSelect . ' </td></tr>';
$table .= '</table>';
$form .= $table;

$form .= ' <br />  <br />';
$form .= '<button class="submit" name="response" value="Block">Block Venues</button>';
$form .= '</form>';
echo $form;
echo ' <br /> <br />';
echo goBackToPageLink("$ref/home", 'Go Back');
