<?php

include_once 'check_access_permissions.php';
include_once 'database.php';
include_once 'tohtml.php';

mustHaveAllOfTheseRoles( array( 'ADMIN', 'AWS_ADMIN' ) );
echo userHTML( );

$logins = getLoginIds( );

?>

<script type="text/javascript" charset="utf-8">
$(function() {
    var logins = <?php echo json_encode( $logins ); ?>;
    $( "#autocomplete_user" ).autocomplete( { source : logins }); 
});
</script>

<?php


// We are using a generic function to create table. We need to add user name as  
// well.

echo '<form method="post" action="admin_acad_add_aws_entry_submit.php"> ';
echo '
    <table>
    <tr>
        <td>Login ID for which following AWS entry is being created </td>
        <td><input name="speaker" id="autocomplete_user" 
            placeholder="I will autocomplete" > </td>
    </tr>
    </table>
    ';
echo '<br />';
echo editableAWSTable( );
echo '</form>';

echo goBackToPageLink( 'admin_acad.php', 'Go back' );
exit(0);

?>
