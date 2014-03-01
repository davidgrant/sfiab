<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');
require_once('config.inc.php');

print("exit for proection");
exit();

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);


$mysqli_old = new mysqli($dbhost, $dbuser, $dbpassword, "sfiab_gvrsf");

$year = $config['year'];


$q = $mysqli_old->query("SELECT * FROM award_awards WHERE year='$year'");
while($a = $q->fetch_assoc()) {
	print($a['name'] . "\n");
	$a['prizes'] = array();
	$aid = $a['id'];
	$q1 = $mysqli_old->query("SELECT * FROM award_prizes WHERE award_awards_id='$aid'");
	while($p = $q1->fetch_assoc()) {
		print("   ".$p['prize'] . "\n");
		$a['prizes'][] = $p;
	}
	$a['categories'] = array();
	$q1 = $mysqli_old->query("SELECT * FROM award_awards_projectcategories WHERE award_awards_id='$aid'");
	while($c =$q1->fetch_assoc()) {
		$a['categories'][] = $c['projectcategories_id'];
	}
	$awards[] = $a;
}

$sponsors = array();
$q = $mysqli_old->query("SELECT * FROM sponsors");
while($s = $q->fetch_assoc()) {
	$sponsors[$s['id']] = $s['organization'];
}


$q = $mysqli->query("SELECT * FROM awards WHERE year='$year'");
while($a = $q->fetch_assoc()) {
	$mysqli->query("DELETE FROM award_prizes WHERE award_id='{$a['id']}'");
}
$mysqli->query("DELETE FROM awards WHERE year='$year'");

$award_type = array(1 => 'divisional',2=>'special',3=>'other',4=>'grand',5=>'other');

// new query
foreach($awards as $a) {
	$n = array();
	$n['name'] = $mysqli->real_escape_string($a['name']);
	$n['description'] = $mysqli->real_escape_string($a['description']);
	$n['criteria'] = $mysqli->real_escape_string($a['criteria']);
	$n['presenter'] = $mysqli->real_escape_string($a['presenter']);
	$n['notes'] = '';
	$n['year'] = $year;
	$n['include_in_script'] = ($a['excludeformac'] == 1) ? 0 : 1;
	$n['self_nominate'] = ($a['self_nominate'] == 'yes') ? 1 : 0;
	$n['schedule_judges'] = ($a['schedule_judges'] == 'yes') ? 1 : 0;
	$n['sponsor'] = $mysqli->real_escape_string($sponsors[$a['sponsors_id']]);
	$n['categories'] = join(',', $a['categories']);
	$n['order'] = $a['order'];
	$n['type'] = $award_type[$a['award_types_id']];
	print("INSERT INTO awards (`".join("`,`", array_keys($n))."`) VALUES ('".join("','", array_values($n))."')\n");
	$mysqli->query("INSERT INTO awards (`".join("`,`", array_keys($n))."`) VALUES ('".join("','", array_values($n))."')");
	print($mysqli->error);
	$aid = $mysqli->insert_id;

	foreach($a['prizes'] as $p) {
		$n = array();
		$n['name'] = $mysqli->real_escape_string($p['prize']);
		$n['award_id'] = $aid;
		$n['cash'] = $p['cash'];
		$n['scholarship'] = $p['scholarship'];
		$n['value'] = $p['value'];
		$t = array();
		if($p['trophystudentkeeper'] == 1) $t[] = 'keeper';
		if($p['trophystudentreturn'] == 1) $t[] = 'return';
		if($p['trophyschoolkeeper'] == 1) $t[] = 'school_keeper';
		if($p['trophyschoolreturn'] == 1) $t[] = 'school_return';
		$n['order'] = $p['order'];
		$n['number'] = $p['number'];
		$n['include_in_script'] = ($p['excludeformac'] == 1) ? 0 : 1;
	$mysqli->query("INSERT INTO award_prizes (`".join("`,`", array_keys($n))."`) VALUES ('".join("','", array_values($n))."')");

		
	}
}

?>
