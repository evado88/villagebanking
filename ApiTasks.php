<?php

if (isset($_REQUEST["task"])) {

    $task = $_REQUEST["task"];

    switch ($task) {
        case "CreateLoan":
        {
            echo json_encode(account_create_loan($task, true));
            break;
        }
        case "MakeLoanPayment":
        {
            echo json_encode(account_make_loan_payment($task, true));
            break;
        }
        case "ApproveLoan":
        {
            echo json_encode(account_approve_loan($task, true));
            break;
        }
        case "ApprovePayment":
        {
            echo json_encode(account_approve_payment($task, true));
            break;
        }
        case "CreateContribution":
        {

            echo json_encode(account_create_contribution($task, true));
            break;
        }
        case "GetTransactions":
        {

            echo json_encode(account_get_transactions($task, true));
            break;
        }
        case "GetLoans":
        {
            echo json_encode(account_get_loans($task, true));
            break;
        }
        case "GetAccountSummary":
        {
            echo json_encode(account_get_account_summary($task, true));
            break;
        }
        case "GetGroupSummary":
        {
            echo json_encode(account_get_group_summary($task, true));
            break;
        }
        case "AddUserNotification":
        {
            echo json_encode(app_add_user_notification($task, true));
            break;
        }
        case "GetUserNotifications":
        {
            echo json_encode(app_get_user_notifications($task, true));
            break;
        }
        case "GetProfile":
        {

            echo json_encode(app_get_profile($task));
            break;
        }
        case "HandShake":
        {
            echo json_encode(core_hand_shake($task));
            break;
        }
        case "GetGroups":
        {
            echo json_encode(post_get_groups($task));
            break;
        }
        case "GetGroupMembers":
        {
            echo json_encode(post_get_group_members($task));
            break;
        }
        case "GetInvestments":
        {
            echo json_encode(post_get_investments($task));
            break;
        }
        case "LogOutDevice":
        {
            echo json_encode(core_log_out($task));
            break;
        }
        case "RegisterUser":
        {
            echo json_encode(core_register_user($task, true));
            break;
        }
        case "AddComment":
        {
            echo json_encode(app_insert_comment($task, true));
            break;
        }
        case "GetComments":
        {
            echo json_encode(app_get_comments($task, true));
            break;
        }
        case "AddCommentVote":
        {
            echo json_encode(app_add_comment_vote($task));
            break;
        }
        default:
        {

            core_invalid_task($task);
            break;
        }
    }
} else {

    //remove
    core_no_task();
}