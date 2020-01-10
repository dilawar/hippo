<?php 

/**
    * @brief Add a new talk.
    *
    * @param $data
    *
    * @return Id of talk or empty array.
 */
function addNewTalk( array $data ) : array
{
    $hippoDB = initDB();;
    // Get the max id
    $res = $hippoDB->query( 'SELECT MAX(id) AS id FROM talks' );
    $maxid = $res->fetch( PDO::FETCH_ASSOC);
    $id = intval( $maxid['id'] ) + 1;

    $data[ 'id' ] = $id;
    $res = insertIntoTable( 'talks'
        , 'id,host,class,coordinator,title,speaker,speaker_id,description,created_by,created_on'
        , $data );

    // Return the id of talk.
    if($res)
        return array("id" => $id);
    return array();
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Get my talks which are not scheduled yet.
    *
    * @Param $created_by
    * @Param $unbooked
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function getMyUnscheduledTalks($created_by)
{
    $talks = getTableEntries( 'talks', 'created_on'
        , "status='VALID' AND created_by='$created_by'");

    $results = [];
    foreach($talks as &$talk)
    {
        if(! __get__($talk,'class', ''))
            continue;

        $request = getBookingRequestOfTalkId($talk['id']);
        $ev = getEventsOfTalkId($talk['id']);
        if($request)
            continue;
        if($ev)
            continue;
        $results[] = $talk;
    }
    return $results;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Rreturn all my talks.
    *
    * @Param $login
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function getMyTalks(string $created_by, bool $withBooking=true
    , bool $onlyUnscheduledAndUpcoming=true): array
{
    $talks = getTableEntries( 'talks', 'created_on'
        , "status='VALID' AND speaker_id > '0' AND created_by='$created_by'");

    if(! $withBooking)
        return $talks;

    $results = [];
    foreach($talks as &$talk)
    {
        if(! __get__($talk,'class', ''))
            continue;

        $talk['booking'] = [];
        $talk['booking_status'] = 'UNSCHEDULED';
        $request = getBookingRequestOfTalkId($talk['id']);
        if($request)
        {
            $talk['booking'] = $request;
            $talk['booking_status'] = 'PENDING';
        }

        // Ignore any talk which is deliverd more than 1 days ago.
        $ev = getEventsOfTalkId($talk['id']);
        if($ev)
        {
            if(strtotime($ev['date']) < strtotime('yesterday'))
                continue;
            $talk['booking'] = $ev;
            $talk['booking_status'] = 'CONFIRMED';
        }
        $results[] = $talk;
    }
    return $results;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  Cancel this talk.
    *
    * @Param $talkid
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function cancelTalk( $talkid )
{
    $res = updateTable( 'talks', 'id', 'status'
        , ['id'=>$talkid, 'status'=>'CANCELLED']
    ); 
    return $res;
}

function getVenuesWithStatusOnThisDayAndTime($date, $startTime, $endTime): array
{
    // Fetch all venues allowed for booking.
    $venues = getVenues('id');
    foreach($venues as &$venue)
    {
        $venue['BOOKING_STATUS'] = 'AVAILABLE';
        $venue['BOOKING'] = [];
        $ev = getEventsOnThisVenueBetweenTime($venue['id'], $date, $startTime, $endTime);
        if($ev && count($ev) > 0)
        {
            $venue['BOOKING_STATUS'] = 'BOOKED';
            $venue['BOOKING'] = @eventToText($ev[0]);
        }
        else
        {
            $req = getRequestsOnThisVenueBetweenTime($venue['id'], $date, $startTime, $endTime);
            if($req && count($req) > 0)
            {
                $venue['BOOKING_STATUS'] = 'REQUEST PENDING';
                $venue['BOOKING'] = @eventToText($req[0]);
            }
        }
    }
    return $venues;
}

/* --------------------------------------------------------------------------*/
/**
    * @Synopsis  User photo if available.
    *
    * @Param string
    *
    * @Returns   
 */
/* ----------------------------------------------------------------------------*/
function getUserPhotoB64(string $user): string 
{
    $conf = getConf( );
    $picPath = $conf['data']['user_imagedir'] . '/' . $user . '.jpg';
    if(file_exists($picPath))
        return base64_encode(file_get_contents($picPath));
    return '';
}

?>
