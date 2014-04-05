<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');
require_once('config.inc.php');

print("remove the exit line at the top of the file");
//exit();

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

$year = $config['year'];

$users = array();

$q = $mysqli->query("SELECT * FROM projects where year=2014");
$c = 0;
while($d = $q->fetch_assoc()) {
	$p = project_load($mysqli, -1, $d);

	$q1  =$mysqli->query("SELECT * FROM users WHERE s_accepted=0 AND s_pid='{$p['pid']}'");
	if($q1->num_rows == 0) {
		$q1 = $mysqli->query("UPDATE projects SET accepted=1 WHERE pid='{$p['pid']}'");
		$c++;
	}
}

print("Set $c projects to accepted\n");



?>
