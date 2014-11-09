<?php
require_once('user.inc.php');
require_once('project.inc.php');

function sponsors_load_all($mysqli, $year = NULL)
{
	global $config;
	if($year === NULL) $year = $config['year'];
	$year = intval($year);

	$q = $mysqli->query("SELECT * FROM users WHERE 
					year='$year'
					AND FIND_IN_SET('sponsor',`roles`)>0
					AND enabled='1'");
	$sponsors = array();
	while($j = $q->fetch_assoc()) {
		$sponsors[(int)$j['uid']] = user_load($mysqli, -1, -1, NULL, $j);
	}
	return $sponsors;
}

function sponsors_load_for_select($mysqli, $year = NULL) 
{
	global $config;
	if($year === NULL) $year = $config['year'];
	$year = intval($year);

	$q = $mysqli->query("SELECT uid,organization FROM users WHERE 
					year='$year'
					AND FIND_IN_SET('sponsor',`roles`)>0
					AND enabled='1'
					ORDER BY organization");
	$sponsors = array();
	while($sp = $q->fetch_assoc()) {
		$sponsors[(int)$sp['uid']] = $sp['organization'];
	}
	return $sponsors;
}

function sponsor_create_or_get($mysqli, $org, $year = NULL)
{
	global $config;
	if($year === NULL) $year = $config['year'];
	$year = intval($year);

	$org = $mysqli->real_escape_string($org);
	$q = $mysqli->query("SELECT uid,organization FROM users WHERE
					year='$year'
					AND FIND_IN_SET('sponsor',`roles`)>0
					AND enabled='1'
					AND organization = '$org'
					ORDER BY organization");
	if($q->num_rows > 0) {
		$sp = $q->fetch_assoc();
		return (int)$sp['uid'];
	}

	$sponsor_u = user_create($mysqli, NULL, '', 'sponsor', $year);
	$sponsor_u['enabled'] = 1;
	$sponsor_u['organization'] = $org;
	user_save($mysqli, $sponsor_u);
	return $sponsor_u['uid'];
}

?>
