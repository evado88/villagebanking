<?php


function set_user_time_zone()
{
    $result = new TaskResult();

    if (isset($_REQUEST["user"])) {


        $user = $_REQUEST["user"];

        $uresult = app_does_user_exist($user);

        if ($uresult->Succeeded) {

            $result->Succeeded = true;

            if ($uresult->Data) {

                //this is a registered user making the request
                //set timezone to their device timezone

                $profile = $uresult->User;

                date_default_timezone_set($profile->phone_timezone);
            } else {
                //this user is not yet registered
                //use default timezone
                date_default_timezone_set('Africa/Lusaka');

            }
        } else {
            $result->Message = "Unable to set the timezone: " . $uresult->Message;

            echo json_encode($result);
            die();
        }


    } else {

        $result->Message = "The user is required for setting the timezone but has not been specified.";

        echo json_encode($result);
        die();
    }
}

function core_check_access_key()
{
    $result = new TaskResult();

    if (isset($_REQUEST["key"])) {

        if ($_REQUEST["key"] != MY_ZAMBIA_APP_KEY) {

            $result = new TaskResult();
            $result->Message = "The specified access token key is incorrect.";

            echo json_encode($result);
            die();
        }
    } else {

        $result->Message = "The access token key is required and has not been specified.";

        echo json_encode($result);
        die();
    }
}

function core_log_out($task)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"])) {

        $result->Message = "The specified task '$task' requires that you specify" .
            " the user, version and token.";

        return $result;
    }

    //Log request
    app_add_request($task, $_REQUEST["user"], $_REQUEST["token"], $_REQUEST["version"]);

    //Sign out
    $resultLogout = app_add_request($task, $_REQUEST["user"], MY_ZAMBIA_LOG_OUT_TOKEN, $_REQUEST["version"]);


    //get rows
    if ($resultLogout->Succeeded) {

        $result->Succeeded=true;
        $result->Message ="The user ".$_REQUEST["token"]." has been successfully logged out from the current device.";

    }else{

        //an error occurred
        $result->Message = "Unable to remove account: " . $resultLogout->Message;
    }

    return $result;
}


function core_hand_shake($task)
{
    $result = new TaskResult();

    if (isset($_REQUEST["user"]) && isset($_REQUEST["version"]) && isset($_REQUEST["token"])) {

        $result = app_get_server_handshake();

        app_add_request("HandShake", $_REQUEST["user"], $_REQUEST["token"], $_REQUEST["version"]);
    } else {
        $result->Message = "The specified task '$task' requires that you specify" .
            " the user, version and token.";
    }

    return $result;
}

function core_register_user($task, $addRequest)
{
    $result = new TaskResult();

    if (isset($_REQUEST["user"]) && isset($_REQUEST["fname"]) && isset($_REQUEST["lname"])
        && isset($_REQUEST["version"]) && isset($_REQUEST["token"]) && isset($_REQUEST["timezone"])
        && isset($_REQUEST["age"]) && isset($_REQUEST["gender"])
    ) {

        if ($addRequest) {

            app_add_request($task, $_GET["user"], $_GET["token"], $_GET["version"]);
        }

        $result = app_register_user($_REQUEST["user"], $_REQUEST["fname"], $_REQUEST["lname"],
            $_REQUEST["email"],
            $_REQUEST["address"], $_REQUEST["town"], $_REQUEST["timezone"],
            $_REQUEST["age"], $_REQUEST["gender"]);


    } else {

        $result->Message = "The specified task '$task' requires that you specify a user," .
            "first name, last name, age, gender, timezone, version and token.";
    }

    return $result;
}

function core_post_trip($task, $addRequest)
{
    $result = new TaskResult();

    if (isset($_REQUEST["user"]) && isset($_REQUEST["local"])
        && isset($_REQUEST["from_district"]) && isset($_REQUEST["to_district"])
        && isset($_REQUEST["from_area"]) && isset($_REQUEST["to_area"])
        && isset($_REQUEST["date"]) && isset($_REQUEST["seats"])
        && isset($_REQUEST["price"]) && isset($_REQUEST["car"])
        && isset($_REQUEST["age"]) && isset($_REQUEST["gender"])
        && isset($_REQUEST["version"]) && isset($_REQUEST["token"])
    ) {

        if ($addRequest) {

            app_add_request($task, $_GET["user"], $_GET["token"], $_GET["version"]);
        }

        $result = app_post_trip($_REQUEST["user"], $_REQUEST["local"],
            $_REQUEST["from_district"], $_REQUEST["to_district"],
            $_REQUEST["from_area"], $_REQUEST["to_area"],
            $_REQUEST["date"], $_REQUEST["age"], $_REQUEST["gender"],
            $_REQUEST["seats"], $_REQUEST["price"], $_REQUEST["car"]);


    } else {

        $result->Message = "The specified task '$task' requires that you specify a user," .
            "province (to and from), district (to and from), area (to and from), gender, age,
            seats, price, car, version and token.";
    }

    return $result;
}

function core_no_task()
{

    $result = new TaskResult();
    $result->Message = "A task has not been specified. All API requests must specify a task.";

    echo json_encode($result);
}

function core_invalid_task($task)
{

    $result = new TaskResult();
    $result->Message = "The specified task '$task' is invalid. Please specify a valid task.";

    echo json_encode($result);
}