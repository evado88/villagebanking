<?php
header('Content-type:application/json;charset=utf-8');

require_once("Classes.php");
require_once("features/Core.php");
require_once("features/Notification.php");
require_once("ApiFunctions.php");

//check access token key
core_check_access_key();

$result = new TaskResult();

if (!isset($_REQUEST["title"]) || !isset($_REQUEST["message"])|| !isset($_REQUEST["group"])) {
    $result->Message = "This requires that you specify a group, title and message";
    echo json_encode($result);
    return;
}

//get connection
$conResult = app_get_database_connection();

if (!$conResult->Succeeded) {

    $result->Message = $conResult->Message;
    echo json_encode($result);
    return;
}

$title = mysqli_real_escape_string($conResult->Data, $_REQUEST["title"]);
$message = mysqli_real_escape_string($conResult->Data, $_REQUEST["message"]);
$group = mysqli_real_escape_string($conResult->Data, $_REQUEST["group"]);

$result->Data = app_send_topic_notification($group, $message, $title, 0, "web");
$result->Succeeded = true;

echo json_encode($result);
