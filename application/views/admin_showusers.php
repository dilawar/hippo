<?php

require_once BASEPATH.'autoload.php';

// mustHaveAllOfTheseRoles( array( 'ADMIN' ) );

$logins = getLogins();

echo '<h1>List of valid users.</h1>';

echo printInfo("Total logins " . count($logins). ".");

$table = '<table border="0" class="info sortable">';
$hide =  'alternative_email,email,roles,created_on,last_login,valid_until,institute,status';

$sampleRow = $logins[0];
// $sampleRow['index'] = '';
array_unshift($sampleRow, 'index');

$table .= arrayToTHRow($sampleRow, 'info', $hide);
foreach ($logins as $i => $login) {
    if ($login['status'] == 'EXPIRED') {
        continue;
    }

    $loginName = $login[ 'login' ];
    $table .= '<tr>';
    $table .= arrayToRowHTML($login, 'info', $hide, true, false);
    $table .= "<td> 
        <form method=\"post\" action=\"" . site_url("admin/addupdatedelete") . "\">
        <input type=\"hidden\" name=\"login\" value=\"$loginName\" />
        <button name=\"edit\" value=\"edit\">Edit</button> </td>
        </form>
        ";
    $table .= '</tr>';
}
$table .= '</table>';

echo "<div style='font-size:x-small'> $table </div> ";

echo goBackToPageLink("admin", "Go back");
