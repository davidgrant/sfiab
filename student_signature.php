<?php

require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('incomplete.inc.php');
require_once('form.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

require_once('tcpdf.inc.php');

sfiab_session_start($mysqli, array('student'));

$page_id = 's_signature';

$u = user_load($mysqli);
$p = project_load($mysqli, $u['s_pid']);

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

if(count($_POST) == 0) {
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

<?php	if($all_complete) {
		$d = '';
	} else {
?>		<p>The signature form can only be printed when all the students in the project are complete.
<?php
		$d = 'disabled="disabled"';
	}
?>
	<form action="student_signature.php" method="post" data-ajax="false">
	<input type="hidden" name="pdf" value="1"/>
	<button type="submit" data-role="button" <?=$d?> data-theme="g">Download Signature Form</button>
	</form>
	
	</div></div>

<?php
	sfiab_page_end();
	exit();

}


/* The signature form */
 //anyone can access a sample, we dont need to be authenticated or anything for that
 if(0 && $_POST['sample']) {
	$registration_number=12345;
	$registration_id=0;

	$users = array();
	$p = array();
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


function sig($pdf, $text)
{
	global $height_sigspace, $height_font;

	$x = $pdf->GetX();
	/* One cell for the whole thing, to force a page break if needed, leave 
	 * the current pos to the right so the Y is unchanged */
	 
	$pdf->Cell(0, $height_sigspace + $height_font, '', 0, 0);

	/* Restore X, and indent a bit, move Y down the signature space */
	$pdf->SetXY($x + 15, $pdf->GetY() + $height_sigspace);

	/* Box with a top line, then a space, then a box with a top line for the date */
	$pdf->Cell(85, $height_font, $text, 'T', 0, 'C');
	$pdf->SetX($pdf->GetX() + 15);
	$pdf->Cell(60, $height_font, i18n('Date'), 'T', 1, 'C');
}

$e_decl = cms_get($mysqli, 'exhibitordeclaration');
if($e_decl !== NULL) {
	$t = nl2br($e_decl);
	$pdf->WriteHTML("<h3>".i18n('Exhibitor Declaration')."</h3>$t");

	foreach($users AS $user) {
		sig($pdf, "{$user['name']} (signature)");
	}
	$pdf->WriteHTML("<br><hr>");
 }

$p_decl = cms_get($mysqli, 'parentdeclaration');
if($p_decl !== NULL) {
 	$t = nl2br($p_decl);
	$pdf->WriteHTML("<h3>".i18n('Parent/Guardian Declaration')."</h3>$t");

	foreach($users AS $user) {
		sig($pdf, "Parent/Guardian of {$user['name']} (signature)");
	}
	$pdf->WriteHTML("<br><hr>");
 }

$t_decl = cms_get($mysqli, 'teacherdeclaration');
if($t_decl !== NULL) {
 	$t = nl2br($t_decl);
	$pdf->WriteHTML("<h3>".i18n('Teacher Declaration')."</h3>$t");
	sig($pdf, i18n('Teacher Signature'));
	$pdf->WriteHTML("<br><hr>");	
 }

$r_decl = cms_get($mysqli, 'regfee');
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

$p_decl = cms_get($mysqli, 'postamble');
if($p_decl !== NULL) {
 	$t = nl2br($p_decl);
	$pdf->WriteHTML("<h3>".i18n('Additional Information')."</h3>$t");
	$pdf->WriteHTML("<br><hr>");	
}

foreach($users AS $user) {
    if($user['grade'] <= 10) continue;

    $pdf->AddPage();
    $page = "<h3>SENIOR MARKS VALIDATION FORM</h3><br>
The Greater Vancouver Regional Science Fair may distribute awards to students
who have demonstrated excellence at the Greater Vancouver Regional Science
Fair.  Some of the awards that are applicable to the Senior Category entrants
require verification of excellence in academic subjects.<br>
<br>
To help us in distributing these awards, please complete the following.  This information is needed for senior category entrants only.<br>
<br>
<table border=\"0\" cellpadding=\"2\">
<tr>
    <td align=\"right\" width=\"100\">Full Name:</td>
    <td ><b>{$user['name']}</b></td>
</tr>
<tr>
    <td align=\"right\" width=\"100\">Grade:</td>
    <td><b>{$user['grade']}</b></td>
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
NOTE: If you are a Grade 11 student, please complete Question 1, Question 2 for any of the Grade 12 courses you have taken, and enter the average of ALL your Grade 11 courses in Question 3.<br>
<br>
1. The post secondary institution(s) I hope to attend are (listed in priority): <br>

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

2. My marks in four (4) provincially examinable subjects:<br>
<br>
<table border=\"0\" cellpadding=\"5\">
<tr>
    <td width=\"25\">a.</td><td width=\"300\">English 12: _________%</td>
</tr>
<tr>
    <td width=\"25\">b.</td><td width=\"300\">Plus 2 or 3 of the following:</td>
</tr>
<tr>
    <td align=\"right\" width=\"100\"> Biology 12:</td><td width=\"100\"> _________%</td>
    <td align=\"right\" width=\"100\"> Geology 12:</td><td width=\"100\"> _________%</td>
</tr>
<tr>
    <td align=\"right\" width=\"100\"> Physics 12:</td><td width=\"100\"> _________%</td>
    <td align=\"right\" width=\"100\"> Chemistry 12:</td><td width=\"100\"> _________%</td>
</tr>
<tr>
    <td width=\"25\">c.</td><td width=\"500\">Plus one (1) other provincially examinable subject (Omit this part if 3 marks were entered in Part 2.b.)</td>
</tr><tr>
    <td width=\"25\"></td><td width=\"500\">Course Name: ____________________________________        _________%</td>
</tr>
</table>
<br>
3. Average of all my provincially examinable subjects:  ________%<br>
<br>
Verification of Mark Status by an Official (a Counsellor or School Administrator)
";
    $pdf->WriteHTML($page, true, false, false, false, '');

    sig($pdf, "Official's Signature", "Official's Name (PRINTED)");
}

foreach($users AS $user) {
	$pdf->AddPage();
	if($user['sex'] == 'male') {
		$h = 'his';
		$m = 'him';
	} else if($user['sex'] == 'female') {
		$h = 'her';
		$m = 'her';
	} else {
		$h = 'his / her';
		$m = 'him / her';
    }

	$page = "<h3>RELEASE OF INFORMATION FORM</h3><br>
Pursuant to the freedom of information and protection of privacy, I, as the parent or legal guardian of:
<br>
<br>
<table border=\"0\" cellpadding=\"2\">
<tr>
	<td align=\"right\" width=\"100\">Participant Name:</td>
	<td ><b>{$user['name']}</b></td>
</tr>
</table><br>
do hereby grant my permission to take, retain, and publish $h photograph and written
materials about $m and $h {$config['year']} Greater Vancouver
Regional Science Fair project to be displayed on print materials and on
the Internet through the Greater Vancouver Regional Science Fair,
Science Fair Foundation of British Columbia, and award sponsor websites.
I hereby give permission to use the materials to promote the Science
Fair Program. This would include media, various social media sites, award sponsors, potential
sponsors.  I understand that materials on social media sites are in the public
domain and these online services may be located outside of Canada.

";


	$pdf->WriteHTML($page, true, false, false, false, '');
	sig($pdf, "Signature of Parent or Guardian", "Name of Parent or Guardian (PRINTED)");
	sig($pdf, "Signature of  {$user['name']}");

	$page="<br><br>Please SIGN and return this form, along with other forms in this package, to the Greater Vancouver Regional Science Fair.<br>";
	$pdf->WriteHTML($page, true, false, false, false, '');
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
