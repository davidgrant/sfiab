<?php

require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('incomplete.inc.php');
require_once('form.inc.php');
require_once('tcpdf.inc.php');

$mysqli = sfiab_init(array('student', 'committee'));

$page_id = 's_signature';

$sample = false;
$generate_pdf = false;

$u = user_load($mysqli);

sfiab_check_abort_in_preregistration($u, $page_id);

if(array_key_exists('pdf', $_POST)) {
	/* Generate a pdf */
	$generate_pdf = true;
	if(array_key_exists('action', $_POST)) {
		if($_POST['action'] == 'sample') {
			$sample = true;
			$sample_category = (int)$_POST['cat_id'];
			$sample_num_students = (int)$_POST['num_students'];
		}
	}
}


if($sample) {
	sfiab_check_access($mysqli, array('committee'), false);
}

/* Load real user data, or fake for a sample? */
if(!$sample) {
	/* Load project and users for a non-sample.  We use all this both
	 * for the landing page and to display the pdf. */
	$p = project_load($mysqli, $u['s_pid']);
	$closed = sfiab_registration_is_closed($u);

	/* Get all users associated with this project */
	$users = user_load_all_for_project($mysqli, $u['s_pid']);

	debug(print_r($users, true));

	/* Check for all complete */
	$all_complete = true;
	foreach($users as &$user) {
		if($user['s_complete'] == 0) {
			$all_complete = false;
		}
	}

	/* Double check complete status with a force-reload */
	incomplete_check($mysqli, $fields, $u, $page_id, true);

	/* Load electronic signatures */
	foreach($users as &$user) {
		$q = $mysqli->query("SELECT * FROM signatures WHERE uid='{$user['uid']}'");
		$user['signatures'] = array();
		while($r = $q->fetch_assoc()) {
			$sig = signature_load($mysqli, NULL, $r);
			$user['signatures'][$sig['type']] = $sig;
		}
	}
}


if($generate_pdf == false) {
	/* Nothing to get, display the landing page */

	$help='
	<p>The last step of registration is to print a signature form.  This
	can only be done when all your sections are complete, and all your
	partner(s) sections are complete too.
	';

	sfiab_page_begin($u, "Student Signature Form", $page_id, $help);

	?>

	<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	form_disable_message($page_id, $closed, $u['s_accepted']);

	if($all_complete) {
		form_page_begin($page_id, $fields, '','','This page will become complete when the committee receives and processes your signature form.  This will happen approximately three weeks before the fair.');
	}
?>

	<h3>Signature Form</h3>

	<p>After all sections are complete for all students in this project, a
	signature form must be printed, signed, and submitted to complete your
	application to the <?=$config['fair_name']?>.  Instructions for
	completing the form and how/where to submit it are attached to the
	form.

	<p>The signature form must be signed by a teacher, parent/guardian, and
	each student.  For partner projects, each student may print a form, or
	a single form can be used for all signatures.  (Basically, as long as
	all the required signatures are present, we don't care how many pieces
	of paper they are on.)

<?php	if($config['sig_enable_senior_marks_form']) { ?>
		<p>For senior students, there is also a marks validation form that should
		be submitted to qualify for University scholarship awards.
<?php	} ?>

	
<?php	if($config['enable_electronic_signatures']) { ?>
		<h4>Electronic Signatures</h4>
		<p>Instead of using the printed form to collect signatures, you
		may instead collect electronic signatures, or you can
		mix-and-match and use both (some signatures electronically,
		some signatures on the printed form).  For an electronic
		signature, we will email a link to an online form
		to the person you need a signature from, and they can complete
		the form online.

		<p><b>If you use electronic signatures, you
		MUST still print and submit the first page of the signature
		form (without signatures) to complete your application.  Your
		application is incomplete until we have received the signature form.</b>

		<?php if($config['sig_enable_senior_marks_form']) { ?>
			<p>The senior marks validation form cannot
			be completed electronically.  It must be printed and
			signed by a school official.  (but all other signatures can still be collected electronically)
		<?php } ?>
<?php	} ?>

	<h4>Status of Students</h4>
		<ul data-role="listview" data-inset="true">
<?php
		foreach($users as &$user) {
			$c = ($user['s_complete'] == 1) ? 'class="happy"' : 'class="error"';
			$s = ($user['s_complete'] == 1) ? 'Complete' : 'Incomplete';
		?>
			<li><?=$user['name']?>: <span <?=$c?>><?=$s?></span></li>
<?php		} ?>
		</ul>


<?php	if($config['enable_electronic_signatures']) {
		$notice_printed = false;

		foreach($users as &$user) { ?>
			<h3>Electronic Signatures for <?=$user['name']?>:</h3>

<?php			if(!$all_complete) { ?>
				<p>Electronic signatures are available when all the students in the project are complete
<?php				continue;
			}

			if(!$notice_printed) { ?>
				<p>You do not have to use electronic signatures.  You can just use the printed form below if you choose.
				<p><b>If you use electronic signatures, you
				MUST still print and submit the first page of the signature
				form to complete your application.  Your
				application is incomplete until we have received the signature form.</b>
<?php				$notice_printed = true;
			} ?>
		
			<table data-role="table" data-mode="none" class="table_stripes">
			<tbody>
<?php
			foreach(array('student','parent','teacher') as $sig_type) {
				$sig_name = $signature_types[$sig_type];
				if(array_key_exists($sig_type, $user['signatures'])) {
					$sig = $user['signatures'][$sig_type];
				} else {
					$sig = NULL;
				}
				if(!array_key_exists($sig_type, $user['signatures']) || $sig['date_sent'] == '0000-00-00 00:00:00') {
					/* Doesn't exist */
					$sent = 'Not Sent';
					$status = 0;
				} else if ($sig['date_signed'] != '0000-00-00 00:00:00') {
					$sent = "Signed by {$sig['signed_name']} ({$sig['email']}) on ".date('F j, g:ia', strtotime($sig['date_signed']));
					$status = 2;
				} else {
					/* Not signed yet */
					$sent = "Sent to {$sig['name']} ({$sig['email']}) on ".date('F j, g:ia', strtotime($sig['date_sent']));
					$status = 1;
				}

?>
				<tr >
				<td align="center"><?=$sig_name?></td>
			<td align="center"><?=$sent?></td>
			<td align="left">
<?php 				if($status == 0) { /* Doesn't exist */?>
					<a href="s_signature_edit.php?uid=<?=$user['uid']?>&type=<?=$sig_type?>" data-mini="true"  data-inline="true" data-role="button" data-theme="g" data-ajax="false">Create Form</a>
<?php 				} else if ($status == 1) { /* Not signed yet */?>
					<span class="info" data-mini="true"  data-inline="true" data-role="button" data-theme="r" data-ajax="false">Waiting for Signature</span>
				<a href="s_signature_edit.php?uid=<?=$user['uid']?>&type=<?=$sig_type?>&del=del" data-mini="true"  data-inline="true" data-role="button" data-theme="r" data-ajax="false">Delete Form</a>
<?php 				} else { /* Signed */?>
					<span class="happy" data-mini="true"  data-inline="true" data-role="button" data-theme="g" data-ajax="false">Signature Received</span>
<?php 				} ?>
					
				</td>

				</tr>

<?php			} ?>
			</tbody>
			</table>
<?php		} 
	} ?>
	

	<h3>Download Information and Signature Form</h3>


<?php	if($closed) {
		$d = 'disabled="disabled"';
	} else if($all_complete) {
		$d = '';
	} else {
?>		<p>The signature form can only be printed when all the students in the project are complete.
<?php
		$d = 'disabled="disabled"';
	}
?>
	<form action="s_signature.php" method="post" data-ajax="false">
	<input type="hidden" name="pdf" value="1"/>
	<button type="submit" data-role="button" <?=$d?> data-theme="g" <?=$d?>>Download Information and Signature Form</button>
	</form>
	
	</div></div>

<?php
	sfiab_page_end();
	exit();

}


$cats = categories_load($mysqli);
$chals = challenges_load($mysqli);


/* The signature form */
if($sample) {
	$users = array();
	$p = array();


	$p['pid'] = 1234;
	$p['title'] = "My Science Fair Project";
	$p['cat_id'] = $sample_category;
	$p['challenge_id'] = 1;

	$users[0]['schools_id'] = 0;
	$users[0]['uid'] = 1111;
	$users[0]['grade'] = $cats[$sample_category]['min_grade'];
	$users[0]['name'] = "John Q. Doe";
	$users[0]['username'] = "john_doe";
	$users[0]['firstname'] = "John";
	$users[0]['lastname'] = "Doe";
	$users[0]['salutation'] = "";
	$users[0]['organization'] = "";
	$users[0]['sex'] = "male";
	$users[0]['email'] = "john@example.com";
	$users[0]['tshirt'] = "medium";

	if($sample_num_students == 2) {
		$users[1]['schools_id'] = 0;
		$users[1]['uid'] = 2222;
		$users[1]['grade'] = $cats[$sample_category]['max_grade'];
		$users[1]['name'] = "Jane R. Foo";
		$users[1]['username'] = "jane_foo";
		$users[1]['firstname'] = "Jane";
		$users[1]['lastname'] = "Foo";
		$users[1]['salutation'] = "";
		$users[1]['organization'] = "";
		$users[1]['sex'] = "female";
		$users[1]['email'] = "jane@example.com";
		$users[1]['tshirt'] = "small";
	}


	$school_name = "Example Secondary School";

} 

$pdf=new pdf( "Participant Signature Form ({$p['pid']})", $config['year'] );

$pdf->setFontSize(11);
$pdf->SetFont('times');
$height_sigspace = 15; //mm
$height_sigfont = $pdf->GetFontSize(); //mm


list($regfee, $rfeedata) = compute_registration_fee($mysqli, $p, $users);
$r_decl = cms_get($mysqli, 'sig_form_regfee', $u);


$pdf->AddPage();

$plural = (count($users)>1) ? 's' : '';

$x = $pdf->GetX();
$y = $pdf->GetY();

$pdf->barcode_2d(175, $y, 30, 30, $p['pid']);
$pdf->SetXY($x, $y);


$pdf->WriteHTML("<h3>".i18n('Registration Summary')."</h3>
	<p>
	".i18n('Registration Number').": {$p['pid']} <br/>
	".i18n('Project Title').": {$p['title']} <br/>
        ".i18n('Project Category').": ".i18n($cats[$p['cat_id']]['name'])." / ".i18n($chals[$p['challenge_id']]['name']));

$regfeestr = '';
if($r_decl !== NULL) {
	$regfeestr = i18n('Registration Fee').": \${$regfee}";
}

$students = "";
$school_names = array();
foreach($users as &$user) {
 	if($sample) {
		/* Skip query for generating a sample */
		$school_names[$user['uid']] = $school_name;
	} else {
		$qq = $mysqli->query("SELECT school FROM schools WHERE id={$user['schools_id']}");
		$rr = $qq->fetch_assoc();
		$school_name = $rr['school'];
		$school_names[$user['uid']] = $rr['school'];
	}

	if($students != '') $students .= '<br/>';
	$students .= "{$user['name']}, Grade {$user['grade']}, {$school_name}";
}
$e = i18n("Exhibitor$plural").":";
$w = $pdf->GetStringWidth($e) + 2;
/* By adding hte regfee right to the end of the student table, it prevents a newline from appearing */
$pdf->WriteHTML("<table><tr><td width=\"{$w}mm\">$e</td><td>$students</td></tr></table>$regfeestr");

$pdf->WriteHTML("<br/><hr/>");


function sig($pdf, $text1, $text2='')
{
	global $height_sigspace, $height_font;

	$x = $pdf->GetX();
	/* One cell for the whole thing, to force a page break if needed, leave 
	 * the current pos to the right so the Y is unchanged */
	 
	$pdf->Cell(0, $height_sigspace + $height_font, '', 0, 0);

	if($text2 == '') {
		/* Restore X, and indent a bit, move Y down the signature space */
		$pdf->SetXY($x + 15, $pdf->GetY() + $height_sigspace);

		/* Box with a top line, then a space, then a box with a top line for the date */
		$pdf->Cell(85, $height_font, $text1, 'T', 0, 'C');
		$pdf->SetX($pdf->GetX() + 15);
		$pdf->Cell(60, $height_font, i18n('Date'), 'T', 1, 'C');
	} else {
		/* Restore X, and indent a bit, move Y down the signature space */
		$pdf->SetXY($x + 5, $pdf->GetY() + $height_sigspace);

		/* Box with a top line, then a space, then a box with a top line for the date */
		$pdf->Cell(65, $height_font, $text1, 'T', 0, 'C');
		$pdf->SetX($pdf->GetX() + 10);
		$pdf->Cell(65, $height_font, $text2, 'T', 0, 'C');
		$pdf->SetX($pdf->GetX() + 10);
		$pdf->Cell(35, $height_font, i18n('Date'), 'T', 1, 'C');
	}
}

$e_instr = cms_get($mysqli, 'sig_form_instructions', $u);
if($e_instr !== NULL) {
	$pdf->WriteHTML("<h3>".i18n('Instructions')."</h3>".nl2br($e_instr));
	$pdf->WriteHTML("<br><hr>");
}


$e_decl = cms_get($mysqli, 'sig_student_declaration', $u);
if($e_decl !== NULL) {
	$pdf->WriteHTML("<h3>".i18n('Exhibitor Declaration')."</h3>".nl2br($e_decl));
	foreach($users AS &$user) {
		sig($pdf, "{$user['name']} (signature)");
	}
	$pdf->WriteHTML("<br><hr>");
 }

$p_decl = cms_get($mysqli, 'sig_parent_declaration', $u);
if($p_decl !== NULL) {
	$pdf->WriteHTML("<h3>".i18n('Parent/Guardian Declaration')."</h3>".nl2br($p_decl));

	foreach($users AS &$user) {
		sig($pdf, "Parent/Guardian of {$user['name']} (signature)");
	}
	$pdf->WriteHTML("<br><hr>");
 }

$t_decl = cms_get($mysqli, 'sig_teacher_declaration', $u);
if($t_decl !== NULL) {
 	$t = nl2br($t_decl);
	$pdf->WriteHTML("<h3>".i18n('Teacher Declaration')."</h3>$t");
	sig($pdf, i18n('Teacher Signature'));
	$pdf->WriteHTML("<br><hr>");	
 }

/* We fetched r_decl above */
if($r_decl !== NULL) {
	$pdf->WriteHTML("<h3>".i18n('Registration Fee Summary')."</h3><br>");

	$x = $pdf->GetX() + 20;
	$pdf->SetX($x);
	$pdf->Cell(60, 0, i18n('Item'), 'B', 0, 'C');
	$pdf->Cell(15, 0, i18n('Unit'), 'B', 0, 'C');
	$pdf->Cell(10, 0, i18n('Qty'), 'B', 0, 'C');
	$pdf->Cell(20, 0, i18n('Extended'), 'B', 1, 'C');
	foreach($rfeedata as $rf) {
		$u = "$".sprintf("%.02f", $rf['base']);
		$e = "$".sprintf("%.02f", $rf['ext']);

		$pdf->SetX($x);
		$pdf->Cell(60, 0, $rf['text'], 0, 0, 'L');
		$pdf->Cell(15, 0, $u, 0, 0, 'R');
		$pdf->Cell(10, 0, $rf['num'], 0, 0, 'C');
		$pdf->Cell(20, 0, $e, 0, 1, 'R');
	}
	$t = "$".sprintf("%.02f", $regfee);
	$pdf->SetX($x);
	$pdf->Cell(85, 0, i18n('Total (including all taxes)'), 'T', 0, 'R');
	$pdf->Cell(20, 0, $t, 'T', 1, 'R');
	$pdf->WriteHTML("<br><hr>");	
}

$p_decl = cms_get($mysqli, 'sig_form_postamble', $u);
if($p_decl !== NULL) {
	$pdf->WriteHTML("<h3>".i18n('Additional Information')."</h3>".nl2br($p_decl));
	$pdf->WriteHTML("<br><hr>");	
}

function course_tr($c1, $c2)
{
	$str = "<tr><td width=\"20\"></td>
			<td width=\"80\" align=\"center\">$c1</td>
			<td width=\"50\" align=\"center\">11&nbsp;&nbsp;&nbsp;12</td>
			<td width=\"50\" style=\"border:1px solid black;\" align=\"right\">%</td>";
	if($c2 != '') {
		$str .= "<td width=\"20\"></td>
			<td width=\"80\" align=\"center\">$c2</td>
			<td width=\"50\" align=\"center\">11&nbsp;&nbsp;&nbsp;12</td>
			<td width=\"50\" style=\"border:1px solid black;\" align=\"right\">%</td>";
	}
	$str .= "</tr>";
	return $str;
}
	

if($config['sig_enable_senior_marks_form']) {
	foreach($users AS $user) {
		if($user['grade'] <= 10) continue;

		$pdf->AddPage();
		$page = "<h3>Senior Marks Validation Form</h3><br>
The [FAIRNAME] may distribute awards to students who have demonstrated
excellence at the [FAIRNAME].  Some of the awards that are applicable to the
Senior Category entrants require verification of excellence in academic
subjects.<br> 
<br>
To help us in distributing these awards, please complete this form.  This
information is used to verify the eligibility of award winners only, <i>not</i>
to select the winners.  This information is needed for Senior Category entrants
only.<br>
<br>
<table border=\"0\" cellpadding=\"2\">
<tr>
    <td align=\"right\" width=\"100\">Full Name:</td>
    <td ><b>[NAME]</b></td>
</tr>
<tr>
    <td align=\"right\" width=\"100\">Grade:</td>
    <td><b>[GRADE]</b></td>
</tr>
<tr>
    <td align=\"right\" width=\"100\">School:</td>
    <td><b>{$school_names[$user['uid']]}</b></td>
</tr>
<tr>
    <td align=\"right\" width=\"100\">Project Title:</td>
    <td><b>{$p['title']}</b></td>
</tr>
</table>
<br>
<br>
1. Please list the post secondary institution(s) you hope to attend (highest priority first): <br>
<br>
<table cellpadding=\"5\">
<tr>
    <td width=\"20\"></td><td width=\"500\">a. ______________________________________________________________________________ </td>
</tr>
<tr>
    <td width=\"20\"></td><td width=\"500\">b. ______________________________________________________________________________ </td>
</tr>
<tr>
    <td width=\"20\"></td><td width=\"500\">c. ______________________________________________________________________________ </td>
</tr>
</table>
<br>
<br>

2. Please enter your most recent grades for the following courses.  Include courses your are 
currently taking if a partial grade is available.

<br>
<table border=\"0\" cellpadding=\"5\" cellspacing=\"10\">
<tr>
	<td width=\"20\"></td><td width=\"80\" align=\"center\"><b><br/>Course</b></td><td width=\"50\" align=\"center\"><b>Circle<br>11 or 12</b></td><td width=\"50\" align=\"center\"><b><br>Grade</b></td>
	<td width=\"20\"></td><td width=\"80\" align=\"center\"><b><br/>Course</b></td><td width=\"50\" align=\"center\"><b>Circle<br>11 or 12</b></td><td width=\"50\" align=\"center\"><b><br>Grade</b></td>
</tr>
".course_tr("Biology", "Geology")."
".course_tr("Calculus", "Math")."
".course_tr("Chemistry", "Physics")."
".course_tr("English", "")."
</table>
<br>
<br>
<br>
Verification of Mark Status by an Official (a Counsellor or School Administrator)
";
		$page = replace_vars($page, $user, array(), true);

 		$pdf->WriteHTML($page, true, false, false, false, '');

		sig($pdf, "Official's Signature", "Official's Name (PRINTED)");
	}
}

if($config['sig_enable_release_of_information']) {
	foreach($users AS $user) {
		$pdf->AddPage();
		$rel_of_info = cms_get($mysqli, 'sig_release_of_information_parent', $user);
	 	$t = nl2br($rel_of_info);
		$t .= "<br/><br/>Please SIGN and return this form, along with other forms in this package";
		$pdf->WriteHTML("<h3>".i18n('Release of Information Form')."</h3>$t");

		sig($pdf, "Signature of Parent or Guardian", "Name of Parent or Guardian (PRINTED)");
		sig($pdf, "Signature of  {$user['name']}");
	}
}

print($pdf->output());
?>
