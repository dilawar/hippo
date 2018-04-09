<?php

include_once 'header.php';
include_once 'tohtml.php' ;
include_once 'database.php';
include_once 'check_access_permissions.php';

mustHaveAllOfTheseRoles( array( 'ADMIN' ) );

$logins = getLogins( );

echo '<h3>Active users </h3>';
echo '<table border="0">';
$i = 0;
foreach( $logins as $login )
{
    if( $login['status'] == 'EXPIRED' )
        continue;

    $i += 1;
    $loginName = $login[ 'login' ];
    echo '<tr>';
    echo "<td>$i</td>";
    echo "<td>";
    echo arrayToTableHTML( $login, 'info', ''
        , array( 'alternative_email', 'email', 'roles', 'created_on', 'last_login' )
    );
    echo "</td>";
    echo "<td> 
        <form method=\"post\" action=\"admin_add_update_user.php\">
        <input type=\"hidden\" name=\"login\" value=\"$loginName\" />
        <button name=\"edit\" value=\"edit\">Edit</button> </td>
        </form>
        ";
    echo '</tr>';
}

echo '</table>';

echo goBackToPageLink( "admin.php", "Go back" );

?>
