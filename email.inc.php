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
	/* Lookup the ID of this email */
	$q = $mysqli->prepare("SELECT id FROM emails WHERE name = ?");
	$q->bind_param('s', $email_name); 
	$q->execute(); 
	$q->store_result();
	$q->bind_result($db_id);
	$q->fetch();

	if($q->num_rows != 1) {
		/* Not found */
		sfiab_log($mysqli, "email error", "Email \"$email_name\" not found.");
		return false;
	}

	/* Lookup the user */
	$u = user_load($mysqli, $uid);
	if($u == NULL) return false;
	if($u['state'] == 'deleted') return false;

	$ad = $mysqli->real_escape_string(serialize($additional_replace));
	$n = $mysqli->real_escape_string($u['name']);
	$em = $mysqli->real_escape_string($u['email']);

	$mysqli->query("INSERT INTO email_queue(`emails_id`,`to_uid`,`to_email`,`to_name`,`additional_replace`,`result`) VALUES 
			($db_id,$uid,'$em','$n','$ad','queued')");

	print($mysqli->error);

	email_queue_start($mysqli);

	return true;
}

function email_get_user_replacements(&$u) {
	global $config;
	/* Replacements that depend on the configuration */
	$rep = array(	'FAIRNAME' => $config['fair_name'],
			'LOGIN_LINK' => $config['fair_url'].'/index.php#login',
			'FAIR_URL' => $config['fair_url'],
			'YEAR' => $config['year'],
		);

	if(is_array($u)) {
		/* Replacements that depend on a user */
		$r = array(
			'NAME' => $u['name'],
			'EMAIL' => $u['email'],
			'USERNAME' => $u['username'],
			'SALUTATION' => $u['salutation'], 
			'FIRSTNAME' => $u['firstname'],
			'LASTNAME' => $u['lastname'],
//			'ORGANIZATION' => $u['sponsor']['organization']
			);
		$rep = array_merge($rep, $r);
	}
	return $rep;
}

function email_replace_vars($text, &$u, $otherrep=array()) {
	global $config;

	$userrep=email_get_user_replacements($u);

	$rep=array_merge($userrep,$otherrep);

	$pats = array();
	$reps = array();
	foreach($rep AS $k=>$v) {
		$pats[] = "/\[$k\]/";
		$reps[] = $v;
	}
	$text=preg_replace($pats, $reps,$text);
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
