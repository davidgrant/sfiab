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

if(array_key_exists('pdf', $_POST)) {
	/* Generate a pdf */
	$generate_pdf = true;
	if(array_key_exists('action', $_POST)) {
		if($_POST['action'] == 'sample') {
			$sample = true;
		}
	}
}

$u = user_load($mysqli);

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
	/* Check for all complete */
	$all_complete = true;
	foreach($users as $user) {
		if($user['s_complete'] == 0) {
			$all_complete = false;
		}
	}

	/* Double check complete status with a force-reload */
	incomplete_check($mysqli, $fields, $u, $page_id, true);
}


if($generate_pdf == false) {
	/* Nothing to get, display the landing page */

	$help='
	<p>The last step of registration is to print a signature form.  This
	can only be done when all your sections are complete, and all your
	partner(s) sections are complete too.
	';

	sfiab_page_begin("Student Signature Form", $page_id, $help);

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
	signature form must be printed, signed, and submitted to the Science
	Fair Committee.  Instructions for completing the form and how/where to
	submit it are attached to the form.
	
	<p>The signature form must be signed by a teacher, parent/guardian, and each student.

	<p>For senior students, there is also a marks validation form that should
	be submitted to qualify for University scholarship awards.
	
	<p>For partner projects, each student may print a form, or a single form can be used for all signatures.

	<h4>Status of Students</h4>
		<ul data-role="listview" data-inset="true">
<?php
		foreach($users as $user) {
			$c = ($user['s_complete'] == 1) ? 'class="happy"' : 'class="error"';
			$s = ($user['s_complete'] == 1) ? 'Complete' : 'Incomplete';
		?>
			<li><?=$user['name']?>: <span <?=$c?>><?=$s?></span></li>
<?php		} ?>
		</ul>

	<h4>Download Signature Form</h4>

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
	<form action="student_signature.php" method="post" data-ajax="false">
	<input type="hidden" name="pdf" value="1"/>
	<button type="submit" data-role="button" <?=$d?> data-theme="g" <?=$d?>>Download Signature Form</button>
	</form>
	
	</div></div>

<?php
	sfiab_page_end();
	exit();

}


/* The signature form */
if($sample) {
	$users = array();
	$p = array();


	$p['pid'] = 1234;
	$p['title'] = "My Science Fair Project";
	$p['cat_id'] = 3;
	$p['challenge_id'] = 1;

	$users[0]['schools_id'] = 0;
	$users[0]['uid'] = 1111;
	$users[0]['grade'] = 11;
	$users[0]['name'] = "John Q. Doe";
	$users[0]['username'] = "john_doe";
	$users[0]['firstname'] = "John";
	$users[0]['lastname'] = "Doe";
	$users[0]['salutation'] = "";
	$users[0]['organization'] = "";
	$users[0]['sex'] = "male";
	$users[0]['email'] = "john@example.com";
	$users[0]['tshirt'] = "medium";



	$school_name = "Example Secondary School";

	/*
 	$projectinfo->title="Sample Project Title";
 	$projectinfo->division="Proj Division";
 	$projectinfo->category="Proj Category";
	$studentinfo->firstname="SampleFirst";
	$studentinfo->lastname="SampleLast";
	$studentinfo->grade="10";
	$studentinfoarray[]=$studentinfo;
	$rr->school="SampleSchool";
	*/
 } else {
 	/* Project and students already loaded */
 }

$cats = categories_load($mysqli);
$chals = challenges_load($mysqli);

$pdf=new pdf( "Participant Signature Form ({$p['pid']})", $config['year'] );

$pdf->setFontSize(11);
$pdf->SetFont('times');
$height_sigspace = 15; //mm
$height_sigfont = $pdf->GetFontSize(); //mm


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
        ".i18n($cats[$p['cat_id']]['name'])." / ".i18n($chals[$p['challenge_id']]['name']));

 $students = "";
 $school_names = array();
 foreach($users as $user) { 
 	if($sample) {
		/* Skip query for generating a sample */
		$school_names[$user['uid']] = $school_name;
		continue;
	} 

	$qq = $mysqli->query("SELECT school FROM schools WHERE id={$user['schools_id']}");
	$rr = $qq->fetch_assoc();

	$school_names[$user['uid']] = $rr['school'];

	if($students != '') $students .= '<br/>';
	$students .= "{$user['name']}, Grade {$user['grade']}, {$rr['school']}";
 }
$e = i18n("Exhibitor$plural").":";
$w = $pdf->GetStringWidth($e) + 2;
$pdf->WriteHTML("<table><tr><td width=\"{$w}mm\">$e</td><td>$students</td></tr></table>");
$pdf->WriteHTML("<hr>");


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

$e_decl = cms_get($mysqli, 'exhibitordeclaration', $u);
if($e_decl !== NULL) {
	$t = nl2br($e_decl);
	$pdf->WriteHTML("<h3>".i18n('Exhibitor Declaration')."</h3>$t");

	foreach($users AS $user) {
		sig($pdf, "{$user['name']} (signature)");
	}
	$pdf->WriteHTML("<br><hr>");
 }

$p_decl = cms_get($mysqli, 'parentdeclaration', $u);
if($p_decl !== NULL) {
 	$t = nl2br($p_decl);
	$pdf->WriteHTML("<h3>".i18n('Parent/Guardian Declaration')."</h3>$t");

	foreach($users AS $user) {
		sig($pdf, "Parent/Guardian of {$user['name']} (signature)");
	}
	$pdf->WriteHTML("<br><hr>");
 }

$t_decl = cms_get($mysqli, 'teacherdeclaration', $u);
if($t_decl !== NULL) {
 	$t = nl2br($t_decl);
	$pdf->WriteHTML("<h3>".i18n('Teacher Declaration')."</h3>$t");
	sig($pdf, i18n('Teacher Signature'));
	$pdf->WriteHTML("<br><hr>");	
 }

$r_decl = cms_get($mysqli, 'regfee', $u);
if($r_decl !== NULL) {
	$pdf->WriteHTML("<h3>".i18n('Registration Fee Summary')."</h3><br>");

	list($regfee, $rfeedata) = compute_registration_fee($mysqli, $p, $users);

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

$p_decl = cms_get($mysqli, 'postamble', $u);
if($p_decl !== NULL) {
 	$t = nl2br($p_decl);
	$pdf->WriteHTML("<h3>".i18n('Additional Information')."</h3>$t");
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

/*
<tr><td width=\"20\"></td><td width=\"300\">Biology</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td></tr>
<tr><td width=\"20\"></td><td width=\"300\">Geology</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td></tr>
<tr><td width=\"20\"></td><td width=\"300\">Physics</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td></tr>
<tr><td width=\"20\"></td><td width=\"300\">Chemistry</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td></tr>
<tr><td width=\"20\"></td><td width=\"300\">Calculus</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td></tr>
<tr><td width=\"20\"></td><td width=\"300\">Algebra</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td></tr>
<tr><td width=\"20\"></td><td width=\"300\">Average:</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td><td width=\"30\">&nbsp;</td></tr>
*/
		$page = replace_vars($page, $user, array(), true);



 		$pdf->WriteHTML($page, true, false, false, false, '');

		sig($pdf, "Official's Signature", "Official's Name (PRINTED)");
	}
}

if($config['sig_enable_release_of_information']) {
	foreach($users AS $user) {
		$pdf->AddPage();
		$rel_of_info = cms_get($mysqli, 'sig_release_of_information', $user);
	 	$t = nl2br($rel_of_info);
		$pdf->WriteHTML("<h3>".i18n('Release of Information Form')."</h3>$t");

		sig($pdf, "Signature of Parent or Guardian", "Name of Parent or Guardian (PRINTED)");
		sig($pdf, "Signature of  {$user['name']}");
	}
}

/*
$pdf->addPage();

$pdf->WriteHTML("<h3>".i18n('Principal or Vice-Principal\'s Signature')."</h3>
    <p>
    ".i18n('Registration Number').": $registration_number <br/>
    ".i18n('Project Title').": {$projectinfo->title} <br/>
        ".i18n($projectinfo->category)." / ".i18n($projectinfo->division));

$e = i18n("Exhibitor$plural").":";
$w = $pdf->GetStringWidth($e) + 2;
$pdf->WriteHTML("<table><tr><td width=\"{$w}mm\">$e</td><td>$students</td></tr></table>");
$pdf->WriteHTML("<hr>");

$page = " The student(s) from your school, named above, has applied to compete in the Greater Vancouver Regional Science Fair (GVRSF) held on the upper floor Student Union Building at UBC on April 11, 12 and 13 of 2013. The application is supported by a teacher on your staff. This letter verifies your knowledge of the entry to this academic event and absence of the above named student(s) for the purposes of this competition. We will contact your school if there are any concerns. We wish your students all the best in this very worthwhile academic challenge.<br>
<br>
Please note that from the eligible participants entered, the GVRSF Committee, upon the recommendations of its Chief Judge(s), will select those to receive medals, prizes or scholarships and will also select participants to compete at this year's Canada-Wide Science Fair (CWSF) May 11 - 18, 2013.<br>
<br>
The GVRSF does considerable fundraising and has secured sufficient funding for one student for each selected project to attend the CWSF. However, for selected two-person projects, you will be contacted on the Friday of the Fair, to ascertain if the school will make financial arrangements for the second person to attend the Fair - the cost is $1,500. There is a time concern as decisions are made Friday and only students for whom finances are guaranteed can be announced on Saturday. Such discussions should be held in confidence directly with you but if you are absent or unavailable Friday, April 12, please alert a designated person of the possibility of our call.<br>
<br>
The Greater Vancouver Regional Science Fair is recognized as one of the most competitive Regional Science Fairs in Canada. Being selected from this Region to be a participant at the national Canada-Wide Science Fair is a tremendous honour for the students and for their school. This experience has proven to be very significant for students, many of whom have stayed involved with science fairs and a good many reference it as a landmark experience in their education and career pathways. Please consult our website www.gvrsf.ca for contacts or for more information. Again, congratulations on your school's participation.<br>
<br>
<h3>Principal or Vice-Principal's signature:</h3><br>
My signature below acknowledges that I understand the opportunities outlined in the above document and authorize the students identified above to attend the Greater Vancouver Regional Science Fair. I also agree to the expectations explained above.<br><br>";

$pdf->WriteHTML($page, true, false, false, false, '');

sig($pdf, "Signature of Principal/Vice-Principal", "Print Name and Title");
*/
             

print($pdf->output());
?>
