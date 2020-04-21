<?php
require_once BASEPATH. 'autoload.php';
echo userHTML();

$ref = $controller ?? 'admin';

$action = 'add';
$faculty = getFaculty();
$facultyMap = array( );
$default = array( );
$specialization = array( );

$symbDelete = '<i class="fa fa-trash-o"></i>';

foreach ($faculty as $fac) {
    $facultyMap[ $fac[ 'email' ] ] = $fac;
    $specialization[ $fac[ 'specialization' ] ] = 0;
}

$facultyEmails = array_keys($facultyMap);
$specialization = array_keys($specialization);
echo printNote('To update a faculty, first search him/her.
    <br />
    To remove a faculty from the list, select <tt>STATUS</tt> to <tt>INVALID</tt>.
    ');
?>

<script type="text/javascript" charset="utf-8">
$( function() {
    var emails = <?php echo json_encode($facultyEmails) ?>;
    var specialization = <?php echo json_encode($specialization) ?>;
    $( "#faculty" ).autocomplete( { source : emails }); 
    $( "#faculty_specialization" ).autocomplete( { source : specialization }); 
    $( "#faculty" ).attr( "placeholder", "valid email id" );
    $( "#faculty_specialization" ).attr( "placeholder", "Specialization" );
});
</script>

<?php

echo '<form method="post" action="#">
    Email of facutly <input id="faculty" name="faculty_email">
    <button class="btn btn-primary"
        type="submit" name="response" value="search">Search</button>
    </form>';

$facEmail = __get__($_POST, 'faculty_email', '');
if ($facEmail) {
    $faculty = getTableEntry('faculty', 'email', ['email'=>$facEmail]);
    if ($faculty) {
        $default = array_merge($default, $faculty);
        $action = 'Update';
    }
}

echo '<br/><br/>';

$default[ 'modified_on' ] = dbDateTime('now');

echo '<form method="post" action="'.site_url("$ref/faculty_task"). '">';
echo dbTableToHTMLTable(
    'faculty',
    $default,
    array( 'email', 'first_name', 'middle_name', 'last_name'
    , 'status', 'specialization', 'affiliation', 'url', 'institute' ),
    $action
);

// If we are updating, do give an delete button.
if ($action == 'submit') {
    echo '<button type="submit" name="response" value="delete">' .
            $symbDelete . '</button>';
}

echo "</form>";

echo goBackToPageLink("$ref/home");

echo '<div class="h2">List of active faculty</div>';

$hide = 'created_on,modified_on,status,email,first_name,middle_name,last_name';
$faculty = getTableEntries('faculty', 'first_name,affiliation', "status='ACTIVE'");

foreach ($faculty as &$fac) {
    $fac = array_merge(['name'=> arrayToName($fac) . '<br/> ' . $fac['email']], $fac);
}

$table = '<table class="info">';
$table .= arrayHeaderRow($faculty[0], 'row', $hide);
foreach ($faculty as $fac) {
    $table .= arrayToRowHTML($fac, 'row', $hide);
}
$table .= '</table>';

echo $table;
echo goBackToPageLink("$ref/home");

?>
