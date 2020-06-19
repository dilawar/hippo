<?php

function updateCovidData()
{
    $raw = file_get_contents(getConfigValue('BBMP_COVID_DATA_URL'));
    $raw = json_decode($raw, true);
    $features = __get__($raw, 'features', []);

    $fs = 'pid,longitude,latitude,pstatus,address,date_of_identification';
    foreach($features as $feature) {
        $row = [];
        $attr = $feature['attributes'];
        $attr = array_change_key_case($attr);
        $row['longitude'] = $attr['x'];
        $row['latitude'] = $attr['y'];
        $row['pid'] = $attr['state_patient_id'];
        $row['id'] = $attr['sl__no_'];
        $date = dbDateTime($attr['date_of_identification']/1000);
        $row['date_of_identification'] = $date;
        $row['address'] = $attr['address'];
        $row['pstatus'] = $attr['status'];
        insertOrUpdateTable('covid19', "id,$fs", $fs, $row);
    }
    return;
}

function notifyCovid()
{
    echo "Todo";

}

?>
