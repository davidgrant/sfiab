<?php

$file = $_GET['f'];

$filename = '';
$mimetype = '';
switch($file) {
case 'logo':
	$filename = 'logo.jpg';
	break;

case 'policy_4.1a':
	$filename = '4.1A_Humans_Low_Risk_0-2.pdf';
	break;
case 'policy_4.1b':
	$filename = '4.1B_Humans_Significant_Risk_0.pdf';
	break;
case 'policy_4.1c':
	$filename = '4.1C_Animals_0-2.pdf';
	break;

case 'research_plan':
	$filename = 'research_proposal_en.doc';
	break;
case 'research_plan_animals':
	$filename = 'research_plan_animals_en.doc';
	break;

case 'judge_scheduler_log':
	$filename = 'judge_scheduler_log.txt';
	break;

default:
	exit();
}

/*                case "pdf": $mimetype="application/pdf"; break;
                case "zip": $mimetype="application/zip"; break;
                case "doc": $mimetype="application/msword"; break;
                case "gif": $mimetype="image/gif"; break;
                case "png": $mimetype="image/png"; break;
                case "jpe": case "jpeg":
                case "jpg": $mimetype="image/jpg"; break;

*/
if($filename != '') {
	$filename = "files/$filename";
	$ext = pathinfo($filename, PATHINFO_EXTENSION);

	switch($ext) {
	case 'jpg':  $mimetype = 'image/jpg'; break;
	case 'pdf':  $mimetype = 'application/pdf'; break;
	case 'doc':  $mimetype = 'application/msword'; break;
	case 'txt':  $mimetype = 'text/plain'; break;
	case 'csv':  $mimetype = 'text/csv'; break;
	default:
		print("Unknown mimetype");
		exit();
	}

	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private",false);
	header("Content-Type: $mimetype");
	header("Content-Disposition: attachment; filename=\"".basename($filename)."\";");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ".@filesize($filename));
//	set_time_limit(0);
	@readfile("$filename");
}

?>
