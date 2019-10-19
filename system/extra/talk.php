<?php
require_once BASEPATH.'/extra/methods.php';

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Fetch a booking by id. The booking must be created by the same 
    *            user as the caller. ADMIN can fetch any random talk.
    *
    * @Param $talkid
    * @Param array
    *
    * @Returns  array with talk and associated request/event (if found).
 */
/* ----------------------------------------------------------------------------*/
function getTalkWithBooking($talkid, string $who): array
{
    $roles = getRoles($who);
    if(in_array('BOOKMYVENUE_ADMIN', $roles) || in_array('ACAD_ADMIN',$roles))
        $talk = getTableEntry('talks', 'id,status', ["id"=>$talkid, "status"=>"VALID"]);
    else
        $talk = getTableEntry('talks', 'id,status,created_by'
        , ["id"=>$talkid, "status"=>"VALID", 'created_by'=>$who]
        );

    $talk['booking_status'] = 'UNSCHEDULED';
    $talk['booking'] = [];
    $req = getBookingRequestOfTalkId($talkid);
    if($req)
    {
        $talk['booking'] = $req;
        $talk['booking_status'] = 'PENDING';
    }
    $ev = getEventsOfTalkId($talkid);
    if($ev)
    {
        $talk['booking'] = $ev;
        $talk['booking_status'] = 'CONFIRMED';
    }
    return $talk;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Updated this talk.
    *
    * @Param array
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function updateThisTalk(array $data): array
{
    $msg = '';
    $res = updateTable( 'talks', 'id'
        , 'class,host,coordinator,title,description'
        , $data 
    );

    if( $res )
    {
        $msg .= printInfo( 'Successfully updated entry' );
        // Now update the related event as wel.
        $event = getEventsOfTalkId( $data[ 'id' ] );
        $tableName = 'events';
        if(! $event)
        {
            $event = getBookingRequestOfTalkId( $data[ 'id' ] );
            $tableName = 'bookmyvenue_requests';
        }

        if($event)
        {
            $res = updateTable( $tableName, 'external_id'
                , 'title,description'
                , array( 'external_id' => "talks." . $data[ 'id' ] 
                    , 'title' => talkToEventTitle( $data )
                    , 'description' => $data['description'])
                );
            if($res)
                $msg .= "Successfully updated associtated event.";
        }
    }

    return ['msg'=>$msg, 'success'=>$res];
}

?>
