<?php

function addNewTransportationSchedule(array $data): array
{
    $ret = ['msg'=>'', 'success'=>false];

    // Add a new schedule.
    $keys = 'pickup_point,drop_point,day';
    $keys .= ',trip_start_time,trip_end_time,vehicle';
    foreach(explode(',', $keys) as $key) 
    {
        if(! $data[$key])
        {
            $ret['msg'] .= "Missing key $key";
            $ret['status'] = false;
            return $ret;
        }
    }

    if(strtotime($data['trip_end_time']) <= strtotime($data['trip_start_time']))
    {
        $ret['success'] = false;
        $ret['msg'] .= "Trip end-time is before trip start time.";
        return $ret;
    }

    $data['id'] = getUniqueID('transport');
    $data['last_modified_on'] = dbDateTime('now');
    $data['edited_by'] = getLogin();
    insertIntoTable('transport', "id,$keys,created_by,comment,last_modified_on", $data);
    $ret['success'] = true;
    return $ret;
}

?>
