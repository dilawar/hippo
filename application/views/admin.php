<?php
include_once 'header.php';
include_once( 'check_access_permissions.php' );
include_once( 'tohtml.php' );

mustHaveAllOfTheseRoles( Array( 'ADMIN' ) );

// Get logins. We'll use them to autocomplete the list of users while modifying
// the privileges.
$logins = getLoginIds( );
?>

<!-- Script to autocomplete user -->
<script>
$(function() {
    var logins = <?php echo json_encode( $logins ); ?>;
    $( "#autocomplete_user" ).autocomplete( { source : logins });
});
</script>


<?php
echo userHTML( );

if( ! requiredPrivilege( 'ADMIN' ) )
{
    echo printWarning( "You are not listed as ADMIN" );
    goToPage( "index.php" );
    exit( 0 );
}

echo '<h2>User management</h2>';
echo "<table class=\"admin\">";
echo '
    <tr>
    <td>Add, Update or Delete user <br>
        <small>Type a login name and press the button.</small>
    </td>
        <td>
            <form method="post" action="admin_add_update_user.php">
            <input id="autocomplete_user" name="login" placeholder="I will autocomplete " >
            <button name="response" value="edit">Add/Update/Delete user</button>
            </form>
        </td>
    </tr>
    <tr>
        <td>Users Info</td>
        <td>
        <a href="admin_show_users.php" target="_blank">Show all users</a>
        </td>
    </tr>
    ';

echo "</table>";

echo '<h2>Email management</h2>';
echo '
    <table class="admin">
    <tr>
        <td>Manage email templates</td>
        <td>
            <a href="admin_manages_email_templates.php">Manage Email template</a>
        </td>
    </tr>
    </table>
    ';


echo "<h2>Database management </h2>";

echo '
    <table class="admin">
        <tr>
            <td>Add/Update faculty</td>
            <td>
                <a href="admin_manages_faculty.php">Manage faculty</a>
            </td>
        </tr>
        <tr>
            <td>Add/Update holidays</td>
            <td>
                <a href="admin_manages_holidays.php">Manage holidays</a>
            </td>
        </tr>
    </table>
    ';


echo ' <br /> <br />';
echo '<h1>Hippo Configuration</h1>';

echo '<table><tr><td>';

echo '<h1>Update the config table</h1>';
$editable = 'id,value,comment';
$default = array( );
echo ' <form action="admin_action.php" method="post" accept-charset="utf-8">';
echo dbTableToHTMLTable( 'config', $default, $editable, 'Add Configuration' );
echo '</form>';

echo '</td><td>';
echo printInfo( "Current Hippo configuration is following." );
echo showConfigTableHTML( );

echo '</td></tr></table>';

echo goBackToPageLink( 'user.php', 'Go back' );

?>
