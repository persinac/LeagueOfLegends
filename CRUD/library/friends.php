<?php
/**
 * Created by PhpStorm.
 * User: APersinger
 * Date: 10/15/14
 * Time: 2:22 PM
 */

class Friends {
    public $mys;
    var $friend_requests = array();
    var $unread_friend_req = array();
    var $friends = array();

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

    /*************** GETTERS AND SETTERS *****************/

    /*
     * Builds an array of friend requests
     * Search against the "requested_friend_id" field to see all pending
     * friend requests
     * params:
     *	@u - user_id
     *  @opt - "  "
     *
     * returns:
     *	@retVal - 0 for failure, 1 for success
     */
    function BuildFriendRequests($u) {
        $retVal = -1;
        $query = "select CONCAT(ui.first_name, ' ', ui.last_name) AS user_name, upi.picture_url,
                    fr.user_id, fr.requested_friend_id,
                    fr.request_sent, fr.request_read from friend_requests fr
                    JOIN user_info ui ON ui.user_id = fr.user_id
                    JOIN user_pub_info upi ON upi.user_id = fr.user_id
                    where fr.requested_friend_id = $u
                    AND (fr.request_read = 1 OR fr.request_read = 0)";
            if ($result = $this->mys->query($query)) {
                while ($row = $result->fetch_assoc()) {
                    $detail = (object) array('user_id' => '', 'picture_url' => '', 'name' => '');
                    $detail->user_id = $row["user_id"];
                    $detail->picture_url = $row["picture_url"];
                    $detail->name = $row["user_name"];
                    $this->friend_requests[] = $detail;
                }
                $result->free();
                $retVal = 1;
        } else {
            $retVal = 0;
        }

        return $retVal;
    }

    function BuildUnreadFriendRequests($u) {
        $retVal = -1;
        $query = "select CONCAT(ui.first_name, ' ', ui.last_name) AS user_name, upi.picture_url,
                    fr.user_id, fr.requested_friend_id,
                    fr.request_sent, fr.request_read from friend_requests fr
                    JOIN user_info ui ON ui.user_id = fr.user_id
                    JOIN user_pub_info upi ON upi.user_id = fr.user_id
                    where fr.requested_friend_id = $u
                    AND fr.request_read = 0";
        if ($result = $this->mys->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $detail = (object) array('user_id' => '', 'picture_url' => '', 'name' => '');
                $detail->user_id = $row["user_id"];
                $detail->picture_url = $row["picture_url"];
                $detail->name = $row["user_name"];
                $this->unread_friend_req[] = $detail;
            }
            $result->free();
            $retVal = 1;
        } else {
            $retVal = 0;
        }

        return $retVal;
    }

    /*
     * Gets an array of friend requests
     *
     * returns:
     *	@this->friend_requests
     */
    function GetFriendRequests() {
        return $this->friend_requests;
    }

    function GetUnreadFriendRequests() {
        return var_dump($this->unread_friend_req);
    }

    function HasUnreadFriendRequests() {
        $retVal = false;
        if (sizeof($this->unread_friend_req) > 0) {
            $retVal = true;
        }

        return $retVal;
    }

    function HasFriendRequests() {
        $retVal = false;
        if (sizeof($this->friend_requests) > 0) {
            $retVal = true;
        }

        return $retVal;
    }

    function BuildFriends($u) {
        $retVal = -1;
        $query = "select CONCAT(ui.first_name, ' ', ui.last_name) AS user_name,
                    upi.picture_url, f.user_id, friend_id, friend_since
                    from friends f
                    JOIN user_info ui ON ui.user_id = f.friend_id
                    JOIN user_pub_info upi ON upi.user_id = f.friend_id
                    where f.user_id = $u";
        if ($result = $this->mys->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $detail = (object) array('friend_id' => '', 'picture_url' => '', 'name' => '');
                $detail->friend_id = $row["friend_id"];
                $detail->picture_url = $row["picture_url"];
                $detail->name = $row["user_name"];
                $this->friends[] = $detail;
            }
            $result->free();
            $retVal = 1;
        } else {
            $retVal = 0;
        }

        return $retVal;
    }

    /*
     * Gets an array of friend requests
     *
     * returns:
     *	@this->friend_requests
     */
    function GetFriends() {
        return $this->friends;
    }

    /************ INSERT / UPDATE / DELETE ****************/

    /*
     * Inserts a new friend into the friends table
     *
     * params:
     * 	@user_id - User id of main user
     * 	@friend_id - ID of friend
     *  @receive_updates - either 1 or 0, currently defaults to 1
     */
    function InsertNewFriend($user_id, $friend_id, $receive_updates) {
        $query = "INSERT INTO friends VALUES(?,?,?,?)";
        $retVal = 0;
        $today = Date('Y-m-d');

        $stmt = $this->mys->prepare($query);
        $stmt->bind_param( 'iisi', $user_id, $friend_id, $today, $receive_updates);
        if($result = $stmt->execute()) {
            $stmt->close();
            $retVal = 1;
        } else {
            $retVal = 0;
        }
        return $retVal;
    }

    /*
     * Updates a friend request to be ACCEPTED
     *
     * params:
     * 	@user_id - User id of user requesting friend
     * 	@friend_id - ID of friend to be requested
     */
    function UpdateFriendRequest($user_id, $friend_id) {
        $query = "UPDATE friend_requests SET request_read = 2 WHERE user_id = ? AND requested_friend_id = ?";
        $stmt = $this->mys->prepare($query);
        $stmt->bind_param( 'ii', $friend_id, $user_id);
        if($result = $stmt->execute()) {
            $stmt->close();
            $retVal = 1;
        } else {
            $retVal = 0;
        }
        return $retVal;
    }

    /*
     * Updates ALL friend requests to be read of a certain user_id
     *
     * params:
     * 	@user_id - User id of user reading friend request
     */
    function UpdateAllFriendRequests($user_id) {
        $query = "UPDATE friend_requests SET request_read = 1 WHERE requested_friend_id = ?";
        echo $query;
        $stmt = $this->mys->prepare($query);
        $stmt->bind_param( 'i', $user_id);
        if($result = $stmt->execute()) {
            $stmt->close();
            $retVal = 1;
        } else {
            $retVal = 0;
        }
        return $retVal;
    }

    /*
     * Sends a friend request
     *
     * params:
     * 	@user_id - User id of user requesting friend
     * 	@friend_id - ID of friend to be requested
     */
    function SendFriendRequest($user_id, $friend_id) {
        $request_sent = 1;
        $request_read = 0;
        $query = "INSERT INTO friend_requests VALUES(?,?,?,?)";
        $stmt = $this->mys->prepare($query);
        $stmt->bind_param( 'iiii', $user_id, $friend_id, $request_sent, $request_read);
        if($result = $stmt->execute()) {
            $stmt->close();
            $retVal = 1;
        } else {
            $retVal = 0;
        }
        return $retVal;
    }
}
?>