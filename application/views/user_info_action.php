<?php

include_once 'header.php';
include_once 'database.php';
include_once 'mail.php';
include_once 'tohtml.php';

// Not all login can be queried from ldap. Let user edit everything.
$res = updateTable( 
        "logins"
        , "login"
        , "valid_until,first_name,last_name,title,specialization" . 
             ",institute,laboffice,joined_on,alternative_email,pi_or_host"
        , $_POST 
    );

if( $res )
{
    echo printInfo( "User details have been updated sucessfully" );
    // Now send an email to user.
    $info = getUserInfo( $_SESSION[ 'user' ] );

    sendHTMLEmail( 
        arrayToVerticalTableHTML( $info, "details" )
        , "Your details have been updated successfully."
        , $info[ 'email' ]
        );

    goToPage( 'user_info.php', 1 );
    exit;
}

echo printWarning( "Could not update user details " );
echo goBackToPageLink( "user_info.php", "Go back" );
exit;

?>
