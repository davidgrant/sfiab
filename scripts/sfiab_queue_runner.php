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
require_once('awards.inc.php');
require_once('PHPMailer/PHPMailerAutoload.php');
require_once('debug.inc.php');


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

$mysqli->real_query("UPDATE config SET val='".time(NULL)."' WHERE var='queue_lock'");


$q = $mysqli->prepare("SELECT `id`,`command`,`year`,`fair_id`,`award_id`,`prize_id`,`project_id`,`emails_id`,`to_uid`,`to_name`,`to_email`,`additional_replace` 
				FROM queue WHERE result='queued' LIMIT 1");
$q1 = $mysqli->prepare("SELECT `name`,`from_name`,`from_email`,`subject`,`body` FROM emails WHERE id = ?");
//loop forever, but not really, it'll get break'd as soon as there's nothing left to send
while(true) {

	/* Check for stop/start queue */
	$qstop = queue_stopped($mysqli);
	if($qstop) break;

   	/* Get an entry from the queue, exit if there are no more */
	$q->execute(); 
	$q->store_result();
	$q->bind_result($db_id, $db_command, $db_year, $db_fair_id, $db_award_id, $db_prize_id, $db_project_id,$db_emails_id,$db_uid,$db_to,$db_email,$db_rep);

	if($q->num_rows == 0) break;
	$q->fetch();

	switch($db_command) {
	case 'email':
		/* Now lookup the email to send from the database of emails.. we don't copy
		 * the full email text into the queue, just the ID of the email to send */
		$q1->bind_param('i', $db_emails_id);
		$q1->execute();
		$q1->store_result();
		$q1->bind_result($db_email_name, $db_email_from_name, $db_email_from_addr, $db_email_subject, $db_email_body);

		if($q->num_rows == 0) {
			/* Email in queue, but the ID doesn't exist?  someone deleted it between sending an email and 
			 * the queue starting? */
//			sfiab_log($mysqli, "email error", "Failed to send email with emails_id {$db_emails_id} that doesn't exist");
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

		$from_name = replace_vars($db_email_from_name, $u, $rep);
		$from_addr = replace_vars($db_email_from_addr, $u, $rep);
		$subject = replace_vars($db_email_subject, $u, $rep);
		$body = replace_vars($db_email_body, $u, $rep);
//		$body = str_replace("\r", "", $body); /* Remove \r's */
		/* Turn the text body into HTML because some modern mailers (like Apple Mail) suck at text */
		$body_html = htmlspecialchars($body);
		$body_html = str_replace("\r", "", $body_html);
		$body_html = str_replace("\n", "<br/>\n", $body_html);
	
		debug("   from: $from_name $from_addr\n");
		$mail = new PHPMailer();
		$mail->isSMTP();	// Use smtp
		$mail->SMTPDebug = 0;  /* 0=off, 1=client, 2=client and server */
		$mail->Debugoutput = 'echo'; /*or 'html' friendly debug output */
		$mail->Helo = gethostname();


		switch($config['smtp_type']) {
		case 'gmail':
			/* Gmail */
			$mail->Host = 'smtp.gmail.com';
			$mail->Port = 587;
			$mail->SMTPSecure = 'tls';
			$mail->SMTPAuth = true;
			$mail->Username = $config['smtp_username'];
			$mail->Password = $config['smtp_password'];
			break;
		case 'gmailrelay':
			/* Gmail Relay, requires a Google Apps account*/
			$mail->Host = 'smtp-relay.gmail.com';
			$mail->Port = 587;
			$mail->SMTPSecure = 'tls';
			$mail->SMTPAuth = true;
			$mail->Username = $config['smtp_username'];
			$mail->Password = $config['smtp_password'];
			break;
		case 'smtp':
			$mail->Host = $config['smtp_host'];
			$mail->Port = $config['smtp_port'];
			$mail->SMTPSecure = $config['smtp_encryption'];
			if($config['smtp_username'] != '' || $config['smtp_password'] != '') {
				$mail->SMTPAuth = true;
				$mail->Username = $config['smtp_username'];
				$mail->Password = $config['smtp_password'];
			} else {
				$mail->SMTPAuth = false;
			}
			break;
		case 'webserver':
		default:
			$mail->Host = "localhost";
			$mail->Port = 25;
			$mail->SMTPAuth = false;
			break;
		}

		$mail->setFrom($from_addr, $from_name);
		$mail->addReplyTo($from_addr, $from_name);

		$mail->addAddress($db_email, $db_to);
		$mail->Subject = $subject;
		$mail->isHTML(true);
		$mail->Body    = $body_html;
		$mail->AltBody = $body;

		debug("Send email: ".print_r($mail, true)."\n");

	//	$mail->msgHTML("ile_get_contents('contents.html'), dirname(__FILE__));
		//Replace the plain text body with one created manually
	//	$mail->AltBody = 'This is a plain-text message body';
		//Attach an image file
	//	$mail->addAttachment('images/phpmailer_mini.gif');


		/* Send the message, but give us a way to override all sending for testing
		 * (put $pretend_to_send_email in the config.inc.php) */
		if(isset($pretend_to_send_email) && $pretend_to_send_email == true) {
			$mail_ok = true;
			debug("Pretending mail to $db_email was sent successfully.\n");
		} else {
			$mail_ok = $mail->send();
		}

		if ($mail_ok) {
			$mysqli->real_query("UPDATE queue SET `result`='ok', `sent`=NOW() WHERE id=$db_id");
		} else {
			$mysqli->real_query("UPDATE queue SET `result`='failed' WHERE id=$db_id");
		}
		sfiab_log_email_send($mysqli, $db_emails_id, $db_uid, $db_email, $mail->ErrorInfo, $mail_ok);
		/* Extra sleep for emails */
		sleep(4);

		break;

	case 'push_award':
		$fair = fair_load($mysqli, $db_fair_id);
		$award = award_load($mysqli, $db_award_id);
		$result = remote_push_award_to_fair($mysqli, $fair, $award);
		$r = ($result == 0) ? 'ok' : 'failed';
		$mysqli->real_query("UPDATE queue SET `result`='$r', `sent`=NOW() WHERE id=$db_id");
		break;

	case 'push_winner':
		print("SFIAB Queue Runner: push_winner: $db_prize_id, $db_project_id\n");
		$result = remote_push_winner_to_fair($mysqli, $db_prize_id, $db_project_id);
		sfiab_log_push_winner($mysqli, $db_fair_id, $db_award_id, $db_prize_id, $db_project_id, $result);
		$r = ($result == 0) ? 'ok' : 'failed';
		$mysqli->real_query("UPDATE queue SET `result`='$r', `sent`=NOW() WHERE id=$db_id");
		break;

	case 'get_stats':
		$fair = fair_load($mysqli, $db_fair_id);
		$year = $db_year; 
		print("SFIAB Queue Runner: get_stats: $year\n");
		$result = remote_get_stats_from_fair($mysqli, $fair, $year);
		$r = ($result == 0) ? 'ok' : 'failed';
		$mysqli->real_query("UPDATE queue SET `result`='$r', `sent`=NOW() WHERE id=$db_id");
		break;

	case 'push_stats':
		$fair = fair_load($mysqli, $db_fair_id);
		$year = $db_year; 
		print("SFIAB Queue Runner: push_stats: $year\n");
		$result = remote_get_stats_from_fair($mysqli, $fair, $year);
		$r = ($result == 0) ? 'ok' : 'failed';
		$mysqli->real_query("UPDATE queue SET `result`='$r', `sent`=NOW() WHERE id=$db_id");
		break;

	case 'judge_scheduler':
		debug("Starting the judge scheduler\n");
		debug(getcwd());
		system("src/sfiab_annealer judges > files/judge_scheduler_log.txt");
		$mysqli->real_query("UPDATE queue SET `result`='ok', `sent`=NOW() WHERE id=$db_id");
		break;

	case 'tour_scheduler':
		debug("Starting the tour scheduler\n");
		debug(getcwd());
		system("src/sfiab_annealer tours > files/tour_scheduler_log.txt");
		$mysqli->real_query("UPDATE queue SET `result`='ok', `sent`=NOW() WHERE id=$db_id");
		break;

	case 'timeslot_scheduler':
		debug("Starting the timeslot scheduler\n");
		debug(getcwd());
		system("src/sfiab_annealer timeslots > files/timeslot_scheduler_log.txt");
		$mysqli->real_query("UPDATE queue SET `result`='ok', `sent`=NOW() WHERE id=$db_id");
		break;

	case 'exhibithall_scheduler':
		debug("Starting the exhibithall scheduler\n");
		debug(getcwd());
		system("src/sfiab_annealer eh > files/exhibithall_scheduler_log.txt");
		$mysqli->real_query("UPDATE queue SET `result`='ok', `sent`=NOW() WHERE id=$db_id");
		break;

	}


	sleep($sleepmin);
}
$mysqli->real_query("UPDATE config SET val='' WHERE var='queue_lock'");

print("SFIAB Queue Runner: Done\n");


?>
