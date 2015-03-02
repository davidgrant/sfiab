<?php
require_once('debug.inc.php');


function project_number_clear($mysqli, &$p)
{
	$p['number'] = NULL;
	$p['floor_number'] = 0;
	$p['number_sort'] = 0;
}

/* Finds an open project number for $p and assigns a project number, sort
 * number, and floor location.  If $move_other_projecst == true, it will
 * potentially renumber floor locations (but not project numbers) to find
 * a location */
function project_number_assign($mysqli, &$p, $move_other_projects=false)
{
	global $config;

	$categories = categories_load($mysqli);
	$challenges = challenges_load($mysqli);

	/* The project must be completed before it can be assigned a number */
	if(!$p['accepted']) {
		print("Project must be accetped before it can be assigned a number");
		return false;
	}

	/* Delete the project's number, floor location, and sort, then save.  The project_load_all below
	 * will then load these deleted values */
	project_number_clear($mysqli, $p);
	project_save($mysqli, $p);

	$floor_number = array();
	/* Find the first empty floor location */
	$projects = projects_load_all($mysqli, true);
	$floor_numbers_in_use = array();
	foreach($projects as &$proj) {
		$floor_numbers_in_use[$proj['floor_number']] = true;
	}
	for($first_unused_floor_number = 1; ; $first_unused_floor_number++) {
		if(!array_key_exists($first_unused_floor_number, $floor_numbers_in_use)) {
			break;
		}
	}

	debug("first unused floor number = $first_unused_floor_number\n");

	switch($config['project_number_format']) {
	case 'CCHHXX':
	case 'CHXX':
		if($config['project_number_format'] == 'CCHHXX') {
			$target_length = 6;
			$number_start = 4;
			$cat = sprintf("%02d", $p['cat_id']);
			$cha = sprintf("%02d", $p['challenge_id']);
		} else {
			$target_length = 4;
			$number_start = 2;
			$cat = sprintf("%01d", $p['cat_id']);
			$cha = sprintf("%01d", $p['challenge_id']);
		}

		/* Find a the lowest 2 digit number that matches $cat.$cha */
		$pns = array();
		foreach($projects as &$proj) {
			if($proj['cat_id'] != $p['cat_id']) continue;
			if($proj['challenge_id'] != $p['challenge_id']) continue;
			if(strlen($proj['number']) != $target_length) continue;

			$nn = (int)substr($proj['number'], $number_start, 2);
			$pns[$nn] = 1;
		}
		for($first_n = 1; ; $first_n++) {
			if(!array_key_exists($first_n, $pns)) {
				break;
			}
		}
		$pn = $cat.$cha.sprintf("%02d", $first_n);
		$p['number'] = $pn;
		$p['number_sort'] = (int)$pn;

		/* Now, the floor can go two ways.. either we insert it in
		 * orr (meaning we have to bump every other project's floor
		 * nubmer by +1 if it overlaps,
		 * or, we just put it in the first free location, which might be out of order */
		if($move_other_projects == false) {
			$p['floor_number'] = $first_unused_floor_number;
		} else {
			/* Build a map of sort number -> project */
			$sort_map = array();
			foreach($projects as &$proj) {
				if($proj['pid'] == $p['pid']) {
					/* Skip the project we're calculating the number for, we're going to add
					 * the alterate version ($p) below, it has the nubmer and sort filled out */
					continue;
				}

				if($proj['number_sort'] !== NULL) {
					$sort_map[$proj['number_sort']] = &$proj;
				}
			}
			/* Add the new project sort too */
			$sort_map[$p['number_sort']] = &$p;

			/* Sort keys by number_sort */
			ksort($sort_map);

			/* Iterate through the sort list in order by keys
			 * (number_sort), assign a floor_number, and adjust any
			 * overlapping floor numbers */
			$last_floor_number = 0;
			foreach($sort_map as $ps=>&$proj) {

				/* Find us, assign floor number */
				if($proj['pid'] == $p['pid']) {
					$p['floor_number'] = $last_floor_number + 1;
					project_save($mysqli, $p);
				}

				/* Check current floor number with last one, if they are the same, increment
				 * our floor number */
				if($proj['floor_number'] == $last_floor_number) {
					/* The current number needs to be incremented */
					$proj['floor_number'] = $last_floor_number + 1;
					$current_floor_number = $proj['floor_number'];
					project_save($mysqli, $proj);
				}
				$last_floor_number = $current_floor_number;
			}
		}
		break;

	case 'X4':
		/* Project gets the first unused floor number */
		$p['floor_number'] = $first_unused_floor_number;
		$p['number_sort'] = $first_unused_floor_number;
		$p['number'] = sprintf("%04d", $first_unused_floor_number);
		break;

	case 'c_X3_h':
		/* Project gets the first unused floor number */
		$p['floor_number'] = $first_unused_floor_number;
		$p['number_sort'] = $first_unused_floor_number;
		$p['number'] = $categories[$p['cat_id']]['shortform'].' '.sprintf("%03d", $first_unused_floor_number).' '.$challenges[$p['challenge_id']]['shortform'];
		debug("number = {$p['number']}\n");
		break;

	default:
		print("Unknown Project Number Format!");
		exit();
	}

	return true;
}



?>
