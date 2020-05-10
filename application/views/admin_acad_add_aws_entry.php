<?php

require_once BASEPATH . 'autoload.php';
echo userHTML();

$logins = getLoginIds();

?>

<script type="text/javascript" charset="utf-8">
$(function() {
    var logins = <?php echo json_encode($logins); ?>;
    $( "#autocomplete_user" ).autocomplete( { source : logins }); 
});
</script>

<?php

// We are using a generic function to create table. We need to add user name as
// well.

echo '<h2>Add new AWS entry.</h2>';

echo '<form method="post" action="adminacad/aws_entry_submit"> ';
echo '<br />';
echo editableAWSTable(-1, [], $withlogin = true);
echo '</form>';

echo goBackToPageLink('adminacad/home', 'Go back');

?>
