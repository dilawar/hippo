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

?>
