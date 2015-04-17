<?php
require_once('user.inc.php');
require_once('project.inc.php');
require_once('debug.inc.php');

$stats_data = array(
	'start_date' => array(	'name' => 'Start date of the fair'),
	'end_date' => array(	'name' => 'End date of the fair'),
	'male' => array(	'name' => 'Number of male students by grade'),
	'female' => array(	'name' => 'Number of female students by grade'),
	'project' => array(	'name' => 'Number of project by grade'),
	'students_public' => array(	'name' => 'Students from public schools'),
	'students_private' => array(	'name' => 'Students from private schools'),
	'students_atrisk' => array(	'name' => 'Number of students from at-risk schools'),
	'schools' => array(	'name' => 'Number of schools'),
	'schools_public' => array(	'name' => 'Number of public schools'),
	'schools_private' => array(	'name' => 'Number of private schools'),
	'schools_districts' => array(	'name' => 'Number of school districts'),
	'schools_atrisk' => array(	'name' => 'Number of at-risk schools'),
	'committee_members' => array(	'name' => 'Number of committee members'),
	'judges' => array(	'name' => 'Number of judges'),
	'scholarships' => array('name' => 'Number of scholarships offered'),
);

/* Gather stats */
function stats_get_export($mysqli, &$fair, $year)
{
	global $config, $stats_data;
	$stats = array();

	foreach(array_keys($stats_data) as $k) {
		$stats[$k] = NULL;
	}

	$stats['year'] = $year;
	$stats['start_date'] = $config['date_fair_begins'];
	$stats['end_date'] = $config['date_fair_ends'];

	list($min_grade, $max_grade) = categories_grade_range($mysqli, $year);

	for($g=$min_grade; $g<=$max_grade; $g++) {
		$stats['male'][$g] = 0;
		$stats['female'][$g] = 0;
		$stats['project'][$g] = 0;
	}
	$schools = array();
	$project_grade = array();
	/* Students =============================================== */
	$q=$mysqli->query("SELECT * FROM users WHERE FIND_IN_SET('student',`roles`)>0 AND s_accepted=1 and `year`='$year'");
	while($s = $q->fetch_assoc()) {
		$grade = intval($s['grade']);
		$gender = $s['sex'];  // male or female 

		if ($gender == 'male') {
			$stats['male'][$grade] += 1;
		} else {
			$stats['female'][$grade] += 1;
		}

		if(!array_key_exists($s['s_pid'], $project_grade)) {
			$project_grade[$s['s_pid']] = $grade;
		} else {
			if($grade > $project_grade[$s['s_pid']]) 
				$project_grade[$s['s_pid']] = $grade;
		}

		$school_id = $s['schools_id'];
		if(!array_key_exists($school_id, $schools)) 
			$schools[$school_id] = 0;

		/* Count students at each school */
		$schools[$school_id] += 1;
	}


	/* Schools =============================================== */
	$stats['schools'] = count(array_keys($schools));
	$stats['schools_public'] = 0;
	$stats['schools_private'] = 0;
	$stats['schools_atrisk'] = 0;
	$stats['students_public'] = 0;
	$stats['students_private'] = 0;
	$stats['students_atrisk'] = 0;

	$districts = array();
	
	$q = $mysqli->query('SELECT * FROM schools WHERE id IN ('.join(',', array_keys($schools)).')');
	while($s = $q->fetch_assoc()) {
		$n_students = $schools[$s['id']];

		if($s['atrisk'] == 'yes') {
			$stats['schools_atrisk'] += 1;
			$stats['students_atrisk'] += $n_students;
		}

		if($s['type'] == 'independent') {
			$stats['schools_private'] += 1;
			$stats['students_private'] += $n_students;
		} else {
			$stats['schools_public'] += 1;
			$stats['students_public'] += $n_students;
		}

		$districts[$s['board']] = 1;

	}
	$stats['schools_districts'] = count(array_keys($districts));


	/* Projects =============================================== */
	foreach($project_grade as $pid=>$grade) {
		$stats['project'][$grade] += 1;
	}

	/* Committee Members=============================================== */
	$q=$mysqli->query("SELECT COUNT(uid) FROM users u
				INNER JOIN ( SELECT MAX(`year`) max_year,username
						FROM users
						WHERE `year`<=$year
						GROUP BY username
						)u2
					ON `u`.username=`u2`.username
					AND `u`.year = `u2`.max_year
				WHERE FIND_IN_SET('committee',`roles`)>0
				AND `enabled`=1");
	print($mysqli->error);
	$r=$q->fetch_row();
	$stats['committee_members'] = $r[0];

	/* Judges ========================================================= */
	$q=$mysqli->query("SELECT COUNT(uid) FROM users WHERE `year`='$year'
				AND FIND_IN_SET('judge',`roles`)>0
				AND `j_complete`=1 AND `attending`=1
				AND `enabled`=1");
	print($mysqli->error);
	$r=$q->fetch_row();
	$stats['judges'] = $r[0];

	/* Scholarships =================================================== */
	$q = $mysqli->query("SELECT COUNT(`awards`.`name`),`awards`.`name` 
			FROM `award_prizes`
			LEFT JOIN `awards` on `award_prizes`.`award_id` = `awards`.`id`
			WHERE `awards`.`year`='$year' AND `award_prizes`.`scholarship`>0 
			GROUP BY `awards`.`id` ORDER BY `awards`.`name`");
	print($mysqli->error);
	/* Get a list that looks like this:
		1 	BCIC Young Innovator Scholarship
		1 	Genome BC Scholarship Nomination
		1 	SFU Scholarship
		6 	UBC Science Entrance Award */
	$a = array();
	while($r = $q->fetch_row()) {
		$num = $r[0];
		$name = $r[1];
		$a[] = "$num - $name";
	}
	$stats['scholarships'] = join(', ', $a);


	$e = false;
	foreach(array_keys($stats_data) as $k) {
		if($stats[$k] === NULL) {
			print("Stats didn't fill out $k\n");
			$e = true;
		}
	}

	if($e) exit();

	return $stats;

}

function stats_sync($mysqli, $fair, $incoming_stats)
{
	global $stats_data;
	
	$year = (int)$incoming_stats['year'];

	debug("Sync stats for year $year\n");

	if($year <= 0) exit();

	$q = $mysqli->query("SELECT id FROM fair_stats WHERE fair_id='{$fair['id']}' AND year='$year'");
	if($q->num_rows == 0) {
		$mysqli->real_query("INSERT INTO fair_stats (`fair_id`,`year`) VALUES ('{$fair['id']}','$year')");
	}

	/* Collapse low grades */
	$m_0 = 0;
	$f_0 = 0;
	$p_0 = 0;
	for($g=0; $g<4; $g++) {
		if(array_key_exists($g, $incoming_stats['male'])) 
			$m_0 += (int)$incoming_stats['male'][$g];
		if(array_key_exists($g, $incoming_stats['female'])) 
			$f_0 += (int)$incoming_stats['female'][$g];
		if(array_key_exists($g, $incoming_stats['project'])) 
			$p_0 += (int)$incoming_stats['project'][$g];
	}

	$update = array();
	foreach(array('male','female','project') as $f) {
		for($g=4;$g<=12;$g++) {
			if(array_key_exists($g, $incoming_stats[$f])) {
				$update[] = "`{$f}_$g`='".(int)$incoming_stats[$f][$g]."'";
			}
		}
	}

	/* Now update it */
	$mysqli->real_query("UPDATE fair_stats SET 
				`start_date`='".$mysqli->real_escape_string($incoming_stats['start_date'])."',
				`end_date`='".$mysqli->real_escape_string($incoming_stats['end_date'])."',
				`male_0`='$m_0',
				`female_0`='$f_0',
				`project_0`='$p_0',
				".join(',', $update).",
				`students_public`='".(int)$incoming_stats['students_public']."',
				`students_private`='".(int)$incoming_stats['students_private']."',
				`students_atrisk`='".(int)$incoming_stats['students_atrisk']."',
				`schools`='".(int)$incoming_stats['schools']."',
				`schools_public`='".(int)$incoming_stats['schools_public']."',
				`schools_private`='".(int)$incoming_stats['schools_private']."',
				`schools_districts`='".(int)$incoming_stats['schools_districts']."',
				`schools_atrisk`='".(int)$incoming_stats['schools_atrisk']."',
				`judges`='".(int)$incoming_stats['judges']."',
				`committee_members`='".(int)$incoming_stats['committee_members']."',
				`scholarships`='".$mysqli->real_escape_string($incoming_stats['scholarships'])."'

			WHERE fair_id='{$fair['id']}' AND year='$year'");
	debug($mysqli->error);

	sfiab_log_sync_stats($mysqli, $fair['id'], 1);
	return 0;
}


?>
