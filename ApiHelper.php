<?php

/**
 * @param $type
 */
function app_get_contribution_title($type)
{

    switch ($type) {
        case "0":
        {

            return "Unknown";
        }
        case "1":
        {

            return "Monthly Contribution";
        }
        case "2":
        {

            return "Welfare Contribution";
        }
        case "3":
        {

            return "Penalty Fee Contribution";
        }
        case "4":
        {

            return "Admin Fee Contribution";
        }
        default:
        {

            return "Unknown";
            break;
        }
    }
}

/** Processes an uploaded image
 * @param $user
 * @param $title
 * @return TaskResult
 */
function app_process_image($user, $title)
{
    //echo "The size of files is ".count($_FILES);
    $req = var_export($_REQUEST, true);
    $pos = var_export($_POST, true);
    $fil = var_export($_FILES, true);
    $final = "REQUEST:\n\n$req \n\n\nPOST:\n\n$pos \n\n\nFILE:\n\n$fil";

    $logfile = app_get_root_directory() . "/logs/log" . date("Y-m-d") . ".txt";
    file_put_contents($logfile, $final);


    //insert
    $src = 'NULL';

    //var_dump($_FILES);

    $size = 0;


    $result = new TaskResult();

    if (isset($_FILES['image'])) {

        //save the image
        $targetDir = app_get_root_directory();

        $relativeDir = "/uploads" . date('/Y/M/j');

        $fullDir = $targetDir . $relativeDir;

        //check if folder exists
        if (!is_dir($fullDir)) {

            mkdir($fullDir, 0775, TRUE);
        }

        // Valid extension
        $valid_ext = array('png', 'jpeg', 'jpg');


        $extension = pathinfo($_FILES['image']["name"], PATHINFO_EXTENSION);

        //make jpg the defualt extension, move_uploaded_file doesnt work without one
        if (empty($extension)) {
            $extension = "jpg";
        }

        // Check extension
        if (!in_array(strtolower($extension), $valid_ext)) {

            $result->Message = "The uploaded file has an invalid extension.";
            return $result;
        }

        do {

            $filename = uniqid($user . "_", TRUE) . "_" . date("Y-m-d_H-i-s") . "_$title." . $extension;


            $fileFull = $fullDir . "/" . $filename;

            $src = "'$relativeDir/$filename'";

        } while (file_exists($fileFull));


        $size = $_FILES['image']['size'] / 1000;
        // $save = move_uploaded_file($_FILES['image']['tmp_name'], $fileFull);

        $source = $_FILES['image']['tmp_name'];

        $info = getimagesize($_FILES['image']['tmp_name']);

        list($width, $height) = $info;

        $r = $width / $height; //0.75 (w=3120/h=4160)

        if ($r < 1) {

            //image height is bigger than width i.e. portrait
            if ($height < MY_ZAMBIA_MAX_IMAGE_SIZE) {

                //image height is less than the standard height
                //use the height of the image itself. Width will be less than 960 since height is less than 960 and height > width
                $w = $width;
                $h = $height;

            } else {

                //image height is bigger than or equal than the standard height
                //resize the image height to max height of 960
                //calculate new width by maintaining aspect ratio
                $h = MY_ZAMBIA_MAX_IMAGE_SIZE;
                $w = round(MY_ZAMBIA_MAX_IMAGE_SIZE * $r, 0);

            }


        } elseif ($r > 1) { //1.3333 (w=4160/h=3120)

            //image width is bigger than height i.e. landscape
            if ($width < MY_ZAMBIA_MAX_IMAGE_SIZE) {

                //image width is less than the standard width
                //use the width of the image itself. Height will be less than 960 since width is less than 960 and  width >  height
                $w = $width;
                $h = $height;

            } else {

                //image width is bigger than or equal to the standard width
                //resize the image width to max height of 960
                //calculate new height by maintaining aspect ratio
                $w = MY_ZAMBIA_MAX_IMAGE_SIZE;
                $h = round(MY_ZAMBIA_MAX_IMAGE_SIZE / $r, 0);
            }
        } else {

            //image width is equal to height i.e. landscape
            if ($width < MY_ZAMBIA_MAX_IMAGE_SIZE) {

                $w = $width;
                $h = $height;

            } else {

                $w = MY_ZAMBIA_MAX_IMAGE_SIZE;
                $h = MY_ZAMBIA_MAX_IMAGE_SIZE;

            }

        }


        if ($info['mime'] == 'image/jpeg') {

            $image = imagecreatefromjpeg($source);

        } elseif ($info['mime'] == 'image/gif') {

            $image = imagecreatefromgif($source);

        } elseif ($info['mime'] == 'image/png') {

            $image = imagecreatefrompng($source);

        } else {

            $image = imagecreatefromjpeg($source);
        }


        $dst = imagecreatetruecolor($w, $h);
        imagecopyresampled($dst, $image, 0, 0, 0, 0, $w, $h, $width, $height);


        if ($info['mime'] == 'image/jpeg') {

            $save = imagejpeg($dst, $fileFull, MY_ZAMBIA_IMAGE_QUALITY);

        } elseif ($info['mime'] == 'image/gif') {

            $save = imagegif($dst, $fileFull);

        } elseif ($info['mime'] == 'image/png') {

            $save = imagepng($dst, $fileFull);

        } else {

            $save = imagejpeg($dst, $fileFull, MY_ZAMBIA_IMAGE_QUALITY);
        }

        //echo "INPUT: " . $_FILES['image']['tmp_name'].",FILEFULL: $fileFull, ".
        //    "SRC: $src, STATUS: $save, DIRECTORY: $relativeDir, FILENAME: $filename";

        if (!$save) {

            $result->Message = "Unable to save uploaded image file";

            return $result;

        } else {
            $result->Data = $src;
            $result->Tag = $size;
            $result->Succeeded = true;
            $result->Message = "The iamge has been successfully proceed.";
        }

    } else {

        $result->Message = "You must upload an image to proceed.";
    }


    return $result;
}


/**Process the speciified number
 * @param $number
 */
function app_process_number_format($number)
{
    if ($number > 1000) {

        $x = round($number);

        $x_number_format = number_format($x);
        $x_array = explode(',', $x_number_format);
        $x_parts = array('k', 'm', 'b', 't');
        $x_count_parts = count($x_array) - 1;
        $x_display = $x;
        $x_display = $x_array[0] . ((int)$x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
        $x_display .= $x_parts[$x_count_parts - 1];

        return $x_display;

    }

    return $number;
}

function facebook_time_ago($timestamp)
{

    $time_ago = $timestamp;


    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes = round($seconds / 60);           // value 60 is seconds
    $hours = round($seconds / 3600);           //value 3600 is 60 minutes * 60 sec
    $days = round($seconds / 86400);          //86400 = 24 * 60 * 60;
    $weeks = round($seconds / 604800);          // 7*24*60*60;
    $months = round($seconds / 2629440);     //((365+365+365+365+366)/5/12)*24*60*60
    $years = round($seconds / 31553280);     //(365+365+365+365+366)/5 * 24 * 60 * 60
    if ($seconds <= 60) {
        return "Now";
    } else if ($minutes <= 60) {
        if ($minutes == 1) {
            return "1 min";
        } else {
            return $minutes . " mins";
        }
    } else if ($hours <= 24) {
        if ($hours == 1) {
            return "1 hr";
        } else {
            return $hours . " hrs";
        }
    } else if ($days <= 7) {
        if ($days == 1) {
            return "1 day";
        } else {
            return $days . " days";
        }
    } else if ($weeks <= 4.3) //4.3 == 52/12
    {
        if ($weeks == 1) {
            return "1 week";
        } else {
            return $weeks . " weeks";
        }
    } else if ($months <= 12) {
        if ($months == 1) {
            return "1 month";
        } else {
            return $months . " months";
        }
    } else {
        if ($years == 1) {
            return "1 year";
        } else {
            return $years . " years";
        }
    }
}

/**Adds all required properties and formatting to a post
 * @param $article
 * @return mixed
 */
function app_process_post($article)
{


    if (date("Y-m-d", strtotime('today')) == date("Y-m-d", $article->post_trip_date_unix)) {

        $article->a_trip_date_formatted = date("\T\o\d\a\y \a\\t H:i", $article->post_trip_date_unix);

    } else if (date("Y-m-d", strtotime('yesterday')) == date("Y-m-d", $article->post_trip_date_unix)) {

        $article->a_trip_date_formatted = date("\Y\\e\s\\t\\e\\r\d\a\y \a\\t H:i", $article->post_trip_date_unix);

    } else if (date("Y-m-d", strtotime('tomorrow')) == date("Y-m-d", $article->post_trip_date_unix)) {

        $article->a_trip_date_formatted = date("\T\o\m\o\\r\\r\o\w \a\\t H:i", $article->post_trip_date_unix);

    } else {

        $article->a_trip_date_formatted = date("F j, Y H:i", $article->post_trip_date_unix);
    }

    $article->a_trip_posted_formatted = date("F j, Y H:i", $article->post_posted);

    $article->a_trip_views_formatted = app_process_number_format($article->a_trip_views) . " views";

    $article->a_trip_comments_formatted = app_process_number_format($article->a_trip_comments);

    $article->a_user_up_votes_formatted = app_process_number_format($article->a_user_up_votes);
    $article->a_user_down_votes_formatted = app_process_number_format($article->a_user_down_votes);
    $article->a_user_votes_formatted = app_process_number_format($article->a_user_votes);


    $article->a_trip_up_votes_formatted = app_process_number_format($article->a_trip_up_votes);
    $article->a_trip_down_votes_formatted = app_process_number_format($article->a_trip_down_votes);
    $article->a_trip_votes_formatted = app_process_number_format($article->a_trip_votes);

    $article->a_trip_posted_mysql = date("Y-m-d H:i:s", $article->post_trip_date_unix);

}

function app_process_group($article)
{

    $article->g_posted = date("F j, Y H:i", $article->g_date_posted_unix);
    $article->g_founded = date("F j, Y", $article->g_date_founded_unix);


}

function app_get_age_int($age)
{
    switch ($age) {

        case "18-24 years old":
        {
            $value = 2;
            break;
        }
        case "25-34 years old":
        {
            $value = 3;
            break;
        }
        case "35-44 years old":
        {
            $value = 4;
            break;
        }
        case "45-54 years old":
        {
            $value = 5;
            break;
        }
        case "55-64 years old":
        {
            $value = 6;
            break;
        }
        case "65-74 years old":
        {
            $value = 7;
            break;
        }
        case "75 years or older":
        {
            $value = 8;
            break;
        }
        default:
        {

            $value = 0;
            break;
        }
    }

    return $value;
}

function app_get_gender_int($gender)
{

    switch ($gender) {
        case "Male":
        {
            $value = 2;
            break;
        }
        case "Female":
        {
            $value = 3;
            break;
        }
        default:
        {

            $value = 0;
            break;
        }
    }

    return $value;

}

function app_process_notification($notification)
{
    $notification->post_posted_formatted = date("D, F j, Y H:i", $notification->post_posted_unix);
}

/**Adds all required properties and formatting to a comment
 * @param $job
 * @return mixed
 */
function app_process_comment($comment)
{
    $comment->comment_posted_formatted = facebook_time_ago($comment->comment_posted);

    $comment->f_replies_formatted = app_process_number_format($comment->f_replies);
    $comment->a_user_up_votes_formatted = app_process_number_format($comment->u_votes);

}

function app_process_transaction($transaction)
{
    $transaction->tran_posted_formatted = date("D, F j, Y H:i", $transaction->tran_posted_unix);


}

function app_process_car($car)
{

    $car->car_posteddate_formatted = date("F j, Y H:i", $car->car_posteddate_unix);

}


/**Adds all required properties and formatting to a post
 * @param $article
 * @return mixed
 */
function app_process_merchant($article)
{
    $article->vendor_posteddate_formatted = facebook_time_ago($article->vendor_posteddate);
}