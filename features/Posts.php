<?php
function post_get_investments_query()
{
    $sql = "SELECT  'MyZambiaInvestment' AS  FeatureType, 'Post' AS PostType,
               invcat_id, invcat_name, invtype_name,invtype_id, invcat_posted, invcat_filename,
               IFNULL(invcat_description,'No Notes') invcat_description,
               invcat_modified, invcat_modifieduser
               FROM app_invest_categories cl 
               LEFT JOIN app_invest_types tp ON tp.invtype_id=cl.invcat_type ORDER BY invcat_order";

    return $sql;
}


function post_get_farming_investment_query()
{
    $sql = "SELECT invcat_id,invqnt_id, invcat_name, invtype_name, invcat_posted, 
               FORMAT(invqnt_units,0) invqnt_units,invcat_filename,
               IFNULL(invcat_description,'No Notes') invcat_description, invcat_modified,
               invcat_modifieduser, invqnt_modified, invqnt_posted, invqnt_modifieduser,
               FORMAT(q_estimated_cost,2) q_estimated_cost, 
               FORMAT(q_estimated_cost * invcat_variable_cost * 0.01,2) AS q_variable_cost,
               FORMAT((q_estimated_cost) + (q_estimated_cost * invcat_variable_cost * 0.01),2) AS q_total_cost,
               FORMAT(q_estimated_output,0) q_estimated_output,
               FORMAT(invcat_price_item,2) invcat_price_item, 
               FORMAT(invcat_price_item * q_estimated_output,2) As q_revenue,
               FORMAT(invqnt_units * invcat_price_item * invqnt_bustaxi_rate,2) As b_revenue,   
               FORMAT(invqnt_bustaxi_variablecost,2) invqnt_bustaxi_variablecost, 
               FORMAT(invqnt_bustaxi_rate,2) invqnt_bustaxi_rate,
               FORMAT(invcat_variable_cost,2) invcat_variable_cost,
               FORMAT(invqnt_units * invcat_price_item * invqnt_bustaxi_rate * 0.01 * invcat_variable_cost,2) bvariable_cost
               FROM v_quantities WHERE NOT invtype_id IN (4,5)";

    return $sql;
}

function post_get_government_investment_query()
{
    $sql = "SELECT invcat_id, invqnt_id, invcat_name, invcat_filename,
            FORMAT(q_price,2) q_price, 
            CONCAT(q_period, ' - K', FORMAT(invqnt_units,2)) q_period,
            FORMAT(q_investment_amount,2) q_investment_amount,
            FORMAT(q_discount_income,2) q_discount_income,
            FORMAT(q_coupon_rate,2) q_coupon_rate,
            FORMAT(q_coupon_payment,2) q_coupon_payment,
            FORMAT(q_no_coupon,0) q_no_coupon,
            FORMAT(q_gross_interest,2) q_gross_interest,
            FORMAT(q_wht,2) q_wht,
            FORMAT(q_handling_fee,2) q_handling_fee,
            CASE invtype_id
            WHEN 4 THEN FORMAT(q_discount_income - q_wht - q_handling_fee,2)
            WHEN 5 THEN FORMAT(q_gross_interest - q_wht - q_handling_fee,2) 
            END AS q_net_interest_income,
            CASE invtype_id
            WHEN 4 THEN FORMAT(q_investment_amount + q_discount_income - q_wht - q_handling_fee,2) 
            WHEN 5 THEN FORMAT(invqnt_units + q_gross_interest - q_wht - q_handling_fee,2) 
            END AS q_amount_received,
            invtype_name, 
            invcat_posted, 
            FORMAT(invqnt_units,0) invqnt_units,
            IFNULL(invcat_description,'No Notes') invcat_description, invcat_modified, invcat_modifieduser, invqnt_modified, invqnt_posted, invqnt_modifieduser
            FROM v_securities";

    return $sql;
}

function post_get_group_members_query($group)
{

    $sql = "SELECT 'MyZambiaMember' AS  FeatureType, 'Post' AS PostType,
            gmember_id, phone_id, phone_number,  gmember_phone, gmember_share,IFNULL(gmember_nickname, '[No Nickname]') g_names,
            CONCAT_WS(' ',phone_fname,phone_lname) p_name, phone_updated, phone_registered,
            UNIX_TIMESTAMP(gmember_posteddate) gmember_posteddate_unix
            FROM app_group_phones ps
            LEFT JOIN app_phones cl ON ps.gmember_phone=cl.phone_number
            WHERE gmember_group='$group'";

    return $sql;


}

function post_get_groups_query($user)
{
    $sql = "SELECT 'MyZambiaGroup' AS  FeatureType, 'Post' AS PostType,
       group_id, group_name, UNIX_TIMESTAMP(group_date_founded) g_date_founded_unix, 
       CONCAT('vb_group_',group_id) g_topic,
       UNIX_TIMESTAMP(group_posteddate) g_date_posted_unix,
       CONCAT(IFNULL(f_posts,0), ' Members') g_members,
       CONCAT_WS(user_fname, user_lname) AS g_administrator,
       group_posteddate,
       group_description, group_modifieddate, group_modifieduser
       FROM app_groups cl
       LEFT JOIN (SELECT gmember_group, COUNT(*) f_posts FROM app_group_phones GROUP BY gmember_group)
       rs ON cl.group_id=rs.gmember_group
       LEFT JOIN app_users u ON cl.group_admin=u.user_username
       WHERE group_id IN (SELECT gmember_group FROM app_group_phones WHERE gmember_phone='$user')";


    return $sql;
}

function post_get_investment_categories()
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $sql = post_get_investments_query();

    //echo $sql;
    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $allPosts = array();


    if ($connectionResult) {

        //success
        $result->Succeeded = true;

        while ($post = mysqli_fetch_object($connectionResult)) {

            //process the job
            array_push($allPosts, $post);
        }

    } else {
        //an error occurred
        $result->Message = "Unable to retrieve investments: " . mysqli_error($conResult->Data);
    }

    $result->PostsAll = $allPosts;

    return $result;
}

function post_get_farming_investments()
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $sql = post_get_farming_investment_query();

    //echo $sql;
    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $allPosts = array();


    if ($connectionResult) {

        //success
        $result->Succeeded = true;

        while ($post = mysqli_fetch_object($connectionResult)) {

            //process the job
            array_push($allPosts, $post);
        }

    } else {
        //an error occurred
        $result->Message = "Unable to retrieve farming investments: " . mysqli_error($conResult->Data);
    }

    $result->PostsAll = $allPosts;

    return $result;
}

function post_get_goverment_investments()
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $sql = post_get_government_investment_query();

    //echo $sql;
    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $allPosts = array();


    if ($connectionResult) {

        //success
        $result->Succeeded = true;

        while ($post = mysqli_fetch_object($connectionResult)) {

            //process the job
            array_push($allPosts, $post);
        }

    } else {
        //an error occurred
        $result->Message = "Unable to retrieve farming investments: " . mysqli_error($conResult->Data);
    }

    $result->PostsAll = $allPosts;

    return $result;
}


function post_get_investments($task)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) ||
        !isset($_GET["token"])) {

        $result->Message = "The specified task '$task' requires that you specify a user," .
            " version, and token";
        return $result;
    }

    app_add_request($task, $_GET["user"], $_GET["token"], $_GET["version"]);

    //categories
    $resInvestments = post_get_investment_categories();
    $investments = null;

    if ($resInvestments->Succeeded) {

        $investments = $resInvestments->PostsAll;

        foreach ($investments as $invest) {

            $invest->Items = array();
        }
    } else {
        return $resInvestments;
    }

    //farming
    $resFarming = post_get_farming_investments();

    if ($resFarming->Succeeded) {

        $finvestments = $resFarming->PostsAll;

        foreach ($investments as $invest) {

            foreach ($finvestments as $finvest) {

                if ($invest->invcat_id == $finvest->invcat_id) {

                    $vbInvestment = new VillageBankingInvestment();

                    $vbInvestment->inv_category = $finvest->invcat_name;
                    $vbInvestment->inv_name = $finvest->invqnt_units. ' '.$finvest->invcat_name;
                    $vbInvestment->invcat_filename = $finvest->invcat_filename;
                    $vbInvestment->invcat_description= $finvest->invcat_description;

                    if($invest->invtype_id==6){
                        array_push($vbInvestment->Items, new ListValue("Units",$finvest->invqnt_units));
                        array_push($vbInvestment->Items, new ListValue("Price",$finvest->invcat_price_item));
                        //array_push($vbInvestment->Items, new ListValue("Variable Cost %",$finvest->bvariable_cost));
                        array_push($vbInvestment->Items, new ListValue("Variable Cost",$finvest->q_variable_cost));
                        array_push($vbInvestment->Items, new ListValue("Rate",$finvest->invqnt_bustaxi_rate));
                        array_push($vbInvestment->Items, new ListValue("Revenue",$finvest->b_revenue));

                        array_push($invest->Items, $vbInvestment);
                    }
                    else{
                        array_push($vbInvestment->Items, new ListValue("Units",$finvest->invqnt_units));
                        array_push($vbInvestment->Items, new ListValue("Estimated Cost",$finvest->q_estimated_cost));
                        array_push($vbInvestment->Items, new ListValue("Variable Cost",$finvest->q_variable_cost));
                        array_push($vbInvestment->Items, new ListValue("Total Cost",$finvest->q_total_cost));
                        array_push($vbInvestment->Items, new ListValue("Estimated Output",$finvest->q_estimated_output));
                        array_push($vbInvestment->Items, new ListValue("Price Per Item",$finvest->invcat_price_item));
                        array_push($vbInvestment->Items, new ListValue("Revenue",$finvest->q_revenue));

                        array_push($invest->Items, $vbInvestment);
                    }


                }

            }
        }
    } else {
        return $resFarming;
    }

    //government

    $resGovernment = post_get_goverment_investments();

    if ($resGovernment->Succeeded) {

        $result->Succeeded = true;
        $finvestments = $resGovernment->PostsAll;

        foreach ($investments as $invest) {

            foreach ($finvestments as $finvest) {

                if ($invest->invcat_id == $finvest->invcat_id) {

                    $vbInvestment = new VillageBankingInvestment();

                    $vbInvestment->inv_category = $finvest->invcat_name;
                    $vbInvestment->inv_name = $finvest->q_period;
                    $vbInvestment->invcat_filename = $finvest->invcat_filename;
                    $vbInvestment->invcat_description= $finvest->invcat_description;

                    array_push($vbInvestment->Items, new ListValue("Period",$finvest->q_period));
                    array_push($vbInvestment->Items, new ListValue("Face Value",$finvest->invqnt_units));
                    array_push($vbInvestment->Items, new ListValue("Price",$finvest->q_price));
                    array_push($vbInvestment->Items, new ListValue("Cost Investment Amount",$finvest->q_investment_amount));
                    array_push($vbInvestment->Items, new ListValue("Discount Income",$finvest->q_discount_income));
                    array_push($vbInvestment->Items, new ListValue("Coupon Rate",$finvest->q_coupon_rate));
                    array_push($vbInvestment->Items, new ListValue("Coupon Payment",$finvest->q_coupon_payment));
                    array_push($vbInvestment->Items, new ListValue("No Coupons",$finvest->q_no_coupon));
                    array_push($vbInvestment->Items, new ListValue("Gross Interest Income",$finvest->q_gross_interest));
                    array_push($vbInvestment->Items, new ListValue("Withholding Tax",$finvest->q_wht));
                    array_push($vbInvestment->Items, new ListValue("Handling Fee",$finvest->q_handling_fee));
                    array_push($vbInvestment->Items, new ListValue("Net Interest Income",$finvest->q_net_interest_income));
                    array_push($vbInvestment->Items, new ListValue("Total Amount Received",$finvest->q_amount_received));



                    array_push($invest->Items, $vbInvestment);
                }

            }
        }
    } else {
        return $resGovernment;
    }

    $result->Investments = $investments;

    return $result;
}

function post_get_vendor_query()
{


    $sql = "SELECT 'MyZambiaVendor' AS  FeatureType, 'Post' AS PostType,
            vendor_id,vendor_name,vendor_code,vendor_name,cat_name,
            vendor_img_filename,vendor_img_sizekb,vendor_category,vendor_branch,
            vendor_town,vendor_postal_address,vendor_modifieduser,vendor_modifieddate
            FROM app_vendors v
            LEFT JOIN app_categories c ON v.vendor_category=c.cat_id";


    return $sql;


}

function post_get_groups($task)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"])) {

        $result->Message = "The specified task '$task' requires that you specify a user," .
            " version, token and Id to retrieve.";

        return $result;
    }

    app_add_request($task, $_GET["user"], $_GET["token"], $_GET["version"]);

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


    $limit = max_items_per_request;


    $sql = post_get_groups_query($_GET["user"]) .
        " ORDER BY group_name DESC LIMIT $limit OFFSET $offset";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $allJobs = array();


    if ($connectionResult) {

        //success
        $result->Succeeded = true;

        while ($job = mysqli_fetch_object($connectionResult)) {

            //process the job
            app_process_group($job);

            array_push($allJobs, $job);
        }

    } else {
        //an error occurred
        $result->Message = "Unable to retrieve groups: " . mysqli_error($conResult->Data);
    }

    $result->Groups = $allJobs;

    return $result;
}

function post_get_group_members($task)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["group"]) || !isset($_GET["version"]) || !isset($_GET["token"])) {

        $result->Message = "The specified task '$task' requires that you specify a user," .
            "group, version, and token to retrieve.";

        return $result;
    }

    app_add_request($task, $_GET["user"], $_GET["token"], $_GET["version"]);

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

    $limit = max_items_per_request;


    $sql = post_get_group_members_query($_GET["group"]) .
        " ORDER BY gmember_nickname DESC LIMIT $limit OFFSET $offset";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $allJobs = array();


    if ($connectionResult) {

        //success
        $result->Succeeded = true;

        while ($job = mysqli_fetch_object($connectionResult)) {

            //process the job
            $job->gmember_posteddate_formatted = date("F j, Y H:i", $job->gmember_posteddate_unix);

            array_push($allJobs, $job);
        }

    } else {
        //an error occurred
        $result->Message = "Unable to retrieve group members: " . mysqli_error($conResult->Data);
    }

    $result->GroupMembers = $allJobs;

    return $result;
}

function post_load_groups($user)
{

    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $sql = post_get_groups_query($_GET["user"]) .
        " ORDER BY group_name";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $allJobs = array();


    if ($connectionResult) {

        //success
        $result->Succeeded = true;

        while ($job = mysqli_fetch_object($connectionResult)) {

            //process the job
            app_process_group($job);

            array_push($allJobs, $job);
        }

    } else {
        //an error occurred
        $result->Message = "Unable to retrieve user groups: " . mysqli_error($conResult->Data);
    }

    $result->Groups = $allJobs;

    return $result;

}

function post_get_merchant($task, $addRequest)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"])
        || !isset($_GET["id"])
    ) {
        $result->Message = "The specified task '$task' requires that you specify a user," .
            " id, version and token.";

        return $result;

    }

    //get connection
    $conResult = app_get_database_connection();

    $id = mysqli_real_escape_string($conResult->Data, $_GET["id"]);;

    if ($addRequest) {

        app_add_request("GetMerchant: $id", $_GET["user"], $_GET["token"], $_GET["version"]);
    }

    //get connection
    $result = post_load_merchant($id);

    return $result;
}

function post_load_merchant($id)
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    $sql = "SELECT 'MyZambiaMerchant' AS  FeatureType, 'Post' AS PostType,
        vendor_id,vendor_name,vendor_code,vendor_name,cat_name,
        vendor_img_filename,vendor_img_sizekb,vendor_category,vendor_branch,
        vendor_town,vendor_postal_address,UNIX_TIMESTAMP(vendor_posteddate) vendor_posteddate,
        vendor_modifieduser,vendor_modifieddate
        FROM app_vendors v
        LEFT JOIN app_categories c on v.vendor_category=c.cat_id WHERE v.vendor_id LIKE '$id'";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $posts = array();

    if ($connectionResult) {

        //success
        //check if user was found

        if (mysqli_num_rows($connectionResult) > 0) {

            while ($post = mysqli_fetch_object($connectionResult)) {

                app_process_merchant($post);

                array_push($posts, $post);
            }
            $result->Succeeded = true;


        } else {
            $result->ShowMessage = true;
            $result->Message = "Unable to find merchant with the specified id '$id'";
        }


    } else {
        //an error occurred
        $result->Message = "Unable to retrieve merchants: " . mysqli_error($conResult->Data);
    }

    $result->Merchants = $posts;

    return $result;
}

function post_load_transaction($id)
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    $sql = app_get_transactions_query() . " WHERE tran_id LIKE '$id'";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $posts = array();

    if ($connectionResult) {

        //success
        //check if user was found

        if (mysqli_num_rows($connectionResult) > 0) {

            while ($post = mysqli_fetch_object($connectionResult)) {

                app_process_transaction($post);

                array_push($posts, $post);
            }
            $result->Succeeded = true;


        } else {
            $result->ShowMessage = true;
            $result->Message = "Unable to find transaction with the specified id '$id'";
        }


    } else {
        //an error occurred
        $result->Message = "Unable to retrieve transaction: " . mysqli_error($conResult->Data);
    }

    $result->Transactions = $posts;

    return $result;
}

function post_get_post($task, $addRequest, $type = "MyZambiaTrip")
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"])
        || !isset($_GET["id"])
    ) {
        $result->Message = "The specified task '$task' requires that you specify a user," .
            " id, version and token.";

        return $result;

    }

    $id = $_GET["id"];

    //add this view
    app_add_view($task);

    if ($addRequest) {

        app_add_request("GetPost: $id", $_GET["user"], $_GET["token"], $_GET["version"]);
    }

    //get connection
    $result = post_load_post($id, $type);

    return $result;
}

function post_load_post($id, $type = "MyZambiaTrip")
{

    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    $sql = post_get_post_query("Single", $type) .
        " WHERE ps.post_id=$id";


    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $posts = array();

    if ($connectionResult) {

        //success
        //check if user was found

        if (mysqli_num_rows($connectionResult) > 0) {

            while ($post = mysqli_fetch_object($connectionResult)) {

                app_process_post($post);

                array_push($posts, $post);
            }

            $result->Succeeded = true;


        } else {
            $result->Message = "Unable to find post with the specified id '$id'";
        }


    } else {
        //an error occurred
        $result->Message = "Unable to retrieve posts: " . mysqli_error($conResult->Data);
    }

    $result->PostsAll = $posts;


    return $result;
}

function post_get_by_search($task)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"])) {

        $result->Message = "The specified task '$task' requires that you specify a user," .
            " version, token and Id to retrieve.";

        return $result;
    }

    app_add_request("GetPostsBySearch", $_GET["user"], $_GET["token"], $_GET["version"]);

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


    if (!isset($_GET["q"])) {

        //an error occurred
        $result->Message = "Unable to search posts: No query has been specified." . mysqli_error($conResult->Data);
        return $result;
    }

    $q = mysqli_real_escape_string($conResult->Data, $_GET["q"]);


    $limit = max_items_per_request;

    $sql = post_get_post_query("None") .
        " WHERE (car_make LIKE '%$q%' OR car_model LIKE '%$q%'
          OR from_province.province_name LIKE '%$q%' OR from_district.district_name LIKE '%$q%'
          OR to_province.province_name LIKE '%$q%' OR to_district.district_name LIKE '%$q%'
          OR post_from_area LIKE '%$q%' OR post_to_area LIKE '%$q%'
          OR CONCAT_WS(' ', phone_fname, phone_lname) LIKE '%$q%') AND UNIX_TIMESTAMP(post_trip_date) >= '" . time() . "'
         ORDER BY post_posted DESC LIMIT $limit OFFSET $offset";

    //get rows

    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $allJobs = array();


    if ($connectionResult) {

        //success
        $result->Succeeded = true;

        while ($job = mysqli_fetch_object($connectionResult)) {

            //process the job
            app_process_post($job);

            array_push($allJobs, $job);

        }

    } else {
        //an error occurred
        $result->Message = "Unable to search posts: " . mysqli_error($conResult->Data);
    }


    $result->PostsAll = $allJobs;


    return $result;
}


function post_get_by_user($task)
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

    $sql = post_get_post_query("None") .
        " WHERE ps.post_posteduser='$user'
         ORDER BY post_posted DESC LIMIT $limit OFFSET $offset";


    //get rows

    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $allJobs = array();


    if ($connectionResult) {

        //success
        $result->Succeeded = true;

        while ($job = mysqli_fetch_object($connectionResult)) {

            //process the job
            app_process_post($job);

            array_push($allJobs, $job);

        }

    } else {
        //an error occurred
        $result->Message = "Unable to get user posts: " . mysqli_error($conResult->Data);
    }


    $result->PostsAll = $allJobs;


    return $result;
}


function post_get_my_reviews($task)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"])) {

        $result->Message = "The specified task '$task' requires that you specify a user," .
            " version, and token to retrieve.";

        return $result;
    }


    app_add_request($task, $_GET["user"], $_GET["token"], $_GET["version"]);


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

    $currentDate = time();

    $sql = post_get_post_query("None", "MyZambiaReview") .
        " WHERE IFNULL(passenger.pass_id,0)!=0 AND post_trip_date > $currentDate ORDER BY post_trip_date DESC LIMIT $limit OFFSET $offset";


    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $allPosts = array();


    if ($connectionResult) {

        //success
        $result->Succeeded = true;

        while ($post = mysqli_fetch_object($connectionResult)) {

            //process the job
            app_process_post($post);

            array_push($allPosts, $post);
        }

    } else {

        //an error occurred
        $result->Message = "Unable to get user reviews: " . mysqli_error($conResult->Data);
    }


    $result->ReviewsAll = $allPosts;


    return $result;
}

function post_get_trip_passengers($task)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"]) || !isset($_GET["trip"])) {

        $result->Message = "The specified task '$task' requires that you specify a user," .
            " version, token and trip to retrieve.";

        return $result;
    }

    app_add_request($task, $_GET["user"], $_GET["token"], $_GET["version"]);

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

    $trip = $_GET["trip"];
    $limit = max_items_per_request;

    $sql = "SELECT 'MyZambiaPassenger' AS  FeatureType, 'Post' AS PostType,
            pass_id, pass_phone, UNIX_TIMESTAMP(pass_posted) pass_posted_unix,
            CONCAT_WS(' ', phone_fname, phone_lname) p_names,
            pass_posted, last_token, a_user_age p_user_age, a_user_gender p_user_gender,
            CONCAT(a_user_gender,', ', a_user_age) p_user_gender_age
            FROM app_passengers p
            LEFT JOIN v_phones v On p.pass_phone=v.phone_number
            WHERE p.pass_trip='$trip'
            ORDER BY pass_posted DESC LIMIT $limit OFFSET $offset";


    //get rows

    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $posts = array();


    if ($connectionResult) {

        //success
        $result->Succeeded = true;

        while ($post = mysqli_fetch_object($connectionResult)) {

            //process the post
            array_push($posts, $post);

            $post->pass_posted_formatted = date("F j, Y H:i", $post->pass_posted_unix);
        }

    } else {
        //an error occurred
        $result->Message = "Unable to get trip passengers: " . mysqli_error($conResult->Data);
    }


    $result->Passengers = $posts;


    return $result;
}

function post_get_user_schedule($task)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"])) {

        $result->Message = "The specified task '$task' requires that you specify a user," .
            " version, token and Id to retrieve.";

        return $result;
    }

    app_add_request("GetUserSchedule", $_GET["user"], $_GET["token"], $_GET["version"]);

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

    $sql = post_get_post_query("None") .
        " WHERE IFNULL(passenger.pass_id,0)!=0
         ORDER BY post_posted DESC LIMIT $limit OFFSET $offset";


    //get rows

    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $allPosts = array();


    if ($connectionResult) {

        //success
        $result->Succeeded = true;

        while ($job = mysqli_fetch_object($connectionResult)) {

            //process the job
            app_process_post($job);

            array_push($allPosts, $job);

        }

    } else {
        //an error occurred
        $result->Message = "Unable to get user schedule: " . mysqli_error($conResult->Data);
    }


    $result->PostsAll = $allPosts;


    return $result;
}

function post_get_by_all($task)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"])) {

        $result->Message = "The specified task '$task' requires that you specify a user," .
            " version, token and Id to retrieve.";

        return $result;
    }

    app_add_request("GetPostsByAll", $_GET["user"], $_GET["token"], $_GET["version"]);

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    $sql = post_get_post_query("None") .
        "ORDER BY post_posted DESC";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $allJobs = array();


    if ($connectionResult) {

        //success
        $result->Succeeded = true;

        while ($job = mysqli_fetch_object($connectionResult)) {

            //process the job
            app_process_post($job);

            array_push($allJobs, $job);

        }

    } else {
        //an error occurred
        $result->Message = "Unable to get user posts: " . mysqli_error($conResult->Data);
    }


    $result->PostsAll = $allJobs;


    return $result;
}