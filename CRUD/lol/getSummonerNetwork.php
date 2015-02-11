<?php require_once('../../Connections/lol_conn.php'); ?>
<?php
/**
 * Created by PhpStorm.
 * User: APersinger
 * Date: 01/29/15
 * Time: 11:32 AM
 */

session_start();

include('../../CRUD/library/league.php');
include('../../CRUD/library/array_utilities.php');
include('../../CRUD/library/league_html_builder.php');

$lol = new League();
$lol->NewConnection($lol_host, $lol_un, $lol_pw, $lol_db);

$toReturn = (object) array('graph_data'=>'', 'table_data'=>'asdf');

$retVal = $lol->FetchDataForSummonerNetwork_FD($_SESSION['summonerId']);
$toReturn->graph_data = $retVal;
$toReturn->table_data = $lol->temp_table;
echo json_encode($toReturn);
$lol->CloseConnection();
