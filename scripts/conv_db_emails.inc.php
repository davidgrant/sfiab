<?php

require_once('email.inc.php');

function conv_emails($mysqli, $mysqli_old)
{
	print("Converting Emails...\n");

	print("   - Delete existing emails from new database\n");
	$mysqli->real_query("DELETE FROM emails WHERE Section != 'System'");

	$c = 0;
	$q = $mysqli_old->query("SELECT * FROM emails WHERE type='user'");
	print("   - Importing Emails...\n");
	while($e = $q->fetch_assoc()) {


		$id = email_create($mysqli);
		$new_e = email_load($mysqli, '', $id);

		$new_e['name'] = $e['name'];
		$new_e['section'] = 'Uncategorized';
		$new_e['description'] = $e['description'];

		if(preg_match("/([^<]*) *<([^>]*)>/", $e['from'], $matches)) {
			$from_name = $matches[1];
			$from_email = $matches[2];
		} else {
			$from_email = $e['from'];
			$from_name = NULL;
		}
		$new_e['from_name'] = trim($from_name);
		$new_e['from_email'] = trim($from_email);
		$new_e['subject'] = $e['subject'];
		$new_e['body'] = $e['body'];
		$new_e['bodyhtml'] = $e['bodyhtml'];

		email_save($mysqli, $new_e);

		print("      - {$new_e['name']} From: {$new_e['from_name']} <{$new_e['from_email']}>\n");
		$c++;
	}
	print("   - Converted $c emails\n");
}

?>
