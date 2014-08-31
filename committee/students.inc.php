<?php
require_once(__DIR__.'/../user.inc.php');

function students_load_all($mysqli, $year)
{
	$q = $mysqli->query("SELECT * FROM users WHERE 
					year='$year'
					AND FIND_IN_SET('student',`roles`)>0
					AND enabled = '1'
					 ");
	$students = array();
	while($j = $q->fetch_assoc()) {
		$students[] = user_load($mysqli, -1, -1, NULL, $j);
	}
	return $students;
}

function students_load_all_accepted($mysqli, $year=0)
{
	global $config;
	if($year == 0) $year = $config['year'];

	$q = $mysqli->query("SELECT * FROM users WHERE 
					year='$year'
					AND FIND_IN_SET('student',`roles`)>0
					AND enabled = '1'
					AND s_accepted = 1
					 ");
	$students = array();
	while($j = $q->fetch_assoc()) {
		$students[(int)$j['uid']] = user_load($mysqli, -1, -1, NULL, $j);
	}
	return $students;
}

?>
