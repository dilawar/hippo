<?php

require_once BASEPATH.'autoload.php';

if (! mustHaveAllOfTheseRoles(array( 'ADMIN' ))) {
    echo printWarning("You don't have admin privileges.");
    echo goBackToPageLink("/welcome", "Go back");
    exit;
}

// Get logins. We'll use them to autocomplete the list of users while modifying
// the privileges.
$logins = getLoginIds();
?>

<!-- Script to autocomplete user -->
<script>
$(function() {
    var logins = <?php echo json_encode($logins); ?>;
    $( "#autocomplete_user" ).autocomplete( { source : logins });
});
</script>


<?php
echo userHTML();

if (! requiredPrivilege('ADMIN')) {
    echo printWarning("You are not listed as ADMIN");
    goToPage("index.php");
    exit(0);
}

echo "<table class=\"admin\">";
echo '<tr>
    <td>
        <form method="post" action="'.site_url('admin/addupdatedelete').'">
            <input id="autocomplete_user" name="login" placeholder="I will autocomplete " >
            <button name="response" value="edit">Add/Update/Delete user</button>
        </form>
    </td>
    <td>
        <a class="clickable" href="'.site_url('admin/showusers') .'" target="_blank">Show all users</a>
    </td>
</tr>
    ';

echo '
<tr>
    <td>
        <a class="clickable" href="'.site_url("admin/emailtemplates").'">Manage Email template</a>
    </td>
    <td>
        <a class="clickable" href="'. site_url('admin/faculty') . '">Manage faculty</a>
    </td>
</tr>
<tr>
    <td>
        <a class="clickable" href="'.site_url('admin/holidays') . '">Manage holidays</a>
    </td>
</tr>
<tr>
    <td>
        <a class="clickable" href="'.site_url('admin/notifyfcm') . '">
        Send Notification To App User</a>
    </td>
</tr>
';
echo '</table>';


echo ' <br /> <br />';
echo '<h1>Hippo Configuration</h1>';
$editable = 'id,value,comment';
$default = array( );

echo ' <form action="'.site_url('admin/configuration') .'" method="post">';
echo dbTableToHTMLTable('config', $default, $editable, 'Add Configuration');
echo '</form>';

echo printInfo("Current Hippo configuration is following.");
echo showConfigTableHTML();


echo goBackToPageLink('user', 'Go back');

?>
