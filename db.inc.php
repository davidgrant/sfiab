<?php
/* Load SQL commands out of a stream and apply them. c_restore.php could also
 * use this function (that's why it takes an $fp instead of a filename) */
function db_apply_update($mysqli, $fp)
{
	$sql = '';
	while(!feof($fp)) {
		/* Multiline read support */
		$line = trim(fgets($fp));
		if(strlen($line) == 0) continue;

		if($line[0] == '#') {
			continue;
		}
		if($line[0] == '-' && $line[1] == '-') {
			continue;
		}

		/* Fixme add support for -- and C-style slash-star star-slash comments  */

		$sql .= $line;
		if($line[strlen($line)-1] == ';') {
			$mysqli->real_query($sql);
//			print("$sql\n");
			if($mysqli->error != '') {
				print("SQL command failed.  SQL: $sql\n");
				print("Error: {$mysqli->error}\n");
			}
			$sql = '';
		}
	}
}



/* Returns $map[old_id] = new_id */
function db_roll_table($mysqli, $current_year, $new_year, $table, $where='', $replace=array())
{
	/*  Field 	Type 			Null 	Key 	Default 	Extra
	 * id 		int(10) unsigned 	NO 	PRI 	NULL 	auto_increment
	 * sponsors_id 	int(10) unsigned 	NO 	MUL 	0 	 
	 * award_source_fairs_id int(10) unsigned YES  	   	NULL  	 
	*/
	$map = array();
	$id_field = NULL;

	/* Get field list for this table */
	$col = array();
 	$q = $mysqli->query("SHOW COLUMNS IN `$table`");
	while(($c = $q->fetch_assoc())) {
		$col[$c['Field']] = $c;
	}

	/* Record fields we care about */
	$fields = array();
	$keys = array_keys($col);
	$has_year = false;
	foreach($keys as $k) {
		/* Skip id field */
		if($col[$k]['Extra'] == 'auto_increment') {
			$id_field = $k;
			continue;
		}

		/* Skip year field */
		if($k == 'year') {
			$has_year = true;
			continue;
		}

		$fields[] = $k;
	} 

	if($where == '') {
		if($has_year == false) {
			print("No where and no year for table $table");
			exit();
		}
		$where='1';
	}

	if($has_year) {
		$where = "year='$current_year' AND $where";
	}

	/* Get data */
	$q=$mysqli->query("SELECT * FROM $table WHERE $where");
//	print("SELECT * FROM $table WHERE $where\n");
	if($mysqli->error != '') {
		print("Failed Query: SELECT * FROM $table WHERE $where");
		print($mysqli->error);
	}
	$names = '`'.join('`,`', $fields).'`';

	/* Process data */
	while($r=$q->fetch_assoc()) {
		
		$v = array();
		foreach($fields as $f) {
			$val = $r[$f];
			if(array_key_exists($f, $replace)) {
				if(!array_key_exists($val, $replace[$f])) {
					print("&nbsp;&nbsp;&nbsp;<b>Warning</b>: key $val doesn't exist in column $f.  Left blank.<br/>");
					$val = '';
				} else {
					$val = $replace[$f][$val];
				}
				$v[]  = "'".$mysqli->real_escape_string($val)."'";
			} else if($col[$f]['Null'] == 'YES' && $val == NULL)
				$v[] = 'NULL';
			else 
				$v[] = "'".$mysqli->real_escape_string($val)."'";
		}
		$vals = join(',',$v);
		if($has_year) {
			$mysqli->query("INSERT INTO `$table`(`year`,$names) VALUES ('$new_year',$vals)");
//			print("INSERT INTO `$table`(`year`,$names) VALUES ('$new_year',$vals)\n");
		} else {
			$mysqli->query("INSERT INTO `$table`($names) VALUES ($vals)");
//			print("INSERT INTO `$table`($names) VALUES ($vals)\n");
		}
		print($mysqli->error);

		if($id_field !== NULL) {
			$map[$r[$id_field]] = $mysqli->insert_id;
		}
	}
	print("   {$q->num_rows} entires copied into $new_year\n");
	return $map;
}

function db_roll($mysqli, $new_year)
{
	global $config;
	$current_year = $config['year'];
	$delta_years = $new_year - $current_year;

	if($new_year <= $current_year) exit();

	print("<pre>\n");
	print("Rolling Categories...\n");
	db_roll_table($mysqli, $current_year, $new_year, "categories");
	print("Rolling Challenges...\n");
	db_roll_table($mysqli, $current_year, $new_year, "challenges");
	print("Rolling Schools...\n");
	db_roll_table($mysqli, $current_year, $new_year, "schools");
	print("Rolling Timeslots...\n");
	db_roll_table($mysqli, $current_year, $new_year, "timeslots");
	print("Rolling Tours...\n");
	db_roll_table($mysqli, $current_year, $new_year, "tours");


	$weeks = 52 * $delta_years;
	print("Adjusting Configuration dates +$weeks weeks...\n");
	/* Fair dates need to be adjusted.  Add +52 weeks to keep them on the same
	 * day of the week */

	foreach(array('date_judge_registration_closes', 'date_judge_registration_opens',
			'date_student_registration_closes','date_student_registration_opens',
			'date_fair_begins','date_fair_ends',
			) as $f) {
		if($current_year > 0) {
			$mysqli->query("UPDATE config SET `val`=DATE_ADD(`val`,INTERVAL $weeks WEEK) WHERE `var`='$f'");
		} else {
			/* Hack for install script startign at year 0, just go right to the year */
			$mysqli->query("UPDATE config SET `val`=DATE_ADD(`val`,INTERVAL $new_year YEAR) WHERE `var`='$f'");
		}
		$q = $mysqli->query("SELECT * FROM config WHERE `var`='$f'");
		$r = $q->fetch_assoc();
		print("   {$r['description']}: {$r['val']}\n");
	}

	/* Timeslots are relative to the date_fair_begins, so don't need further adjustment */

	/* Awards need some ID rewrites */
	print("Rolling Sponsors...\n");
	$sponsor_map = db_roll_table($mysqli, $current_year, $new_year, "users",  "FIND_IN_SET('sponsor',`roles`)>0");
	print("Rolling Awards...\n");
	$award_map = db_roll_table($mysqli, $current_year, $new_year, "awards", '', array('sponsor_uid' => $sponsor_map ));

	/* Make a comma separated list of all the old award ids, and roll prizes that match one of the
	 * award ids, replacing the award_id with the new one */
	print("Rolling Prizes...\n");
	$ids = join(',', array_keys($award_map));
	db_roll_table($mysqli, $current_year, $new_year, "award_prizes", "FIND_IN_SET(`award_id`,'$ids')>0", array('award_id'=>$award_map ));

	/* Last thing, update the year */
	print("Setting new fair year to $new_year...\n");
	$mysqli->query("UPDATE config SET val='$new_year' WHERE var='year'");

	print("Done.</pre>\n");
}


?>
