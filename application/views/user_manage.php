<?php 

include_once( "header.php" );
include_once( "methods.php" );
include_once( "database.php" );

if( $_POST['response'] == 'Delete Request' )
{
    echo printInfo( "deleting request from database" );

}

echo goBackToPageLink( "user.php", "Go back" );

?>
