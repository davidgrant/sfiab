<?php
require_once('common.inc.php');
require_once('user.inc.php');


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

	$mysqli->query("INSERT INTO email_queue(`emails_id`,`to_uid`,`to_email`,`to_name`,`additional_replace`,`result`) VALUES 
			($db_id,$uid,'{$u['email']}','{$u['name']}','$ad','queued')");

	print($mysqli->error);

	/* Start the queue processing */
	 if(!file_exists("logs")) {
		 mkdir("logs");
	 }

	 exec("php -q scripts/sfiab_send_email_queue.php >> logs/emailqueue.log 2>&1 &");

	 
	
	return true;
}

function email_get_user_replacements(&$u) {
	global $config;
	/* Replacements that depend on the configuration */
	$rep = array(	'FAIRNAME' => $config['fair_name'],
			'LOGIN_LINK' => $config['fair_url'].'/index.php#login',
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



?>
