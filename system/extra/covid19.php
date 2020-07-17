<?php

// THis is dysfunctional.
//function updateCovidDataPatient()
//{
//    $raw = file_get_contents(getConfigValue('BBMP_COVID_DATA_URL'));
//    $raw = json_decode($raw, true);
//    $features = __get__($raw, 'features', []);
//
//    $fs = 'pid,longitude,latitude,pstatus,address,date_of_identification';
//    foreach($features as $feature) {
//        $row = [];
//        $attr = $feature['attributes'];
//        $attr = array_change_key_case($attr);
//        $row['longitude'] = $attr['x'];
//        $row['latitude'] = $attr['y'];
//        $row['pid'] = $attr['state_patient_id'];
//        $row['id'] = $attr['sl__no_'];
//        $date = dbDateTime($attr['date_of_identification']/1000);
//        $row['date_of_identification'] = $date;
//        $row['address'] = $attr['address'];
//        $row['pstatus'] = $attr['status'];
//        insertCovidRow($row);
//    }
//    return;
//}

function fetch_resources_bbmp($url, $type='GET', $token='') 
{
    $ch = curl_init();
    $headers = array(
        'Referer: https://analysis.bbmpgov.in/'
        , 'Origin: https://analysis.bbmpgov.in'
        , 'Accept: application/json, text/plain, */*'
        , 'Connection: keep-alive'
        , 'Accept-Language: en-US;en;q=0.5'
        , 'DNT: 1'
    );

    $agent = 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:79.0) Gecko/20100101 Firefox/79.0';
    if($token)
        $headers[] = "Authorization: Bearer $token";

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
    //curl_setopt($ch, CURLOPT_VERBOSE, true);
    if('POST'===$type) {
        curl_setopt($ch, CURLOPT_POST, true);
    }
    else
        curl_setopt($ch, CURLOPT_POST, false);

    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 2);
    
    curl_setopt($ch, CURLOPT_POSTREDIR, 3);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_URL, $url);
    $res = curl_exec($ch);
    curl_close($ch);
    if(is_string($res))
        $res = json_decode($res, true);
    return $res;
}

function updateCovidData()
{
    // get the token.
    $tokenUrl = "https://covid19.quantela.com/qpa/1.0.0/public/token/bbmp.com/6a4d20c0-87dd-556b-9319-ab7147e388d9";
    $res = fetch_resources_bbmp($tokenUrl, 'GET');
    $token = $res["result"]["access_token"];
    $url = 'https://covid19.quantela.com/qpa/1.0.0/public/dashboard/getData/bbmp.com/7WGKgnEBHZrt-aeeDTHg/TmpE-XEBiNslxwBhF2EA';

    $data = fetch_resources_bbmp($url, 'POST', $token);
    if(! $data) {
        return;
    }


    // lets mark pstatus to INACTIVE. 
    executeQueryReadonly("UPDATE IGNORE covid19 SET pstatus='INACTIVE' WHERE pstatus='ACTIVE'");

    // insert of update.
    $fs = 'pid,longitude,latitude,pstatus,address';
    foreach($data["features"] as $entry) {
        $prop = $entry['properties'];
        $row = ['pid' => $prop['CONTAINMENT_ZONE_ID']];
        $coords = $entry["geometry"]["coordinates"];
        $x = $coords[0];
        $y = $coords[1];
        $row['longitude'] = $x;
        $row['latitude'] = $y;
        $row['id'] = getUniqueID('covid19');

        $row['pstatus'] = 'ACTIVE';
        $row['address'] = $prop['WARD_NAME'] . ': ' . $prop['ZONE_NAME'];

        $existing = getTableEntry('covid19', 'longitude,latitude', $row);
        if($existing) {
            // If this SI already exists then update.
            updateTable('covid19', 'id', $fs, $row);
            continue;
        } else {
            $row['date_of_identification'] = dbDateTime('now');
            $row['timestamp'] = dbDateTime('now');
            insertIntoTable('covid19', "id,$fs,date_of_identification", $row);
        }
    }
}

function notifyCovid()
{

}

function test_function() 
{
    $tokenUrl = "https://covid19.quantela.com/qpa/1.0.0/public/token/bbmp.com/6a4d20c0-87dd-556b-9319-ab7147e388d9";
    $res = fetch_resources_bbmp($tokenUrl, 'GET');
    $token = $res["result"]["access_token"];
    $url = 'https://covid19.quantela.com/qpa/1.0.0/public/dashboard/getData/bbmp.com/7WGKgnEBHZrt-aeeDTHg/TmpE-XEBiNslxwBhF2EA';

    $data = fetch_resources_bbmp($url, 'POST', $token);
    if(! $data) {
        return;
    }
}

?>
