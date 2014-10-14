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
require_once('fairs.inc.php');
require_once('project.inc.php');
require_once('user.inc.php');


require_once('PHPMailer/PHPMailerAutoload.php');


$sleepmin=0.5;  // 0.5 seconds

print("SFIAB Queue Runner: Start\n");

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

if(count($_SERVER['argv']) > 1) {
	switch($_SERVER['argv'][1]) {
	case '--force':
		$config['queue_lock'] = '';
		break;
	}
}


if($config['queue_lock'] != '') {
	print("Queue is already running (or the lock needs to be cleared)\n");
	exit();
}

$mysqli->query("UPDATE config SET val='".time(NULL)."' WHERE var='queue_lock'");


$q = $mysqli->prepare("SELECT `id`,`command`,`fair_id`,`award_id`,`prize_id`,`project_id`,`emails_id`,`to_uid`,`to_name`,`to_email`,`additional_replace` 
				FROM queue WHERE result='queued' LIMIT 1");
$q1 = $mysqli->prepare("SELECT `name`,`from_name`,`from_email`,`subject`,`body`,`bodyhtml` FROM emails WHERE id = ?");
//loop forever, but not really, it'll get break'd as soon as there's nothing left to send
while(true) {

	/* Check for stop/start queue */
	$qstop = queue_stopped($mysqli);
	if($qstop) break;

   	/* Get an entry from the queue, exit if there are no more */
	$q->execute(); 
	$q->store_result();
	$q->bind_result($db_id, $db_command, $db_fair_id, $db_award_id, $db_prize_id, $db_project_id,$db_emails_id,$db_uid,$db_to,$db_email,$db_rep);

	if($q->num_rows == 0) break;
	$q->fetch();

	switch($db_command) {
	case 'email':
		/* Now lookup the email to send from the database of emails.. we don't copy
		 * the full email text into the queue, just the ID of the email to send */
		$q1->bind_param('i', $db_emails_id);
		$q1->execute();
		$q1->store_result();
		$q1->bind_result($db_email_name, $db_email_from_name, $db_email_from_addr, $db_email_subject, $db_email_body, $db_email_body_html);

		if($q->num_rows == 0) {
			/* Email in queue, but the ID doesn't exist?  someone deleted it between sending an email and 
			 * the queue starting? */
			sfiab_log($mysqli, "email error", "Failed to send email with emails_id {$db_emails_id} that doesn't exist");
			$mysqli->query("UPDATE queue SET `result`='failed' WHERE id=$db_id");
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
		
		/* Load additional replacements */
		$rep = unserialize($db_rep);

		$subject = email_replace_vars($db_email_subject, $u, $rep);
		$body = email_replace_vars($db_email_body, $u, $rep);
		if($db_email_body_html != '') {
			$body_html = email_replace_vars($db_email_body_html, $u, $rep);
		} else {
			$body_html = '';
		}

		$mail = new PHPMailer();
		$mail->isSMTP();	// Use smtp
		$mail->SMTPDebug = 0;  /* 0=off, 1=client, 2=client and server */
		$mail->Debugoutput = 'echo'; /*or 'html' friendly debug output */
		$mail->Host = "localhost";
		$mail->Port = 25;
		$mail->SMTPAuth = false;	/* No auth */
		$mail->setFrom($db_email_from_addr, $db_email_from_name);
		//Set an alternative reply-to address
	//	$mail->addReplyTo('replyto@example.com', 'First Last');
		//Set who the message is to be sent to
		$mail->addAddress($db_email, $db_to);
		$mail->Subject = $subject;
		if($db_email_body_html == '') {
			$mail->isHTML(false);
			$mail->Body    = $body;
		} else {
			$mail->isHTML(true);
			$mail->Body    = $body_html;
			$mail->AltBody = $body;
		}

	//	$mail->msgHTML("ile_get_contents('contents.html'), dirname(__FILE__));
		//Replace the plain text body with one created manually
	//	$mail->AltBody = 'This is a plain-text message body';
		//Attach an image file
	//	$mail->addAttachment('images/phpmailer_mini.gif');

		//send the message, check for errors
		if (!$mail->send()) {
			$mysqli->real_query("UPDATE queue SET `result`='failed' WHERE id=$db_id");
			sfiab_log($mysqli, "email error", "Failed to send email with emails_id {$db_emails_id}: {$mail->ErrorInfo}");
		} else {
			$mysqli->real_query("UPDATE queue SET `result`='ok', `sent`=NOW() WHERE id=$db_id");
		}
		break;

	case 'push_award':
		$fair = fair_load($mysqli, $db_fair_id);
		$award = award_load($mysqli, $db_award_id);
		$result = remote_push_award_to_fair($mysqli, $fair, $award);
		sfiab_log($mysqli, "push award", "Push award \"{$award['name']}\" to fair \"{$fair['name']}\", result=$result");
		if($result == 0) {
			$mysqli->real_query("UPDATE queue SET `result`='ok', `sent`=NOW() WHERE id=$db_id");
		}
		break;

	case 'push_winner':
		$fair = fair_load($mysqli, $db_fair_id);
		$prize = prize_load($mysqli, $db_prize_id);
		$project = project_load($mysqli, $db_project_id);
		$result = remote_push_winner_to_fair($mysqli, $fair, $award);
		sfiab_log($mysqli, "push winner", "Push Winner \"{$prize['name']}\" to fair \"{$fair['name']}\", result=$result");
		if($result == 0) {
			$mysqli->real_query("UPDATE queue SET `result`='ok', `sent`=NOW() WHERE id=$db_id");
		}
		break;
	}

	sleep($sleepmin);
}
$mysqli->query("UPDATE config SET val='' WHERE var='queue_lock'");

print("SFIAB Queue Runner: Done\n");


?>
