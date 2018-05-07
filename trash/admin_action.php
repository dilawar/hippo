<?php

include_once 'header.php';
include_once 'check_access_permissions.php';
include_once 'tohtml.php';
include_once 'methods.php';

mustHaveAllOfTheseRoles( 'ADMIN' );

echo userHTML( );

if( $_POST['response'] == 'Add Configuration' )
{
    $res = insertOrUpdateTable( 'config'
        , 'id,value,comment', 'value,comment'
        , $_POST );
    if( $res )
    {
        echo printInfo( 'Successfully added new config' );
        goToPage( 'admin.php', 2 );
        exit;
    }

}
else
{
    echo printWarning( 'Invalid response by user' . $_POST['response'] );
}


echo goBackToPageLink( 'admin.php', 'Go back' );

?>
