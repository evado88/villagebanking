<?php
/**
 * Created by PhpStorm.
 * User: nkole
 * Date: 14/10/2019
 * Time: 3:42 AM
 */
header('Content-type:application/json;charset=utf-8');

//default to zambian time
date_default_timezone_set('Africa/Lusaka');

require_once("Classes.php");
require_once("ApiFunctions.php");
require_once("ApiHelper.php");
require_once("features/Notification.php");
require_once("features/Posts.php");


function post_get_cron_get_posts()
{

    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();


    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    //echo $time;
    //$currentTime = strtotime('2020-04-14 14:45:00');
    $currentTime = time();

    $remindTime = reminder_minutes * 60;

    $sql = post_get_post_query("All") .
        " WHERE UNIX_TIMESTAMP(post_trip_date) > $currentTime AND UNIX_TIMESTAMP(post_trip_date) - $currentTime <= $remindTime ORDER BY post_trip_date ASC";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);


    //get rows
    $allPosts = array();


    if ($connectionResult) {

        //success
        $result->Succeeded = true;
        $result->PostTotal = mysqli_num_rows($connectionResult);

        while ($post = mysqli_fetch_object($connectionResult)) {

            //process the job
            app_process_post($post);

            //process the job
            array_push($allPosts, $post);
        }

    } else {
        //an error occurred
        $result->Message = "Unable to retrieve posts: " . mysqli_error($conResult->Data);
    }

    $result->PostsAll = $allPosts;

    return $result;
}

function post_process_cron()
{

    //alert for groups starting load shedding
    $result = post_get_cron_get_posts();

    app_add_request("ProcessCron", "Auto", "1", 1);

    $messages = array();

    if ($result->Succeeded) {

        $items = $result->PostsAll;

        if (count($items) > 0) {

            //schedules were found
            foreach ($items as $item) {

                if (isset($item->a_trip_passenger_token_list) && isset($item->last_token)) {

                    //process the schedule

                    $title = "Trip " . $item->post_from_area . " To " . $item->post_to_area . " Starting Soon";
                    $message = "Your trip from " . $item->a_trip_from . " to " . $item->a_trip_to .
                        " is starting in less than 15min.";

                    //get tokens for all passengers
                    $passengerTokens = explode(",", $item->a_trip_passenger_token_list);

                    //add token for trip owner
                    array_push($passengerTokens, $item->last_token);

                    //create a list of valid tokens
                    $validTokens = array();

                    //add passenger and trip owner to valid tokens
                    foreach ($passengerTokens as $token) {

                        //do not add if user has logged out
                        if ($token != MY_ZAMBIA_LOG_OUT_TOKEN) {

                            array_push($validTokens, $token);
                        }
                    }

                    //only send message if there is at least one token

                    if (count($validTokens) > 0) {

                        $res = app_send_multiple_notification($validTokens, $message, $title, $item->post_id, "reminder");
                        array_push($messages, $res);
                    }

                }

            }

            $result->Data = $messages;
            $result->Message = "The cron has been ran successfully for " . count($items) . " trips.";

        } else {
            $result->Message = "The cron did not ran. There are no trips available at the moment.";
        }
    } else {
        $result->Message = "Unable to ran cron for trips: Unable to retrieve trips.";
    }

    app_add_request("ProcessedCron", $result->Message, "1", 1);

    return $result;

}

$result = post_process_cron();

echo json_encode($result);

