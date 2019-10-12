<?php

require_once __DIR__ . '/methods.php';

/*
 * Assign AWS
 */
function assignAWS(string $speaker, string $date, string $venue=""): array
{

    $res = [ 'success'=>false, 'msg'=>''];

    if(! $venue)
        $venue = getDefaultAWSVenue($date);

    if(  $speaker && getLoginInfo( $speaker ) && strtotime( $date ) > strtotime( '-7 day' ) )
    {
        $aws = getUpcomingAWSOfSpeaker( $speaker );
        if( $aws )
        {
            $res['msg'] = "$speaker already has AWS scheduled. Doing nothing.";
            return $res;
        }

        $awsID = acceptScheduleOfAWS( $speaker, $date, $venue );
        if( $awsID > 0 )
        {
            $res['success'] = true;
            $res['msg'] = "Successfully assigned";

            // Don't rescheduleAWS. It will change the rest of the 
            // entries for the week.
            // rescheduleAWS( );

            // Send email to user.
            $st = notifyUserAboutUpcomingAWS( $speaker, $date, $awsID );
            if(! $st)
                $res['msg'] .= "Failed to send email to user";

            return $res;
        }
        else
        {
            $res['success'] = false;
            $res['msg'] = "Invalid entry. Probably date ('$date') is in past.";
            return $res;
        }
    }

    $res['msg'] = "Invalid speaker '$speaker' or date '$date' is in past.  Could not assign AWS.";
    return $res;

}

function removeAWS(array $data) : array
{
    $speaker = $data['speaker'];
    $date = $data['date'];
    $res = clearUpcomingAWS( $speaker, $date );
    $piOrHost = getPIOrHost( $speaker );
    $final = ['msg'=>'', 'status'=>false];

    if( $res )
    {
        $final['msg'] = "Successfully cleared upcoming AWS of $speaker on $date.";

        $admin = whoAmI();
        // Notify the hippo list.
        $msg = "<p>Hello " . loginToHTML( $data[ 'speaker' ] ) . "</p>";
        $msg .= "<p>
            Your upcoming AWS schedule has been removed by Hippo admin ($admin).
            If this is a  mistake, please write to acadoffice@ncbs.res.in
            immediately.
            </p>
            <p> The AWS schedule which is removed is the following </p>
";

        $msg .= p( "Following reason was given by admin." );
        $msg .= p( $reason );

        $data = array( );

        $data[ 'speaker' ] = $data[ 'speaker' ];
        $data[ 'date' ] = $data[ 'date' ];

        $msg .= arrayToVerticalTableHTML( $data, 'info' );

        $cclist = "acadoffice@ncbs.res.in,hippo@lists.ncbs.res.in";
        if($piOrHost)
            $cclist .= ",$piOrHost";

        sendHTMLEmail( $msg
            , "Your ($speaker) AWS schedule has been removed from upcoming AWSs"
            , $to = getLoginEmail( $data[ 'speaker' ] )
            , $cclist 
        );
        $final['status'] = true;
        return $final;
    }
    return $final;
}

?>
