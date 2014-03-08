<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');
require_once('config.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

require_once('reports.inc.php');


$mysqli_old = new mysqli($dbhost, $dbuser, $dbpassword, "sfiab_gvrsf");

$year = $config['year'];

$users = array();


$mysqli->real_query("DELETE FROM emails WHERE `type`='user'");

$q = $mysqli_old->query("SELECT * FROM emails WHERE type='user'");
while($e = $q->fetch_assoc()) {
	$new_e = array();
	$new_e['name'] = $e['name'];
	$new_e['type'] = 'user';
	$new_e['description'] = $e['description'];

	if(preg_match("/([^<]*) *<([^>]*)>/", $e['from'], $matches)) {
		$from_name = $matches[1];
		$from_email = $matches[2];
	} else {
		$from_email = $e['from'];
		$from_name = NULL;
	}
	$new_e['from_name'] = trim($from_name);
	$new_e['from_email'] = trim($from_email);
	$new_e['subject'] = $e['subject'];
	$new_e['body'] = $e['body'];
	$new_e['bodyhtml'] = NULL;

	$ks = '';
	$vs = '';
	foreach($new_e as $k=>$v) {
		if($ks != '') {
			$ks .= ',';
			$vs .= ',';
		}
		$ks .= "`$k`";
		if($v === NULL) {
			$vs .= 'NULL';
		} else {
			$vs .= "'".$mysqli->real_escape_string($v)."'";
		}
	}
	print("   {$new_e['name']} From: {$new_e['from_name']} <{$new_e['from_email']}>\n");
	$mysqli->real_query("INSERT INTO emails ($ks) VALUES ($vs)");
}

?>
