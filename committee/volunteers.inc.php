<?php
require_once(__DIR__.'/../user.inc.php');

function volunteers_load_all($mysqli, $year)
{
	$q = $mysqli->query("SELECT * FROM users WHERE 
					year='$year'
					AND FIND_IN_SET('volunteer',`roles`)>0");
	$volunteers = array();
	while($j = $q->fetch_assoc()) {
		$volunteers[] = user_load($mysqli, -1, -1, NULL, $j);
	}
	return $volunteers;
}


?>

