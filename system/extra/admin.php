<?php
require_once  __DIR__.'/methods.php';

// Reference: 
// https://firebase.google.com/docs/cloud-messaging/concept-options#notifications_and_data_messages
function sendFirebaseCloudMessage(string $topic, string $title, string $body) : array
{
    // Get the key.
    $key = getConfigValue('HIPPO_FCM_API_KEY');
    $url = "https://fcm.googleapis.com/fcm/send";

    $payload = [ "to" => '/topics/'.$topic
        , "notification" => [ "title" => $title, "body" => $body ]
        , "android" => ["ttl" => "86500s" ]  // Live for a day.
    ];

    $ch = @curl_init($url);
    @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    @curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    @curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    @curl_setopt($ch, CURLOPT_HEADER, true);
    @curl_setopt($ch, CURLOPT_HTTPHEADER
        , ["Content-Type: application/json", "Authorization:key=$key"]
    );
    $result = @curl_exec($ch);
    curl_close($ch);
    return [$result, $payload];
}

?>
