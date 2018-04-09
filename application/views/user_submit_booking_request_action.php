<?php

include_once 'header.php';

include_once 'check_access_permissions.php';
mustHaveAnyOfTheseRoles( array( 'USER' ) );

include_once "header.php" ;
include_once "methods.php" ;
include_once "database.php" ;
include_once "mail.php";
include_once 'tohtml.php';

$msg = verifyRequest( $_POST );

if( $msg == "OK" )
{
    // Generate repeat pattern from days, week and month repeat patter. If we 
    // are coming here from quickbook.php, it may not be here.

    if( array_key_exists( 'day_pattern', $_POST ) )
    {
        // Only lab-meet and JC are allowed more than 12 months. For others its 
        // 6 months max.
        $nMonths = intval( __get__( $_POST, 'month_pattern', 6) );
        if( $_POST[ 'class' ] == 'LAB MEETING' || $_POST[ 'class' ] == 'JOURNAL CLUB MEETING' )
           if( $nMonths > 12 )
                $nMonths = 12;
        else
            if( $nMonths > 6 )
                $nMonths = 6;

        $_POST[ 'month_pattern'] = "$nMonths";

        $repeatPat = constructRepeatPattern( 
            $_POST['day_pattern'], $_POST['week_pattern'] , $_POST['month_pattern']
            );

        if( $repeatPat )
            echo "<pre>Repeat pattern $repeatPat </pre>";

        $_POST['repeat_pat']  = $repeatPat;
    }

    $_POST['timestamp']  = dbDateTime( 'now' );
    $gid = submitRequest( $_POST );

    if( $gid )
    {
        $userInfo = getLoginInfo( $_SESSION[ 'user' ] );
        $userEmail = $userInfo[ 'email' ];
        $msg = initUserMsg( $_SESSION[ 'user' ] );

        $msg .= "<p>Your booking request id $gid has been created. </p>";
        $msg .= arrayToVerticalTableHTML( getRequestByGroupId( $gid )[0], 'request' );
        $msg .= "<p>You can edit/cancel the request anytime you like </p>";

        sendHTMLEmail( $msg
            , "Your booking request (id-$gid) has been recieved"
            , $userEmail 
            );

        // Send email to hippo@lists.ncbs.res.in 
        sendHTMLEmail( "<p>Details are following </p>" . $msg
            , "A new booking request has been created by $userEmail"
            , 'hippo@lists.ncbs.res.in'
            );

        echo printInfo( "Your request has been submitted" );
    }
    else
    {
        echo printWarning( 
            "Your request could not be submitted. Please notify the admin." 
        );
    }
}
else
{
    echo printWarning( "There was an error in request" );
    echo printWarning( $msg );
    echo alertUser( "Complete entry is following" );
    echo arrayToVerticalTableHTML( $_POST, "request" );
    echo goBackToPageLink( "quickbook.php", "Go back" );
    exit;
}

echo goBackToPageLink( "user.php", "Go back" );


?>
