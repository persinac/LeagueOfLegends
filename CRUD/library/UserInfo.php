<?php
/**
 * Created by PhpStorm.
 * User: APersinger
 * Date: 11/06/14
 * Time: 10:55 AM
 */

class UserInfo {
    public $mys;
    private $user_id;
    private $first_name;
    private $last_name;
    private $email;

    var $friend_requests = array();
    var $group_requests = array();

    function __construct($id) {
        $this->id = $id;
    }

    function NewConnection($host, $user, $pass, $database) {
        $this->mys = mysqli_connect($host, $user, $pass, $database);
        if (mysqli_connect_errno()) {
            printf("Connect failed: %s\n", mysqli_connect_error());
            exit();
        }
    }

    function CloseConnection() {
        try {
            mysqli_close($this->mys);
            return true;
        } catch (Exception $e) {
            printf("Close connection failed: %s\n", $this->mys->error);
        }
    }

    function SetUserID($i) {
        $this->user_id = $i;
    }

    function GetUserID() {
        return $this->user_id;
    }

    function BuildFriendRequests() {
        //$query = "SELECT * FROM friend_requests WHERE "
    }

    function BuildGroupRequests() {

    }

    function GetFriendRequests(){

    }

    function GetGroupRequests() {

    }

} 