<?php

define("default_version", "0");
define("default_token", "server");


function app_get_database_connection()
{
    $result = new TaskResult();

    //database configuration
    $configuration = new DatabaseConfig();

    //connect to database
    $conn = mysqli_connect($configuration->Host, $configuration->User,
        $configuration->Password, $configuration->Database);

    if (!$conn) {

        $result->Message = "Unable to connect to the database: " . mysqli_connect_error();

    } else {
        $result->Succeeded = true;
        $result->Data = $conn;
    }

    return $result;
}

function app_get_server_handshake()
{
    $result = new TaskResult();

    $server = new MyZambiaServer();

    $result->Succeeded = true;
    $result->Server = $server;

    $proceed = false;
    //profile
    if ($_GET["user"] != "Unregistered") {

        $userInfo = app_get_user_info($_GET["user"]);

        if ($userInfo->Succeeded) {

            $result->User = $userInfo->User;
            $proceed = true;
        } else {
            $result->Message = $userInfo->Message;
            $result->Succeeded = false;
        }

    } else {

        $proceed = true;
    }


    //config
    if ($proceed) {

        $configInfo = app_get_config_info();

        if ($configInfo->Succeeded) {

            $result->Config = $configInfo->Data;

            if ($_GET["user"] != "Unregistered") {

                $groupResult = post_load_groups($result->User->phone_number);

                if ($groupResult->Succeeded) {

                    $result->Groups = $groupResult->Groups;
                } else {
                    $result->Message = $groupResult->Message;
                    $result->Succeeded = false;
                }

            } else {
                $result->Groups = array();
            }


        } else {
            $result->Message = $configInfo->Message;
            $result->Succeeded = false;
        }

    }


    return $result;
}

function app_get_user_info($userName)
{
    $result = new TaskResult();


    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    $sql = "SELECT * FROM v_phones WHERE phone_number LIKE '$userName'";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $user = null;

    if ($connectionResult) {


        //check if user was found
        if (mysqli_num_rows($connectionResult) > 0) {

            //success
            $result->Succeeded = true;

            $user = mysqli_fetch_object($connectionResult);
            $user->phone_registered_formatted = "Registered " . date("F j, Y", strtotime($user->phone_registered));

        } else {

            //an error occurred
            $result->Message = "The specified user '$userName' could not be found";
        }

    } else {
        //an error occurred
        $result->Message = "Unable to retrieve user: " . mysqli_error($conResult->Data);
    }

    $result->User = $user;

    return $result;
}


/**
 * @param $paymentId
 * @return TaskResult
 */
function app_get_payment_info($paymentId)
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    $sql = "SELECT * FROM v_payments WHERE tran_id LIKE '$paymentId'";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $transaction = null;

    if ($connectionResult) {


        //check if user was found
        if (mysqli_num_rows($connectionResult) > 0) {

            //success
            $result->Succeeded = true;

            $transaction = mysqli_fetch_object($connectionResult);
        } else {

            //an error occurred
            $result->Message = "The specified payment '$paymentId' could not be found";
        }

    } else {
        //an error occurred
        $result->Message = "Unable to retrieve payment: " . mysqli_error($conResult->Data);
    }

    $result->Tag = $transaction;

    return $result;
}
function app_get_loan_info($loanId)
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    $sql = app_get_loans_query() . " WHERE loan_id='$loanId'";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $loan = null;

    if ($connectionResult) {

        //check if user was found
        if (mysqli_num_rows($connectionResult) > 0) {

            //success
            $result->Succeeded = true;

            $loan = mysqli_fetch_object($connectionResult);

        } else {

            //an error occurred
            $result->Message = "The specified loan '$loanId' could not be found";
        }

    } else {
        //an error occurred
        $result->Message = "Unable to retrieve loan: " . mysqli_error($conResult->Data);
    }

    $result->Loan = $loan;

    return $result;
}
function app_get_group_info($groupId)
{
    $result = new TaskResult();


    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    $sql = "SELECT * FROM app_groups WHERE group_id LIKE '$groupId'";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $group = null;

    if ($connectionResult) {


        //check if user was found
        if (mysqli_num_rows($connectionResult) > 0) {

            //success
            $result->Succeeded = true;

            $group = mysqli_fetch_object($connectionResult);

        } else {

            //an error occurred
            $result->Message = "The specified group '$groupId' could not be found";
        }

    } else {
        //an error occurred
        $result->Message = "Unable to retrieve group: " . mysqli_error($conResult->Data);
    }

    $result->Group = $group;

    return $result;
}

function app_get_config_info()
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    $sql = "SELECT config_about,config_privacy_policy,config_topup_url FROM app_config";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $config = null;

    if ($connectionResult) {


        //check if user was found
        if (mysqli_num_rows($connectionResult) > 0) {

            //success
            $result->Succeeded = true;

            $row = mysqli_fetch_object($connectionResult);
            $result->Data = $row;


        } else {

            //an error occurred
            $result->Message = "Configuration data could not be found";
        }

    } else {
        //an error occurred
        $result->Message = "Unable to retrieve config: " . mysqli_error($conResult->Data);
    }


    return $result;
}

function app_insert_view($user, $post)
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $sql = "INSERT INTO `app_views`(`view_post`,`view_phone`, `view_posted`) VALUES ('%s','%s',FROM_UNIXTIME('%s'))";

    $sql = sprintf($sql, $post, $user, time());

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    if ($connectionResult) {

        //success
        $result->Succeeded = true;

    } else {
        //an error occurred
        $result->Message = "Unable to insert view: " . mysqli_error($conResult->Data);
    }

    return $result;
}

function app_add_vote($task)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"])
        || !isset($_GET["id"]) || !isset($_GET["vtype"]) || !isset($_GET["source"])
    ) {
        $result->Message = "The specified task '$task' requires that you specify a user," .
            " post id, vote type, version and token.";

        return $result;

    }

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $user = $_GET["user"];
    $post = $_GET["id"];
    $type = $_GET["vtype"];
    $source = $_GET["source"];

    if ($type != 1 && $type != 2) {

        $result->Message = "The specified vote type '$type' is not valid.";
        $result->Succeeded = false;

        return $result;
    }

    if ($source != 1 && $source != 2) {

        $result->Message = "The specified source '$source' is not valid.";
        $result->Succeeded = false;

        return $result;
    }

    $sql = "SELECT vote_id,vote_type FROM app_votes WHERE vote_phone LIKE '$user' and vote_post LIKE '$post'";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);


    if ($connectionResult) {


        //check if user was found
        if (mysqli_num_rows($connectionResult) > 0) {

            //user already voted on the post
            //check vote type
            $posts = array();

            while ($item = mysqli_fetch_object($connectionResult)) {

                array_push($posts, $item);
            }

            if ($posts[0]->vote_type == $type) {

                //user wants to remove existing vote
                $result = app_remove_vote($user, $post);

            } else {
                //user wants to change existing vote
                $result = app_insert_vote($user, $post, $type, true);
            }


        } else {

            //user hasnt yet voted on the post
            $result = app_insert_vote($user, $post, $type, false);
        }

        //get new statistics
        if ($result->Succeeded) {
            //get current details
            if ($source == 1) {

                $result = post_get_post($task, false, "MyZambiaReview");
            } else if ($source == 2) {

                $result = post_get_post($task, false, "MyZambiaReview");
            }

        }

    } else {
        //an error occurred
        $result->Message = "Unable to add vote: " . mysqli_error($conResult->Data);
    }


    return $result;
}

function app_join_trip($task)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"]) || !isset($_GET["id"])
    ) {
        $result->Message = "The specified task '$task' requires that you specify a user," .
            " trip id, version and token.";

        return $result;

    }

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $user = $_GET["user"];
    $post = $_GET["id"];

    $sql = "SELECT pass_id, pass_trip FROM app_passengers WHERE pass_phone LIKE '$user' and pass_trip LIKE '$post'";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    if (!$connectionResult) {

        //an error occurred
        $result->Message = "Unable to join or cancel trip due to a server error";
        return $result;
    }

    $rows = mysqli_num_rows($connectionResult);

    $tripResult = post_load_post($post);

    if ($tripResult->Succeeded) {

        $trip = $tripResult->PostsAll[0];

        //check if user posted this trip
        if ($trip->post_posteduser == $user) {
            $result->Message = "You cannot join this trip because you own it. You can\'t be driver and passenger at once";
            return $result;
        }

        //check seats
        if ($rows == 0 && $trip->post_seats == $trip->a_trip_passengers) {

            $result->Message = "You cannot join this trip because all seats have been taken";
            return $result;
        }

        //check if user is allowed to make changes
        $tripDate = strtotime($trip->post_trip_date_unix);

        $minutes = round(abs($tripDate - time()) / 60, 2);

        if ($minutes < 15) {

            $result->Message = "You can only join or cancel trips that are starting in 15min or more. This is mean\'t to give the driver time to plan";
            return $result;
        }


    } else {
        $tripResult->Message = "Unable to check details for this trip. Please try again.";
        return $tripResult;
    }


    if ($connectionResult) {


        //check if user was found
        if ($rows > 0) {

            //user already on the trip
            //user wants to cancel the trip
            $result = app_user_cancel_trip($user, $post);


        } else {

            //user not on the trip
            //user wants to join the trip
            $result = app_user_join_trip($user, $post);
        }

        //get new info about post
        if ($result->Succeeded) {

            $msg = $result->Message;

            //get current info
            $result = post_get_post($task, false);
            $result->Message = $msg;

            if ($rows == 0) {

                //send notification to trip owner

                $title = "New passenger joined your trip";
                $msg = "A new passenger has joined your trip starting " . $trip->a_trip_date_formatted . " from " .
                    $trip->a_trip_from . " to " . $trip->a_trip_to;
                $type = "joinTrip";

                $result->Data = app_send_user_notification($trip->last_token, $msg, $title, $trip->post_id, $type);

            }


        }

    } else {
        //an error occurred
        $result->Message = "Unable to join trip due to a server error";
    }


    return $result;
}

function app_add_view($task)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"])
        || !isset($_GET["id"])
    ) {
        $result->Message = "The specified task '$task' requires that you specify a user," .
            " post id, version and token.";

        return $result;

    }

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $user = $_GET["user"];
    $post = $_GET["id"];

    $sql = "SELECT view_id FROM app_views WHERE view_phone LIKE '$user' and view_post LIKE '$post'";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);


    if ($connectionResult) {


        //check if user was found
        if (mysqli_num_rows($connectionResult) > 0) {

            //success
            $result->Succeeded = true;
            $result->Message = "The user has already viewed this post";

        } else {

            //user hasnt yet viewed the post
            $result = app_insert_view($user, $post);
        }

    } else {
        //an error occurred
        $result->Message = "Unable to add view: " . mysqli_error($conResult->Data);
    }


    return $result;
}

function app_insert_vote($user, $post, $vtype, $voted)
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    if ($voted) {

        //user has already voted
        $sql = "UPDATE `app_votes` SET `vote_type`='%s', `vote_posted`=FROM_UNIXTIME('%s') WHERE `vote_phone`='%s' AND `vote_post`='%s'";

        $sql = sprintf($sql, $vtype, time(), $user, $post);
    } else {
        //user is yet to vote
        $sql = "INSERT INTO `app_votes`(`vote_post`,`vote_phone`,`vote_type`, `vote_posted`) VALUES ('%s','%s','%s',FROM_UNIXTIME('%s'))";

        $sql = sprintf($sql, $post, $user, $vtype, time());
    }

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    if ($connectionResult) {

        //success
        $result->Succeeded = true;

    } else {
        //an error occurred
        $result->Message = "Unable to insert vote: " . mysqli_error($conResult->Data);
    }

    return $result;
}

function app_user_join_trip($user, $post)
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    //user is yet to vote
    $sql = "INSERT INTO `app_passengers`(`pass_trip`,`pass_phone`,`pass_status`, `pass_posted`)
            VALUES ('%s','%s','%s',FROM_UNIXTIME('%s'))";

    $sql = sprintf($sql, $post, $user, 1, time());


    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    if ($connectionResult) {

        //success
        $result->Succeeded = true;
        $result->Message = "You have successfully joined this trip!";

    } else {
        //an error occurred
        $result->Message = "Unable to join trip due to server error";
    }

    return $result;
}

function app_user_cancel_trip($user, $post)
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    //remove
    $sql = "DELETE FROM `app_passengers` WHERE `pass_trip` LIKE '%s' AND `pass_phone` LIKE '%s'";
    $sql = sprintf($sql, $post, $user);


    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    if ($connectionResult) {

        //success
        $result->Succeeded = true;
        $result->Message = "You have successfully canceled the trip";

    } else {
        //an error occurred
        $result->Message = "Unable to cancel trip due to server error";
    }

    return $result;
}

function app_remove_vote($user, $post)
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    //remove
    $sql = "DELETE FROM `app_votes` WHERE `vote_post` LIKE '%s' AND `vote_phone` LIKE '%s'";
    $sql = sprintf($sql, $post, $user);


    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    if ($connectionResult) {

        //success
        $result->Succeeded = true;

    } else {
        //an error occurred
        $result->Message = "Unable to remove vote: " . mysqli_error($conResult->Data);
    }

    return $result;
}

function app_update_save($user, $post, $saved)
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $action = "";

    if ($saved) {

        //saved, remove
        $sql = "DELETE FROM `app_saves` WHERE `save_post` LIKE '%s' AND `save_phone` LIKE '%s'";
        $sql = sprintf($sql, $post, $user);
        $action = "remove";

    } else {

        //not saved, save
        $sql = "INSERT INTO `app_saves`(`save_post`,`save_phone`, `save_posted`) VALUES ('%s','%s',FROM_UNIXTIME('%s'))";
        $sql = sprintf($sql, $post, $user, time());
        $action = "add";
    }

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    if ($connectionResult) {

        //success
        $result->Succeeded = true;

    } else {
        //an error occurred
        $result->Message = "Unable to $action save: " . mysqli_error($conResult->Data);
    }

    return $result;
}

function app_add_save($task)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"])
        || !isset($_GET["id"])
    ) {
        $result->Message = "The specified task '$task' requires that you specify a user," .
            " post id, source, version and token.";

        return $result;

    }

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $user = $_GET["user"];
    $post = $_GET["id"];

    $sql = "SELECT save_id FROM app_saves WHERE save_phone LIKE '$user' and save_post LIKE '$post'";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    $msg = null;

    if ($connectionResult) {


        //check if user was found
        if (mysqli_num_rows($connectionResult) > 0) {

            //user already saved this post
            //remove
            $result = app_update_save($user, $post, true);

            $msg = "The post '$post' has been removed for the user '$user'";
        } else {

            //user hasnt yet saved the post
            $result = app_update_save($user, $post, false);
            $msg = "The post '$post' has been saved for the user '$user'";
        }

        //get new statistics
        if ($result->Succeeded) {

            //get current details
            $result = post_get_post($task, false);


            if ($result->Succeeded) {

                $result->Message = $msg;
            }

        }

    } else {
        //an error occurred
        $result->Message = "Unable to process save: " . mysqli_error($conResult->Data);
    }


    return $result;
}

function app_does_user_exist($user)
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $sql = "SELECT * FROM `app_phones` WHERE phone_number LIKE '$user'";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    if ($connectionResult) {

        $result->Succeeded = true;

        //check if user was found
        if (mysqli_num_rows($connectionResult) > 0) {

            //exists
            $result->User = mysqli_fetch_object($connectionResult);
            $result->Data = true;
        } else {

            //doesn't exist
            $result->Data = false;
        }

    } else {
        //an error occurred
        $result->Message = "Unable to check user: " . mysqli_error($conResult->Data);
    }


    return $result;
}

function app_does_group_exist($group)
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $sql = "SELECT * FROM `app_groups` WHERE group_id LIKE '$group'";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    if ($connectionResult) {

        $result->Succeeded = true;

        //check if user was found
        if (mysqli_num_rows($connectionResult) > 0) {

            //exists
            $result->Group = mysqli_fetch_object($connectionResult);
            $result->Data = true;
        } else {

            //doesn't exist
            $result->Data = false;
        }

    } else {
        //an error occurred
        $result->Message = "Unable to check group: " . mysqli_error($conResult->Data);
    }


    return $result;
}

function app_register_user($user, $fname, $lname, $email, $address, $town, $timezone, $age, $gender)
{
    $result = new TaskResult();


    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $uresult = app_does_user_exist($user);


    if (!$uresult->Succeeded) {

        return $uresult;
    }


    $user = mysqli_real_escape_string($conResult->Data, $user);
    $fname = mysqli_real_escape_string($conResult->Data, $fname);
    $lname = mysqli_real_escape_string($conResult->Data, $lname);

    $address = mysqli_real_escape_string($conResult->Data, $address);
    $town = mysqli_real_escape_string($conResult->Data, $town);

    $age = mysqli_real_escape_string($conResult->Data, $age);
    $gender = mysqli_real_escape_string($conResult->Data, $gender);

    $ageInt = app_get_age_int($age);

    if ($ageInt == 0) {

        $result->Message = "The specified age group '$age' is not valid.";
        return $result;
    }

    $genderInt = app_get_gender_int($gender);

    if ($genderInt == 0) {

        $result->Message = "The specified gender group '$gender' is not valid.";
        return $result;
    }

    $timezone = mysqli_real_escape_string($conResult->Data, $timezone);

    $hash = hash('sha256', $user);

    if ($uresult->Data) {

        $result->Message = "User account exists. Updating details for user: $user";
        //user exists
        $sql = "UPDATE `app_phones` SET phone_updated=FROM_UNIXTIME('%s'),
                phone_fname='%s', phone_lname='%s',
                phone_email='%s',
                phone_address='%s', phone_town='%s',
                phone_timezone='%s',
                phone_age='%s', phone_gender='%s'
                WHERE phone_number LIKE '%s'";

        $sql = sprintf($sql, time(), $fname, $lname, $email,
            $address, $town, $timezone, $ageInt, $genderInt, $user);

    } else {
        //user doesnt exist
        $result->Message = "User account doesn't exist. Creating new user: $user";

        $sql = "INSERT INTO `app_phones`(`phone_number`, phone_fname, phone_lname,
            phone_email,phone_balance,
            phone_address, phone_town,
            phone_timezone,phone_age,phone_gender, phone_hash, phone_status,
            phone_updated, phone_registered) VALUES
            ('%s','%s','%s',
             '%s','%s',
             '%s','%s',
             '%s','%s','%s','%s',1,
             FROM_UNIXTIME('%s'),FROM_UNIXTIME('%s'))";

        $sql = sprintf($sql, $user, $fname, $lname,
            $email, NEW_USER_BALANCE,
            $address, $town,
            $timezone, $ageInt, $genderInt, $hash, time(), time());


    }

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    if ($connectionResult) {

        //get profile
        $uresult = app_get_user_info($user);

        if ($uresult->Succeeded) {

            $result->Succeeded = true;
            $result->User = $uresult->User;

        } else {

            $result->Message = $uresult->Message;
        }


    } else {
        //an error occurred
        $result->Message = "Unable to register user: " . mysqli_error($conResult->Data);
    }

    return $result;
}

function app_post_trip($user, $local, $from_district, $to_district,
                       $from_area, $to_area, $date, $age, $gender, $seats, $price, $car)
{
    $result = new TaskResult();


    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $userResult = app_get_user_info($user);

    //check if user exists
    if (!$userResult->Succeeded) {

        //doesnt exist, stop
        return $userResult;
    }


    $user = mysqli_real_escape_string($conResult->Data, $user);
    $local = mysqli_real_escape_string($conResult->Data, $local);

    $from_area = mysqli_real_escape_string($conResult->Data, $from_area);
    $to_area = mysqli_real_escape_string($conResult->Data, $to_area);

    $date = mysqli_real_escape_string($conResult->Data, $date);

    $seats = mysqli_real_escape_string($conResult->Data, $seats);
    $price = mysqli_real_escape_string($conResult->Data, $price);
    $car = mysqli_real_escape_string($conResult->Data, $car);


    $age = mysqli_real_escape_string($conResult->Data, $age);
    $gender = mysqli_real_escape_string($conResult->Data, $gender);

    $configInfo = app_get_config_info();


    if (!$configInfo->Succeeded) {

        return $configInfo;

    } else {
        $config = $configInfo->Data;
    }


    if ($userResult->User->phone_balance < $config->config_price_per_trip) {

        $result->Message = "Your phone balance " . $userResult->User->u_phone_balance .
            " is not enough to post a trip which requires " . $config->c_price_per_trip;
        return $result;
    }


    $sql = "INSERT INTO `app_posts`(post_local, post_trip_date,
            post_from_district, post_to_district,
            post_from_area, post_to_area,
            post_gender, post_age,
            post_seats, post_car,post_price_zm,
            post_posted, post_posteduser,
            post_modified, post_modifieduser) VALUES
            ('%s','%s',
             '%s','%s',
             '%s','%s',
             '%s','%s',
             '%s','%s','%s',
             FROM_UNIXTIME('%s'),'%s',
             FROM_UNIXTIME('%s'),'%s')";

    $sql = sprintf($sql, $local, $date,
        $from_district, $to_district,
        $from_area, $to_area,
        $gender, $age,
        $seats, $car, $price,
        time(), $user,
        time(), $user);


    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    if ($connectionResult) {

        $postId = mysqli_insert_id($conResult->Data);

        //deduct amount
        $result = account_update_user_balance($user, $config->config_price_per_trip, true);

        if ($result) {

            //add purchase
            $result = account_process_user_purchase($user, $postId, $config->config_price_per_trip,
                $userResult->User->phone_balance);

            if ($result->Succeeded) {

                //get profile
                $result = post_load_post($postId);

                if ($result->Succeeded) {

                    $post = $result->PostsAll[0];

                    $userResult = app_get_user_info($user);


                    //check if user exists
                    if (!$userResult->Succeeded) {

                        //doesnt exist, stop
                        return $userResult;
                    } else {
                        $result->User = $userResult->User;

                        //send topic to everyone who is subscribed
                        $title = "New trip from $from_area";
                        $msg = "A new trip starting " . $post->a_trip_date_formatted . " from " . $post->a_trip_from . " has been posted ";
                        $type = "newPost";


                        $result->Data = app_send_topic_notification($post->a_trip_topic, $msg, $title, $postId, $type);
                    }
                }
            }

        } else {

            $result->Message = "Unable to post trip transaction: " . mysqli_error($conResult->Data);
        }


    } else {
        //an error occurred
        $result->Message = "Unable to post trip: " . mysqli_error($conResult->Data);
    }

    return $result;
}


function app_add_request($feature, $user, $token, $version)
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $feature = mysqli_real_escape_string($conResult->Data, $feature);
    $user = mysqli_real_escape_string($conResult->Data, $user);
    $token = mysqli_real_escape_string($conResult->Data, $token);
    $version = mysqli_real_escape_string($conResult->Data, $version);

    $sql = "INSERT INTO `app_requests`(`request_feature`,`request_user`, `request_token`,
            `request_version`,`request_posted`) VALUES ('%s','%s','%s','%s',FROM_UNIXTIME('%s'))";

    $sql = sprintf($sql, $feature, $user, $token, $version, time());

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    if ($connectionResult) {

        //success
        $result->Succeeded = true;

    } else {
        //an error occurred
        $result->Message = "Unable to add request: " . mysqli_error($conResult->Data);
    }

    return $result;
}
