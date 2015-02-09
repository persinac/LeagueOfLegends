<?php
/**
 * Created by PhpStorm.
 * User: APersinger
 * Date: 10/16/14
 * Time: 11:25 AM
 */

class Groups {
    public $mys;
    var $group_requests = array();
    var $groups = array();
    var $group_members = array();
    var $group_details = array();
    var $unread_group_req = array();
    var $maxGroupID = -1;

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
     * Builds an array of group requests
     * Search against the "requested_group_id" field to see all pending
     * group requests
     * params:
     *	@u - user_id
     *  @opt - "  "
     *
     * returns:
     *	@retVal - 0 for failure, 1 for success
     */
    function BuildGroupRequests($u) {
        $retVal = -1;
        $query = "select gd.group_id, gd.name, gd.picture_url FROM group_requests gr
                    JOIN group_header gh ON gr.group_id = gh.group_id
                    JOIN group_details gd ON gh.group_id = gd.group_id
                    WHERE user_id = $u AND gr.request_read < 2";
        if ($result = $this->mys->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $detail = (object) array('group_id'=>'', 'group_name' => '', 'picture_url' => '');
                $detail->group_id = $row["group_id"];
                $detail->picture_url = $row["picture_url"];
                $detail->group_name = $row["group_name"];
                $this->group_requests[] = $detail;
            }
            $result->free();
            $retVal = 1;
        } else {
            $retVal = 0;
        }

        return $retVal;
    }

    /*
     * Gets an array of group requests
     *
     * returns:
     *	@this->group_requests
     */
    function GetGroupRequests() {
        return $this->group_requests;
    }

    function BuildGroups($u) {
        $retVal = -1;
        $query = "select gh.group_id, gh.creator_id, gm.user_id, gd.picture_url,
                    CONCAT(ui.first_name, ' ', ui.last_name) AS creator_name,
                    inception, name AS group_name, gd.location from group_header gh
                     JOIN group_details gd ON gh.group_id = gd.group_id
                    JOIN user_info ui ON ui.user_id = gh.creator_id
                    JOIN group_members gm ON gh.group_id = gm.group_id
                    WHERE gm.user_id =  $u";
        if ($result = $this->mys->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $detail = (object) array('picture_url' => '','group_id' => '', 'creator_id' => '', 'creator_name' => '',
                    'inception' => '', 'group_name' => '', 'location' => '');
                $detail->picture_url = $row["picture_url"];
                $detail->group_id = $row["group_id"];
                $detail->creator_id = $row["creator_id"];
                $detail->creator_name = $row["creator_name"];
                $detail->inception = $row["inception"];
                $detail->group_name = $row["group_name"];
                $detail->location = $row["location"];
                $this->groups[] = $detail;
            }
            $result->free();
            $retVal = 1;
        } else {
            $retVal = 0;
        }

        return $retVal;
    }

    function BuildGroupsOnGroupID($gid) {
        $retVal = -1;
        $query = "select gh.group_id, gh.creator_id,
                    CONCAT(ui.first_name, ' ', ui.last_name) AS creator_name,
                    inception, name AS group_name, gd.location from group_header gh
                     JOIN group_details gd ON gh.group_id = gd.group_id
                    JOIN user_info ui ON ui.user_id = gh.creator_id
                    WHERE gh.group_id = $gid";
        if ($result = $this->mys->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $detail = (object) array('picture_url' => '','group_id' => '', 'creator_id' => '', 'creator_name' => '',
                    'inception' => '', 'group_name' => '', 'location' => '');
                $detail->picture_url = "-";
                $detail->group_id = $row["group_id"];
                $detail->creator_id = $row["creator_id"];
                $detail->creator_name = $row["creator_name"];
                $detail->inception = $row["inception"];
                $detail->group_name = $row["group_name"];
                $detail->location = $row["location"];
                $this->groups[] = $detail;
            }
            $result->free();
            $retVal = 1;
        } else {
            $retVal = 0;
        }

        return $retVal;
    }

    function BuildMembersOfGroup($gid) {
        $retVal = -1;
        $query = "call getMembersOfGroup($gid)";
        if ($result = $this->mys->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $detail = (object) array('picture_url' => '','user_name' => '',
                    'join_date' => '', 'user_id' => '');
                $detail->picture_url = $row["picture_url"];
                $detail->user_id = $row["user_id"];
                $detail->user_name = $row["user_name"];
                $detail->join_date = $row["join_date"];
                $this->group_members[] = $detail;
            }
            $result->free();
            $retVal = 1;
        } else {
            $retVal = 0;
            echo $this->mys->error;
        }

        return $retVal;
    }

    /*
     * Gets an array of groups
     *
     * returns:
     *	@this->group_members
     */
    function GetGroupMembers() {
        return $this->group_members;
    }

    /*
     * Gets an array of groups
     *
     * returns:
     *	@this->groups
     */
    function GetGroups() {
        return $this->groups;
    }

    function SetMaxGroupID() {
        $query = "select MAX(group_id) AS group_id
                    from group_header";
        if ($result = $this->mys->query($query)) {
            while ($row = $result->fetch_assoc()) {
                if(is_numeric($row['group_id'])) {
                    $this->maxGroupID = $row['group_id'] + 1;
                } else {
                    $this->maxGroupID = 1;
                }
            }
            $result->free();
        } else {
            $retVal = 0;
        }
    }

    function BuildGroupDetails($group_id) {
        $retVal = -1;
        $query = "call getGroupDetails($group_id)";
        if ($result = $this->mys->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $detail = (object) array('picture_url' => '', 'creator' => '', 'creator_id'=>'','inception' => '',
                    'location' => '', 'numOfMems' => '', 'group_name' => '', 'bio' => 'SHORT BIO');
                $detail->picture_url = $row["picture_url"];
                $detail->creator = $row["user_name"];
                $detail->creator_id = $row["creator_id"];
                $detail->inception = $row["inception"];
                $detail->location = $row["location"];
                $detail->group_name = $row["group_name"];
                $detail->numOfMems = $row["numOfMems"];
                $this->group_details[] = $detail;
            }
            $result->free();
            $retVal = 1;
        } else {
            $retVal = 0;
        }

        return $retVal;
    }

    function GetGroupDetails() {
        return $this->group_details;
    }

    function BuildMembersNotInGroup($group_id) {
        $retVal = -1;
        $query = "call getUsersNotInGroup($group_id)";
        //echo $query;
        if ($result = $this->mys->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $detail = (object) array('picture_url' => '', 'full_name' => '', 'user_id'=>'', 'invited'=>'');
                $detail->picture_url = $row["picture_url"];
                $detail->full_name = $row["full_name"];
                $detail->user_id = $row["user_id"];
                $detail->invited = $row["invited"];
                $this->group_members[] = $detail;
            }
            $result->free();
            $retVal = 1;
        } else {
            $retVal = 0;
        }

        return $retVal;
    }

    /*
     * Returns a comma delimited string of user ids
     *
     * params:
     *  $id - group id
     */
    function GetAllMembersUserIDs($id) {
        $retVal = "";
        $query = "select * from group_members where group_id = $id";
        if ($result = $this->mys->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $retVal .= $row['user_id'] . ',';
            }
        }
        return $retVal;
    }

    /************ INSERT / UPDATE / DELETE ****************/

    /*
     * Creates a new group
     *
     * params:
     * 	$user_id - User id of creator of the group
     * 	$group_name - Name of the group
     *  $invite_only - either 1 or 0, determines if users can request to be a part of a group or not
     */
    function CreateNewGroup($user_id) {
        $query = "INSERT INTO group_header VALUES(?,?,?)";
        $retVal = -1;
        $today = Date('Y-m-d');

        if($this->maxGroupID == -1) {
            $retVal = 4;
        } else {
            $stmt = $this->mys->prepare($query);
            $stmt->bind_param( 'iis', $this->maxGroupID, $user_id, $today);
            if($result = $stmt->execute()) {
                $stmt->close();
                $retVal = 1;
            } else {
                $retVal = 0;
            }
        }
        return $retVal;
    }

    function InsertNewGroupDetail($user_id, $group_name, $invite_only, $location) {
        $picture_url = "-";
        $today = Date('Y-m-d');
        $query = "INSERT INTO group_details VALUES(?,?,?,?,?)";
        if($this->maxGroupID == -1) {
            $retVal = 4;
        } else {
            $stmt = $this->mys->prepare($query);
            $stmt->bind_param( 'issis', $this->maxGroupID, $group_name, $location, $invite_only, $picture_url);
            if($result = $stmt->execute()) {
                $stmt->close();
                $retVal = $this->InsertNewGroupMember($this->maxGroupID, $user_id, $today);
                //echo $retVal;
                if(trim($retVal) == 0) {
                    $retVal = 5; //error
                } else {
                    $retVal = 2; //success
                }

            } else {
                $retVal = 0;
            }
        }
        return $retVal;
    }

    /*
     * Updates a group request to be ACCEPTED
     *
     * params:
     * 	@user_id - User id of user requesting friend
     * 	@group_id - ID of friend to be requested
     */
    function AcceptGroupRequest($user_id, $group_id) {
        $query = "UPDATE group_requests SET request_read = 2 WHERE user_id = ? AND group_id = ?";
        $stmt = $this->mys->prepare($query);
        $stmt->bind_param( 'ii', $user_id, $group_id);
        if($result = $stmt->execute()) {
            $stmt->close();
            $retVal = 1;
        } else {
            $retVal = 0;
        }
        return $retVal;
    }

    /*
     * Updates a group request to be DECLINED
     *
     * params:
     * 	@user_id - User id of user requesting friend
     * 	@group_id - ID of friend to be requested
     */
    function DeclineGroupRequest($user_id, $group_id) {
        $query = "UPDATE group_requests SET request_read = 9 WHERE user_id = ? AND group_id = ?";
        $stmt = $this->mys->prepare($query);
        $stmt->bind_param( 'ii', $user_id, $group_id);
        if($result = $stmt->execute()) {
            $stmt->close();
            $retVal = 1;
        } else {
            $retVal = 0;
        }
        return $retVal;
    }

    /*
     * User joins group
     *
     * params:
     * 	@user_id - User id of user requesting friend
     * 	@group_id - ID of friend to be requested
     */
    function JoinGroup($user_id, $group_id, $date) {
        $query = "INSERT INTO group_members VALUES (?,?,?)";
        $stmt = $this->mys->prepare($query);
        $stmt->bind_param( 'iis', $group_id, $user_id, $date);
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
    function SendGroupRequest($user_id, $friend_id) {
        $request_sent = 1;
        $request_read = 0;
        $query = "INSERT INTO group_requests VALUES(?,?,?,?)";
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

    function InsertNewGroupMember($gid, $uid, $date) {
        $query = "INSERT INTO group_members VALUES(?,?,?)";
        $stmt = $this->mys->prepare($query);
        $stmt->bind_param( 'iis', $gid, $uid, $date);
        if($result = $stmt->execute()) {
            $stmt->close();
            $retVal = 1;
        } else {
            $retVal = 0;
        }
        return $retVal;
    }

    function UpdateGroupPicture($gid, $purl) {
        $query = "update group_details set picture_url=? where group_id=?";
        $stmt = $this->mys->prepare($query);
        $stmt->bind_param( 'si', $purl, $gid);
        if($result = $stmt->execute()) {
            $stmt->close();
            $retVal = 1;
        } else {
            $retVal = 0;
        }
        return $retVal;
    }

    function UpdateGroupLocation($gid, $loc) {
        //echo "$gid, $loc\n";
        $query = "update group_details set location=? where group_id=?";
        $stmt = $this->mys->prepare($query);
        $stmt->bind_param( 'si', $loc, $gid);
        if($result = $stmt->execute()) {
            $stmt->close();
            $retVal = 1;
        } else {
            $retVal = 0;
        }
        return $retVal;
    }

    function GroupRequestUser($group_id, $user_id) {
        $group_req = 1;
        $user_req = 0;
        $read = 0;
        $query = 'INSERT INTO group_requests VALUES(?,?,?,?,?)';
        $stmt = $this->mys->prepare($query);
        $stmt->bind_param( 'iiiii', $group_id, $user_id, $group_req, $user_req, $read);
        if($result = $stmt->execute()) {
            $stmt->close();
            $retVal = 1;
        } else {
            $retVal = 0;
        }
        return $retVal;
    }

    function UtilMoveNext() {
        $this->mys->next_result();
    }
}

class Group_Workouts extends Groups{
    var $group_name = "";
    var $group_workout_dates = array();
    var $group_workouts = array();
    var $global_index = 1;
    var $global_time = 1;
    var $warmup_index = 1;
    var $str_index = 1;
    var $cond_index = 1;
    var $speed_index = 1;
    var $core_index = 1;
    var $warmups = array();
    var $strength = array();
    var $condit = array();
    var $speed = array();
    var $core = array();
    var $rest = array();
    /*
     * Returns the max workout ID for the date given
     *
     * params:
     *  $date - ...self-explanatory
     */
    function GetMaxWorkoutID($gid, $date) {
        $retVal = 99;
        $query = "select CASE WHEN MAX(workout_id) IS NULL THEN -1
                    ELSE MAX(workout_id)
                  END AS workout_id from group_workouts where group_id = $gid AND date = '$date'";

        if ($result = $this->mys->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $retVal = $row["workout_id"];
            }
            $result->free();
        } else {
            $retVal = 0;
        }
        return $retVal;
    }

    /*
     * This is to display the workouts on the gorup page
     * that the group admin has put out
     *
     */
    function BuildGroupWorkoutDates($group_id) {
        $retVal = -1;
        $query = "select workout_id, date from group_workouts where group_id = $group_id ORDER BY date, workout_id ";
        //echo "Build group workout dates: " . $query . "\n";
        if ($result = $this->mys->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $detail = (object) array('wid' => '', 'date' => '');
                $detail->wid = $row["workout_id"];
                $detail->date = $row["date"];
                $this->group_workout_dates[] = $detail;
            }
            $result->free();
            $retVal = 1;
            //echo "RETVAL: $retVal\n";
        } else {
            echo $this->mys->error;
            //var_dump($this->mys);
            $retVal = 0;
        }
        return $retVal;
    }

    /*
	 * Builds Group Workouts
	 * params:
	 *	@u - user_id
	 *  @opt - " AND date = '<YYYY-MM-DD>' "
	 *
	 * returns:
	 *	@a - new array of all workouts
	 */
    function BuildGroupWorkouts($u, $opt) {
        $retVal = -1;
        $query = "SELECT gd.name AS group_name, DATE_FORMAT(date, '%Y-%m-%d') AS date,
                  gw.group_id, workout_id, warmup, strength, conditioning,
                  speed, core, mobility
                  FROM group_workouts gw
                  JOIN group_details gd ON gd.group_id = gw.group_id
                  WHERE gw.group_id IN (SELECT group_id
                                       FROM group_members
                                       WHERE user_id = $u)
                    $opt
                    ORDER BY date";
        //echo $query;
        if ($result = $this->mys->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $detail = (object) array('id' => '', 'title' => '',
                    'start' => '', 'end' => '', 'description' => '',
                    'color' => '','t_date'=>'');
                $this->global_time = 3;
                $detail->id = 'g'.$row['group_id'] . '_' . $row['workout_id'];
                $detail->title = $row['group_name'] . ' Workout';
                $detail->start = $row["date"] . "T1".$this->global_time.":00:00";
                $detail->end = $row["date"] . "T1".$this->global_time.":59:00";
                $detail->t_date = $row["date"];
                if(strlen($row['warmup']) > 3) {
                    $detail->description .= "<h4>Warmup</h4>".$row["warmup"]."<p></p>";
                }
                if(strlen($row['strength']) > 3) {
                    $detail->description .= "<h4>Strength</h4>".$row["strength"]."<p></p>";
                }

                if(strlen($row['conditioning']) > 3) {
                    $detail->description .= "<h4>Conditioning</h4>".$row["conditioning"]."<p></p>";
                }

                if(strlen($row['speed']) > 3) {
                    $detail->description .= "<h4>Speed</h4>".$row["speed"]."<p></p>";
                }

                if(strlen($row['core']) > 3) {
                    $detail->description .= "<h4>Core</h4>".$row["core"]."<p></p>";
                }

                if(strlen($row['mobility']) > 3) {
                    $detail->description .= "<h4>Mobility</h4>".$row["mobility"]."<p></p>";
                }
                $detail->color = 'rgb(240,84,230)';
                $this->group_workouts[] = $detail;
            }
            $result->free();
            $retVal = 1;
        } else {
            $retVal = 0;
        }
        return $retVal;
    }


    /*
	 * Builds Group Workouts
	 * params:
	 *	@u - user_id
	 *  @opt - " AND date = '<YYYY-MM-DD>' "
	 *
	 * returns:
	 *	@a - new array of all workouts
	 */
    function BuildGroupWorkoutDetails($gid, $wid, $date) {
        $retVal = -1;
        $query = "SELECT gd.name AS group_name, DATE_FORMAT(date, '%Y-%m-%d') AS date,
                  gw.group_id, workout_id, warmup, strength, conditioning,
                  speed, core, mobility
                  FROM group_workouts gw
                  JOIN group_details gd ON gd.group_id = gw.group_id
                  WHERE gw.group_id = $gid
                  AND date = '".$date."'
                  AND workout_id = $wid";
        //echo $query;
        if ($result = $this->mys->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $detail = (object) array('id' => '',
                    'group_name' => '',
                    'description' => '',
                    'warmup' => '',
                    'strength' => '',
                    'conditioning' => '',
                    'speed' => '',
                    'core' => '',
                    'mobility' => '',
                    't_date'=>'');
                $detail->id = 'g'.$row['group_id'] . '_' . $row['workout_id'];
                $detail->group_name = $row["group_name"];
                $detail->t_date = $row["date"];
                if(strlen($row['warmup']) > 3) {
                    $detail->description .= "<h4>Warmup</h4>".$row["warmup"]."<p></p>";
                    $detail->warmup = $row["warmup"];
                }
                if(strlen($row['strength']) > 3) {
                    $detail->description .= "<h4>Strength</h4>".$row["strength"]."<p></p>";
                    $detail->strength = $row["strength"];
                }

                if(strlen($row['conditioning']) > 3) {
                    $detail->description .= "<h4>Conditioning</h4>".$row["conditioning"]."<p></p>";
                    $detail->conditioning = $row["conditioning"];
                }

                if(strlen($row['speed']) > 3) {
                    $detail->description .= "<h4>Speed</h4>".$row["speed"]."<p></p>";
                    $detail->speed = $row["speed"];
                }

                if(strlen($row['core']) > 3) {
                    $detail->description .= "<h4>Core</h4>".$row["core"]."<p></p>";
                    $detail->core = $row["core"];
                }

                if(strlen($row['mobility']) > 3) {
                    $detail->description .= "<h4>Mobility</h4>".$row["mobility"]."<p></p>";
                    $detail->mobility = $row["mobility"];
                }
                $this->group_workouts[] = $detail;
            }
            $result->free();
            $retVal = 1;
        } else {
            $retVal = 0;
        }
        return $retVal;
    }

    function GetGroupWorkoutDates() {
        return $this->group_workout_dates;
    }

    function GetGroupWorkouts() {
        return $this->group_workouts;
    }

    /******************* INSERT UPDATE DELETE **************************/
    /*
     * Inserts new workout
     *
     * returns:
     *  -1 if the SQL didn't even execute
     *  0 if the sql executed but encountered an error
     *  1 if the sql executed successfully
     */
    function InsertWorkout($gid, $wid, $date, $warmup, $strength,
                            $cond, $speed, $core, $mob) {
        $retVal = -1;
        $query = "INSERT INTO group_workouts VALUES(?,?,?,?,?,?,?,?,?)";
        $stmt = $this->mys->prepare($query);
        $stmt->bind_param( 'iisssssss', $gid, $wid, $date, $warmup, $strength,
                            $cond, $speed, $core, $mob);
        if($result = $stmt->execute()) {
            $stmt->close();
            $retVal = 1;
        } else {
            $retVal = 0;
        }
        return $retVal;
    }
}