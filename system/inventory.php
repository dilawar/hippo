<?php

include_once 'database/base.php';
include_once 'methods.php';

function getUserInventory( $user ) : array
{
    return getTableEntries( 'inventory', 'common_name', "status='VALID'" );
}


function getMyInvetory(  )
{
    $user = whoAmI( );
    return getUserInventory( $user );
}

?>
