<?php

$awards_map = array();
$prizes_map = array();

function conv_awards($mysqli, $mysqli_old, $year) 
{
	global $sponsors_map, $awards_map, $prizes_map;

	print("Convert Awards for $year...\n");

	/* Delete existing old awards and prizes for the year */
	$mysqli->query("DELETE FROM award_prizes WHERE award_id IN ( SELECT id FROM awards WHERE year='$year' )");
	$mysqli->query("DELETE FROM awards WHERE year='$year'");


	/* Load the old awards, and save to a new award array */
	$q = $mysqli_old->query("SELECT * FROM award_awards WHERE year='$year'");
	while($a = $q->fetch_assoc()) {
		$a['prizes'] = array();
		$aid = $a['id'];
		$q1 = $mysqli_old->query("SELECT * FROM award_prizes WHERE award_awards_id='$aid'");
		while($p = $q1->fetch_assoc()) {
			$a['prizes'][] = $p;
		}
		$a['categories'] = array();
		$q1 = $mysqli_old->query("SELECT * FROM award_awards_projectcategories WHERE award_awards_id='$aid'");
		while($c =$q1->fetch_assoc()) {
			$a['categories'][] = $c['projectcategories_id'];
		}
		$awards[] = $a;
	}

	$award_types = array('divisional','special','grand','other');
	/* Load award types and try to map to the static types */
	$q = $mysqli_old->query("SELECT * FROM award_types WHERE year='$year'");
	while($t = $q->fetch_assoc()) {
		$n = strtolower($t['type']);
		$type_map[(int)$t['id']] = 'other';
		foreach($award_types as $new_t) {
			if(strstr($n, $new_t) !== false) {
				$type_map[(int)$t['id']] = $new_t;
			}
		}
	}

	/* Insert awards into db */
	foreach($awards as $old_a) {

		$aid = award_create($mysqli, $year);
		$a = award_load($mysqli, $aid);

		$a['name'] = $old_a['name'];
		print($a['name'] . "\n");

		$a['s_desc'] = $old_a['description'];
		$a['j_desc'] = $old_a['criteria'];
		$a['presenter'] = $old_a['presenter'];
		$a['notes'] = '';
		$a['include_in_script'] = ($old_a['excludefromac'] == 1) ? 0 : 1;
		$a['self_nominate'] = ($old_a['self_nominate'] == 'yes') ? 1 : 0;
		$a['schedule_judges'] = ($old_a['schedule_judges'] == 'yes') ? 1 : 0;
		$a['sponsor_uid'] = get_or_create_sponsor_uid_for_year($mysqli, (int)$old_a['sponsors_id'], $year);

		/* Category IDs are the same, no need to map them */
		$a['categories'] = $old_a['categories'];
		$a['order'] = $old_a['order'];
		$a['type'] = $type_map[(int)$old_a['award_types_id']];

		foreach($old_a['prizes'] as &$old_p) {
			$pid = prize_create($mysqli, $aid);
			$p = prize_load($mysqli, $pid);

			$p['name'] = $old_p['prize'];
			print("   ".$p['name'] . "\n");
			$p['cash'] = $old_p['cash'];
			$p['scholarship'] = $old_p['scholarship'];
			$p['value'] = $old_p['value'];
			$p['trophies'] = array();
			if($old_p['trophystudentkeeper'] == 1) $p['trophies'] [] = 'keeper';
			if($old_p['trophystudentreturn'] == 1) $p['trophies'] [] = 'return';
			if($old_p['trophyschoolkeeper'] == 1) $p['trophies'] [] = 'school_keeper';
			if($old_p['trophyschoolreturn'] == 1) $p['trophies'] [] = 'school_return';
			$p['order'] = (int)$old_p['order'];
			$p['number'] = (int)$old_p['number'];
			if($p['number'] == 0) $p['number'] = 1;

//			print_r($old_p);
			if($old_p['excludefromac'] == 0) $a['include_in_script'] = 1;

			prize_save($mysqli, $p);
			$prizes_map[(int)$old_p['id']] = $pid;
		}

		award_save($mysqli, $a);

		$awards_map[(int)$old_a['id']] = $aid;
	}
}



function conv_winners($mysqli, $mysqli_old, $year) 
{
	global $prizes_map, $projects_map, $fairs_map;

	print("Convert Winners for $year...\n");

//	print_r($prizes_map);

	/* Delete existing old awards and prizes for the year */
	$mysqli->query("DELETE FROM winners WHERE year='$year'");

	/* Load the old awards, and save to a new award array */
	$q = $mysqli_old->query("SELECT * FROM winners WHERE year='$year'");
	while($w = $q->fetch_assoc()) {
		$prize_id = $prizes_map[(int)$w['awards_prizes_id']];
		$pid = $projects_map[(int)$w['projects_id']];
		$fair_id = (int)$w['fairs_id'];

		$mysqli->query("INSERT INTO winners (`award_prize_id`,`pid`,`year`,`fair_id`)
				VALUES('$prize_id','$pid','$year','$fair_id')");
	}
}


?>
