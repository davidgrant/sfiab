<?php


function conv_volunteers($mysqli, $old_prefix, $year)
{
	print("Converting Volunteers for $year...\n");
	$mysqli->real_query("DELETE FROM users WHERE FIND_IN_SET('volunteer',`roles`)>0 AND year='$year'");

	$q = $mysqli->query("SELECT * FROM {$old_prefix}users WHERE FIND_IN_SET('volunteer',`types`)>0 AND deleted='no' AND year='$year'");
	print($mysqli->error);
	$c = 0;
	while($old_u = $q->fetch_assoc()) {

		$q1 = $mysqli->query("SELECT * FROM {$old_prefix}users_volunteer WHERE users_id='{$old_u['id']}'");
		$r = $q1->fetch_assoc();
		$old_u = array_merge($old_u, $r);

		/* Skip incomplete users */
		if($old_u['volunteer_active'] == 'no') continue;
		if($old_u['volunteer_complete'] == 'no') continue;

		/* Create a new user */
		$password = NULL;
		$u = user_create($mysqli, NULL, $old_u['email'], 'volunteer', $year, $password);

		$u['phone1'] = $old_u['phonehome'];
		$u['phone2'] = $old_u['phonecell'];
		filter_phone($u['phone1']);
		filter_phone($u['phone2']);

		$u['username'] = NULL;
		$u['firstname'] = $old_u['firstname'];
		$u['lastname'] = $old_u['lastname'];
		$u['salutation'] = $old_u['salutation'];
		$u['organization'] = $old_u['organization'];
		$u['sex'] = $old_u['sex'];
		$u['city'] = $old_u['city'];
		$u['province'] = $old_u['province'];
		$u['new'] = 0;
		$u['enabled'] = 1;

		user_save($mysqli, $u);
		$c++;
	}
	print("   Converted $c volunteers.\n");
}

?>
