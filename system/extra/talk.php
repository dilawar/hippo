<?php
require_once BASEPATH.'/extra/methods.php';

function getTalkIfMineOrAdmin(string $talkid, string $who) : array
{
    $roles = getRoles($who);
    $talk = [];
    if(in_array('BOOKMYVENUE_ADMIN', $roles) || in_array('ACAD_ADMIN',$roles))
        $talk = getTableEntry('talks', 'id,status', ["id"=>$talkid, "status"=>"VALID"]);
    else
        $talk = getTableEntry('talks', 'id,status,created_by'
        , ["id"=>$talkid, "status"=>"VALID", 'created_by'=>$who]
        );
    return $talk;
}

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
    $talk = getTalkIfMineOrAdmin($talkid, $who);
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
        , 'class,host,host_extra,coordinator,title,description'
        , $data 
    );

    if( $res )
    {
        $msg .= 'Successfully updated entry.';
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
                , 'class,title,description'
                , ['external_id' => "talks." . $data[ 'id' ] 
                    , 'title' => talkToEventTitle( $data )
                    , 'description' => $data['description']
                    , 'class' => $data['class']
                ]);
            if($res)
                $msg .= "Successfully updated associtated event.";
        }
    }

    return ['msg'=>$msg, 'success'=>$res];
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Remove a talk given by talk id.
    *
    * @Param $id
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function removeThisTalk(string $id, string $who) : array
{
    $talk = getTalkIfMineOrAdmin($id, $who);
    if( ! $talk)
        return ['success'=>false, 'msg'=>"Access denied for talk id $id."];

    $ret = ['success'=>true, 'msg'=>'', 'html'=>''];

    // Delete this entry from talks.
    $res = deleteFromTable( 'talks', 'id', $talk );
    if($res)
    {
        $ret['msg'] .= 'Successfully deleted talk';
        $externalId = getTalkExternalId( $id );
        $events = getTableEntries( 'events'
            , 'external_id', "external_id='$externalId' AND status='VALID'" 
        );
        $requests = getTableEntries( 'bookmyvenue_requests'
            , 'external_id', "external_id='$externalId' AND status='PENDING'" 
        );
        foreach($events as $e)
        {
            $ret['html'] .= arrayToTableHTML( $e, 'info' );
            $e[ 'status' ] = 'CANCELLED';
            // Now cancel this talk in requests, if there is any.
            $res = updateTable( 'events', 'external_id', 'status', $e );
        }

        foreach( $requests as $r )
        {
            $ret['html'] .= arrayToTableHTML( $r, 'info' );
            $r[ 'status' ] = 'CANCELLED';
            $res = updateTable( 'bookmyvenue_requests', 'external_id', 'status', $r);
        }

        // /* VALIDATION: Check the bookings are deleted  */
        $events = getTableEntries( 'events'
            , 'external_id', "external_id='$externalId' AND status='VALID'"
        );
        $requests = getTableEntries( 'bookmyvenue_requests'
            , 'external_id', "external_id='$externalId' AND status='VALID'"
        );
        assert( ! $events );
        assert( ! $requests );
        $ret['msg'] .= "Successfully deleted related events/requests.";
    }
    else
    {
        $ret['success'] = false;
        $ret['msg'] .= "Failed to delete the talk.";
    }
    // Remote this talk and also remove its events/request.
    return $ret;
}

?>
