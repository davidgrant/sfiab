<?php
/* 
   This file is part of the 'Science Fair In A Box' project
   SFIAB Website: http://www.sfiab.ca

   Copyright (C) 2009 James Grant <james@lightbox.org>

   This program is free software; you can redistribute it and/or
   modify it under the terms of the GNU General Public
   License as published by the Free Software Foundation, version 2.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program; see the file COPYING.  If not, write to
   the Free Software Foundation, Inc., 59 Temple Place - Suite 330,
   Boston, MA 02111-1307, USA.
*/

require_once('common.inc.php');
require_once('email.inc.php');
require_once('user.inc.php');

$sleepmin=500000;  // 0.5 seconds
$sleepmax=2000000; // 2.0 second

print("Starting SFIAB Email Queue\n");

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

if(count($_SERVER['argv']) > 1) {
	switch($_SERVER['argv'][1]) {
	case '--force':
		$config['email_queue_lock'] = '';
		break;
	}
}


if($config['email_queue_lock'] != '') {
	print("Queue is already running (or the lock needs to be cleared)\n");
	exit();
}

$mysqli->query("UPDATE config SET val='".time(NULL)."' WHERE var='email_queue_lock'");


$q = $mysqli->prepare("SELECT `id`,`emails_id`,`to_uid`,`to_name`,`to_email`,`additional_replace` FROM email_queue WHERE result='queued' LIMIT 1");
$q1 = $mysqli->prepare("SELECT `name`,`from`,`subject`,`body`,`bodyhtml` FROM emails WHERE id = ?");
//loop forever, but not really, it'll get break'd as soon as there's nothing left to send
while(true) {
	$q->execute(); 
	$q->store_result();
	$q->bind_result($db_id, $db_emails_id,$db_uid,$db_to,$db_email,$db_rep);

	if($q->num_rows == 0) break;

	$q->fetch();

	/* Now lookup the email to send*/
	$q1->bind_param('i', $db_emails_id);
	$q1->execute();
	$q1->store_result();
	$q1->bind_result($db_email_name, $db_email_from, $db_email_subject, $db_email_body, $db_email_body_html);

	if($q->num_rows == 0) {
		/* Email in queue, but the ID doesn't exist?  someone deleted it between sending an email and 
		 * the queue starting? */
		sfiab_log($mysqli, "email error", "Failed to send email with emails_id {$db_emails_id} that doesn't exist");
		$mysqli->query("UPDATE email_queue SET `result`='failed' WHERE id=$db_id");
		continue;
	}

	$q1->fetch();

	/* Load the user if we can, this enables all sorts of replacements since
	 * normally we only send emails to users */
	if($db_uid > 0) {
		$u = user_load($mysqli, $db_uid);
	} else {
		$u = false;
	}

	/* Do replacements */
	$rep = unserialize($db_rep);

	if($db_email_body) 
		$body = $db_email_body;
	else if($db_email_body_html) 
		$body = $db_email_body_html;
	else
		$body="No message body specified";


	$body = email_replace_vars($body, $u, $rep);
			
//	if($email->bodyhtml)
//		$bodyhtml=communication_replace_vars($email->bodyhtml,$blank,$replacements);

	if($db_to)
		$to = "\"$db_to\" <$db_email>";
	else
		$to = $db_email;

//	$result=email_send_new($to,$email->from,$email->subject,$body,$bodyhtml);

	print("Would have sent email to: $to\n");
	print("$body");

	$mysqli->query("UPDATE email_queue SET `result`='failed' WHERE id=$db_id");

/*
			if($result) {
				mysql_query("UPDATE emailqueue_recipients SET sent=NOW(), `result`='ok' WHERE id='$r->id'");
				echo mysql_error();
				$newnumsent=$email->numsent+1;
				mysql_query("UPDATE emailqueue SET numsent=$newnumsent WHERE id='$email->id'");
				echo mysql_error();
				echo "ok\n";
			}
			else {
				mysql_query("UPDATE emailqueue_recipients SET `sent`=NOW(), `result`='failed' WHERE id='$r->id'");
				echo mysql_error();
				$newnumfailed=$email->numfailed+1;
				mysql_query("UPDATE emailqueue SET numfailed=$newnumfailed WHERE id='$email->id'");
				echo mysql_error();
				echo "failed\n";
			}
			*/
}
$mysqli->query("UPDATE config SET val='' WHERE var='email_queue_lock'");

?>
