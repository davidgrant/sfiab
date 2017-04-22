<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');

$mysqli = sfiab_init(array('student'));


$file = $_GET['f'];


/* Turn this into a database query */
$files = array( "2017_floorplan.pdf",
		"2017_schedule.pdf",
		"2017_judgeform.pdf", 
		"2017_safety_checklist.pdf",
		"2017_ubcmap.pdf",
		);


$filename = '';
$mimetype = '';
$in_year_dir = false;
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
case 'tour_scheduler_log':
	$filename = 'tour_scheduler_log.txt';
	break;

default:
	/* Allow JPEGs in files/fairyear/ */
	if(preg_match("/[A-Za-z0-9_ \-]*.jpg/", $file)) {
		if(!sfiab_user_is_a('student') && !sfiab_user_is_a('committee')) {
			/* Only let students load photos */
			print("Not allowed");
			exit();
		}

		$u = user_load($mysqli);
		$p = project_load($mysqli, $u['s_pid']);

		/* Make sure this student is allowed to download this photo */
		$possible_filenames = array("{$p['number']}", 
			  		  "{$p['floor_number']}",
					    sprintf("%03d", $p['floor_number']));
		$files = array();		
		foreach($possible_filenames as $f) {
			if(preg_match('/[^A-Za-z0-9 _\-]/', $f)) {
				/* STring contains non alphanumeric+space characters */
				continue;
			}

			$fn = "files/{$config['year']}/$f.jpg";
			debug("Trying: $fn");
			if(file_exists("$fn")) {
				$files[] = "$f.jpg";
			}

			/* Alos try adding _number to the end of it */
			for($x = 1; $x < 50; $x++) {
				$fn = "files/{$config['year']}/{$f}_{$x}.jpg";
				debug("Trying: $fn");
				if(file_exists("$fn")) {
					$files[] = "{$f}_{$x}.jpg";
				} else {
					break;
				}
			}
		}
		if(!in_array($file, $files)) {
			print("Not allowed.");
			exit();
		}

		$filename = $file;
		$in_year_dir = true;
		break;
	}

	/* Allow a passed-in file only if it appears in our database of files.  Below we exit if the filename contains
	 * any characters other than a-z, A-Z, 0-9, -._ strip out directories and
	 * force a file access in files/ */
	if(in_array($file, $files)) {
		$filename = $file;
	} else {
		exit();
	}
}

if($filename != '') {

	if(preg_match('/[^A-Za-z0-9_\-\. ]/', $filename)) {
		print("Invalid filename: $filename");
		exit();
	}

	if($filename[0] == '.' || $filename[0] == '/') {
		/* Forbid files starting with . (that includes ..) */
		print("Invalid filename: $filename");
		exit();
	}


	$file_info = pathinfo($filename);

	/* Check the filename and extension */
	switch($file_info['extension']) {
	case 'jpg':  $mimetype = 'image/jpg'; break;
	case 'pdf':  $mimetype = 'application/pdf'; break;
	case 'doc':  $mimetype = 'application/msword'; break;
	case 'txt':  $mimetype = 'text/plain'; break;
	case 'csv':  $mimetype = 'text/csv'; break;
//          case "gif": $mimetype="image/gif"; break;
//        case "zip": $mimetype="application/zip"; break;
	default:
		print("Invalid filename: extension");
		exit();
	}
	if(preg_match('/[^A-Za-z0-9_\-\.]/', $file_info['filename'])) {
		print("Invalid filename: filename");
		exit();
	}

	/* So now we know the filename only has A-Za-z0-9_- in it (and doesn't
	 * have any . or / or other funny characters that might change the directory)
	 * Construct the filename relative to files/ */
	if($in_year_dir) {
		$filename = "files/{$config['year']}/".$file_info['filename'].".".$file_info['extension'];
	} else {
		$filename = "files/".$file_info['filename'].".".$file_info['extension'];
	}
	if(!file_exists($filename)) {
		print("Not found :(");
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
