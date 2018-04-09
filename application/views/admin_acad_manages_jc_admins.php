<?php

include_once 'header.php';

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'AWS_ADMIN' ) );

include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';

echo userHTML( );
echo "<h1>Journal Clubs Admins</h1>";

$admins = getTableEntries( 'jc_subscriptions', 'jc_id', "subscription_type='ADMIN'" );
$jcs = getTableEntries( 'journal_clubs', 'id', "status!='INVALID'" );
$jcIDs = array_map( function( $x ) { return $x['id']; }, $jcs );

$table = '<table class="info">';
$table .= '<tr>';
foreach( $admins as $i => $admin )
{
    $table .= "<td>" . $admin['login'] . ' (' . colored($admin['jc_id'], 'darkred') . ') ';

    // Form to detele.
    $table .= '<form action="admin_acad_manages_jc_admins_action.php" method="post" accept-charset="utf-8">';
    $table .= '<button type="submit" name="response" title="Remove admin" 
                value="Remove Admin"><i class="fa fa-trash fa-2x"></i></button>';
    $table .= '</td>';
    $table .= '<input type="hidden" name="jc_id" value="' . $admin['jc_id'] . '" />';
    $table .= '<input type="hidden" name="login" value="' . $admin['login'] . '" />';
    $table .= '</form>';

    if( ($i + 1) % 3 == 0 )
        $table .= '</tr><tr>';
}
$table .= '</tr>';
$table .= '</table>';
echo $table;

echo '<h1> Add new admin </h1>';

$action = 'Add New Admin';
$editable = 'jc_id,login';
$default = array(
    'subscription_type' => 'ADMIN'
    , 'jc_id' => arrayToSelectList( 'jc_id', $jcIDs )
    , 'last_modified_on' => dbDateTime( 'now' )
);

echo '<form action="admin_acad_manages_jc_admins_action.php" method="post" accept-charset="utf-8">';
echo dbTableToHTMLTable( 'jc_subscriptions', $default, $editable, $action);
echo '</form>';

echo goBackToPageLink( "admin_acad.php", "Go back" );

?>
