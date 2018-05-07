<?php

include_once 'header.php';
include_once 'database.php';
include_once 'tohtml.php';
include_once 'methods.php';
include_once 'mail.php';
include_once 'actions/jc_admin.php';

echo userHTML( );

// If current user does not have the privileges, send her back to  home
// page.
if( ! isJCAdmin( $_SESSION[ 'user' ] ) )
{
    echo printWarning( "You don't have permission to access this page" );
    echo goToPage( "user.php", 2 );
    exit;
}

if( __get__( $_POST, 'response', '' ) == 'Add' )
{
    // Add new members
    $logins = $_POST[ 'logins'];
    $logins = preg_replace( '/\s+/', ',', $logins );

    $logins = explode( ',', $logins );

    $anyWarning = false;
    foreach( $logins as $login )
    {
        $login = explode( '@', $login )[0];

        if( ! getLoginInfo( $login ) )
        {
            echo printWarning( "$login is not a valid id. Ignoring " );
            $anyWarning = true;
            continue;
        }

        $_POST[ 'status' ] = 'VALID';
        $_POST[ 'login' ] = $login;
        $res = insertOrUpdateTable( 'jc_subscriptions'
            , 'jc_id,login', 'status', $_POST );

        if( ! $res )
            $anyWarning = true;
        else
        {
            echo printInfo( "$login is successfully added to JC" );
            // TODO: Notify user that he/she has been added to JC?
        }
    }

    if( ! $anyWarning )
    {
        //goToPage( "user_jc_admin.php", 1 );
        //exit;
    }
}
else if( $_POST['response'] == 'DO_NOTHING' )
{
    echo printInfo( "User cancelled operation." );
    goToPage( "user_jc_admin.php", 0 );
    exit;
}
else if( $_POST['response'] == 'delete' )
{
    echo printInfo( "Removing user from subscription list" );
    $_POST[ 'status' ] = 'UNSUBSCRIBED';
    $res = updateTable( 'jc_subscriptions', 'login,jc_id', 'status', $_POST );
    if( $res )
    {
        echo printInfo( ' ... successfully removed ' . $_POST[ 'login' ] );
        goToPage( 'user_jc_admin.php', 1 );
        exit;
    }
}
else if( $_POST['response'] == 'Assign Presentation' )
{
    $anyError = false;
    if( strtotime( $_POST[ 'date' ]) < strtotime( 'today' ) )
    {
        echo printWarning( "You cannot assign JC presentation in past." );
        echo printInfo( " Assignment date: " . humanReadableDate( $_POST[ 'date' ] ) );
    }
    else
    {
        $login = getLoginID( $_POST[ 'presenter' ] );

        $loginInfo = getLoginInfo( $login );
        if( ! $loginInfo )
        {
            $presenter = $_POST[ 'presenter' ];
            echo printWarning( "I could not find $presenter in database. Invalid user?" );
            $anyError = true;
        }
        else
        {
            $res = assignJCPresentationToLogin( $_POST['presenter'],  $_POST );
            if( $res )
            {
                echo printInfo( 'Assigned user ' . $_POST[ 'presenter' ] .
                    ' to present a paper on ' . dbDate( $_POST['date' ] )
                );
            }
        }
    }

    if( ! $anyError )
    {
        //goToPage( 'user_jc_admin.php', 1 );
        //exit;
    }
}
else if( $_POST[ 'response' ] == 'Remove Presentation' )
{
    $_POST[ 'status' ] = 'INVALID';
    $res = updateTable( 'jc_presentations', 'id', 'status', $_POST );

    if( $res )
    {
        $data = getTableEntry( 'jc_presentations', 'id', $_POST );
        $to = getLoginEmail( $data[ 'presenter' ] );
        $cclist = 'hippo@ncbs.res.in,jccoords@ncbs.res.in';

        $subject = $data[ 'jc_id' ] . ' | Your presentation date has been removed';
        $msg = '<p>
            Your presentation scheduled on ' . humanReadableDate( $data['date'] )
            . ' has been removed by JC coordinator ' . $_SESSION[ 'user' ]
            . '</p>';

        $msg .= '<p> If it is a mistake, please contant your JC coordinator. </p>';
        $res = sendHTMLEmail( $msg, $subject, $to, $cclist );
        if( $res )
        {
            echo printInfo( "Successfully invalidated entry." );
            goToPage( 'user_jc_admin.php', 1 );
            exit;
        }
    }
}
else if( $_POST[ 'response' ] == 'Remove Incomplete Presentation' )
{
    $res = deleteFromTable( 'jc_presentations', 'id', $_POST );
    if( $res )
    {
        echo printInfo( "Successfully deleted entry!" );
        goToPage( 'user_jc_admin.php', 1 );
        exit;
    }
}
else
{
    echo alertUser( "Response " . $_POST[ 'response' ] . ' is not known or not
        supported yet' );
}

echo goBackToPageLink( 'user_jc_admin.php', 'Go Back' );
