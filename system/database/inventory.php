<?php

include_once BASEPATH. 'database/base.php';
include_once BASEPATH. 'extra/methods.php';

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
