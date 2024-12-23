<?php


$request_body = file_get_contents("php://input");
$request_json = json_decode($request_body, true);
$action = $request_json["action"];
$payload = $request_json["payload"];
$result = $action($payload);
echo json_encode($result);

function response($success, $message, $data = null){
    return [
        'success' => $success,
        'message' => $message,
        'data' => $data
    ];
}

function put_access_token($payload)
{
    $fragment = $payload["fragment"];
    $public_ip = $payload["public_ip"];

    $matches = null;
    if (!preg_match('/(?<=access_token=)\w+/', $fragment, $matches)) {
        return response(false, "Cannot find access_token");
    }
    $access_token_short_live = $matches[0];

    // exchange
    $url = "https://graph.facebook.com/v20.0/oauth/access_token?fb_exchange_token=$access_token_short_live&grant_type=fb_exchange_token&client_id=532015669635491&client_secret=dca7fdc1534bb30a6e2f7dd09d9bef37&";
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $res = curl_exec($curl);
    $json = json_decode($res, 1);
    if (!isset($json['access_token'])) {
        return response(false, "Exchange long live access_token fail");
    }
    $access_token_long_live = $json['access_token'];
    file_put_contents($public_ip, $access_token_long_live);
    return response(true, 'Put access_token success');
}
function take_access_token($payload) {
    $public_ip = $payload['public_ip'];
    $access_token = file_get_contents($public_ip);
    if ($access_token === false) {
        return [
            'success' => false,
            'message' => 'Cannot find access_token'
        ];
    }
    else{
        unlink($public_ip);
        return [
            'success' => true,
            'message' => 'Success',
            'data' => $access_token
        ];
    }
}
