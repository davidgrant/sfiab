<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');
require_once('config.inc.php');

print("Exit for protection.");

exit();

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);


$mysqli_old = new mysqli($dbhost, $dbuser, $dbpassword, "sfiab_gvrsf");

$year = $config['year'];

$users = array();

$q = $mysqli_old->query("SELECT * FROM users WHERE FIND_IN_SET('committee',`types`) > 0");
while($u = $q->fetch_assoc()) {
	if(!array_key_exists($u['email'], $users)) {
		$users[$u['email']] = $u;
		continue;
	}

	if($users[$u['email']]['year'] < $u['year']) {
		$users[$u['email']] = $u;
	}
}

function s($str) {
	if($str === NULL) 
		return "NULL";
	return "'$str'";
}

foreach($users as $e=>$u) {
	if($u['deleted'] == 'yes') continue;
	print($u['firstname'] . ' '.$u['lastname'].' '.$u['year']. "\n");

	$un = strstr($e, '@', true);

	$un = s($un);
	$fn = s($u['firstname']);
	$ln = s($u['lastname']);
	$sal = s($u['salutation']);
	$p1 = s($u['phonehome']);
	$p2 = s($u['phonework']);

	$str = "INSERT INTO users(`username`,`password_expired`, `firstname`,`lastname`,`salutation`,`email`,`phone1`,`phone2`,`year`,`state`,`roles`) VALUES($un,1,$fn,$ln,$sal,'$e',$p1,$p2,2014,'active','committee')";
	print($str."\n");
	$mysqli->real_query($str);
	print($mysqli->error);
}

?>
