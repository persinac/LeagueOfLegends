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
include('../../CRUD/library/SummonerNetwork.php');
include('../../CRUD/library/array_utilities.php');
include('../../CRUD/library/league_html_builder.php');

$network = new SummonerNetwork($_SESSION['summonerId'],
    $lol_host, $lol_un, $lol_pw, $lol_db);

$retVal = $network->FetchDataForSummonerNetwork_FD();

$toReturn = (object) array('graph_data'=>'', 'table_data'=>'asdf', 'table_data_2'=>'asdf');
$toReturn->graph_data = $retVal;
$toReturn->table_data = $network->GetTable();
$toReturn->table_data_2 = $network->GetSummonerLinkArrayTable();
echo json_encode($toReturn);

$network->CloseConnection();
/*
$lol = new League();
$lol->NewConnection($lol_host, $lol_un, $lol_pw, $lol_db);


$lol->CloseConnection();*/
