<?php

include_once "header.php" ;
include_once "methods.php" ;
include_once 'tohtml.php' ;
include_once "check_access_permissions.php" ;
include_once 'mail.php';

mustHaveAnyOfTheseRoles( Array( 'USER' ) );

echo userHTML( );

/*****************************************************************************h*
 * Handling $_POST.
 * ****************************************************************************/
if( __get__( $_POST, 'response', 'xxx' ) == 'delete_preference' )
{
    $prefID = $_POST[ 'id' ];
    if( intval( $prefID ) > 0 )
    {
        $res = deleteFromTable( 'aws_scheduling_request', 'id', $_POST );
        if( $res )
        {
            echo alertUser( "Preference has been revoked" );
            $user = loginToText( $_SESSION[ 'user' ] );

            $subject = "$user has revoked AWS preference";
            $body = "<p> $user has deleted his/her AWS preference. 
               AWS admin need to reschedule AWS again. 
                </p> ";
            sendHTMLEmail( $body, $subject, 'hippo@lists.ncbs.res.in' );
            goBack( "user_aws.php", 1 );
            exit;
        }

    }
}

echo goBackToPageLink( "user_aws.php", "Go back" );

?>
