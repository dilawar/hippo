<?php
include_once( "header.php" );
include_once( "database.php" );

if( strcasecmp($_POST['response'], 'submit' ) == 0 )
{
    $res = updateRequestGroup( $_POST['gid'] ,  $_POST );
    if( $res )
        echo printInfo( "Successfully updated request" );
    else
        echo printWarning( "Failed to update update request" );

    if( array_key_exists( 'go_back_to', $_GET ) )
        goToPage( $_GET[ 'go_back_to' ], 1 );
    else
        goToPage( "user_show_requests.php", 1 );
}
?>
