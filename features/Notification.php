<?php
/**
 * Created by PhpStorm.
 * User: nkole
 * Date: 12/05/2019
 * Time: 12:24 PM
 */
function app_send_user_notification($token, $message, $title, $post, $type)
{
    if($token==MY_ZAMBIA_LOG_OUT_TOKEN){

        $res=new TaskResult();
        $res->Message="Unable to send notification to this user because they re not logged on to a device";
        return $res;
    }

    $url = 'https://fcm.googleapis.com/fcm/send';

    $fields = array(
        'to' => $token,
        'collapse_key' => 'type_a',
        'notification' => array(
            "body" => $message,
            "title" => $title,
            "post" => $post,
            "type" => $type,
        ),
        'data' => array(
            "body" => $message,
            "title" => $title,
            "post" => $post,
            "type" => $type,
        )
    );

    $fields = json_encode($fields);

    $headers = array(
        'Authorization: key=' . MY_ZAMBIA_PROMOSYS_SERVER_KEY,
        'Content-Type: application/json'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

    $result = curl_exec($ch);

    curl_close($ch);

    return json_decode($result);

}
function app_send_multiple_notification($tokens, $message, $title, $post, $type)
{
    $url = 'https://fcm.googleapis.com/fcm/send';

    $fields = array(
        'registration_ids' => $tokens,
        'collapse_key' => 'type_a',
        'notification' => array(
            "body" => $message,
            "title" => $title,
            "post" => $post,
            "type" => $type,
        ),
        'data' => array(
            "body" => $message,
            "title" => $title,
            "post" => $post,
            "type" => $type,
        )
    );

    $fields = json_encode($fields);

    $headers = array(
        'Authorization: key=' . MY_ZAMBIA_PROMOSYS_SERVER_KEY,
        'Content-Type: application/json'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

    $result = curl_exec($ch);

    curl_close($ch);

    return json_decode($result);

}

function app_send_topic_notification($topic, $message, $title, $post, $type)
{
    $url = 'https://fcm.googleapis.com/fcm/send';

    $fields = array(
        'condition' => "'$topic' in topics",
        'notification' => array(
            "body" => $message,
            "title" => $title,
            "post" => $post,
            "type" => $type,
        ),
        'data' => array(
            "body" => $message,
            "title" => $title,
            "post" => $post,
            "type" => $type,
        )
    );

    $fields = json_encode($fields);

    $headers = array(
        'Authorization: key=' . MY_ZAMBIA_PROMOSYS_SERVER_KEY,
        'Content-Type: application/json'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

    $result = curl_exec($ch);

    curl_close($ch);

    return json_decode($result);

}
