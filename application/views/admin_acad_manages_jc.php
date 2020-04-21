<?php
require_once BASEPATH.'autoload.php';
echo userHTML();

$venues = getVenues();

echo "<h1>List of Journal Clubs</h1>";
$jcs = getTableEntries('journal_clubs', 'id', "status!='INVALID'");

echo '<table class="tiles"><tr>';
foreach ($jcs as $i => $jc) {
    echo '<td>';
    echo arrayToVerticalTableHTML($jc, 'info');

    // Form to update.
    echo '<form action="" method="post" accept-charset="utf-8">';
    echo '<button name="response" value="Edit">Edit</button>';
    echo '<input type="hidden" name="id" value="' . $jc['id'] . '" />';
    echo '</form>';

    // Form to detele.
    echo '<form action="'.site_url('adminacad/jc_action').'" method="post">';
    echo '<button name="response" value="Delete">Delete</button>';
    echo '<input type="hidden" name="id" value="' . $jc['id'] . '" />';
    echo '</form>';
    echo '</td>';

    if (($i+1)%3 == 0) {
        echo '</tr><tr>';
    }
}
echo '</tr>';
echo '</table>';
echo goBackToPageLink("adminacad/home", "Go back");

$editables = 'title,status,description,day,time,venue,scheduling_method,send_email_on_days';
$action = 'Add';
$default = array(
    'venue' => venuesToHTMLSelect($venues)
    );

if (__get__($_POST, 'response', '') == 'Edit') {
    $default = getTableEntry('journal_clubs', 'id', $_POST);
    $default[ 'venue' ] = venuesToHTMLSelect($venues, false, 'venue', array( $default['venue'] ));
    $action = 'Update';

    echo printInfo("Please update the table shown below: ");
}

echo "<h1>$action Journal Club </h1>";

echo '<form action="'.site_url('adminacad/jc_action').'" method="post">';
echo dbTableToHTMLTable('journal_clubs', $default, "id,$editables", $action);
echo '</form>';

echo goBackToPageLink("adminacad/home", "Go back");

?>
<script type="text/javascript" charset="utf-8">
    $('#journal_clubs_send_email_on_days').attr( 'placeholder', 'Tue,Fri' );
</script>
