<?php session_start(); ?>
<?php 
require_once('../../Connections/cboxConn.php');

unset($_SESSION['MM_Username']);
unset($_SESSION['MM_UserGroup']);
unset($_SESSION['MM_UserID']);
unset($_SESSION['MM_Admin']);
session_destroy();
?>