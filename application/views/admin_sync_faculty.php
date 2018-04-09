<?php

include_once( 'header.php' );
include_once( 'database.php' );
include_once( 'check_access_permissions.php' );

mustHaveAnyOfTheseRoles( Array( 'ADMIN' ) );

echo "TODO: Note done yet";
echo goBackToPageLink( "user.php", "Go back" );
exit;

?>

