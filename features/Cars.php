<?php
/**
 * Created by PhpStorm.
 * User: nkole
 * Date: 18/04/2020
 * Time: 6:29 AM
 */

function app_get_car_query()
{

    $sql = "SELECT 'MyZambiaCar' AS  FeatureType, 'Post' AS PostType,
            CONCAT_WS(' ', phone_fname, phone_lname) a_trip_user_names,
            UNIX_TIMESTAMP(car_posteddate) AS car_posteddate_unix,
            car_id, car_phone, car_make, car_model, car_year, car_plate, car_img_filename,
            CONCAT_WS(' ',car_year, car_model) c_car_model,
            IFNULL(f_posts,0) a_posts,
            car_posteduser,
            car_posteddate
            FROM app_cars cs
            LEFT JOIN v_phones ph ON cs.car_posteduser=ph.phone_number
            LEFT JOIN (SELECT post_car, COUNT(*) f_posts FROM app_posts GROUP BY post_car) ps ON ps.post_car=cs.car_id";

    return $sql;
}

function post_get_my_cars($task)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"])) {

        $result->Message = "The specified task '$task' requires that you specify a user," .
            " version, token and Id to retrieve.";

        return $result;
    }

    app_add_request("GetPostsByUser", $_GET["user"], $_GET["token"], $_GET["version"]);

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $offset = 0;

    if (isset($_GET["offset"])) {
        $offset = $_GET["offset"];
    }

    $user = $_GET["user"];
    $limit = max_items_per_request;


    $sql = app_get_car_query() .
        " WHERE cs.car_posteduser='$user' ORDER BY car_posteddate DESC LIMIT $limit OFFSET $offset";


    //get rows

    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $posts = array();


    if ($connectionResult) {

        //success
        $result->Succeeded = true;

        while ($post = mysqli_fetch_object($connectionResult)) {

            //process the car
            app_process_car($post);

            array_push($posts, $post);

        }

    } else {
        //an error occurred
        $result->Message = "Unable to get user cars: " . mysqli_error($conResult->Data);
    }


    $result->Cars = $posts;


    return $result;
}

function post_load_car($id)
{

    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    $sql = app_get_car_query() .
        " WHERE cs.car_id=$id";


    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $posts = array();

    if ($connectionResult) {

        //success
        //check if user was found

        if (mysqli_num_rows($connectionResult) > 0) {

            while ($post = mysqli_fetch_object($connectionResult)) {

                app_process_car($post);

                array_push($posts, $post);
            }

            $result->Succeeded = true;


        } else {
            $result->Message = "Unable to find car with the specified id '$id'";
        }


    } else {
        //an error occurred
        $result->Message = "Unable to retrieve cars: " . mysqli_error($conResult->Data);
    }

    $result->Cars = $posts;


    return $result;
}

function app_save_car($task, $addRequest)
{

    $result = new TaskResult();

    if (!isset($_REQUEST["user"]) || !isset($_REQUEST["version"]) || !isset($_REQUEST["token"]) ||
        !isset($_REQUEST["make"]) || !isset($_REQUEST["model"]) || !isset($_REQUEST["year"]) ||
        !isset($_REQUEST["plate"])
    ) {

        $result->Message = "The specified task '$task' requires that you specify make, model, year, plate, user," .
            " version and token.";

        return $result;

    }

    if ($addRequest) {

        app_add_request($task, $_REQUEST["user"], $_REQUEST["token"], $_REQUEST["version"]);
    }

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    $user = mysqli_real_escape_string($conResult->Data, $_REQUEST["user"]);
    $make = mysqli_real_escape_string($conResult->Data, $_REQUEST["make"]);
    $model = mysqli_real_escape_string($conResult->Data, $_REQUEST["model"]);
    $year = mysqli_real_escape_string($conResult->Data, $_REQUEST["year"]);
    $plate = mysqli_real_escape_string($conResult->Data, $_REQUEST["plate"]);


    if (!is_numeric($year)) {

        $result->Message = "The specified year '$year' is not valid.";
        return $result;
    }

    $processImageResult=app_process_image($user,"car");

    if(!$processImageResult->Succeeded){

        $result->Message=$processImageResult->Message;
        return $result;
    }


    $sql = "INSERT INTO app_cars (car_phone, car_make, car_model, car_year, car_plate,
            car_img_filename,car_img_size, car_posteduser, car_posteddate)
            VALUES ('$user','$make','$model','$year','$plate',$processImageResult->Data,'".
             $processImageResult->Tag."','$user',FROM_UNIXTIME('%s'))";

    $sql = sprintf($sql, time());



    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    if ($connectionResult) {

        //get user
        $carId = mysqli_insert_id($conResult->Data);

        $carResult=post_load_car($carId);

        //check if user exists
        if (!$carResult->Succeeded) {

            //doesnt exist, stop
            return $carResult;

        } else {

            //user exists
            $result->Cars = $carResult->Cars;
            $result->Succeeded = true;
        }

    } else {
        //an error occurred
        $result->Message = "Unable to save car: " . mysqli_error($conResult->Data);
    }

    return $result;
}
