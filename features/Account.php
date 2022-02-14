<?php

function app_get_group_summary_query($groupId)
{
    $sql="SELECT group_name,IFNULL(g_active, 0) AS g_active, 
        CONCAT('K',FORMAT(IFNULL(g_savings,0),2)) AS g_savings, 
        CONCAT('K',FORMAT(IFNULL(g_loan_balance,0),2)) AS g_loan, 
        CONCAT('K',FORMAT(IFNULL(g_member_share,0),2)) AS g_member_share, 
        CONCAT('K',FORMAT(IFNULL(g_interest_recievable,0),2)) AS g_interest_recievable,  
        CONCAT('K',FORMAT(IFNULL(g_interest_received,0),2)) AS g_interest_received,
        CONCAT('K',FORMAT(IFNULL(g_interest_payable,0),2)) AS  g_interest_payable,
        CONCAT('K',FORMAT(IFNULL(g_social_cont,0),2)) AS g_social_cont,
        CONCAT('K',FORMAT(IFNULL(g_admin_fee,0),2)) AS g_admin_fee,
        CONCAT('K',FORMAT(IFNULL(g_penalty_received,0),2)) AS g_penalty_received,
        CONCAT('K',FORMAT(IFNULL(t_expenses, 0),2)) g_expenses, 
        CONCAT('K',FORMAT(IFNULL(t_earnings,0),2)) g_earnings,
        CONCAT('K',FORMAT(IFNULL(g_savings,0) + IFNULL(g_interest_received,0) + IFNULL(g_penalty_received,0) - IFNULL(g_loan_balance,0),2)) AS g_group_total_funds,
        CONCAT('K',FORMAT(IFNULL(g_savings,0) + IFNULL(g_interest_received,0) + IFNULL(g_penalty_received,0) +  IFNULL(t_earnings,0) +
                          IFNULL(g_admin_fee,0) + IFNULL(g_social_cont,0)  - IFNULL(g_loan_balance,0) + - IFNULL(t_expenses, 0),2)) AS g_available_funds
            FROM
                (
                                SELECT  loan_group_id, 
                                        COUNT(*) g_active, 
                                        SUM(m_total_savings) g_savings, 
                                        SUM(t_loan) g_loan, 
                                        SUM(l_loan_balance) g_loan_balance, 
                                        SUM(gmember_share) g_member_share, 
                                        SUM(l_interest) g_interest_recievable,  
                                        SUM(t_interest_paid) g_interest_received,
                                        SUM(l_interest_payable) g_interest_payable,
                                        SUM(t_social_cont) g_social_cont,
                                        SUM(t_admin_fee) g_admin_fee,
                                        SUM(t_penalty) g_penalty_received
                                        FROM 
                                        (
                                                SELECT gmember_nickname, tran_source_phone, loan_group,loan_group_id, gmember_share, 
                                                m_total_savings,l_loan_balance,t_alloweed_borrow,
                                                tran_group, t_savings, t_admin_fee, t_social_cont, t_penalty, t_loan, l_interest,
                                                l_interest_payable, t_interest_earned, t_interest_paid
                                                FROM v_sum v_group_tra
                                                WHERE tran_group IN (SELECT group_id FROM app_groups WHERE group_id='$groupId')
                                        ) AS m_summary 
                                        group by loan_group_id
                        ) msum 
            LEFT JOIN v_group_tran_summary gsum ON msum.loan_group_id=gsum.tran_group
            LEFT JOIN app_groups g ON g.group_id=msum.loan_group_id";

    return $sql;
}

function app_get_account_summary_query()
{
    $sql = "SELECT gmember_nickname, tran_source_phone, tran_group, 
                    CONCAT('K',FORMAT(gmember_share,2)) gmember_share, 
                    CONCAT('K',FORMAT(m_total_savings,2)) m_total_savings,
                    CONCAT('K',FORMAT(l_loan_balance,2)) l_loan_balance,
                    CONCAT('K',FORMAT(t_alloweed_borrow,2)) t_alloweed_borrow,
                    CONCAT('K',FORMAT(t_savings,2)) t_savings, 
                    CONCAT('K',FORMAT(t_admin_fee,2)) t_admin_fee, 
                    CONCAT('K',FORMAT(t_social_cont,2)) t_social_cont, 
                    CONCAT('K',FORMAT(t_penalty,2)) t_penalty, 
                    CONCAT('K',FORMAT(t_loan,2)) t_loan, 
                    CONCAT('K',FORMAT(t_interest_earned,2)) t_interest_earned,
                    CONCAT('K',FORMAT(l_interest_payable,2)) l_interest_payable, 
                    CONCAT('K',FORMAT(gmember_share + t_interest_earned + t_savings - l_loan_balance - l_interest_payable - t_penalty,2)) m_total
             FROM v_sum";

    return $sql;
}

function app_get_transactions_query()
{
    $sql = "SELECT 'Transaction' AS  FeatureType, 'Post' AS PostType,
               phone_number, phone_avater, phone_fname, phone_lname, CONCAT_WS(' ',phone_fname,phone_lname) phone_name,
               tran_id,
               tran_source_phone,
               tran_amount,
               tran_current_balance,
               CONCAT('K',FORMAT(tran_amount,0)) tran_amount_formatted,
               CONCAT('K',FORMAT(tran_current_balance,0)) tran_current_balance_formatted,
               tran_type,tran_post,tran_status,tran_posted,tran_posteduser,
               UNIX_TIMESTAMP(tran_posted) AS tran_posted_unix,
               l.list_text a_tran_title,
               status_name
               FROM app_transactions ts
               LEFT JOIN v_phones ph ON ph.phone_number=ts.tran_posteduser
               LEFT JOIN app_statuses s ON s.status_id=ts.tran_status
               LEFT JOIN app_lists l ON ts.tran_type=l.list_value AND l.list_type='Transaction Type'";

    return $sql;
}

function app_get_loans_query()
{
    $sql = "SELECT 'MyZambiaLoan' AS  FeatureType, 'Post' AS PostType,
            loan_id, loan_amount, loan_interest_rate,loan_period,
            loan_group, loan_group_id, gmember_nickname,
            loan_phone,loan_comments,loan_posteddate,loan_posteduser, 
            p_loan_paid, p_interest_paid, l_interest,
            l_total,l_repayment,l_loan_balance, l_loan_intest_balance,
            l_due, loan_status,g_interest_type,
            p_no_payments,l_interest_payable, u_l_loan_intest_balance,   
            UNIX_TIMESTAMP(loan_posteddate) AS loan_posteddate_unix,
            CONCAT('K',FORMAT(loan_amount,0)) loan_amount_formatted,
            CONCAT(FORMAT(loan_interest_rate,0),'%') loan_interest_rate_formatted,  
            CONCAT('K',FORMAT(l_interest,0)) l_interest_formatted,
            CONCAT('K',FORMAT(p_loan_paid,0)) p_loan_paid_formatted,
            CONCAT('K',FORMAT(l_loan_balance,0)) l_loan_balance_formatted,  
            CONCAT('K',FORMAT(l_interest_payable,0)) l_interest_payable_formatted,
            CONCAT(loan_period, ' Months') loan_period_formatted,
            CONCAT(p_no_payments, ' Payments') p_no_payments_formatted
            FROM v_loan_summary";

    return $sql;
}

function account_get_loans($task, $addRequest)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"]) || !isset($_GET["group"])
    ) {

        $result->Message = "The specified task '$task' requires that you specify a user, group" .
            "version and token.";

        return $result;

    }

    if ($addRequest) {

        app_add_request($task, $_GET["user"], $_GET["token"], $_GET["version"]);
    }


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

    $group = $_GET["group"];
    $user = $_GET["user"];
    $number_post = max_items_per_request;

    $sql = app_get_loans_query() . " WHERE loan_phone='$user' AND loan_group_id='$group' ORDER BY loan_id DESC LIMIT $number_post OFFSET $offset";


    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $loans = array();

    if ($connectionResult) {

        //success
        $result->Succeeded = true;

        while ($loan = mysqli_fetch_object($connectionResult)) {

            $loan->loan_posteddate_formatted = date("F j, Y H:i", $loan->loan_posteddate_unix);

            array_push($loans, $loan);
        }

    } else {
        //an error occurred
        $result->Message = "Unable to retrieve loan: " . mysqli_error($conResult->Data);
    }

    $result->Loans = $loans;


    return $result;
}

function account_get_transactions($task, $addRequest)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"]) || !isset($_GET["group"])
    ) {

        $result->Message = "The specified task '$task' requires that you specify a user, group" .
            "version and token.";

        return $result;

    }

    if ($addRequest) {

        app_add_request($task, $_GET["user"], $_GET["token"], $_GET["version"]);
    }


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

    $group = $_GET["group"];
    $user = $_GET["user"];
    $number_post = max_items_per_request;

    $sql = app_get_transactions_query() . " WHERE tran_source_phone='$user' AND tran_group='$group' ORDER BY tran_id DESC LIMIT $number_post OFFSET $offset";


    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $transactions = array();

    if ($connectionResult) {

        //success
        $result->Succeeded = true;

        while ($transaction = mysqli_fetch_object($connectionResult)) {

            app_process_transaction($transaction);

            array_push($transactions, $transaction);
        }

    } else {
        //an error occurred
        $result->Message = "Unable to retrieve transactions: " . mysqli_error($conResult->Data);
    }

    $result->Transactions = $transactions;


    return $result;
}

function account_get_group_summary($task, $addRequest)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"])
        || !isset($_GET["token"]) || !isset($_GET["group"])
    ) {

        $result->Message = "The specified task '$task' requires that you specify a user," .
            "version and token.";

        return $result;

    }

    if ($addRequest) {

        app_add_request($task, $_GET["user"], $_GET["token"], $_GET["version"]);
    }


    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    $user = $_GET["user"];
    $group = $_GET["group"];

    $sql = app_get_group_summary_query($group);

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $summary = null;

    if ($connectionResult) {


        //check if user was found
        if (mysqli_num_rows($connectionResult) > 0) {

            //success
            $result->Succeeded = true;

            $summary = mysqli_fetch_object($connectionResult);
        } else {

            //an error occurred
            $result->Message = "Unable to find the account summary " .
                "for the specified user '$user' and group '$group'";
        }

    } else {

        //an error occurred
        $result->Message = "Unable to retrieve the account summary " .
            "for the specified user '$user' and group '$group'" . mysqli_error($conResult->Data);
    }

    $result->GroupSummary = $summary;


    return $result;
}

function account_get_account_summary($task, $addRequest)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"])
        || !isset($_GET["token"]) || !isset($_GET["group"])
    ) {

        $result->Message = "The specified task '$task' requires that you specify a user," .
            "version and token.";

        return $result;

    }

    if ($addRequest) {

        app_add_request($task, $_GET["user"], $_GET["token"], $_GET["version"]);
    }


    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    $user = $_GET["user"];
    $group = $_GET["group"];

    $sql = app_get_account_summary_query() .
        " WHERE tran_source_phone='$user' AND tran_group='$group'";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows
    $summary = null;

    if ($connectionResult) {


        //check if user was found
        if (mysqli_num_rows($connectionResult) > 0) {

            //success
            $result->Succeeded = true;

            $summary = mysqli_fetch_object($connectionResult);
        } else {

            //an error occurred
            $result->Message = "Unable to find the account summary " .
                "for the specified user '$user' and group '$group'";
        }

    } else {

        //an error occurred
        $result->Message = "Unable to retrieve the account summary " .
            "for the specified user '$user' and group '$group'" . mysqli_error($conResult->Data);
    }

    $result->AccountSummary = $summary;


    return $result;
}

function account_create_contribution($task, $addRequest)
{
    $result = new TaskResult();

    if (!isset($_REQUEST["user"])
        || !isset($_REQUEST["amount"])
        || !isset($_REQUEST["group"])
        || !isset($_REQUEST["type"])
    ) {
        $result->Message = "The specified task '$task' requires that you specify a user," .
            " amount, group and type";

        return $result;
    }



    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $ref = "NULL";

    if (isset($_REQUEST["ref"])) {
        $ref = "'" . mysqli_real_escape_string($conResult->Data, $_REQUEST["ref"]) . "'";
    }

    $user = mysqli_real_escape_string($conResult->Data, $_REQUEST["user"]);
    $amount = mysqli_real_escape_string($conResult->Data, $_REQUEST["amount"]);
    $type = mysqli_real_escape_string($conResult->Data, $_REQUEST["type"]);
    $group = mysqli_real_escape_string($conResult->Data, $_REQUEST["group"]);

    if (!is_numeric($amount)) {

        $result->Message = "The specified amount '$amount' is not valid.";
        return $result;
    }

    if (!is_numeric($type)) {

        $result->Message = "The specified contribution type '$type' is not valid.";
        return $result;
    }

    $postedUser = $user;

    if (isset($_REQUEST["postedUser"])) {

        $postedUser = mysqli_real_escape_string($conResult->Data, $_REQUEST["postedUser"]);
    }

    if ($addRequest) {

        $version = default_version;

        if (isset($_REQUEST["version"])) {

            $version = mysqli_real_escape_string($conResult->Data, $_REQUEST["version"]);
        }

        $token = default_token;

        if (isset($_REQUEST["token"])) {

            $token = mysqli_real_escape_string($conResult->Data, $_REQUEST["token"]);
        }

        app_add_request($task, $_REQUEST["user"], $token, $version);
    }

    //get user
    $userResult = app_get_user_info($user);

    //check if user exists
    if (!$userResult->Succeeded) {

        //doesnt exist, stop
        return $userResult;
    }

    //get group
    $groupResult = app_get_group_info($group);

    //check if group exists
    if (!$groupResult->Succeeded) {

        //doesnt exist, stop
        return $groupResult;
    }

    //user exists
    $userProfile = $userResult->User;
    $groupProfile = $groupResult->Group;


    $sql = "INSERT INTO `app_transactions`(`tran_type`,`tran_source_phone`,`tran_ref`, 
                                           `tran_amount`, `tran_status`,`tran_group`,
                                           `tran_posted`,`tran_posteduser`)
                                       VALUES ('$type','$user',$ref,
                                               '$amount','1','$group',
                                               FROM_UNIXTIME('%s'),'$user')";


    //run query
    $sql = sprintf($sql, time());


    $connectionResult = mysqli_query($conResult->Data, $sql);


    //check if succeeded
    if ($connectionResult) {

        $result->Succeeded = true;

        $currentDate = date('M j, Y H:i', time());
        $amountFormatted = "K" . number_format($amount, 2);

        $tranType = app_get_contribution_title($type);

        $result->Message = "$tranType of $amountFormatted has been made for account $user on " .
            "$currentDate under the group " . $groupProfile->group_name;


        //send fcm message to receiver
        $msg = "You made a $tranType of $amountFormatted on $currentDate. You will be notified when the contribution is approved";

        $title = "Village Banking $amountFormatted $tranType";
        $type = "contribution";

        $result->Data = app_send_user_notification($userProfile->last_token, $msg, $title, 0, $type);

    } else {
        //an error occurred
        $result->Message = "Unable to process contribution " . mysqli_error($conResult->Data);
    }

    return $result;
}

function account_get_loan_interest($group, $period)
{
    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    $sql = "SELECT grate_id, grate_months, grate_value, 
            grate_posteddate, grate_posteduser 
            FROM app_group_intrest_rates WHERE grate_group='$group' AND grate_months='$period'";

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    //get rows

    if ($connectionResult) {


        //check if user was found
        if (mysqli_num_rows($connectionResult) > 0) {

            //success
            $result->Succeeded = true;

            $rates = mysqli_fetch_object($connectionResult);
            $result->Rates = $rates;
        } else {

            //an error occurred
            $result->Message = "Unable to create loan. The specified re-payment period of $period months is not allowed for this group";
        }

    } else {

        //an error occurred
        $result->Message = "Unable to create loan. Could not retrieve interest rates" .
            " for the specified period '$period' and group '$group'" . mysqli_error($conResult->Data);
    }


    return $result;
}

function account_create_loan($task, $addRequest)
{
    $result = new TaskResult();

    if (!isset($_REQUEST["user"])
        || !isset($_REQUEST["amount"])
        || !isset($_REQUEST["group"])
        || !isset($_REQUEST["period"])
    ) {
        $result->Message = "The specified task '$task' requires that you specify a user," .
            " amount, re-payment period and group";

        return $result;
    }


    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $user = mysqli_real_escape_string($conResult->Data, $_REQUEST["user"]);
    $amount = mysqli_real_escape_string($conResult->Data, $_REQUEST["amount"]);
    $group = mysqli_real_escape_string($conResult->Data, $_REQUEST["group"]);
    $period = mysqli_real_escape_string($conResult->Data, $_REQUEST["period"]);

    if (!is_numeric($amount)) {

        $result->Message = "The specified amount '$amount' is not valid.";
        return $result;
    }

    if (!is_numeric($period)) {

        $result->Message = "The specified re-payment period '$period' is not valid.";
        return $result;
    }

    $resRates = account_get_loan_interest($group, $period);


    if ($resRates->Succeeded) {

        $rate = $resRates->Rates->grate_value;
    } else {
        return $resRates;
    }

    $postedUser = $user;

    if (isset($_REQUEST["postedUser"])) {

        $postedUser = mysqli_real_escape_string($conResult->Data, $_REQUEST["postedUser"]);
    }

    if ($addRequest) {

        $version = default_version;

        if (isset($_REQUEST["version"])) {

            $version = mysqli_real_escape_string($conResult->Data, $_REQUEST["version"]);
        }

        $token = default_token;

        if (isset($_REQUEST["token"])) {

            $token = mysqli_real_escape_string($conResult->Data, $_REQUEST["token"]);
        }

        app_add_request($task, $_REQUEST["user"], $token, $version);
    }

    //get user
    $userResult = app_get_user_info($user);

    //check if user exists
    if (!$userResult->Succeeded) {

        //doesnt exist, stop
        return $userResult;
    }

    //get group
    $groupResult = app_get_group_info($group);

    //check if group exists
    if (!$groupResult->Succeeded) {

        //doesnt exist, stop
        return $groupResult;
    }

    //user exists
    $userProfile = $userResult->User;
    $groupProfile = $groupResult->Group;


    $loanAmount = ($rate * 0.01 * $amount) + $amount;
    $monthlyPayable = $loanAmount / $period;

    $loanAmount = round($loanAmount, 2);
    $monthlyPayable = round($monthlyPayable, 2);

    $sql = "INSERT INTO `app_transactions`(`tran_type`,`tran_source_phone`, `tran_group`,
                                           `tran_amount`, `tran_status`,`tran_period`,
                                           `tran_amount_loan`,`tran_interest_rate`,
                                           `tran_posted`,`tran_posteduser`)
                                            VALUES 
                                          ('5','$user','$group',
                                           '$amount','1','$period',
                                           '$loanAmount','$rate',
                                           FROM_UNIXTIME('%s'),'$user')";


    //get rows
    $sql = sprintf($sql, time(), time());


    $connectionResult = mysqli_query($conResult->Data, $sql);


    //check if succeeded
    if ($connectionResult) {

        $result->Succeeded = true;

        $currentDate = date('M j, Y H:i', time());
        $amountFormatted = "K" . number_format($amount, 2);
        $loanAmountFormatted = "K" . number_format($loanAmount, 2);
        $monthlyPayableFormatted = "K" . number_format($monthlyPayable, 2);

        $result->Message = "Loan of $amountFormatted has been posted at interest rate $rate% for user $user on $currentDate " .
            "for group " . $groupProfile->group_name;


        //send fcm message to receiver
        $msg = "A loan of $amountFormatted has been posted at interest rate $rate%  on $currentDate. " .
            "for group " . $groupProfile->group_name . ". You will be notified when the loan is approved";

        $title = "Village Banking $amountFormatted Loan";
        $type = "loan";

        $result->Data = app_send_user_notification($userProfile->last_token, $msg, $title, 0, $type);

    } else {
        //an error occurred
        $result->Message = "Unable to process loan " . mysqli_error($conResult->Data);
    }

    return $result;
}

function account_make_loan_payment($task, $addRequest)
{
    $result = new TaskResult();

    if (!isset($_REQUEST["user"])
        || !isset($_REQUEST["loan"])
        || !isset($_REQUEST["loanAmount"])
        || !isset($_REQUEST["interestAmount"])
        || !isset($_REQUEST["referenceNo"])
    ) {
        $result->Message = "The specified task '$task' requires that you specify a user, loan, " .
            " loan amount, interest amount, and reference number";

        return $result;
    }


    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $user = mysqli_real_escape_string($conResult->Data, $_REQUEST["user"]);
    $loan = mysqli_real_escape_string($conResult->Data, $_REQUEST["loan"]);
    $loanAmount = mysqli_real_escape_string($conResult->Data, $_REQUEST["loanAmount"]);
    $interestAmount = mysqli_real_escape_string($conResult->Data, $_REQUEST["interestAmount"]);
    $referenceNo = mysqli_real_escape_string($conResult->Data, $_REQUEST["referenceNo"]);


    if (!is_numeric($loanAmount)) {

        $result->Message = "The specified loan amount '$loanAmount' is not valid.";
        return $result;
    }

    if (!is_numeric($interestAmount)) {

        $result->Message = "The specified interest amount '$interestAmount' is not valid.";
        return $result;
    }

    $postedUser = $user;

    if (isset($_REQUEST["postedUser"])) {

        $postedUser = mysqli_real_escape_string($conResult->Data, $_REQUEST["postedUser"]);
    }

    if ($addRequest) {

        $version = default_version;

        if (isset($_REQUEST["version"])) {

            $version = mysqli_real_escape_string($conResult->Data, $_REQUEST["version"]);
        }

        $token = default_token;

        if (isset($_REQUEST["token"])) {

            $token = mysqli_real_escape_string($conResult->Data, $_REQUEST["token"]);
        }

        app_add_request($task, $_REQUEST["user"], $token, $version);
    }

    //get user
    $userResult = app_get_user_info($user);

    //check if user exists
    if (!$userResult->Succeeded) {

        //doesnt exist, stop
        return $userResult;
    }

    //get loan
    $loanResult = app_get_loan_info($loan);

    //check if group exists
    if (!$loanResult->Succeeded) {

        //doesnt exist, stop
        return $loanResult;
    }

    //user exists
    $userProfile = $userResult->User;
    $loanProfile = $loanResult->Loan;

    if ($loanAmount >  $loanProfile->l_loan_balance) {

        $result->Message="The loan amount must be less or equal to the loan balance";
        return $result;
    }

    /* prevent payment of unequal interest
    if ($interestAmount != $loanProfile->l_interest_payable) {

        $result->Message="The interest amount must be equal to the interest balance";
        return $result;
    }*/


    $loanId=$loanProfile->loan_id;
    $groupId=$loanProfile->loan_group_id;

    $sql = "INSERT INTO `app_transactions`(`tran_type`,`tran_source_phone`, `tran_group`,`tran_loan`,
                                           `tran_amount`, `tran_status`, `tran_ref`,
                                           `tran_posted`,`tran_posteduser`)
                                            VALUES 
                                          ('6','$user','$groupId','$loanId',
                                           '$interestAmount','1','$referenceNo',
                                           FROM_UNIXTIME('%s'),'$user'),
                                          ('8','$user','$groupId','$loanId',
                                           '$loanAmount','1','$referenceNo',
                                           FROM_UNIXTIME('%s'),'$user')";


    //get rows
    $sql = sprintf($sql, time(), time());


    $connectionResult = mysqli_query($conResult->Data, $sql);


    //check if succeeded
    if ($connectionResult) {

        $result->Succeeded = true;

        $currentDate = date('M j, Y H:i', time());
        $amountFormatted = "K" . number_format($loanAmount, 2);
        $interestAmountFormatted = "K" . number_format($interestAmount, 2);

        $result->Message = "Loan payment $amountFormatted and interest payment $interestAmountFormatted has been posted for user $user on $currentDate " .
            "for group " . $loanProfile->loan_group;


        //send fcm message to receiver
        $msg = "Loan payment $amountFormatted and interest payment $interestAmountFormatted has been posted for user $user on $currentDate " .
            "for group " . $loanProfile->loan_group . ". You will be notified when the payments are approved";

        $title = "Village Banking Loan Payment";
        $type = "loanpayment";

        $result->Data = app_send_user_notification($userProfile->last_token, $msg, $title, 0, $type);

    } else {
        //an error occurred
        $result->Message = "Unable to process loan payment " . mysqli_error($conResult->Data);
    }

    return $result;
}

function account_approve_loan($task, $addRequest)
{
    $result = new TaskResult();

    if (!isset($_REQUEST["user"]) || !isset($_REQUEST["loan"])
    ) {
        $result->Message = "The specified task '$task' requires that you specify a user," .
            " and the loan";

        return $result;
    }


    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $user = mysqli_real_escape_string($conResult->Data, $_REQUEST["user"]);
    $loan = mysqli_real_escape_string($conResult->Data, $_REQUEST["loan"]);


    if (!is_numeric($loan)) {

        $result->Message = "The specified loan '$loan' is not valid.";
        return $result;
    }

    $postedUser = $user;

    if (isset($_REQUEST["postedUser"])) {

        $postedUser = mysqli_real_escape_string($conResult->Data, $_REQUEST["postedUser"]);
    }

    if ($addRequest) {

        $version = default_version;

        if (isset($_REQUEST["version"])) {

            $version = mysqli_real_escape_string($conResult->Data, $_REQUEST["version"]);
        }

        $token = default_token;

        if (isset($_REQUEST["token"])) {

            $token = mysqli_real_escape_string($conResult->Data, $_REQUEST["token"]);
        }

        app_add_request($task, $_REQUEST["user"], $token, $version);
    }

    //get user
    $userResult = app_get_user_info($user);



    //get loan
    $loanResult = app_get_loan_info($loan);

    //check if loan exists
    if (!$loanResult->Succeeded) {

        //doesnt exist, stop
        return $loanResult;
    }

    //user exists

    $loanProfile = $loanResult->Loan;

    $sql = "UPDATE `app_transactions` SET `tran_status`=2 WHERE tran_id='$loan'";

    $connectionResult = mysqli_query($conResult->Data, $sql);

    //check if succeeded
    if ($connectionResult) {

        $result->Succeeded = true;

        $amount = $loanProfile->loan_amount;
        $loanAmount = $loanProfile->l_total;
        $monthlyPayable = $loanProfile->l_repayment;
        $rate = $loanProfile->loan_interest_rate;
        $period = $loanProfile->loan_period;

        $currentDate = date('M j, Y H:i', time());

        $amountFormatted = "K" . number_format($amount, 2);
        $loanAmountFormatted = "K" . number_format($loanAmount, 2);
        $monthlyPayableFormatted = "K" . number_format($monthlyPayable, 2);

        $result->Message = "Loan of $amountFormatted has been approved at interest rate $rate% for user $user on $currentDate. " .
            "Total repayable is $loanAmountFormatted in $period installments of $monthlyPayableFormatted";


        //send fcm message to receiver
        $msg = "A loan of $amountFormatted has been approved at interest rate $rate%  on $currentDate. " .
            "Total repayable is $loanAmountFormatted in $period installments of $monthlyPayableFormatted. ";

        $title = "Village Banking $amountFormatted Loan Approved";
        $type = "loanapproval";


        //check if user exists for notification
        if ($userResult->Succeeded) {

            //exists
            $userProfile = $userResult->User;

            $result->Data = app_send_user_notification($userProfile->last_token, $msg, $title, 0, $type);
        }


    } else {
        //an error occurred
        $result->Message = "Unable to approve loan " . mysqli_error($conResult->Data);
    }

    return $result;
}

function account_approve_payment($task, $addRequest)
{
    $result = new TaskResult();

    if (!isset($_REQUEST["user"]) || !isset($_REQUEST["payment"])
    ) {
        $result->Message = "The specified task '$task' requires that you specify a user," .
            " and the payment";

        return $result;
    }


    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }

    $user = mysqli_real_escape_string($conResult->Data, $_REQUEST["user"]);
    $payment = mysqli_real_escape_string($conResult->Data, $_REQUEST["payment"]);


    if (!is_numeric($payment)) {

        $result->Message = "The specified payment '$payment' is not valid.";
        return $result;
    }

    $postedUser = $user;

    if (isset($_REQUEST["postedUser"])) {

        $postedUser = mysqli_real_escape_string($conResult->Data, $_REQUEST["postedUser"]);
    }

    if ($addRequest) {

        $version = default_version;

        if (isset($_REQUEST["version"])) {

            $version = mysqli_real_escape_string($conResult->Data, $_REQUEST["version"]);
        }

        $token = default_token;

        if (isset($_REQUEST["token"])) {

            $token = mysqli_real_escape_string($conResult->Data, $_REQUEST["token"]);
        }

        app_add_request($task, $_REQUEST["user"], $token, $version);
    }

    //get user
    $userResult = app_get_user_info($user);


    //get payment
    $paymentResult = app_get_payment_info($payment);

    //check if loan exists
    if (!$paymentResult->Succeeded) {

        //doesnt exist, stop
        return $paymentResult;
    }

    $paymentProfile = $paymentResult->Tag;

    $sql = "UPDATE `app_transactions` SET `tran_status`=2 WHERE tran_id='$payment'";

    $connectionResult = mysqli_query($conResult->Data, $sql);

    //check if succeeded
    if ($connectionResult) {

        $result->Succeeded = true;




            $amount = $paymentProfile->tran_amount;
            $tranType = $paymentProfile->a_tran_title;
            $source = $paymentProfile->a_tran_source;
            $group = $paymentProfile->group_name;

            $currentDate = date('M j, Y H:i', time());
            $amountFormatted = "K" . number_format($amount, 2);

            $result->Message = "$tranType of $amountFormatted has been approved for account $user on " .
                "$currentDate under the group $group via $source";


            //send fcm message to receiver
            $msg = "$tranType of $amountFormatted approved on $currentDate via $source";

            $title = "Village Banking $amountFormatted $tranType Approved";
            $type = "paymentApproval";

        //user exists, send notification
        if ($userResult->Succeeded) {

            $userProfile = $userResult->User;
            $result->Data = app_send_user_notification($userProfile->last_token, $msg, $title, 0, $type);
        }

    } else {
        //an error occurred
        $result->Message = "Unable to approve payment " . mysqli_error($conResult->Data);
    }

    return $result;
}

function account_process_user_purchase($user, $post, $amount, $currentBalance)
{

    $result = new TaskResult();

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        return $conResult;
    }

    //add purchase
    $sql = "INSERT INTO `app_transactions`(`tran_type`,`tran_source_phone`,`tran_post`, `tran_amount`, `tran_current_balance`,
                                                `tran_status`,`tran_posted`,`tran_posteduser`)
                                       VALUES ('2','$user','$post','$amount','$currentBalance','2',FROM_UNIXTIME('%s'),'$user')";

    $sql = sprintf($sql, time());

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);

    if ($connectionResult) {

        //success
        $result->Succeeded = true;
        $result->Data = mysqli_insert_id($conResult->Data);

    } else {
        //an error occurred
        $result->Message = "Unable to add purchase for trip $post: " . mysqli_error($conResult->Data);
    }


    return $result;
}


function app_get_profile($task)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"])
    ) {
        $result->Message = "The specified task '$task' requires that you specify a user," .
            "version and token.";

        return $result;

    }


    app_add_request($task, $_GET["user"], $_GET["token"], $_GET["version"]);


    //get user
    $userResult = app_get_user_info($_GET["user"]);

    //check if user exists
    if (!$userResult->Succeeded) {

        //doesnt exist, stop
        return $userResult;
    }

    //user exists
    $result->User = $userResult->User;
    $result->Succeeded = true;

    return $result;
}

function app_add_user_notification($task, $addRequest)
{
    $result = new TaskResult();


    if (!isset($_REQUEST["user"]) || !isset($_REQUEST["title"]) || !isset($_REQUEST["body"])
        || !isset($_REQUEST["version"]) || !isset($_REQUEST["token"]) || !isset($_REQUEST["post"]) ||
        !isset($_REQUEST["type"])
    ) {
        $result->Message = "The specified task '$task' requires that you specify a user,type,post, version, token, title and" .
            " body";

        return $result;
    }

    //get connection
    $conResult = app_get_database_connection();

    if (!$conResult->Succeeded) {

        $result->Message = $conResult->Message;
        return $result;
    }


    $user = mysqli_real_escape_string($conResult->Data, $_REQUEST["user"]);
    $notification = mysqli_real_escape_string($conResult->Data, $_REQUEST["body"]);
    $title = mysqli_real_escape_string($conResult->Data, $_REQUEST["title"]);
    $post = mysqli_real_escape_string($conResult->Data, $_REQUEST["post"]);
    $type = mysqli_real_escape_string($conResult->Data, $_REQUEST["type"]);

    if (!is_numeric($post)) {

        $result->Message = "The specified post '$post' is not valid.";
        return $result;
    }

    if ($addRequest) {

        app_add_request($task, $_GET["user"], $_GET["token"], $_GET["version"]);
    }

    //get user
    $userResult = app_get_user_info($user);

    //check if user exists
    if (!$userResult->Succeeded) {

        //doesnt exist, stop
        return $userResult;
    }

    //user exists
    $userProfile = $userResult->User;


    $sql = "INSERT INTO `app_notifications`(notification_type, `notification_post`,`notification_user`,`notification_title`,
                  `notification_description`, `notification_posted`)
                                       VALUES ('%s','%s','%s','%s','%s',FROM_UNIXTIME('%s'))";

    $sql = sprintf($sql, $type, $post, $userProfile->phone_number, $title, $notification, time());

    //get rows
    $connectionResult = mysqli_query($conResult->Data, $sql);


    if (!$connectionResult) {

        //an error occurred
        $result->Message = "Unable to add notification: " . mysqli_error($conResult->Data);
    } else {

        //check the kind of notification
        if ($type == "topUp" || $type == "transfer") {

            //its  a top up, get the balance for the user
            $result = app_get_user_info($user);

        } else {
            $result->Succeeded = true;
        }

    }

    return $result;
}

function app_get_user_notifications($task, $addRequest)
{
    $result = new TaskResult();

    if (!isset($_GET["user"]) || !isset($_GET["version"]) || !isset($_GET["token"])) {

        $result->Message = "The specified task '$task' requires that you specify a user, type, " .
            "version and token.";

        return $result;
    }


    $user = $_GET["user"];

    if ($addRequest) {

        app_add_request($task, $_GET["user"], $_GET["token"], $_GET["version"]);
    }


    //get user
    $userResult = app_get_user_info($user);

    //check if user exists
    if (!$userResult->Succeeded) {

        //doesnt exist, stop
        return $userResult;
    }

    //user exists
    $userProfile = $userResult->User;


    //get connection
    $conResult = app_get_database_connection();

    //check if we got a connection

    if (!$conResult->Succeeded) {

        //unable to get connection
        $result->Message = $conResult->Message;
    } else {

        //got connection, get rows from db

        $limit = max_items_per_request;


        $offset = 0;

        if (isset($_GET["offset"])) {
            $offset = $_GET["offset"];
        }


        $sql = "SELECT n.*,'Notification' AS  FeatureType, 'Post' AS PostType,
                notification_posted post_posted, 
                UNIX_TIMESTAMP(notification_posted) post_posted_unix 
                FROM app_notifications n
                WHERE notification_user LIKE '%s'
                ORDER BY notification_posted DESC LIMIT $limit OFFSET $offset";

        $sql = sprintf($sql, $userProfile->phone_number);


        $connectionResult = mysqli_query($conResult->Data, $sql);

        //check if we succeeded
        if ($connectionResult) {

            //success
            $result->Succeeded = true;
            $items = array();

            while ($item = mysqli_fetch_object($connectionResult)) {

                app_process_notification($item);

                array_push($items, $item);
            }

            $result->Notifications = $items;

        } else {
            //an error occurred
            $result->Message = "Unable to retrieve notifications: " . mysqli_error($conResult->Data);
        }

    }

    return $result;
}
