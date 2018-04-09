<?php

include_once 'header.php' ;
include_once 'check_access_permissions.php' ;
include_once 'tohtml.php' ;
include_once 'database.php' ;
include_once 'methods.php';
include_once 'mail.php';


echo userHTML( );

mustHaveAnyOfTheseRoles( Array( 'AWS_ADMIN' ) );

$toUpdate = 'title,joined_on,eligible_for_aws,status,pi_or_host';
$res = updateTable( 'logins', 'login', $toUpdate, $_POST );
if( $res )
{
    $login = $_POST[ 'login' ];

    echo printInfo( "Successfully updated profile. " );
    echo printInfo( "Notifying $login by email." );

    // Get previous status of student.
    $msg = initUserMsg( $login );
    $msg .= "<p>Your Hippo profile has been updated by Academic Admin.</p>";
    $msg .= "<p>You current profile is following </p>";
    $msg .= arrayToVerticalTableHTML(
        getTableEntry( 'logins', 'login', array( 'login' => $login ) )
        , 'profile'
    );

    $msg .= "<p>If there is any mistake, please contact academic office. You can
            also update your profile after login to Hippo
            </p>";

    $subject = "Your Hippo profile has been updated by admin";
    $to = getLoginEmail( $login );
    $cc = 'hippo@lists.ncbs.res.in';
    sendHTMLEmail( $msg, $subject, $to, $cc );

    // Rerun the scheduling script every time a change is made.
    rescheduleAWS( );

    goToPage( 'admin_acad.php', 1 );
    exit;
}

echo goBackToPageLink( 'admin_acad.php', 'Go back' );

?>
