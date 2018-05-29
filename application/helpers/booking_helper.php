<?php

require_once BASEPATH . 'autoload.php';

function register_talk_and_optionally_book(array $data) : string
{
    $msg = verifyRequest( $data );
    if( $msg != "OK" )
        return $msg;

    // Generate repeat pattern from days, week and month repeat patter. If we 
    // are coming here from quickbook.php, it may not be here.
    if( array_key_exists( 'day_pattern', $data ) )
    {
        // Only lab-meet and JC are allowed more than 12 months. For others its 
        // 6 months max.
        $nMonths = intval( __get__( $data, 'month_pattern', 6) );
        if( $data[ 'class' ] == 'LAB MEETING' || $data[ 'class' ] == 'JOURNAL CLUB MEETING' ) if( $nMonths > 12 )
            $nMonths = 12;
        else
            if( $nMonths > 6 )
                $nMonths = 6;

        $data[ 'month_pattern'] = "$nMonths";

        $repeatPat = constructRepeatPattern( 
            $data['day_pattern'], $data['week_pattern'] , $data['month_pattern']
        );

        if( $repeatPat )
            echo "<pre>Repeat pattern $repeatPat </pre>";

        $data['repeat_pat']  = $repeatPat;
    }

    $data['timestamp']  = dbDateTime( 'now' );
    $gid = submitRequest( $data );

    if( $gid )
    {
        $userInfo = getLoginInfo( whoAmI() );
        $userEmail = $userInfo[ 'email' ];
        $msg = initUserMsg( whoAmI() );

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
        return "OK";
    }

    return "Your request could not be submitted. Please notify the admin.";
}


?>
