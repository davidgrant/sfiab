<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');
require_once('config.inc.php');
require_once('awards.inc.php');

$action = 'check';
if(count($_SERVER['argv']) > 1) {
        switch($_SERVER['argv'][1]) {
        case '--go':
		$action = 'go';
                break;
        }
}


$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

if(array_key_exists('SERVER_ADDR', $_SERVER)) {
	/* Run from server, requires committee */
	sfiab_session_start($mysqli, array('committee'));
}

$old_db = "sfiab_gvrsf";

/* Connect to old sfiab db */
$mysqli_old = new mysqli($dbhost, $dbuser, $dbpassword, $old_db);

print("<pre>\n");

print("Configuration:\n");
print("   Old Database: $old_db\n");
print("   New Database: $dbdatabase\n");

/* Figure out which years to convert */
$skip_years = array();
$q = $mysqli->query("SELECT DISTINCT(year) FROM config");
while($r = $q->fetch_assoc()) {
	$skip_years[] = (int)$r['year'];
}

$years = array();
$q = $mysqli_old->query("SELECT DISTINCT(year) FROM config WHERE year>1");
while($r = $q->fetch_assoc()) {
	$y = (int)$r['year'];
	if(!in_array($y, $skip_years)) {
		$years[] = $y;
	}
}

sort($years);

print("   Will convert years: ".join(', ', $years)."\n");

require_once('scripts/conv_db_categories.inc.php');
require_once('scripts/conv_db_sponsors.inc.php');
require_once('scripts/conv_db_awards.inc.php');
require_once('scripts/conv_db_schools.inc.php');
require_once('scripts/conv_db_tours.inc.php');
require_once('scripts/conv_db_students.inc.php');
require_once('scripts/conv_db_judges.inc.php');
require_once('scripts/conv_db_fairs.inc.php');
require_once('scripts/conv_db_reports.inc.php');
require_once('scripts/conv_db_emails.inc.php');
require_once('scripts/conv_db_volunteers.inc.php');
require_once('scripts/conv_db_committee.inc.php');

if($action == 'go') {

	load_sponsors($mysqli, $mysqli_old);

	conv_reports($mysqli, $mysqli_old);
	conv_fairs($mysqli, $mysqli_old);
	conv_emails($mysqli, $mysqli_old);

	foreach($years as $year) {
		conv_categories($mysqli, $mysqli_old, $year);
		conv_schools($mysqli, $mysqli_old, $year);
		conv_tours($mysqli, $mysqli_old, $year);
	}

	foreach($years as $year) {
		clear_sponsors($mysqli, $year);
		conv_awards($mysqli, $mysqli_old, $year);
		conv_students($mysqli, $mysqli_old, $year);
		conv_winners($mysqli, $mysqli_old, $year);

		conv_judges($mysqli, $mysqli_old, $year);
		conv_committee($mysqli, $mysqli_old, $year);
		conv_volunteers($mysqli, $mysqli_old, $year);
	}
} else {
	print("Use '--go' if you actually want to run this.  It will DELETE EVERYTHING in your New Database for the above years and replace it all with data from the Old Database.\n");
}



?>
