<?php

define('MY_UNREGISTERED_USER', 'Unregistered');
define("reminder_minutes", 15);
define('MY_ZAMBIA_PROMOSYS_SERVER_KEY', 'AAAAzHFkGzo:APA91bEy37CQFjUFuctbKsA8Z0kjZNuWSgJBLe6JpEafRhwTEjZCxPbvcv8zhUc2yCO1fNjoybTubBE0UGRBkSkIdl7ePrYUcaIYC9Exb9mr-UIsGNlNaps1iJ9E--cOUHNq0tCX-f03');

define("max_items_per_request", 10);
define("max_transfer_amount", 500);

define("NEW_USER_BALANCE", 30000);

define('MY_ZAMBIA_APP_KEY', 'zyKROQ8sMMx676HLah3t9zaaPNtfXyrf');

define("MY_ZAMBIA_LOG_OUT_TOKEN", "USER_LOGGED_OUT");

define("MY_ZAMBIA_IMAGE_QUALITY", 70);

define("MY_ZAMBIA_MAX_IMAGE_SIZE", 960);

class TaskResult
{
    public $Succeeded = false;
    public $Message = "";

    public $Merchants;
    public $Groups;
    public $Group;

    public $PostsAll;
    public $ReviewsAll;
    public $Cars;
    public $Passengers;
    public $Comments;

    public $User;
    public $Server;

    public $Data;
    public $Tag;
    public $Config;
    public $AccountSummary;
    public $GroupSummary;
    public $Rates;
    public $Transactions;
    public $Loans;
    public $Loan;
    public $GroupMembers;
    public $GovermentInvestment;
    public $FarmingInvestment;
    public $Investments;
}

class VillageBankingInvestment
{
    public $inv_category;
    public $inv_name;
    public $invcat_filename;
    public $invcat_description;
    public $FeatureType = 'MyZambiaCategory';
    public $PostType = 'Post';
    public $Items = array();
}

class ListValue
{
    public $Name;
    public $Description;
    public $FeatureType = 'ListValue';
    public $PostType = 'Post';

    function __construct($name, $description)
    {
        $this->Name = $name;
        $this->Description = $description;
    }

}

class User
{
    public $phone_number;
    public $phone_fname;
    public $phone_lname;
    public $phone_registered;
    public $phone_updated;
}

class MyZambiaServer
{
    //the version of the server
    public $server_version = 1;

    //the recommended version for the client apps
    public $recommended_client_version = 1;

    //the minimum version of the client which the servers supports.
    // All clients running this version will be warned to update soon
    public $minimum_supported_client_version = 0;

}

class DatabaseConfig
{

    public $Host = 'mysql5044.site4now.net';
    public $User = 'a67c40_vb';
    public $Password = 'Rabecca1989';
    public $Database = 'db_a67c40_vb';
    /*
        public $Host = 'localhost';
        public $User = 'root';
        public $Password = '';
        public $Database = 'micropay';*/
}


?>