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
        $request = getBookingRequestOfTalkId($talk['id']);
        $ev = getEventsOfTalkId($talk['id']);
        if(! $request &&  ! $ev)
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

?>
