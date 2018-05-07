<?php

include_once 'methods.php';
include_once 'check_access_permissions.php';

mustHaveAllOfTheseRoles( array( 'AWS_ADMIN' ) );

if( __get__( $_POST, 'response', '' )  == 'update_pi_or_host' )
{
    // Show only this user.
    $login = $_POST[ 'login' ];
    $pi = $_POST[ 'pi_or_host' ];
    if( $login )
    {
        $res = updateTable( 'logins', 'login', 'pi_or_host', $_POST );
    }

    goToPage( "admin_acad_aws_speakers.php", 1 );
    exit( );
}

echo goBackToPageLink( "admin_acad_aws_speakers.php", "Go Back" );

?>
