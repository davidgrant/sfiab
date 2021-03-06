<?php
require_once(__DIR__.'/../user.inc.php');
require_once(__DIR__.'/../project.inc.php');

function judges_load_all($mysqli, $year = NULL)
{
	global $config;
	if($year === NULL) $year = $config['year'];
	$q = $mysqli->query("SELECT * FROM users WHERE 
					year='$year'
					AND FIND_IN_SET('judge',`roles`)>0
					AND `enabled`='1'");
	$judges = array();
	while($j = $q->fetch_assoc()) {
		$judges[(int)$j['uid']] = user_load($mysqli, -1, -1, NULL, $j);
	}
	return $judges;
}



function jteam_load($mysqli, $jteam_id, $pdata = false)
{
	if($pdata == false) {
		$r = $mysqli->query("SELECT * FROM judging_teams WHERE id=$jteam_id LIMIT 1");
		if($r->num_rows == 0) {
			return NULL;
		}
		$jteam = $r->fetch_assoc();
	} else {
		$jteam = $pdata;
	}

	filter_int_list($jteam['project_ids']);
	filter_int_list($jteam['user_ids']);
	filter_int($jteam['award_id']);
	filter_int($jteam['num']);
	filter_int($jteam['round']);
	filter_int($jteam['prize_id']);
	filter_int($jteam['cusp_n_up']);

	/* HACK Remove zeros because sometimes they get added, not sure how */
	$l = array();
	foreach($jteam['user_ids'] as $uid) {
		if($uid > 0) {
			$l[] = $uid;
		}
	}
	$jteam['user_ids'] = $l;

	unset($jteam['original']);
	$original = $jteam;
	$jteam['original'] = $original;
	return $jteam;
}

function jteams_load_all($mysqli, $year=NULL)
{
	global $config;
	if($year === NULL) $year = $config['year'];
	$q = $mysqli->query("SELECT * FROM judging_teams WHERE	
				judging_teams.year='$year'");
	$jteams = array();
	while($j = $q->fetch_assoc()) {
		$jteams[(int)$j['id']] = jteam_load($mysqli, -1, $j);
	}
	return $jteams;
}

function jteams_load_all_for_judge($mysqli, $judge_id, $year=NULL)
{
	global $config;
	if($year === NULL) $year = $config['year'];
	$q = $mysqli->query("SELECT * FROM judging_teams WHERE
				judging_teams.year='$year'
				AND FIND_IN_SET('$judge_id',`judging_teams`.`user_ids`)>0
				ORDER BY `round` ");
	$jteams = array();
	while($j = $q->fetch_assoc()) {
		$jteams[intval($j['id'])] = jteam_load($mysqli, -1, $j);
	}
	return $jteams;
	
}

function jteam_save($mysqli, &$jteam)
{
	generic_save($mysqli, $jteam, "judging_teams", "id");
}

function jteam_create($mysqli)
{
	global $config;
	$mysqli->real_query("INSERT INTO judging_teams (`name`,`autocreated`,`year`) VALUES ('New Judging Team', '0','{$config['year']}')");
	$id = $mysqli->insert_id;
	return jteam_load($mysqli, $id);
}

?>
