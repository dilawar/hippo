<?php 

include_once( 'header.php' );
include_once( 'check_access_permissions.php' );
include_once( 'tohtml.php' );
include_once( 'database.php' );

echo userHTML( );

mustHaveAnyOfTheseRoles( Array( 'ADMIN' ) );

//var_dump( $_POST );

if( $_POST[ 'response' ] == "Add New" )
{
    $_POST[ 'created_on' ] = dbDateTime( 'now' );
    $res = insertIntoTable( 'logins'
        , "id,title,roles,joined_on,eligible_for_aws,status,first_name,last_name"
        . ",login,valid_until,laboffice,email,created_on"
        , $_POST );
    if( $res )
    {
        echo printInfo( "Successfully added a new login" );
        goToPage( "admin.php", 1 );
    }
    else
    {
        echo printWarning( "Failed to add a new user" );
        exit;
    }
}
else if( $_POST[ 'response' ] == 'Delete' )
{
    $user = $_POST[ 'login' ];
    echo printInfo( "Deleting $user" );
    $res = deleteFromTable( 'logins', 'login', array( 'login' => $user ) ); 
    if( $res )
    {
        echo printInfo( "Successfully deleted a new login" );
        goToPage( "admin.php", 1 );
    }
}
else
{
    $toUpdate = array( 'roles', 'title', 'joined_on', 'eligible_for_aws'
                , 'laboffice',  'status', 'valid_until', 'alternative_email' 
            );
    $res = updateTable( 'logins', 'login', $toUpdate, $_POST ); 
    if( $res )
    {
        echo printInfo( "Successfully updated : " . implode(',', $toUpdate)  );
        goToPage( 'admin.php', 1 );
        exit;
    }
}
echo goBackToPageLink( 'admin.php', 'Go back' );

?>
