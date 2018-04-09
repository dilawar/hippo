<?php

include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';
include_once 'mail.php';

echo userHTML( );

// If current user does not have the privileges, send her back to  home
// page.
if( ! isJCAdmin( $_SESSION[ 'user' ] ) )
{
    echo printWarning( "You don't have permission to access this page" );
    echo goToPage( "user.php", 2 );
    exit;
}

if( __get__( $_POST, 'response', '' ) == 'submit' )
{

    $_POST[ 'status' ] = 'VALID';

    // In rare case the speaker 'A' may have one invalid entry on date D for
    // which this table is being updated.
    $res = updateTable( 'jc_requests', 'id', 'status,date', $_POST);
    if( $res )
    {
        $entry = getTableEntry( 'jc_requests', 'id', $_POST );
        $presenter = getLoginInfo( $entry[ 'presenter' ] );
        $entryHTML = arrayToVerticalTableHTML($entry, 'info');

        $msg = "<p>Dear " . arrayToName( $presenter ) . "</p>";
        $msg .= "<p>
            Your presentation request has been rescheduled by admin.
            the latest entry is following.
            </p>";
        $msg .= $entryHTML;
        $subject = 'Your presentation request date is changed by JC admin';
        $to = $presenter['email'];
        $res = sendHTMLEmail( $msg, $subject, $to );
        echo printInfo( 'Successfully updated presentation entry.' );
    }
}

else if( __get__( $_POST, 'response', '' ) == 'delete' )
{
    $_POST[ 'status' ] = 'CANCELLED';
    $res = updateTable( 'jc_requests', 'id', 'status', $_POST);
    if( $res )
    {
        $entry = getTableEntry( 'jc_requests', 'id', $_POST );

        $presenter = getLoginInfo( $entry[ 'presenter' ] );
        $entryHTML = arrayToVerticalTableHTML($entry, 'info');

        $msg = "<p>Dear " . arrayToName( $presenter ) . "</p>";
        $msg .= "<p>Your presentation request has been cancelled by admin.
                    the latest entry is following. </p>";
        $msg .= $entryHTML;

        $subject = 'Your presentation request is CANCELLED by JC admin';
        $to = $presenter['email'];
        $res = sendHTMLEmail( $msg, $subject, $to );
        if( $res )
        {
            echo printInfo( 'Successfully updated presentation entry.' );
            goToPage( 'user_jc_admin.php', 1 );
            exit;
        }
    }
}
else if( __get__( $_POST, 'response', '' ) == 'transfer_admin' )
{
    $newAdmin = explode( '@', $_POST[ 'new_admin' ])[0];
    $error = '';

    if( ! getLoginInfo( $newAdmin ) )
    {
        $error =  "Error: $newAdmin is not a valid user.";
        echo printWarning( $error );
    }
    else
    {
        $jcID = $_POST[ 'jc_id'];
        // Check the new owner is already admin of this JC.
        $admins = getJCAdmins( $jcID );

        foreach( $admins as $admin )
        {
            if( $admin[ 'login' ] == $newAdmin )
            {
                $error = "$newAdmin is already ADMIN of this JC.  Please pick someone else.";
                echo printWarning( $error );
                break;
            }
        }

        if( ! $error )
        {
            // Add new user to admin.
            $data = array( 'login' => $newAdmin, 'subscription_type' => 'ADMIN'
                , 'status' => 'VALID' , 'jc_id' => $jcID
            );
            $res = updateTable( 'jc_subscriptions', 'jc_id,login', 'status,subscription_type', $data );

            if( $res )
            {
                echo printInfo( "Sucessfully assigned $newAdmin as admin" );
                $subject = "You have been made ADMIN of $jcID by " . loginToText( whoAmI() );
                $msg = "<p>Dear " . loginToText( $newAdmin ) . "</p>";
                $msg .= "<p>You have been given admin rights to $jcID. In case this is
                    a mistake, " . loginToText( whoAmI( ) ) . ' is to blame!</p>';

                $cclist = 'hippo@lists.ncbs.res.in';
                $to = getLoginEmail( $newAdmin );
                $res = sendHTMLEmail( $msg, $subject, $to, $cclist );
                if( $res )
                    echo printInfo( "New admin has been notified" );
            }

            // Remove myself.
            $data = array( 'login' => whoAmI( ), 'subscription_type' => 'NORMAL'
                , 'status' => 'VALID' , 'jc_id' => $jcID
            );
            $res = updateTable( 'jc_subscriptions', 'login,jc_id', 'subscription_type', $data );

            if( $res )
            {
                echo printInfo( "You are removed from ADMIN list of this JC" );
                goToPage( "user_jc_admin.php", 3 );
                exit;
            }
        }
        else
        {
            echo printErrorSevere( "Some error occurred" );
        }
    }

    if( $error )
        echo goBackToPageLink( 'user_jc_admin.php', 'Go Back' );
}
else if( __get__( $_POST, 'response', '' ) == 'DO_NOTHING' )
{
    goToPage( 'user_jc_admin.php', 1 );
    exit;
}

if( __get__( $_POST, 'id', null ) )
{
    echo '<h1>Edit presentation request</h1>';
    $editables = 'date';
    if( __get__( $_POST, 'response', '' ) == 'Reschedule' )
        $editables = 'date';

    $entry = getTableEntry( 'jc_requests', 'id', $_POST );
    echo '<form action="#" method="post" accept-charset="utf-8">';
    echo dbTableToHTMLTable( 'jc_requests', $entry, $editables );
    echo '</form>';

    echo " <br /> <br /> ";
    echo "<strong>Afer your are finished editing </strong>";
    echo goBackToPageLink( 'user_jc_admin.php', 'Go Back' );
}

?>
