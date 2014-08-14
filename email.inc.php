<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');

function email_load($mysqli, $email_name, $eid=-1, $data=NULL)
{
	if($email_name != '') {
		$n = $mysqli->real_escape_string($email_name);
		$q = $mysqli->query("SELECT * FROM emails WHERE name='$n'");
		$e = $q->fetch_assoc();
	} else if($eid > 0) {
		$q = $mysqli->query("SELECT * FROM emails WHERE id='$eid'");
		$e = $q->fetch_assoc();
	} else {
		$e = $data;
	}

	unset($e['original']);
	$original = $e;
	$e['original'] = $original;
	return $e;
}

function email_save($mysqli, &$e)
{
	generic_save($mysqli, $e, 'emails', 'id');
}

function email_create($mysqli)
{
	global $config;
	$r = $mysqli->real_query("INSERT INTO emails(`section`) VALUES('Uncategorized')");
	$eid = $mysqli->insert_id;
	return $eid;
}

function email_send($mysqli, $email_name, $uid, $additional_replace = array()) 
{
	global $config;

	/* Lookup the ID of this email */
	$q = $mysqli->query("SELECT `id` FROM `emails` WHERE `name`='$email_name'");
	if($q->num_rows != 1) {
		/* Not found */
		sfiab_log($mysqli, "email error", "Email \"$email_name\" not found.");
		return false;
	}
	$r = $q->fetch_assoc();
	$db_id = $r['id'];

	/* Lookup the user */
	$u = user_load($mysqli, $uid);
	if($u == NULL) return false;
	if(!$u['enabled'] == 'deleted') return false;

	/* Fill in additional replace vars that the email send script can't calculate, like the fair URL */
	$additional_replace['fair_url'] = $config['fair_url'];


	$ad = $mysqli->real_escape_string(serialize($additional_replace));
	$n = $mysqli->real_escape_string($u['name']);
	$em = $mysqli->real_escape_string($u['email']);

	$mysqli->real_query("INSERT INTO email_queue(`emails_id`,`to_uid`,`to_email`,`to_name`,`additional_replace`,`result`) VALUES 
			($db_id,$uid,'$em','$n','$ad','queued')");

	print($mysqli->error);

	email_queue_start($mysqli);

	return true;
}

function email_get_user_replacements(&$u, &$additional_replacements) 
{
	global $config;
	/* Replacements that depend on the configuration or must be specified */
	$rep = array(	'FAIRNAME' => $config['fair_name'],
			'FAIRABBR' => $config['fair_abbreviation'],
			'YEAR' => $config['year'],
			'LOGIN_LINK' => $additional_replacements['FAIR_URL'].'/index.php#login',
			'FAIR_URL' => $additional_replacements['FAIR_URL'],
		);

	/* Optional replacements */
	if(array_key_exists('PASSWORD', $additional_replacements)) {
		$rep['PASSWORD'] = $additional_replacements['PASSWORD'];
	}

	if(is_array($u)) {
		/* Replacements that depend on a user */
		$rep['NAME'] = $u['name'];
		$rep['EMAIL'] = $u['email'];
		$rep['USERNAME'] = $u['username'];
		$rep['SALUTATION'] = $u['salutation'];
		$rep['FIRSTNAME'] = $u['firstname'];
		$rep['LASTNAME'] = $u['lastname'];
		$rep['ORGANIZATION'] = $u['organization'];
	}
	return $rep;
}

/* This is only called from the send email script */
function email_replace_vars($text, &$u, &$additional_replacements) 
{
	global $config;

	$rep=email_get_user_replacements($u, $additional_replacements);

	$pats = array();
	$reps = array();
	foreach($rep AS $k=>$v) {
		$pats[] = "/\[$k\]/";
		$reps[] = $v;
	}
	$text=preg_replace($pats, $reps, $text);
	return $text;
}

function email_queue_stopped($mysqli) 
{
	$qstop = $mysqli->query("SELECT val FROM config WHERE var='email_queue_stop'");
	$vstop = $qstop->fetch_assoc();
	if((int)$vstop['val'] == 1) {
		return true;
	}
	return false;
}

function email_queue_stop($mysqli) 
{
	$mysqli->query("UPDATE config SET val='1' WHERE var='email_queue_stop'");
}

function email_queue_start($mysqli) 
{
	$mysqli->query("UPDATE config SET val='0' WHERE var='email_queue_stop'");

 	/* Start the queue processing */
	if(!file_exists("logs")) {
		mkdir("logs");
	}
	exec("php -q scripts/sfiab_send_email_queue.php >> logs/emailqueue.log 2>&1 &");
}



?>
