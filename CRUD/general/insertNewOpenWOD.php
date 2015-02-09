<?php require_once('Connections/cboxConn.php'); ?>
<?php
session_start();

$colname_getUser = "-1";
if (isset($_SESSION['MM_UserID'])) {
  $colname_getUser = $_SESSION['MM_UserID'];
}

class wod {
	public $wod_id = "";
	public $date = "";
	public $divsion = "";
	public $score = "";
}

$wods = array();
$division = $_POST['division'];
$count = 0;
$w = new wod();
foreach($_POST as $key => $value)
{
	if($count % 2 === 0) {
		$w = new wod();
		$w->division = $division;
		$w->wod_id = substr($key, 0, 3);
	}
	
	if (substr($key, 3, strlen($key)) == "input") {
		$w->score = $value;
	} else if (substr($key, 3, strlen($key)) == "date") {
		$w->date = $value;
	}
	if($count % 2 === 1) {
		$wods[] = $w;
	}
	$count++;
}

$mysqli = new mysqli($hostname_cboxConn, $username_cboxConn, $password_cboxConn, $database_cboxConn);
	/* check connection */
if (mysqli_connect_errno()) {
	printf("Connect failed: %s\n", mysqli_connect_error());
	exit();
}

foreach ($wods as $w) {
   
	if(strlen($w->score) > 0) {
		//echo "W: " .$w->wod_id . " " .$w->date . " ".$w->division . " ".$w->score. " UID: $colname_getUser\n ";
		
		$stmt = $mysqli->prepare("insert into athlete_open_workouts values (?, ?, ?, ?, ?)");
		$stmt->bind_param( 'issss', $colname_getUser,$w->wod_id,$w->score,$w->date,$w->division);

		if($result = $stmt->execute()) {
			echo "Entered data successfully\n";
			$stmt->close();
		} else {
			echo "1 ";
		}
		
	}

}

$mysqli->close();
?>