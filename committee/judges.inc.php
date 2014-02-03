<?php
require_once(__DIR__.'/../user.inc.php');

function judges_load_all($mysqli, $year)
{
	$q = $mysqli->query("SELECT * FROM users WHERE 
					year='$year'
					AND FIND_IN_SET('judge',`roles`)>0");
	$judges = array();
	while($j = $q->fetch_assoc()) {
		$judges[] = user_load($mysqli, -1, -1, NULL, $j);
	}
	return $judges;
}


?>

