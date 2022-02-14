<?php

header('Content-type:application/json;charset=utf-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ E_WARNING);

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

//include all files which have functions or classes we are using throughout the application
require_once("Classes.php");
require_once("features/Core.php");
require_once("features/Comments.php");
require_once("ApiFunctions.php");

//check access token key
core_check_access_key();

//set user timezone
set_user_time_zone();

//features API
require_once("features/Posts.php");
require_once("features/Account.php");
require_once("features/Notification.php");
require_once("features/Cars.php");

//API functions
require_once("ApiHelper.php");
require_once("ApiTasks.php");


function app_get_root_directory(){

    $path =  dirname(__FILE__);

    return str_replace("\\",'/',$path);
}


