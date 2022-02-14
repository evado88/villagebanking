<?php
/**
 * Created by PhpStorm.
 * User: nkole
 * Date: 03/08/2018
 * Time: 5:00 PM
 */


function app_get_comment_query()
{
    $user = $_GET["user"];

    $sql = "SELECT 'Comment' AS  FeatureType, 'Post' AS PostType,
               phone_number, phone_avater, phone_fname, phone_lname, CONCAT_WS(' ',phone_fname,phone_lname) phone_name,
               comment_id, comment_post, ps.comment_parent, comment_src, comment_content,
               u_votes, d_votes,
               IFNULL(f_comments,0) f_replies,
               IFNULL(upv.vote_id,0) comment_uvoted,IFNULL(dnv.vote_id,0) comment_dvoted,
               IFNULL(u_votes,0) u_votes, IFNULL(d_votes,0) d_votes,IFNULL(f_votes,0) f_votes,
               UNIX_TIMESTAMP(comment_posted) AS comment_posted, comment_posteduser,
               ph.last_token
               FROM app_comments ps
               LEFT JOIN (SELECT comment_parent, COUNT(*) f_comments FROM app_comments
                          GROUP BY comment_parent)
               rp ON ps.comment_id=rp.comment_parent
               LEFT JOIN (SELECT vote_comment,
                           SUM(CASE WHEN vote_type=1 THEN 1 WHEN vote_type=2 THEN -1 ELSE 0 END) f_votes,
                           SUM(CASE WHEN vote_type=1 THEN 1 ELSE 0 END) u_votes,
                           SUM(CASE WHEN vote_type=2 THEN 1 ELSE 0 END) d_votes
                           FROM app_comment_votes GROUP BY vote_comment
                          )
               vt ON ps.comment_id=vt.vote_comment
               LEFT JOIN app_comment_votes upv
               ON upv.vote_comment=ps.comment_id AND upv.vote_type=1 AND upv.vote_phone='$user'
               LEFT JOIN app_comment_votes dnv
               ON dnv.vote_comment=ps.comment_id AND dnv.vote_type=2 AND dnv.vote_phone='$user'
               LEFT JOIN v_phones ph ON ph.phone_number=ps.comment_posteduser";

    return $sql;
}

function app_get_comments($task, $addRequest)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"])
        || !isset($_GET["parent"]) || !isset($_GET["post"])
    ) {

        $result->Message = "The specified task '$task' requires that you specify a user," .
            "parent, post, version and token.";

        return $result;

    }

    if ($addRequest) {

        app_add_request("GetComments", $_GET["user"], $_GET["token"], $_GET["version"]);
    }

    $parent = $_GET["parent"];
    $post = $_GET["post"];


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

    $number_post=max_items_per_request;
    $sql = app_get_comment_query()." WHERE ps.comment_post='$post' AND ps.comment_parent='$parent' ORDER BY u_votes DESC, comment_posted DESC LIMIT $number_post OFFSET $offset";


    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $comments = array();

    if ($connectionResult) {

        //success
        $result->Succeeded = true;

        while ($comment = mysqli_fetch_object($connectionResult)) {

            app_process_comment($comment);

            array_push($comments, $comment);
        }

    } else {
        //an error occurred
        $result->Message = "Unable to retrieve comments: " . mysqli_error($conResult->Data);
    }

    $result->Comments = $comments;


    return $result;
}

function app_add_comment_vote($task)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"])
        || !isset($_GET["id"]) || !isset($_GET["vtype"])
    ) {
        $result->Message = "The specified task '$task' requires that you specify a user," .
            " id, vote type, version and token.";

        return $result;

    }

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $user = $_GET["user"];
    $id = $_GET["id"];
    $type = $_GET["vtype"];

    if ($type != 1 && $type != 2) {

        $result->Message = "The specified vote type '$type' is not valid.";
        $result->Succeeded = false;

        return $result;
    }

    $sql = "SELECT vote_id,vote_type FROM app_comment_votes WHERE vote_phone LIKE '$user' and vote_comment LIKE '$id'";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);


    if ($connectionResult) {


        //check if user was found
        if (mysqli_num_rows($connectionResult) > 0) {

            //user already voted on the comment
            //check vote type
            $comments = array();

            while ($item = mysqli_fetch_object($connectionResult)) {

                array_push($comments, $item);
            }

            if ($comments[0]->vote_type == $type) {

                //user wants to remove existing vote
                $result = app_remove_comment_vote($user, $id);

            } else {
                //user wants to change existing vote
                $result = app_insert_comment_vote($user, $id, $type, true);
            }


        } else {

            //user hasnt yet voted on the comment
            $result = app_insert_comment_vote($user, $id, $type, false);
        }

        //get new statistics
        if ($result->Succeeded) {
            //get current details

            $result = app_get_comment(true, $id);


        }

    } else {
        //an error occurred
        $result->Message = "Unable to add comment vote: " . mysqli_error($conResult->Data);
    }


    return $result;
}

function app_insert_comment_vote($user, $comment, $vtype, $voted)
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
        $sql = "UPDATE `app_comment_votes` SET `vote_type`='%s', `vote_posted`=FROM_UNIXTIME('%s') WHERE `vote_phone`='%s' AND `vote_comment`='%s'";

        $sql = sprintf($sql, $vtype, time(), $user, $comment);
    } else {
        //user is yet to vote
        $sql = "INSERT INTO `app_comment_votes`(`vote_comment`,`vote_phone`,`vote_type`, `vote_posted`) VALUES ('%s','%s','%s',FROM_UNIXTIME('%s'))";

        $sql = sprintf($sql, $comment, $user, $vtype, time());
    }

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    if ($connectionResult) {

        //success
        $result->Succeeded = true;

    } else {
        //an error occurred
        $result->Message = "Unable to insert comment vote: " . mysqli_error($conResult->Data);
    }

    return $result;
}

function app_remove_comment_vote($user, $comment)
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    //remove
    $sql = "DELETE FROM `app_comment_votes` WHERE `vote_comment` LIKE '%s' AND `vote_phone` LIKE '%s'";
    $sql = sprintf($sql, $comment, $user);


    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    if ($connectionResult) {

        //success
        $result->Succeeded = true;

    } else {
        //an error occurred
        $result->Message = "Unable to remove comment vote: " . mysqli_error($conResult->Data);
    }

    return $result;
}

function app_insert_comment($task, $addRequest)
{
    $result = new TaskResult();

    if (!isset($_REQUEST["user"]) || !isset($_REQUEST["version"]) || !isset($_REQUEST["token"])
        || !isset($_REQUEST["parent"]) || !isset($_REQUEST["comment"]) || !isset($_REQUEST["post"])
    ) {

        $result->Message = "The specified task '$task' requires that you specify a user," .
            "parent, comment, post, version and token.";

        return $result;

    }

    if ($addRequest) {

        app_add_request("AddComment", $_REQUEST["user"], $_REQUEST["token"], $_REQUEST["version"]);
    }

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $comment = mysqli_real_escape_string($conResult->Data, $_REQUEST["comment"]);;
    $user = $_REQUEST["user"];
    $parent = $_REQUEST["parent"];
    $post = $_REQUEST["post"];


    $result = post_load_post($post);

    if (!$result->Succeeded) {

        return $result;
    }



    $trip = $result->PostsAll[0];


    $processImageResult=app_process_image($user,"comment_image");

    if(!$processImageResult->Succeeded){

        $result->Message=$processImageResult->Message;
        return $result;
    }



    $sql = "INSERT INTO `app_comments`(`comment_post`,`comment_parent`,`comment_src`,`comment_content`,
                                       `comment_posted`,`comment_posteduser`,`comment_img_size`)
                                       VALUES ($post,$parent,".$processImageResult->Data.",'%s',
                                               FROM_UNIXTIME('%s'),'%s','".$processImageResult->Tag."')";

    $sql = sprintf($sql, $comment, time(), $user);

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    if ($connectionResult) {

        //success
        if ($parent == "0") {



            //normal comment
            $result = app_get_comment(true, mysqli_insert_id($conResult->Data));

            if ($result->Succeeded && $trip->post_posteduser != $user && isset($trip->last_token)) {



                $title = "New comment posted on your trip " . $trip->post_from_area . " To " . $trip->post_to_area . " Has a ";
                $message = $comment;


                $res = app_send_user_notification($trip->last_token, $message, $title, $trip->post_id, "comment");

                $result->Data = $res;
            }
        } else {
            //reply
            //send notification to comment owner
            $result = app_get_comment(false, $parent);

            if ($result->Succeeded) {

                $parentComment = $result->Comments[0];

                //ensure you dont send comment to owner of parent comment incase user replied to themselves
                if ($parentComment->comment_posteduser != $user && isset($parentComment->last_token)) {

                    $title = "New reply to your comment " . $parentComment->comment_content;
                    $message = $comment;


                    $res = app_send_user_notification($parentComment->last_token, $message, $title, $trip->post_id, "comment");

                    $result->Data = $res;
                }


            }

        }


    } else {
        //an error occurred
        $result->Message = "Unable to insert comment: " . mysqli_error($conResult->Data);
    }

    return $result;
}

function app_get_comment($addRequest, $id)
{
    $result = new TaskResult();


    if ($addRequest) {

        app_add_request("GetComment: $id", $_GET["user"], $_GET["token"], $_GET["version"]);
    }

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    $sql = app_get_comment_query() .
        " WHERE comment_id=$id";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $comments = array();

    if ($connectionResult) {

        //success
        //check if user was found

        if (mysqli_num_rows($connectionResult) > 0) {

            $result->Succeeded = true;

            while ($article = mysqli_fetch_object($connectionResult)) {

                app_process_comment($article);

                array_push($comments, $article);
            }
        } else {
            $result->Message = "Unable to find comment with the specified id '$id'";
        }


    } else {
        //an error occurred
        $result->Message = "Unable to retrieve comments: " . mysqli_error($conResult->Data);
    }

    $result->Comments = $comments;

    return $result;
}