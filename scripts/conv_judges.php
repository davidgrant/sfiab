<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');
require_once('config.inc.php');

print("remove the exit line at the top of the file");
exit();

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);


$mysqli_old = new mysqli($dbhost, $dbuser, $dbpassword, "sfiab_gvrsf");

$year = $config['year'];

$users = array();

$q = $mysqli_old->query("SELECT * FROM users WHERE FIND_IN_SET('judge',`types`) > 0 AND year=2014");
while($u = $q->fetch_assoc()) {

	$q1 = $mysqli_old->query("SELECT * FROM users_judge WHERE users_id='{$u['id']}'");
	$r = $q1->fetch_assoc();
	$u = array_merge($u, $r);

	$a = array();
	$q1 = $mysqli_old->query("SELECT * FROM judges_availability WHERE users_id='{$u['id']}'");
	while($r = $q1->fetch_assoc()) {
		if($r['start'] == '14:00:00') 
			$a[] = '1';
		else 
			$a[] = '2';
	}
	$u['j_rounds'] = join(',', $a);
	$u['cat_prefs'] = unserialize($u['cat_prefs']);

	if(!array_key_exists($u['email'], $users)) {
		$users[$u['email']] = $u;
		continue;
	}

	if($users[$u['email']]['year'] < $u['year']) {
		$users[$u['email']] = $u;
	}
	print("Loaded {$u['firstname']} {$u['lastname']}\n");

}

/* Attach judge info */
foreach($users as $em=>$u) {
	print_r($u);
}


function s($str) {
	if($str === NULL) 
		return "NULL";
	return "'$str'";
}

foreach($users as $e=>$u) {
	if($u['deleted'] == 'yes') continue;

	if($u['firstname'] == 'David') continue;
	if($u['firstname'] == 'Leonard') continue;

	print("Processing: ". $u['firstname'] . ' '.$u['lastname'].' '.$u['year']. "\n");
	$uid = $u['id'];
	$un = $e;
	$in= array();
	$in['username'] = $e;
	$in['email'] = $e;
	$in['firstname'] = $u['firstname'];
	$in['lastname'] = $u['lastname'];
	$in['salutation'] = $u['salutation'];
	$in['phone1'] = $u['phonehome'];
	$in['phone2'] = $u['phonecell'];
	$in['organization'] = $u['organization'];
	$in['sex'] = $u['sex'];
	$in['city'] = $u['city'] ? $u['city'] : 'Vancouver';
	$in['province'] = $u['province'] ? $u['province'] : 'bc';
	$in['language'] = $u['lang'];
	if($u['highest_psd'] == '') {
		$in['j_psd'] = NULL;
	} else {
		switch($u['highest_psd'][0]) {
		case 'P': case 'p':
			$in['j_psd'] = 'doctorate';
			break;
		case 'M': case 'm':
			$in['j_psd'] = 'master';
			break;
		default:
			$in['j_psd'] = 'bachelor';
			break;
		}
	}

	$in['j_willing_lead'] = $u['willing_chair'];
	$in['j_dinner'] = 0;
	$q = $mysqli_old->query("SELECT * FROM question_answers WHERE users_id='$uid'");
	while($r = $q->fetch_assoc()) {
			if((int)$r['questions_id'] == 115) {
				$in['j_dinner'] = $r['answer'] == 'yes' ? 1 : 0;
			}
	}
	$in['j_languages'] = $u['languages'];
	$in['j_sa_only'] = ($u['special_award_only'] == 'yea') ? 1 : 0;
	$in['j_years_school'] = $u['years_school'];
	$in['j_years_regional'] = $u['years_regional'];
	$in['j_years_national'] = $u['years_national'];
	$in['password'] = hash('sha512', $u['password']);
	$in['state'] = 'active';
	$in['year'] = 2014;
	$in['j_rounds'] = $u['j_rounds'];
	$in['roles'] = 'judge';
	$in['j_pref_cat'] = 0;
	$highest = 0;
	for($x=1;$x<=3;$x++) {
		if($u['cat_prefs'][$x] >= $highest) {
			$highest = $u['cat_prefs'][$x];
			$in['j_pref_cat'] = $x;
		}
	}



	$i1 = '';
	$i2 = '';
	foreach($in as $k=>$v) {
		if($i1 != '') {
			 $i1 .= ',';
			 $i2 .= ',';
		}
		$i1 .="`$k`";
		if($v === NULL) {
			$i2 .= "NULL";
		} else {
			$i2 .= "'$v'";
		}
	}

	$str = "INSERT INTO users($i1) VALUES($i2)";
	print($str."\n");
	$mysqli->real_query("DELETE FROM users WHERE `username`='{$in['username']}'");
	$mysqli->real_query($str);
	print($mysqli->error);
}

?>
