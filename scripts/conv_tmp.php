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

$q = $mysqli->query("SELECT * FROM users where year=2014");
while($ud = $q->fetch_assoc()) {
	$u = user_load($mysqli, -1, -1, NULL, $ud);

	print("Loaded {$u['firstname']} {$u['lastname']}\n");

	$mysqli->query("DELETE FROM emergency_contacts WHERE uid='{$u['uid']}'");

	for($x=1;$x<=2;$x++) {
		$fn = $mysqli->real_escape_string($u["emerg{$x}_firstname"]);
		$ln = $mysqli->real_escape_string($u["emerg{$x}_lastname"]);
		$re = $mysqli->real_escape_string($u["emerg{$x}_relation"]);
		$phone1 = trim($u["emerg{$x}_phone1"]);
		$phone2 = trim($u["emerg{$x}_phone2"]);
		$phone3 = trim($u["emerg{$x}_phone3"]);
		$email = $mysqli->real_escape_string($u["emerg{$x}_email"]);

		if($fn == '' || $fn === NULL) continue;


		$str = "INSERT INTO emergency_contacts(`uid`,`firstname`,`lastname`,`email`,`phone1`,`phone2`,`phone3`,`relation`)
				VALUES('{$u['uid']}','$fn','$ln','$email','$phone1','$phone2','$phone3','$re')";
		print($str."\n");
		$mysqli->real_query($str);
		print($mysqli->error);
	}
}

?>
