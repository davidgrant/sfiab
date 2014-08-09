<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);
sfiab_session_start($mysqli, array('committee'));


/* Returns $map[old_id] = new_id */
function roll($mysqli, $current_year, $new_year, $table, $where='', $replace=array())
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
	print("&nbsp;&nbsp;&nbsp;<b>{$q->num_rows}</b> entires copied into $new_year<br/>");
	return $map;
}




$page_id = 'c_rollover';

$help = '
<ul>
</ul>';

sfiab_page_begin("Rollover Fair Year", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

if($action == 'rollover') {
	/* Check the current year with the post value, if they dont' match, don't do a rollover (someone just realoaded the rollvoer page) */
	$curr_year = (int)$_POST['current_year'];
	if($curr_year != $config['year']) {
		$action = '';
	}
}

switch($action) {
case 'rollover':

	$new_year = (int)$_POST['year'];
	$current_year = $config['year'];

	if($new_year <= $current_year) exit();

	print("Rolling Configuration...<br/>\n");
	roll($mysqli, $current_year, $new_year, "config");
	print("Rolling Categories...<br/>\n");
	roll($mysqli, $current_year, $new_year, "categories");
	print("Rolling Challenges...<br/>\n");
	roll($mysqli, $current_year, $new_year, "challenges");
	print("Rolling Schools...<br/>\n");
	roll($mysqli, $current_year, $new_year, "schools");
	print("Rolling Timeslots...<br/>\n");
	roll($mysqli, $current_year, $new_year, "timeslots");
	print("Rolling Tours...<br/>\n");
	roll($mysqli, $current_year, $new_year, "tours");


	print("Adjusting Configuration dates +52 weeks...<br/>\n");
	/* Fair dates need to be adjusted.  Add +52 weeks to keep them on the same
	 * day of the week */
	foreach(array('date_judge_registration_closes', 'date_judge_registration_opens',
			'date_student_registration_closes','date_student_registration_opens',
			'date_fair_begins','date_fair_ends',
			) as $f) {
		$mysqli->query("UPDATE config SET `val`=DATE_ADD(`val`,INTERVAL 52 WEEK) WHERE `var`='$f' AND year='$new_year'");
		$q = $mysqli->query("SELECT * FROM config WHERE `year`='$new_year' AND `var`='$f'");
		$r = $q->fetch_assoc();
		print("&nbsp;&nbsp;&nbsp;{$r['description']}: {$r['val']}<br/>");
	}

	/* Timeslots are relative to the date_fair_begins, so don't need further adjustment */

	/* Awards need some ID rewrites */
	print("Rolling Sponsors...<br/>\n");
	$sponsor_map = roll($mysqli, $current_year, $new_year, "users",  "FIND_IN_SET('sponsor',`roles`)>0");
	print("Rolling Awards...<br/>\n");
	$award_map = roll($mysqli, $current_year, $new_year, "awards", '', array('sponsor_uid' => $sponsor_map ));

	/* Make a comma separated list of all the old award ids, and roll prizes that match one of the
	 * award ids, replacing the award_id with the new one */
	print("Rolling Prizes...<br/>\n");
	$ids = join(',', array_keys($award_map));
	roll($mysqli, $current_year, $new_year, "award_prizes", "FIND_IN_SET(`award_id`,'$ids')>0", array('award_id'=>$award_map ));

	/* Last thing, update the year */
	print("Setting new fair year to $new_year...<br/>\n");
	$mysqli->query("UPDATE config SET val='$new_year' WHERE var='year'");

	print("Done.<br/>");

?>
	<a href="c_rollover.php" data-ajax="false" data-role="button" data-icon="back" data-theme="g" >All Done.  Go Back</a>
<?php

	break;

default:

?>
	<p>Rolling over the fair year prepares the fair for a new year.  The
	current year is <?=$config['year']?>.  The new year is whatever you'd like,
	usually the current year + 1, so <?=$config['year']+1?>.  The rollover process
	copies data for the new year, leaving a copy in the
	current year so that reports generated for past years make sense and
	reflect the data as it was.  

	<p>These lists may not be 100% up-to-date.

	<p>The following data is duplicated for the new year:  Awards and Prizes,
	Configuration, Categories, Challenges, Schools, Sponsors, Timeslots, and Tours

	<p>The following data doesn't change year-over-year, so is not copied: CMS (page contents), Emails, Reports

	<p>The following data is NOT rolled over because it is not needed for a new year: Judging Assignments, Judging Teams, Projects

	<p>Users are a special case.  Users are copied to the new year one-by-one when a user logs in (provided the last activity on the account wasn't to delete or disable it).


<?php
	$form_id = $page_id.'_form';
	form_page_begin($page_id, array());

	$new_year = $config['year'] + 1;
	/* No ajax on this form */
	form_begin($form_id, 'c_rollover.php', false, false);
	form_label($form_id, 'cyear', "Current Fair Year", $config['year']);
	form_hidden($form_id, 'current_year', $config['year']);
	form_int($form_id, 'year', "New Fair Year", $new_year, $new_year, 9999);

	form_button($form_id, 'rollover', 'Rollover Fair Year', 'g', 'check', 'onclick="return confirm(\'Really rollover? This cannot be undone\');"' );
	form_end($form_id);
	break;

}		
?>

</div></div>
	
<?php
sfiab_page_end();
?>
