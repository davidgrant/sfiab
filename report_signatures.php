<?php

require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('schools.inc.php');
require_once('tcpdf.inc.php');

$mysqli = sfiab_init("committee");
sfiab_load_config($mysqli);

$sig_uid = array_key_exists('uid',$_GET) ? int($_GET['uid']) : -1;
$sig_pid = array_key_exists('pid',$_GET) ? int($_GET['pid']) : -1;
$sig_year = array_key_exists('year',$_GET) ? (int)$_GET['year'] : $config['year'];

$projects = array();

if($sig_uid != -1) {
	/* Get the project we're supposed to load */
	$s = user_load($mysqli, $sig_uid);
	$sig_pid = $s['s_pid'];
	$p = project_load($mysqli, $s['pid']);
	project_load_students($mysqli, $p);
	$projects = array($p);
} else if($sig_pid != -1) {
	$p = project_load($mysqli, $sig_pid);
	project_load_students($mysqli, $p);
	$projects = array($p);
} else {
	/* Load everything */
	$projects = projects_load_all($mysqli, $sig_year);
	foreach($projects as &$p) {
		project_load_students($mysqli, $p);
	}
}


/* Load all signatures */
$sigs = array();
$q = $mysqli->query("SELECT * FROM signatures WHERE year='{$sig_year}'");
while($r = $q->fetch_assoc()) {
	$sig = signature_load($mysqli, NULL, $r);
	$uid = $sig['uid'];
	if(!array_key_exists($uid, $sigs)) {
		$sigs[$uid] = array();
	}
	$sigs[$uid][$sig['type']] = $sig;
}

$flags = ENT_QUOTES;
if(PHP_VERSION_ID >= 50400) $flags |= ENT_HTML401;



/* Begin a report */
$pdf=new pdf( "E-Signatures" , $sig_year );
$pdf->setFontSize(9);

$sig_types = array('student', 'parent', 'teacher');

foreach($projects as $pid=>&$project) {
	foreach($project['students'] as &$student) {
		$uid = $student['uid'];
		/* If a specific UID is specified, and it's not what we're looking for, skip it */
		if($sig_uid != -1 && !$sig_uid != $uid) {
//			print("$uid mismatch");
			continue;
		}

		if(!array_key_exists($uid, $sigs)) {
//			print("$uid doesn't exist");
			continue;
		}

		$school = school_load($mysqli, $student['schools_id']);


		/* See if the signature exists  */
		foreach($sig_types as $sig_type) {
			if(!array_key_exists($sig_type, $sigs[$uid])) {
				continue;
			}
			$sig = $sigs[$uid][$sig_type];

			/* Generate some HTML for the sig page */
			$pdf->AddPage();

			$already = "";
			if($sig['date_signed'] != '0000-00-00 00:00:00') {
				$already = "This form was signed on ". date('F j, g:ia', strtotime($sig['date_signed']));
			}
			


			$decl = cms_get($mysqli, "sig_{$sig_type}_declaration", $student);

			$html = "<h3>$already</h3>
				<p>Please review the Project Information and Declaration(s) below.  If you agree
				to the declaration(s), type in your name in the box below in lieu of a
				signature and submit this form.  This will function as your electronic
				signature and you do not need to sign the paper signature form.

				<p>If you have any questions or concerns, please contact us at {$config['email_registration']}";

			if($sig_type == 'student') { 
				$html .="<p>If you do not agree to use an electronic signature, please print the paper signature form and submit that.";
			} else {
				$html .="<p>If you do not agree to use an electronic signature, please request a paper copy of the signature form from the student and sign that.";
			}

			$html .= "<h3>Project Information</h3>
				<table>
				<TR><td>Student: </td><td>".htmlentities($student['name'], $flags , "UTF-8")."</td></tr>
				<TR><td>Project Title: </td><td>".htmlentities($project['title'], $flags , "UTF-8")."</td></tr>
				<TR><td>School: </td><td>".htmlentities($school['school'], $flags , "UTF-8")."</td></tr>
				</table>
				<hr/>
				<h3>{$signature_types[$sig_type]} Declaration</h3>
				<blockquote>".nl2br($decl)."</blockquote>";

			$html .= "<form action=\".\"><input type=\"checkbox\" name=\"box\" value=\"1\" checked=\"checked\">I Agree to the {$signature_types[$sig_type]} Declaration above";
			$html .= "</form><hr/>";


			if($config['sig_enable_release_of_information'] && ($sig_type == 'student' || $sig_type == 'parent')) {
				if($sig_type == 'student') {
					$rel_of_info = cms_get($mysqli, 'sig_release_of_information_student', $student);
				} else {
					$rel_of_info = cms_get($mysqli, 'sig_release_of_information_parent', $student);
				}

				$html .= "<h3>{$signature_types[$sig_type]} Release of Information</h3>
					<blockquote>".nl2br($rel_of_info)."</blockquote>";
				$html .= "<form action=\".\"><input type=\"checkbox\" name=\"box\" value=\"1\" checked=\"checked\">I Agree to the {$signature_types[$sig_type]} Release of Information above above";
				$html .= "</form><hr/>";
			}

			$html .= "<h3>{$signature_types[$sig_type]} Signature</h3>
				<blockquote>";
			if($sig_type == 'student') {
				$html .="<p>If you do not agree to the use of an electronic signature, please print a paper copy of this form to sign and submit it to the Science Fair Committee.";
			} else {
				$html .="<p>If you do not agree to the use of an electronic signature, please ask the student to print a paper copy of this form to sign and have the student submit that to the Science Fair Committee.";
			}

			$html .= "</blockquote>";
			$html .= "<form action=\".\"><input type=\"checkbox\" name=\"box\" value=\"1\" checked=\"checked\">I Agree to the use of an Electronic Signature instead of a Paper/Ink Signature<br/>";
			$html .= "</form><br/>";
			$html .= "Type your Name: <b><u>&nbsp;&nbsp;&nbsp;{$sig['signed_name']}&nbsp;&nbsp;&nbsp;</u></b>";
			$html .="<h3>$already</h3>";

			$pdf->writeHTML($html);
		}			
	}
}



$pdf->output();

?>
