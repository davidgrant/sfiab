<?php
require_once(__DIR__.'/../user.inc.php');

function students_load_all($mysqli, $year)
{
	$q = $mysqli->query("SELECT * FROM users WHERE 
					year='$year'
					AND FIND_IN_SET('student',`roles`)>0
					AND state = 'active'
					 ");
	$students = array();
	while($j = $q->fetch_assoc()) {
		$students[] = user_load($mysqli, -1, -1, NULL, $j);
	}
	return $students;
}


?>

