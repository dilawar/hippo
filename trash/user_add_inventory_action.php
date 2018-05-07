<?php
include_once 'header.php';
include_once 'database.php';
include_once 'methods.php';
include_once 'tohtml.php';
include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles('USER' );
echo userHTML( );

// Add new item.

if( __get__( $_POST, 'response', '' ) == 'Add New Item' )
{
    $res = insertIntoTable( 'inventory'
        , 'id,common_name,exact_name,vendor,quantity_with_unit,description'
            . ',status,last_modified_on,edited_by,owner'
        ,  $_POST
        );
    if( $res )
    {
        echo printInfo( "Successfully added inventory item." );
        goBack( "user_add_inventory.php", 1 );
        exit;
    }
}
else if( __get__( $_POST, 'response', '' ) == 'delete' )
{
    echo printInfo( "Removing inventory item" );
    $res = updateTable( 'inventory'
        , 'id', 'status',  array( 'id' => $_POST['id'], 'status' => 'DELETED' )
        );
    if( $res )
    {
        echo printInfo( "Successfully removed inventory item" );
        goBack( "user_add_inventory.php", 1 );
        exit;
    }
}
else if( __get__( $_POST, 'response', '' ) == 'DO_NOTHING' )
{
    echo printInfo( "Doing nothing" );
    goBack( "user_add_inventory.php", 0 );
    exit;
}
else
{
    echo printInfo( "unknow operation " . $_POST[ 'response' ] );
}

echo goBackToPageLink( "user_add_inventory.php", "Go Back" );

?>
