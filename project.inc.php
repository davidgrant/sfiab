<?php

function int_or_null(&$val)
{
	if(is_null($val)) return;
	$val = (int)$val;
}

function project_load($mysqli, $pid)
{
	if($pid == NULL) return NULL;
	$r = $mysqli->query("SELECT * FROM projects WHERE pid=$pid LIMIT 1");

	if($r->num_rows == 0) {
		return NULL;
	}

	$p = $r->fetch_assoc();

	/* Sanitize some fields */
	$p['pid'] = (int)$p['pid'];
	int_or_null($p['cat_id']);
	int_or_null($p['challenge_id']);
	int_or_null($p['isef_id']);
	int_or_null($p['req_electricity']);
	int_or_null($p['num_students']);

	/* Store an original copy so save() can figure out what (if anything) needs updating */
	unset($p['original']);
	$original = $p;
	$p['original'] = $original;

	return $p;
}

function project_create($mysqli) 
{
	global $config;
	$r = $mysqli->real_query("INSERT INTO projects(`year`) VALUES('{$config['year']}')");
	$pid = $mysqli->insert_id;
	return $pid;
}

function project_save($mysqli, &$p) 
{

	global $sfiab_roles;
	/* Find any fields that changed */
	/* Construct a query to update just those fields */
	/* Always save in the current year */
	$set = "";
	foreach($p as $key=>$val) {
		if($key == 'original') continue;
		if(!array_key_exists($key, $p['original'])) continue;

		if($val !== $p['original'][$key]) {
			/* Key changed */
			if($set != '') $set .= ',';

			if($key == 'something_special_not_defined_yet') {
				/* It's all ok, join it with commas so the query
				 * looks like ='teacher,committee,judge' */
				$v = implode(',', $r);
			} else {
				/* Serialize any non-special arrays */
				if(is_array($val)) 
					$v = serialize($val);
				else 
					$v = $val;

				/* Then for everything, strip slashes and escape */
				$v = stripslashes($v);
				$v = $mysqli->real_escape_string($v);
			}
			$set .= "`$key`='$v'";

			/* Set the original to the unprocessed value */
			$p['original'][$key] = $val;
		}
	}
//	print_r($p);
	if($set != '') {
		$query = "UPDATE projects SET $set WHERE pid='{$p['pid']}'";
//		print($query);
		$mysqli->query($query);
	}
}


/* What category IDs can this project register for? */
function project_get_legal_category_ids($mysqli, $pid)
{
	$cats = categories_load($mysqli);

	$highest_grade = 0;
	$q = $mysqli->query("SELECT MAX(`grade`) AS `max_grade` FROM users WHERE users.student_pid='$pid'");

	if($q->num_rows != 1) {
		return array_keys($cats);
	}

	$ret = array();
	$r = $q->fetch_assoc();

	$max_grade = (int)$r['max_grade'];
	foreach($cats as $cid=>$c) {
		if($c['min_grade'] <= $max_grade && $c['max_grade'] >= $max_grade) {
			$ret[] = $cid;
		} else if($c['min_grade'] > $max_grade) {
			$ret[] = $cid;
		}
	}
	return $ret;
}

?>
