<?php session_start(); ?>
<?php 
require_once('../../Connections/cboxConn.php');

unset($_SESSION['MM_cal_user_id']);
session_destroy();
?>