<?php
/**
    * @Synopsis  Same as submitRequest but slighly modernised for API class. We
    * did not touched the submitRequest function because it might have broke the
    * working interface.
    * TODO: Retire submitRequest function and use this one.
    *
    * @Param $request Array containing all params.
    *
    * @Returns  An array containing status and error message if any.
 */
/* ----------------------------------------------------------------------------*/
function submitBookingRequest( array $request ) : array
{
    $ret = ['success'=> true, 'msg' => 'ok'];

    $hippoDB = initDB();;
    $collision = false;

    if(__get__($request, 'created_by', whoAmI()) === 'HIPPO')
    {
        $res['success'] = false;
        $res['msg'] = 'Could not figure out who is trying to create request.';
        return $res;
    }

    // Check if $request contains 'dates'. If not then check 'repeat_pat'
    $days = __get__($request, 'dates', []);
    if($days && count($days) == 0)
    {
        $repeatPat = __get__( $request, 'repeat_pat', '' );
        if( strlen( $repeatPat ) > 0 )
            $days = repeatPatToDays( $repeatPat, $request[ 'date' ] );
        else
            $days = array($request['date']);
    }

    if(count($days) < 1)
    {
        $ret[ 'msg'] = "I could not generate list of slots for you reuqest";
        $ret['success'] = false;
        return $ret;
    }

    $rid = 0;
    $res = $hippoDB->query( 'SELECT MAX(gid) AS gid FROM bookmyvenue_requests' );
    $prevGid = $res->fetch( PDO::FETCH_ASSOC);
    $gid = intval( $prevGid['gid'] ) + 1;

    $errorMsg = 'MSG FROM HIPPO: ';
    foreach( $days as $day )
    {
        $rid += 1;
        $request[ 'gid' ] = $gid;
        $request[ 'rid' ] = $rid;
        $request[ 'date' ] = $day;

        $collideWith = checkCollision( $request );
        $hide = 'rid,external_id,description,is_public_event,url,modified_by';

        if( $collideWith )
        {
            $errorMsg .= 'Collision with following event/request';
            foreach( $collideWith as $ev )
                $errorMsg .= arrayToTableHTML( $ev, 'events', $hide );
            $collision = true;
            continue;
        }

        $request[ 'timestamp' ] = dbDateTime( 'now' );
        $res = insertIntoTable( 'bookmyvenue_requests'
            , 'gid,rid,external_id,created_by,venue,title,description' .
                ',date,start_time,end_time,timestamp,is_public_event,class'
            , $request
        );

        if( ! $res )
        {
            $errorMsg .= "Could not submit request id $gid";
            $ret['msg'] = $errorMsg;
            $ret['success'] = false;
            return $ret;
        }
    }
    $ret['success'] = true;
    $ret['msg'] = $errorMsg;
    $ret['gid'] = $gid;
    return $ret;
}

?>
