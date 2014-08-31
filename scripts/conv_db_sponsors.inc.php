<?php

/* Sponsors are a bit special, due to an old SFIAB bug, sponsors from past
 * years may be linked * to current years.   So we're goign to load all
 * sponsors for all years into "year 0", then * copy that data into the current
 * year as we parse awards and need the sponsors, writing * out new sponsors as
 * we go */

$sponsors_map = array();
function load_sponsors($mysqli, $mysqli_old)
{

	global $sponsors_map;

	print("Load Sponsors for all years...\n");


	/* Load sponsors by ID */
	$sponsors_map[0] = array();

	$q = $mysqli_old->query("SELECT * FROM sponsors");
	while($s = $q->fetch_assoc()) {
		$sponsor_id = (int)$s['id'];
		/* Find them in the users databse */
		$q2 = $mysqli_old->query("SELECT * FROM users LEFT JOIN users_sponsor ON users_sponsor.users_id = users.id 
						WHERE users_sponsor.sponsors_id='$sponsor_id' AND users_sponsor.primary='yes'");
		print($mysqli_old->error);
		if($q2->num_rows > 0) {
			$r2 = $q2->fetch_assoc();
			$fn = $r2['firstname'];
			$ln = $r2['lastname'];
			$sa = $r2['salutation'];
			$em = $r2['email'];
			$ph = $r2['phonework'];
		} else {
			$fn = '';
			$ln = '';
			$sa = '';
			$ph = '';
			$em = '';
		}

		$em = $s['email'] == '' ? $em : $s['email'];
		$ph = $s['phone'] == '' ? $ph : $s['phone'];

		$sponsors_map[0][$sponsor_id] = array(
			'organization' => $s['organization'],
			'firstname' => $fn,
			'lastname' => $ln,
			'salutation' => $sa,
			'address' => $s['address'],
			'city' => $s['city'],
			'province' => $s['province_code'],
			'postalcode' => $s['postalcode'],
			'email' => $em,
			'phone' => $ph,
			'website' => $s['website'],
			'notes' => $s['notes'],
			);
	}
}

function clear_sponsors($mysqli, $year) 
{
	print("Clearing Sponsors for $year\n");
	/* Delete existing */
	$mysqli->query("DELETE FROM users WHERE FIND_IN_SET('sponsor',`roles`)>0 AND year='$year'");
}

/* Given an old sponsor id, what is the new sponsor id... if the
 * sponsor doesn't exist yet in the year requested copy them
 * from year 0 and create a new user */
function get_or_create_sponsor_uid_for_year($mysqli, $sponsor_id, $year)
{
	global $sponsors_map;

	if(!array_key_exists($year, $sponsors_map)) 
		$sponsors_map[$year] = array();

	if(!array_key_exists($sponsor_id, $sponsors_map[0])) {
		print("Can't find old sponsor ID {$sponsor_id}\n");
		exit();
	}

	$s = &$sponsors_map[0][$sponsor_id];

	if(array_key_exists((int)$sponsor_id, $sponsors_map[$year])) {
		return $sponsors_map[$year][(int)$sponsor_id];
	}

	$p = '';
	$uid = user_create($mysqli, NULL, $s['email'], 'sponsor', $year, $p);
	print("   Created new sponsor {$s['organization']} for year $year  ({$s['firstname']} {$s['lastname']}) (uid=$uid)\n");
	
	/* Old => New map */
	$sponsors_map[$year][$sponsor_id] = $uid;

	$u = user_load($mysqli, $uid);

	$u['organization'] = $s['organization'];
	$u['enabled'] = 1;
	$u['new'] = 0;
	$u['firstname'] = $s['firstname'];
	$u['lastname'] = $s['lastname'];
	$u['salutation'] = $s['salutation'];
	$u['address'] = $s['address'];
	$u['city'] = $s['city'];
	$u['province'] = $s['province'];
	$u['postalcode'] = $s['postalcode'];
	$u['website'] = $s['website'];
	$u['notes'] = $s['notes'];
	$u['phone1'] = $s['phone'];

	user_save($mysqli, $u);

	return $uid;
}

?>
