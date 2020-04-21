<?php
include_once FCPATH . 'system/autoload.php';
mustHaveAllOfTheseRoles(array( 'ADMIN' ));
echo userHTML();

echo '<h3>Add a holiday or non-working day</h3>';

$default = array( );
echo '<form method="post" action="'.site_url("admin/add_holiday").'">';
echo dbTableToHTMLTable(
    'holidays',
    $default,
    'date,description,schedule_talk_or_aws',
    'Add'
);
echo '</form>';
echo '<h3>List of holidays in my database</h3>';

$holidays = getHolidays();
foreach ($holidays as $index => $holiday) {
    echo '<form method="post" action="'. site_url('admin/delete_holiday') . '">';
    echo '<small><table>';
    echo '<tr>';
    echo '<td>' . ($index + 1) . '</td><td>' . arrayToTableHTML($holiday, 'info') . '</td>';
    echo '<td> 
        <input type="hidden" name="date" value="' . $holiday['date'] . '" >
        <input type="hidden" name="description" value="' . $holiday['description'] . '"/>
        <button name="response" value="Delete">Delete</button> 
        </td>';
    echo '</tr>';
    echo '</table></small>';
    echo '</form>';
}

echo goBackToPageLink("admin/home", "Go back");
