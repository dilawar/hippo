<?php 

include_once( "header.php" );
include_once( "methods.php" );
include_once( 'tohtml.php' );
include_once( "check_access_permissions.php" );

if( trim( $_POST[ 'email'] ) && trim( $_POST[ 'first_name' ] ) )
{

    $res = insertOrUpdateTable( 'supervisors'
        , 'email,first_name,middle_name,last_name,affiliation,url'
        , 'first_name,middle_name,last_name,affiliation,url'
        , $_POST 
    );

    if( $res )
    {
        echo printInfo( "Successfully added/updated supervisor to list" );
        goBack( 'user_update_supervisors.php', 1 );
        exit;
    }
    else
        echo printWarning( "Could not add/update supervisor" );
}
else
{
    echo printWarning( "Incomplete details. Try again .." );
    goBack( 'user_update_supervisors.php', 1 );
    exit;
}


echo goBackToPageLink( "user_update_supervisors.php", "Go back" );

?>
