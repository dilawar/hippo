<?php 

require_once BASEPATH.'autoload.php';
require_once __DIR__.'/snippets/pi_specialization.php';

mustHaveAnyOfTheseRoles( Array( 'ADMIN', 'AWS_ADMIN' ) );

echo userHTML( );


if( ! array_key_exists( 'login', $_POST ) )
{
    echo printInfo( "You didn't select anyone. Go back and select someone." );
    goBackToPageLink( 'admin' );
    exit;
}

// Update $_POST 
if( $_POST[ 'response' ] == "Add New" )
{
    echo ' <h1>Creating new user profile</h1> ';
    $_POST[ 'created_on' ] = dbDateTime( 'now' );
    $res = insertIntoTable( 'logins'
        , "id,title,roles,joined_on,eligible_for_aws,status,first_name,last_name"
        . ",login,valid_until,laboffice,email,created_on"
        , $_POST );
    if( $res )
    {
        echo flashMessage( "Successfully added a new login" );
        redirect('admin');
    }
    else
        echo printWarning( "Failed to add a new user. " . goBackToPageLinkInline('admin') );

}
else if( $_POST[ 'response' ] == 'Delete' )
{
    $user = $_POST[ 'login' ];
    echo printInfo( "Deleting $user" );
    $res = deleteFromTable( 'logins', 'login', array( 'login' => $user ) ); 
    if( $res )
    {
        echo flashMessage( "Successfully deleted $user." );
        redirect( 'admin' );
    }
}
else if( $_POST['response'] == 'Update' )
{
    echo ' <h1>Update user profile</h1> ';
    // When updating the table, remain here on this page. Admin may wants to
    // update more.
    $toUpdate = array( 'roles', 'title', 'joined_on', 'eligible_for_aws'
                , 'laboffice',  'status', 'valid_until', 'alternative_email' 
            );
    $res = updateTable( 'logins', 'login', $toUpdate, $_POST ); 
    if( $res )
        echo printInfo(
            "Successfully updated user profile." . goBackToPageLinkInline( "admin" ) 
            );
}

$default = getUserInfo( $_POST['login'] );
$buttonVal = 'Update';

if( ! $default )
{
    $default = getUserInfoFromLdap( $_POST[ 'login' ] );
    if( ! $default )
    {
        echo printWarning( "Invalid username. I did not find anyone named " .
            $_POST[ 'login' ] . " on LDAP server" 
        );
        redirect( 'admin' );
    }

    $default[ 'login' ] = $_POST[ 'login' ];
    $buttonVal = 'Add New';
}

echo '<form method="post" action="#">';
echo dbTableToHTMLTable(
    'logins', $default
    , Array( 'alternative_email', 'roles', 'status'
                , 'title', 'eligible_for_aws', 'joined_on'
                , 'valid_until' , 'laboffice', 'specialization', 'pi_or_host'
            ) 
    , $buttonVal
    );

echo  '<br/><br/>';
echo '<button type="submit" name="response" value="Delete">Delete User!</button>';
echo '</form>';

echo goBackToPageLink( 'admin', 'Go back' );

?>
