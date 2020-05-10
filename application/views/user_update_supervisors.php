<?php
require_once BASEPATH . 'autoload.php';
echo userHTML();

$rows = array_merge(getSupervisors(), getFaculty());
$tcms = [];
$tcmEmails = [];
foreach ($rows as $row) {
    if (in_array($row['email'], $tcmEmails)) {
        continue;
    }

    array_push($tcmEmails, $row['email']);
    $tcms[$row['email']] = $row;
}

?>

<script type="text/javascript" charset="utf-8">
// Autocomplete speaker.
$( function() {
    var tcms = <?php echo json_encode($tcms); ?>;
    var emails = <?php echo json_encode($tcmEmails); ?>;
    $( "#supervisors_email" ).autocomplete( { source : emails }); 
    $( "#supervisors_email" ).attr( "placeholder", "autocomplete" );

    // Once email is matching we need to fill other fields.
    $( "#supervisors_email" ).attr( "placeholder", "autocomplete" );
    $( "#supervisors_email" ).autocomplete( {
        source : emails, focus : function( ) { return false; } 
    }).on( 'autocompleteselect', function( e, ui ) 
        {
            var email = ui.item.value;
            $('#supervisors_first_name').val( tcms[ email ]['first_name'] );
            $('#supervisors_middle_name').val( tcms[ email ]['middle_name'] );
            $('#supervisors_last_name').val( tcms[ email ]['last_name'] );
            $('#supervisors_affiliation').val( tcms[ email ]['affiliation'] );
            $('#supervisors_url').val( tcms[ email ]['url'] );
        });
});

</script>

<?php

echo '<h3>Adding a supervisor</h3>';

echo printInfo('A supervisor is idenfitied by his/her email address. 
    It must be correct. I send them notification before your AWS.
    ');

echo '<form id="add_supervisor" method="post" 
    action="' . site_url('user/update_supervisor_submit') . '">';
echo '<br>';

echo '<p> Except <tt>URL</tt>, and <tt>MIDDLE NAME</tt>, all fields are mandatory</p>';
echo dbTableToHTMLTable(
    'supervisors',
    $defaults = [],
    $editables = 'email,first_name,middle_name,last_name,affiliation,url'
);
echo '</form>';

echo '<h3>List of supervisors/TCM members in my database</h3>';

$count = 0;
echo '<table class="info"><tr>';
foreach ($rows as $row) {
    if (0 == $count % 4) {
        echo '</tr><tr>';
    }
    echo '<td>' . loginToText($row) . '</td>';
    ++$count;
}
echo '</tr></table>';

echo goBackToPageLink('user/aws', 'Go back to AWS');

?>
