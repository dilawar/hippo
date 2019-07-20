<?php

require_once BASEPATH.'autoload.php';
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
    $table .= '<form action="jc_admins_action" method="post" >';
    $table .= '<button type="submit" name="response" title="Remove admin" 
                value="Remove Admin"><i class="fa fa-trash fa-2x"></i></button>';
    $table .= '</td>';
    $table .= '<input type="hidden" name="jc_id" value="' . $admin['jc_id'] . '" />';
    $table .= '<input type="hidden" name="login" value="' . $admin['login'] . '" />';
    $table .= '</form>';

    if( ($i + 1) % 4 == 0 )
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

echo '<form action="jc_admins_action" method="post">';
echo dbTableToHTMLTable( 'jc_subscriptions', $default, $editable, $action);
echo '</form>';

echo goBackToPageLink( "adminacad/home", "Go back" );

?>
