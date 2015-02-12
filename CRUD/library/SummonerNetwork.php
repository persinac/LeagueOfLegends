<?php
/**
 * Created by PhpStorm.
 * User: APersinger
 * Date: 02/12/15
 * Time: 9:48 AM
 */

class SummonerNetwork {

    public $mys;
    var $summoner_id = 0;
    var $host = "";
    var $user = "";
    var $pass = "";
    var $db = "";

    var $player_nodes = '';
    var $player_links = '';
    var $temp_table = '';
    var $global_counter = 0;
    var $recursive_calls = 0;

    var $summonerLinks = array();

    var $node_array = array();

    function __construct($sid, $host, $user, $pass, $database) {
        $this->summoner_id = $sid;
        $this->NewConnection($host, $user, $pass, $database);
    }

    function NewConnection($host, $user, $pass, $database)
    {
        $this->mys = mysqli_connect($host, $user, $pass, $database);
        if (mysqli_connect_errno()) {
            printf("Connect failed: %s\n", mysqli_connect_error());
            exit();
        }
    }

    function CloseConnection()
    {
        try {
            mysqli_close($this->mys);
            return true;
        } catch (Exception $e) {
            printf("Close connection failed: %s\n", $this->mys->error);
        }
    }

    function FetchDataForSummonerNetwork_FD($sid = -1)
    {
        if($sid == -1) {
            $sid = $this->summoner_id;
        }

        $main_string = "";
        $nodes = '"nodes":[';
        $links = '"links":[';
        $index = 1;
        $target = 0;
        $source = 0;
        $type = 1;
        $value = 1;
        $degree = 0;
        $distance = 10;
        $query = "CALL getNetworkOfSummoners($sid)";
        $s_name = $this->GetSummonerName($sid);

        $html = '<div class="recent_games_size">';
        $html .= '<table id="win_perc_by_lane_table" class="table table-striped table-hover">';
        $html .= '<thead class="header_bg">
            <th>Source Summoner ID</th>
            <th>Source</th>
            <th>Target Summoner ID</th>
            <th>Target</th>
            <th>Where</th>
            </tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $this->temp_table = $html;
        $this->global_counter = $this->global_counter + 1;

        $this->player_nodes .= '{"name":"' . $s_name . '"
                                , "type":"'.$type.'"
                                , "full_name":"' . $s_name . '"
                                , "slug":""
                                , "entity":"company"
                                , "img_hrefD":""
                                , "img_hrefL":""
                                },';
        $entity = "company";
        $slug = "";
        $this->AddToNodeArray($sid, $s_name, $type, $s_name, $slug, $entity);
        $html = '<tr>';
        $html .= '<td >'.$sid.'</td>';
        $html .= '<td >'.$source.'</td>';
        $html .= '<td > 0 </td>';
        $html .= '<td > 0 </td>';
        $html .= '<td > Main function PRE WHILE </td>';
        $html .= '</tr>';
        $this->temp_table .= $html;
        $this->mys->next_result();
        if ($result = $this->mys->query($query)) {
            $type = $type + 1;
            while ($row = $result->fetch_assoc()) {
                $target = $index;
                $degree = 1;
                $sum_id = $row['summonerId'];
                $sum_name = $row['summonerName'];
                $slug = "";
                $entity = "company";
                if ($index % 10 == 0) {
                    $distance = $distance + 10;
                }

                $this->player_nodes .= '{"name":"'.$sum_name.'"
                , "type":"'.$type.'", "full_name":"'.$sum_name.'", "slug":""
                , "entity":"company", "img_hrefD":"", "img_hrefL":"", "summonerId":"'.$sum_id.'"},';

                $this->player_links .= '{"source":'.$source.', "target":' . $target . '
                , "distance":' . $distance . ', "value":'.$value.'},';

                if(!$this->DoesPlayerExistInNodeArray($sum_id)) {
                    $this->AddToNodeArray($sum_id, $sum_name, $type, $sum_name, $slug, $entity);
                }

                if(!$this->DoesLinkExist($sid, $sum_id)) {
                    $this->AddToLinkArray($sid, $sum_id, $source, $target, $distance, $value, $degree);
                }

                $html = '<tr>';
                $html .= '<td >'.$sid.'</td>';
                $html .= '<td >'.$source.'</td>';
                $html .= '<td >'.$sum_id.'</td>';
                $html .= '<td >'.$target.'</td>';
                $html .= '<td > Main function </td>';
                $html .= '</tr>';
                $this->temp_table .= $html;
                $this->global_counter = $this->global_counter + 1;

                $this->recursive_NoS($row['summonerId'], $degree,
                    $type, $value, $source + 1,
                    $distance, $index);
                $this->recursive_calls = $this->recursive_calls + 1;
                //$source = $source + 1;
                $index = $index + 1;
            }
            $result->free();
        }

        $nodes .= substr($this->player_nodes, 0, strlen($this->player_nodes) - 1);
        $links .= substr($this->player_links, 0, strlen($this->player_links) - 1);

        $main_string = "{" . $nodes . "]," . $links . "]}";
        $html = '</tbody></table>';
        $html .= '</div>';
        $this->temp_table .= $html;
        return $main_string;
    }

    function recursive_NoS($id, $degree, $type, $value, $source, $distance, &$index) {

        $t_id = $id;
        $t_degree = $degree;
        $t_type = $type;
        $t_value = $value;

        $t_distance = ($distance * $degree);

        if($index == 1) {
            $t_source = $source;
        } else {
            $t_source = $this->recursive_calls + 1;
        }
        $html = "";
        $target = 0;
        $t_source = $index;
        $my_counter = 0;

        $query = "CALL getNetworkOfSummoners($t_id)";

        if($degree < 2) {
            $this->mys->next_result();
            if ($result = $this->mys->query($query)) {
                while ($row = $result->fetch_assoc()) {
                    $target = $index + 1;
                    $sum_id = $row['summonerId'];
                    $sum_name = $row['summonerName'];
                    $slug = "";
                    $entity = "company";

                    if(!$this->DoesPlayerExistInNodeArray($sum_id)) {
                        $this->AddToNodeArray($sum_id, $sum_name, $type, $sum_name, $slug, $entity);
                    }

                    if(!$this->DoesLinkExist($t_id, $sum_id)) {
                        $this->AddToLinkArray($t_id, $sum_id, $t_source, $target, $t_distance, $value, $degree);
                    }

                    /*if($whatIndex == -1) {

                        $html .= '<tr>';
                        $html .= '<td >'.$t_id.'</td>';
                        $html .= '<td >'.$t_source.'</td>';
                        $html .= '<td >'.$sid.'</td>';
                        $html .= '<td >'.$target.'</td>';
                        $html .= '<td > recursive_NoS ID NOT FOUND </td>';
                        $html .= '</tr>';


                        $this->player_nodes .= '{"name":"'.$row['summonerName'].'"
                        , "type":3, "full_name":"'.$row['summonerName'].'", "slug":""
                        , "entity":"company", "img_hrefD":"", "img_hrefL":"", "summonerId":"'.$row['summonerId'].'"},';

                        $this->player_links .= '{"source":'.$t_source.', "target":' . $target . '
                        , "distance":' . $t_distance . ', "value":10},';

                        $my_counter = $my_counter + 1;
                        $index = $index + 1;
                        $this->global_counter = $this->global_counter + 1;
                    } else {
                        //add link based on source at $whatIndex
                        $t_source = $this->summonerLinks[$whatIndex]->source;
                        $this->player_links .= '{"source":'.$t_source.', "target":' . $target . '
                        , "distance":' . $t_distance . ', "value":10},';
                        $html .= '<tr>';
                        $html .= '<td >'.$sid.'</td>';
                        $html .= '<td >'.$t_source.'</td>';
                        $html .= '<td >'.$t_id.'</td>';
                        $html .= '<td >'.$target.'</td>';
                        $html .= '<td > recursive_NoS ID FOUND </td>';
                        $html .= '</tr>';
                    }*/
                }
            }
            //$this->temp_table .= $html;
            $this->recursive_NoS($t_id+1, $t_degree + 1, $t_type + 1, $t_value + 1, $t_source+1, $t_distance, $index);
        }
    }

    function AddRecordToArray($sid, $source, $target) {
        $detail = new stdClass();
        $detail->summonerId = $sid;
        $detail->source = $source;
        $detail->target = $target;
        $this->summonerLinks[] = $detail;
    }

    function AddToNodeArray($sid, $name, $type, $full_name,
                $slug, $entity) {
        $detail = new stdClass();
        $detail->summonerId = $sid;
        $detail->name = $name;
        $detail->type = $type;
        $detail->full_name = $full_name;
        $detail->slug = $slug;
        $detail->entity = $entity;
        $this->node_array[] = $detail;
    }

    function AddToLinkArray($source_id, $target_id, $source, $target, $distance,
                            $value, $degree) {
        $detail = new stdClass();
        $detail->source_summonerId = $source_id;
        $detail->target_summonerId = $target_id;
        $detail->source = $source;
        $detail->target = $target;
        $detail->distance = $distance;
        $detail->value = $value;
        $detail->degree = $degree;
        $this->link_array[] = $detail;
    }

    /**
     * @param $sid - summonerID to search for
     * @return int - index of found ID, -1 if no ID is found
     */
    function SearchForIDInArray($sid) {
        $html = '<tr>';
        $html .= '<td > - </td>';
        $html .= '<td > - </td>';
        $html .= '<td > - </td>';
        $html .= '<td > - </td>';
        $html .= '<td > SEARCHING FOR: '.$sid.' </td>';
        $html .= '</tr>';
        $isFound = -1;
        for($i = 0; $i < sizeof($this->link_array); $i++) {
            if($this->link_array[$i]->source_summonerId == $sid) {
                $isFound = $i;
                $html .= '<tr>';
                $html .= '<td >'.$this->link_array[$i]->source_summonerId.'</td>';
                $html .= '<td >'.$this->link_array[$i]->source.'</td>';
                $html .= '<td > - </td>';
                $html .= '<td > - </td>';
                $html .= '<td > FOUND AT: '.$i.' </td>';
                $html .= '</tr>';
                break;
            }
        }
        $this->temp_table .= $html;
        return $isFound;
    }


    function DoesPlayerExistInNodeArray($sid) {
        $isFound = FALSE;
        for($i = 0; $i < sizeof($this->node_array); $i++) {
            if($this->node_array[$i]->summonerId == $sid) {
                $isFound = TRUE;
                break;
            }
        }
        return $isFound;
    }

    function DoesLinkExist($source_id, $target_id) {
        $isFound = FALSE;
        for($i = 0; $i < sizeof($this->link_array); $i++) {
            if($this->link_array[$i]->source_summonerId == $source_id
                && $this->link_array[$i]->target_summonerId == $target_id) {
                $isFound = TRUE;
                break;
            }
        }
        return $isFound;
    }

    function GetLinkArrayTable() {
        $html = '<div class="recent_games_size">';
        $html .= '<table id="win_perc_by_lane_table" class="table table-striped table-hover">';
        $html .= '<thead class="header_bg">
            <th>Summoner ID</th>
            <th>Source</th>
            <th>Target</th>
            </tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        for($i = 0; $i < sizeof($this->summonerLinks); $i++) {
            $html .= '<tr>';
            $html .= '<td >'.$this->summonerLinks[$i]->summonerId.'</td>';
            $html .= '<td >'.$this->summonerLinks[$i]->source.'</td>';
            $html .= '<td >'.$this->summonerLinks[$i]->target.'</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $html .= '</div>';

        return $html;
    }

    function GetNodeArrayTable() {
        $html = '<div class="recent_games_size">';
        $html .= '<table id="win_perc_by_lane_table" class="table table-striped table-hover">';
        $html .= '<thead class="header_bg">
            <th>Summoner ID</th>
            <th>Source</th>
            <th>Target</th>
            </tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        for($i = 0; $i < sizeof($this->summonerLinks); $i++) {
            $html .= '<tr>';
            $html .= '<td >'.$this->summonerLinks[$i]->summonerId.'</td>';
            $html .= '<td >'.$this->summonerLinks[$i]->source.'</td>';
            $html .= '<td >'.$this->summonerLinks[$i]->target.'</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $html .= '</div>';

        return $html;
    }

    function GetSummonerLinkArrayTable() {
        $html = '<div class="recent_games_size">';
        $html .= '<table id="win_perc_by_lane_table" class="table table-striped table-hover">';
        $html .= '<thead class="header_bg">
            <th>Summoner ID</th>
            <th>Source</th>
            <th>Target</th>
            </tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        for($i = 0; $i < sizeof($this->summonerLinks); $i++) {
            $html .= '<tr>';
            $html .= '<td >'.$this->summonerLinks[$i]->summonerId.'</td>';
            $html .= '<td >'.$this->summonerLinks[$i]->source.'</td>';
            $html .= '<td >'.$this->summonerLinks[$i]->target.'</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $html .= '</div>';

        return $html;
    }

    function GetTable() {
        return $this->temp_table;
    }

    function GetSummonerName($id)
    {
        $x = "";
        $query = 'select league_name from Players
                  WHERE playerid = ' . $id;
        $this->mys->next_result();
        if ($result = $this->mys->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $x = $row['league_name'];
            }
            $result->free();
        }
        return $x;
    }
}