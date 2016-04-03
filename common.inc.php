<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

require_once('config.inc.php');
require_once('filter.inc.php');

/* It's kinda important that there be no blank lines BEFORE this, or they're sent as newlines.  This messes
 * up login.php */

$config = array();

$sfiab_roles = array(	'student' => array('name' => 'Student'),
//			'teacher' => array(),
			'judge' => array('name' => 'Judge'),
			'committee' => array('name' => 'Committee'),
			'volunteer' => array('name' => 'Volunteer'),
			'sponsor' => array('name' => 'Sponsor')
		);

$tshirt_sizes = array('none' => 'None',
			'xsmall' => 'X-Small',
			'small' => 'Small',
			'medium' => 'Medium',
			'large' => 'Large',
			'xlarge' => 'X-Large' );

$pages_disabled_in_preregistration = array ('s_tours', 's_awards','s_signature' );

$signature_types = array('student' => 'Exhibitor', 'parent' => 'Parent/Guardian', 'teacher'=>'Teacher', 'ethics'=>'Supervisor');


function sfiab_db_connect()
{
	global $dbhost, $dbuser, $dbpassword, $dbdatabase;
	$mysqli = new mysqli($dbhost, $dbuser, $dbpassword, $dbdatabase);
	return $mysqli;
}

function sfiab_session_is_active()
{
	 if ( version_compare(phpversion(), '5.4.0', '>=') ) {
	  return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
	         } else {
	 return session_id() === '' ? FALSE : TRUE;
   }
}

function sfiab_load_config($mysqli)
{
	global $config;
	$q = $mysqli->query("SELECT var,val FROM config WHERE 1");
	while($r = $q->fetch_row()) {
		$config[$r[0]] = $r[1];
	}

	if(array_key_exists('HTTP_HOST', $_SERVER)) {
		/* The fair URL is http[s]://, then the HTTP host, then the real directory of this script with the real document root removed */
		$real_docroot = realpath($_SERVER['DOCUMENT_ROOT']);
		$real_dir = realpath(__DIR__);
		$config['fair_url'] = (array_key_exists('HTTPS', $_SERVER) ? 'https://' : 'http://')
					.$_SERVER['HTTP_HOST'].substr($real_dir, strlen($real_docroot) );

	} 
	$config['provincestate'] = 'Province';
	$config['postalzip'] = 'Postal Code';

	$a = explode(',', $config['judge_divisional_distribution']);
	$config['judge_divisional_distribution'] = array();
	foreach($a as $d) {
		$config['judge_divisional_distribution'][] = (int)$d;
	}
	$a = explode(',', $config['judge_divisional_prizes']);
	$config['judge_divisional_prizes'] = array();
	foreach($a as $p) {
		$config['judge_divisional_prizes'][] = $p;
	}

	date_default_timezone_set($config['timezone']);
}

function sfiab_init($roles, $skip_password_expiry_check=false)
{
	global $config;
	$mysqli = sfiab_db_connect();
	sfiab_load_config($mysqli);

	$db_version = intval(file_get_contents('updates/db_version.txt', 0, NULL, 0, 5));
	if($db_version != $config['db_version']) {
		print("The database needs to be updated (have={$config['db_version']}, latest={$db_version}).");
		exit();
	}

	sfiab_session_start();

	if($roles !== NULL) {
		if(!is_array($roles)) {
			$roles = array($roles);
		}
		sfiab_check_access($mysqli, $roles, $skip_password_expiry_check);
	}
	return $mysqli;
}

function sfiab_log($mysqli, $type, &$u_or_uid, $result=0, $data='', $message='', $pid=0, $fair_id=0, $email_id=0, $award_id=0, $prize_id=0)
{
	global $config;

	if(array_key_exists('REMOTE_ADDR', $_SERVER)) {
		$ip = $_SERVER['REMOTE_ADDR'];
	} else {
		$ip = "commandline";
	}

	$pid = (int)$pid;
	if(is_array($u_or_uid)) {
		$uid = $u_or_uid['uid'];
		$pid = $u_or_uid['s_pid'];
		if($data == '') {
			$data = $u_or_uid['username'];
		}
	} else if(is_null($u_or_uid) || $u_or_uid == 0) {
		$uid = 0;
		if(sfiab_session_is_active()) {
			if(array_key_exists('uid', $_SESSION)) {
				$uid = $_SESSION['uid'];
				$pid = $_SESSION['u']['s_pid'];
			}
		} 
	} else {
		$uid = (int)$u_or_uid;
	}

	$type = $mysqli->real_escape_string($type);
	$data = $mysqli->real_escape_string($data);
	$m = $mysqli->real_escape_string($message);
	$year = $config['year'];
	$fair_id = (int)$fair_id;
	$result = (int)$result;
	$email_id = (int)$email_id;
	$award_id = (int)$award_id;
	$prize_id = (int)$prize_id;
	$result = (int)$result;

	$mysqli->real_query("INSERT INTO log (`ip`,`uid`,`pid`,`fair_id`,`email_id`,`award_id`,`prize_id`,`year`,`time`,`type`,`data`,`message`,`result`) 
				VALUES('$ip','$uid','$pid','$fair_id','$email_id','$award_id','$prize_id','$year',NOW(),'$type','$data','$m','$result')");
	$str = "uid=$uid";
	if($fair_id > 0) $str .= ", fair_id=$fair_id";
	if($email_id > 0) $str .= ", email_id=$email_id";
	if($award_id > 0) $str .= ", award_id:prize_id=$award_id:$prize_id";
	debug("sfiab_log: $type: $result, $ip, $year, $str, $data, $m\n");
	if($mysqli->error != '') {
		debug("sfiab_log: {$mysqli->error}\n");
	}
}

function sfiab_log_sync_stats($mysqli, $fair_id, $result)
{
	$uid = 0;
	sfiab_log($mysqli, "sync_stats", $uid, $result, "", "", 0, $fair_id);
}

function sfiab_log_push_award($mysqli, $fair_id, $award_id, $result, $data='')
{
	$uid = 0;
	sfiab_log($mysqli, "push_award", $uid, $result, $data, "", 0, $fair_id, 0, $award_id);
}

function sfiab_log_push_winner($mysqli, $fair_id, $award_id, $prize_id, $project_id, $result)
{
	$uid = 0;
	sfiab_log($mysqli, "push_winner", $uid, $result, "", "", $project_id, $fair_id, 0, $award_id, $prize_id);
}

function sfiab_log_email_send($mysqli, $email_id, $uid, $email, $error_message, $result) 
{
	sfiab_log($mysqli, "email_send", $uid, $result, $email, $error_message, 0, 0, $email_id);
}

function sfiab_log_register($mysqli, $u_or_username, $email, $role, $error_message, $result)
{
	if(is_array($u_or_username)) {
		$u = &$u_or_username;
		$username = $u['username'];
	} else {
		$u = NULL;
		$username = "$u_or_username";
	}
	sfiab_log($mysqli, "register", $u, $result, "{$username}, {$email}, {$role}", $error_message);
}

function sfiab_log_login($mysqli, $u_or_username, $reason, $result)
{
	if(is_array($u_or_username)) {
		$u = &$u_or_username;
		$username = $u['username'];
	} else {
		$u = NULL;
		$username = "$u_or_username";
	}
	sfiab_log($mysqli, "login", $u, $result, $username, $reason);
}

function sfiab_session_start() 
{
        $session_name = 'sfiab';
        $secure = false; 
        $httponly = true; /* Don't let javascript get the session id */
 
        ini_set('session.use_only_cookies', 1); 
        $cookieParams = session_get_cookie_params(); 
        session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httponly); 
        session_name($session_name);
        session_start();

	$session_hash = hash('sha512', 'X'.$_SERVER['REMOTE_ADDR'].'X'.$_SERVER['HTTP_USER_AGENT']);
		/* Hash the passwd with the browser, the browser shouldn't change. we can check
	 * it every page load */
	if(array_key_exists('session_hash', $_SESSION)) {
		if($_SESSION['session_hash'] != $session_hash) {
			/* Something changed, user is trying to session hijack? start a new session */
			session_regenerate_id();
			session_unset();
		}
	}
	$_SESSION['session_hash'] = $session_hash;
}

function sfiab_logged_in()
{
	if(!isset($_SESSION['unique_uid'], $_SESSION['username'])) 
		return false;
	return true;
}

function sfiab_user_is_a($role) 
{
	if(!sfiab_logged_in()) return false;
	if(in_array($role, $_SESSION['roles'])) 
		return true;
	return false;
}

/* Roles is an array of roles to allow */
function sfiab_check_access($mysqli, $roles, $skip_expiry_check) 
{
	global $config;
	/* FIXME: this needs work */

	/* Mostly from http://www.wikihow.com/Create-a-Secure-Login-Script-in-PHP-and-MySQL */
	if(!sfiab_logged_in()) {
		header("Location: index.php#login");
		exit();
	}

	/* If the password has expired don't let the user go anywhere else */
	if($_SESSION['password_expired'] && !$skip_expiry_check) {
?>
		<html><head>
		<script>
		window.location = "<?=$config['fair_url']?>/a_change_password.php";
		</script></head></html>
<?php		exit();
	}

	$uid = $_SESSION['uid'];
//	$unique_id = $_SESSION['unique_uid'];
//	$username = $_SESSION['username'];

	$q = $mysqli->query("SELECT roles FROM users WHERE `uid`='$uid' LIMIT 1");
	if($q->num_rows != 1) {
		print("Access Denied<br/>");
		exit();
	}
	$r = $q->fetch_row();
	$db_roles = $r[0];
	
	/* If editting another user, enforce committee no mater what
	 * the page asked for */
	if(array_key_exists('edit_uid', $_SESSION)) {
		$roles = array('committee');
	}

	if(count($roles) > 0) {
		$db_roles = explode(',',$db_roles);
		$ok = false;
		/* Check the auth type */
		foreach($roles as $r) {
			if(in_array($r, $db_roles)) {
				$ok = true;
			}
		}
		if(!$ok) {
			print("Access Denied<br/>");
			exit();
		}
	}
}

function sfiab_left_nav_incomplete_count($page_id)
{
	$count = 0;
	if(array_key_exists('incomplete', $_SESSION)) {
		if(array_key_exists($page_id, $_SESSION['incomplete'])) {
			$fields = $_SESSION['incomplete'][$page_id];
			$count = count($fields);
		}
	}
	return $count;
}
function sfiab_print_left_nav_menu_entries(&$u, $current_page_id, $menu)
{
	/* $u could be NULL! */
	global $pages_disabled_in_preregistration;

	foreach($menu as $id=>$d) {

		if($d === NULL) continue;

		/* Disable some entries if in pregregistration mode */
		$cl = ($current_page_id == $id) ? 'class="ui-li-selected"' : ''; 
		$disable_link = false;
		if(is_array($u) && sfiab_preregistration_is_open($u)) {
			if(in_array($id, $pages_disabled_in_preregistration)) {
				$cl = 'class="ui-li-disabled"';
				$disable_link = true;
			}
		}

		$theme = ($id == 'c_editing') ? 'data-theme="l"' : '';

		$count = sfiab_left_nav_incomplete_count($id);
		$style = ($count == 0) ? 'style="display: none;"' : '';
		$incomplete = "<span $style class=\"ui-li-count\">$count</span>";
		/* Apply $sel to the <li> and the <a> so it actually shows up */
?>
		<li data-icon="false" <?=$cl?> <?=$theme?> >
<?php			if($disable_link) { ?>
				<span class="entry"><?=$d[0]?><?=$incomplete?></span>
<?php			} else { ?>
				<a id="left_nav_<?=$id?>" href="<?=$d[1]?>" <?=$cl?> data-rel="external" data-ajax="false" data-transition="fade" data-inline="true" >
					<?=$d[0]?><?=$incomplete?>
				</a>
<?php			} ?>				
		</li>
<?php	} 
}


function sfiab_print_left_nav_menu(&$u, $menu_id, $text, $current_page_id, $menu)
{
	/* $u could be NULL! */

	/* Count all incomplete */
	$count = 0;
	foreach($menu as $id=>$d) {
		if($d === NULL) continue;
		$count += sfiab_left_nav_incomplete_count($id);
	}
	$style = ($count == 0) ? 'style="display: none;"' : '';
	$incomplete = "<span $style class=\"ui-li-count\">$count</span>";
	$collapsed = array_key_exists($current_page_id, $menu) ? 'false' : 'true';
?>
	<div id="<?=$menu_id?>" data-role="collapsible" data-inset="true" data-collapsed="<?=$collapsed?>" data-mini="true" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" class="ui-shadow ui-alt-icon ui-nodisc-icon">
		<h3><?=$text?><?=$incomplete?></h3>
		<ul data-role="listview" class="jqm-list ui-alt-icon ui-nodisc-icon" data-inset="false" >
			<?=sfiab_print_left_nav_menu_entries($u, $current_page_id, $menu);?>
		</ul>
	</div>
<?php
}

function sfiab_print_left_nav(&$u, $menu, $current_page_id="")
{
	/* $u could be NULL! */

	global $config;
	$main_menu = array(
			'welcome' => array('Welcome', 'index.php#welcome'),
			'important_dates' => array('Important Dates', 'index.php#important_dates'),
			'winners' => array('Winners', 'main_winners.php'),
			'committee' => array('Committee', 'index.php#committee'),
			'contact' => array('Contact Us', 'index.php#contact'),
		);

	$student_menu = array('s_home' => array('Student Home', 's_main.php'),
			      's_personal' => array('Personal Info', 's_personal.php'),
			      's_emergency' => array('Emergency Contact', 's_emergency.php'),
			      's_reg_options' => array('Options', 's_reg_options.php'),
			      's_tours' => array('Tours', 's_tours.php'),
			      's_partner' => array('Partner', 's_partner.php'),
			      's_project' => array('Project Info', 's_project.php'),
			      's_ethics' => array('Project Ethics', 's_ethics.php'),
			      's_safety' => array('Project Safety', 's_safety.php'),
			      's_mentor' => array('Mentor Info', 's_mentor.php'),
			      's_awards' => array('Award Selection', 's_awards.php'),
			      's_signature' => array('Signature Form', 's_signature.php'),
			      );
	if(!$config['tours_enable']) {
		unset($student_menu['s_tours']);
	}

	$judge_menu = array('j_home' => array('Judge Home', 'j_main.php'),
			    'j_personal' => array('Personal Info', 'j_personal.php'),
			    'j_options' => array('Judging Options', 'j_options.php'),
			    'j_expertise' => array('Judging Expertise', 'j_expertise.php'),
			    'j_mentorship' => array('Mentorship', 'j_mentorship.php'),
			    'j_schedule' => array('Judging Assignments', 'j_schedule.php'),
			    );

	$volunteer_menu = array('v_home' => array('Volunteer Home', 'v_main.php'),
				'v_personal' => array('Personal Info', 'v_personal.php'),
				'v_options' => array('Options', 'v_options.php'),
				'v_tours' => array('Tours', 'v_tours.php'),
		);
	if(!$config['tours_enable']) {
		unset($volunteer_menu['v_tours']);
	}

	$committee_menu = array('c_main' => array('Committee Home', 'c_main.php'),
			    'c_awards' => array('Awards', 'c_awards.php'),
			    'c_awards_list' => NULL,
			    'c_awards_edit' => NULL,
			    'c_award_winners' => NULL,
			    'c_backup' => NULL,
			    'c_check_tours' => NULL,
			    'c_config' => array('Configuration', 'c_config.php'),
			    'c_config_variables' => NULL,
			    'c_config_categories' => NULL,
			    'c_config_challenges' => NULL,
			    'c_config_logo' => NULL,
			    'c_config_cms' => NULL,
			    'c_config_fairs' => NULL, 
			    'c_judging' => array('Judging', 'c_judging.php'),
			    'c_judge_sanity' => NULL,
			    'c_judge_score_entry' => NULL,
			    'c_judge_score_summary' => NULL,
			    'c_judge_scheduler' => NULL,
			    'c_reports' => array('Reports', 'c_reports.php'),
			    'c_communication' => array('Send Emails', 'c_communication.php'),
			    'c_stats' => array('Statistics and Logs', 'c_stats.php'),
			    'c_ysc_stats' => NULL,
			    'c_students' => array('Students / Projects', 'c_students.php'),
			    'c_assign_project_numbers' => NULL,
			    'c_input_signature_forms' => NULL,
			    'c_input_ethics' => NULL,
			    'c_tours' => array('Tours', 'c_tours.php'),
			    'c_tours_edit' => NULL,
			    'c_tours_list' => NULL,
			    'c_volunteers' => array('Volunteers', 'c_volunteers.php'),
			    'c_user_list' => NULL,
			    'c_user_edit' => NULL,
			    'c_register_feeder' => NULL,
			    'c_report_editor' => NULL,
			    'c_timeslots' => NULL,
			    'c_timeslots_assign' => NULL,
			    'c_jteam_edit' => NULL,
			    'c_jteam_list' => NULL,
			    'c_input_signature_forms' => NULL,
			    'c_communication_send' => NULL,
			    'c_communication_queue' => NULL,
			    'c_visit_list' => NULL,
			    'c_checkin_list' => NULL,
		);

	$login_menu = array('register' => array('Registration', 'index.php#register'),
			    'login' => array('Login', 'index.php#login'),
		);

	$account_menu = array('a_change_password' => array('Change Password', 'a_change_password.php'),
			      'a_delete_account' => array('Delete Account', 'a_delete_account.php'),
		);
	$logout_menu = array( 'logout' => array('Logout', 'login.php?action=logout'),
		);

	$user_edit_menu = array ('c_editing' => array('Return To Your User', 'c_user_list.php?return=1'),
		);
?>

	<div id="leftnav" data-role="panel" class="leftnav_panel" data-position="left" data-display="overlay" data-theme="a">
	<br/><br/>

<?php

	$editing_another_user = 0;
	if(array_key_exists('edit_uid', $_SESSION)) {
		$editing_another_user = $_SESSION['edit_uid'];
?>
		<ul data-role="listview" data-inset="true" class="jqm-list ui-alt-icon ui-nodisc-icon">
		<?=sfiab_print_left_nav_menu_entries($u, $current_page_id, $user_edit_menu, true);?>
		</ul>
<?php
		$roles = $_SESSION['edit_roles'];
	} else  {
		sfiab_print_left_nav_menu($u, 'leftnav_main', 'Main Menu', $current_page_id, $main_menu);
		if(!sfiab_logged_in()) 
			$roles = array();
		else 
			$roles = $_SESSION['roles'];
	}

	if(in_array('student', $roles)) 
		sfiab_print_left_nav_menu($u, 'leftnav_student', 'Student Menu', $current_page_id, $student_menu);

	if(in_array('judge', $roles)) 
		sfiab_print_left_nav_menu($u, 'leftnav_judge', 'Judge Menu', $current_page_id, $judge_menu);

	if(in_array('committee', $roles)) 
		sfiab_print_left_nav_menu($u, 'leftnav_committee', 'Committee Menu', $current_page_id, $committee_menu);

	if(in_array('volunteer', $roles)) 
		sfiab_print_left_nav_menu($u, 'leftnav_volunteer', 'Volunteer Menu', $current_page_id, $volunteer_menu);

	if($editing_another_user == 0) {

		if(sfiab_logged_in())
			sfiab_print_left_nav_menu($u, 'leftnav_account', 'Account Menu', $current_page_id, $account_menu);
?>
		<ul data-role="listview" data-inset="true" class="jqm-list ui-alt-icon ui-nodisc-icon">
<?php
		if(sfiab_logged_in()) {
			sfiab_print_left_nav_menu_entries($u, $current_page_id, $logout_menu, true);
?>
			<script>
				$("#left_nav_logout").on("click", function(event, ui) {
					$.post( "login.php", { action: "logout" }, function( data ) {
						window.location = "<?=$config['fair_url']?>/index.php";
					});
					return false;
				});
			</script>
<?php		
		} else {
			sfiab_print_left_nav_menu_entries($u, $current_page_id, $login_menu, true);
		}
	}
?>
	</ul>
	</div>
	<script>
//		.mobile.changePage( ".sfiab_page", { allowSamePageTransition:true } );
	</script>

<?php
}

function sfiab_print_header($page_id)
{
	global $config;
?>
	<div id="header" data-theme="a" data-role="header"  >
		<a href="#leftnav" data-icon="bars" data-iconpos="notext" class="leftnav_button ui-nodisc-icon ui-alt-icon">Menu</a>
<?php		if(sfiab_logged_in() && isset($_SESSION['edit_uid'])) { ?>
			<h3 style="white-space:normal"><?=sfiab_info("Temporarily logged in as {$_SESSION['edit_name']}. <a href=\"c_user_list.php?return=1\" data-ajax=\"false\" >Click Here</a> to return.")?></h3>
<?php		} else { ?>
			<h3><?=$config['fair_name']?> <?=$config['year']?></h3>
<?php		} ?>
		<a href="#help_panel_<?=$page_id?>" data-icon="info" data-iconpos="notext" class="ui-nodisc-icon ui-alt-icon">Help</a>

	</div>
<?php
}

function sfiab_print_help_panel($page_id, $text)
{
?>
	<div id="help_panel_<?=$page_id?>" data-theme="a" data-role="panel" data-position="right" data-display="overlay">
		<h2>Help</h2>
		<?=$text?>
	</div>
<?php
}

function output_start($title = '')
{
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black">
  <title><?=$title?></title>
  <link rel="stylesheet" href="jquery/jquery.mobile-1.4.5.min.css" />
  <script src="jquery/jquery-1.9.1.min.js"></script>
<?php /* This allows the same page to be reloaded by default... it must be done before jquerymobile is loaded */ ?>
  <script>
    $(document).on("mobileinit", function(){ 
//    	$.mobile.changePage.defaults.allowSamePageTransition = true;
//	$.mobile.page.prototype.options.domCache = false;
//    	$.mobile.changePage.defaults.reloadPage = true; 
	});
  </script>
  <script src="jquery/jquery.mobile-1.4.5.min.js"></script>
  <script src="jquery/jquery-ui.min.js"></script>
  <script src="jquery/jquery.ui.touch-punch.min.js"></script>
  <link rel="stylesheet" href="sfiab.css">
  <script src="sfiab.js"></script>
</head>
<body>
<?php
//  <link rel="stylesheet" href="http://code.jquery.com/mobile/1.4.0/jquery.mobile-1.4.0.min.css" />
//  <script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
//  <script src="http://code.jquery.com/mobile/1.4.0/jquery.mobile-1.4.0.min.js"></script>

}

function output_end()
{
?>
</body></html>
<?php
}

function sfiab_page_begin(&$u, $title, $page_id="", $help="")
{
	output_start($title);
	sfiab_print_header($page_id);
	sfiab_print_left_nav($u, $title, $page_id);
	if($help != "") {
		sfiab_print_help_panel($page_id, $help);
	}
}

function sfiab_page_end()
{
	output_end();
}

function sfiab_error($text)
{
?>
	<div class="error"><?=$text?></div>
<?php
}

function sfiab_happy($text)
{
?>
	<div class="happy"><?=$text?></div>
<?php
}

function sfiab_info($text)
{
?>
	<div class="info"><?=$text?></div>
<?php
}

function challenges_load($mysqli, $year = false) 
{
	global $config;
	static $challenges = NULL;
	if($year == false) $year = $config['year'];

	if($challenges === NULL) {
		$challenges = array();
		$q = $mysqli->query("SELECT * FROM challenges WHERE year='$year'");
		while($c=$q->fetch_assoc()) {
			$challenges[$c['chal_id']] = $c;
		}
	}
	return $challenges;
}

function categories_load($mysqli, $year = false) 
{
	global $config;
	static $categories = NULL;

	if($year == false) $year = $config['year'];

	if($categories === NULL) {
		$categories = array();
		$q = $mysqli->query("SELECT * FROM categories WHERE year='$year' ORDER BY cat_id");
		while($c=$q->fetch_assoc()) {
			filter_int($c['min_grade']);
			filter_int($c['max_grade']);
			$categories[$c['cat_id']] = $c;
		}
	}
	return $categories;
}

function category_get_from_grade($mysqli, $grade)
{
	/* Note: to category_get_from_grade for a different year, just call categories_load first to cache
	 * another year */
	$categories = categories_load($mysqli);

	foreach($categories as $cid=>&$c) {
		if($grade >=$c['min_grade'] && $grade <= $c['max_grade']) 
			return $cid;
	}
	return false;
}

function categories_grade_range($mysqli, $year = false)
{
	global $config;

	if($year == false) $year = $config['year'];
	$q = $mysqli->query("SELECT MIN(min_grade),MAX(max_grade) FROM categories WHERE year='$year'");
	$r = $q->fetch_row();
	$min_grade = $r[0];
	$max_grade = $r[1];

	return array($min_grade, $max_grade);
}

function print_replace_vars_table(&$u)
{ 
	global $config;
	?>
	<p>Text in [ALL CAPS] surrounded in square brackets has special meaning.  Here is a list of replacements that will be made: </p>
	<table>
	<tr><td>Key</td><td>Example</td><td>Replacement</td></tr>
	<tr><td colspan=2><b>Fair Information</b></td></tr>
	<tr><td>[FAIRNAME]</td><td><?=$config['fair_name']?></td><td>The name of the science fair.</td></tr>
	<tr><td>[FAIRABBR]</td><td><?=$config['fair_abbreviation']?></td><td>The abbreviation for the science fair name.</td></tr>
	<tr><td>[LOGIN_LINK]</td><td><?=$config['fair_url']?>/index.php#login</td><td>A URL pointing to the login page on the registration site.</td></tr>
	<tr><td>[FAIR_URL]</td><td><?=$config['fair_url']?></td><td>The main URL for the registration site.</td></tr>
	<tr><td>[YEAR]</td><td><?=$config['year']?></td><td>The current fair year.</td></tr>

	<tr><td colspan=2><b>Info Specific to the Email Recipient</b></td></tr>
	<tr><td>[FIRSTNAME]</td><td><?=$u['firstname']?></td><td>The first name of the email recipient.</td></tr>
	<tr><td>[LASTNAME]</td><td><?=$u['lastname']?></td><td>The last name of the email recipient.</td></tr>
	<tr><td>[NAME]</td><td><?=$u['name']?></td><td>The full name (first + last) of the email recipient.</td></tr>
	<tr><td>[PASSWORD]</td><td>35db324fc</td><td>The plain-text password of the mail recipient.  This only works in the "New Registration" and "Forgot Password" emails because the password is generated at the time the email is sent.  At all other times, the user's password is encoded in the database using a one-way cryptographic hash.  It cannot be unencoded. </td></tr>
	<tr><td>[SALUTATION]</td><td>Dr. </td><td>Salutation to be used for the mail recipient. </td></tr>

	<tr><td>[USERNAME]</td><td><?=$u['username']?></td><td>The username of the email recipient.</td></tr>
	<tr><td>[USERNAME_LIST]</td><td><?=$u['username']?></td><td>A list of ALL usernames associated with the email of the recipient.</td></tr>
	<tr><td colspan=2><b>Email Addresses</b></td></tr>
	<tr><td>[EMAIL_CHAIR]</td><td><?=$config['email_chair']?></td><td>The chair email address.</td></tr>
	<tr><td>[EMAIL_REGISTRATION]</td><td><?=$config['email_registration']?></td><td>The registration coordinator's email address.</td></tr>
	<tr><td>[EMAIL_ETHICS]</td><td><?=$config['email_ethics']?></td><td>The ethics committee's email address</td></tr>
	<tr><td>[EMAIL_CHIEFJUDGE]</td><td><?=$config['email_chiefjudge']?></td><td>The chief judge's email address.</td></tr>
	</table>
<?php
}


function replace_vars($text, &$u=NULL, $additional_vars = array(), $html = false)
{
	global $config;
	$rep = array(	'/\[FAIRNAME\]/' => $config['fair_name'],
			'/\[FAIRABBR\]/' => $config['fair_abbreviation'],
			'/\[YEAR\]/' => $config['year'],
			'/\[CHAIR_EMAIL\]/' => $html ? mailto($config['email_chair']) : $config['email_chair'],
			'/\[EMAIL_CHAIR\]/' => $html ? mailto($config['email_chair']) : $config['email_chair'],
			'/\[EMAIL_REGISTRATION\]/' => $html ? mailto($config['email_registration']) : $config['email_registration'],
			'/\[EMAIL_ETHICS\]/' => $html ? mailto($config['email_ethics']) : $config['email_ethics'],
			'/\[EMAIL_CHIEFJUDGE\]/' => $html ? mailto($config['email_chiefjudge']) : $config['email_chiefjudge'],
			'/\[DATE_FORMS_DUE\]/' => date('F d, Y', strtotime($config['date_student_registration_closes']) + 60*60*24),

			);

	/* A list of variables that aren't always available. */
	foreach($additional_vars as $var=>&$val) {
		switch($var) {
		case 'fair_url':
			/* We can get a whole lot of vars from the config */
			$rep['/\[LOGIN_LINK\]/'] = $val.'/index.php#login';
			$rep['/\[FAIR_URL\]/'] = $val;
			break;
		case 'password':
		case 'username_list':
		case 'student_name':
			$rep['/\['.strtoupper($var).'\]/'] = $val;
			break;
		case 'signature_key':
			$rep['/\[SIGNATURE_LINK\]/'] = $additional_vars['fair_url'].'/signature.php?k='.$val;
			break;
		}
	}

	if(is_array($u)) {
		/* Replacements that depend on a user */
		$rep += array(
			'/\[NAME\]/' => $u['name'],
			'/\[EMAIL\]/' => $html ? mailto($u['email']) : $u['email'],
			'/\[USERNAME\]/' => $u['username'],
			'/\[SALUTATION\]/' => $u['salutation'],
			'/\[FIRSTNAME\]/' => $u['firstname'],
			'/\[LASTNAME\]/' => $u['lastname'],
			'/\[GRADE\]/' => $u['grade'],
			'/\[ORGANIZATION\]/' => $u['organization'],
			);

		if($u['sex'] == 'male') {
			$rep['/\[HISHER\]/'] = 'his';
			$rep['/\[HIMHER\]/'] = 'him';
		} else if($u['sex'] == 'female') {
			$rep['/\[HISHER\]/'] = 'her';
			$rep['/\[HIMHER\]/'] = 'her';
		} else {
			$rep['/\[HISHER\]/'] = 'his / her';
			$rep['/\[HIMHER\]/'] = 'him / her';
		}
	}

	return preg_replace(array_keys($rep), array_values($rep), $text);
}

function cms_get($mysqli, $name, &$u = NULL) 
{
	$q = $mysqli->query("SELECT `text`,`use` FROM `cms` WHERE `name`='$name'");
	print($mysqli->error);
	$r = $q->fetch_assoc();
	if($r['use'] == 1) {
		$r = replace_vars($r['text'], $u, array(), true);
		$r = preg_replace('/\[FILE ([A-Za-z0-9_-]+\.[a-z]+)\]/', 'file.php?f=$1', $r);
		return $r;
	}
	return NULL;
}

function compute_registration_fee($mysqli, &$p, &$users)
{
 	global $config;
	$ret = array();

	$regfee_items = array();
	$n_students = count($users);
	$regfee = 0;

/*	$regfee_items[] = array('id' => 'funrun',
				'name' => 'Science Fair Fun Run ',
				'per' => 'student',
				'cost' => 50,
				);
*/
	$n_tshirts = 0;
	$sel = array();
	foreach($users as $u) {
		if($u['tshirt'] != 'none' && $config['tshirt_enable']) $n_tshirts++;

		/* Check their regfee items too */
/*		if($config['participant_regfee_items_enable'] != 'yes') continue;

		$sel_q = mysql_query("SELECT * FROM regfee_items_link 
					WHERE students_id={$s->id}");
		while($info_q = mysql_fetch_assoc($sel_q)) {
			$sel[] = $info_q['regfee_items_id'];
		}*/
	}

	if(true) {  /* Reg per student */
		$f = $config['regfee'] *  $n_students;
		$ret[] = array( 'id' => 'regfee',
				'text' => "Fair Registration (per student)",
				'base' => $config['regfee'],
				'num' => $n_students,
				'ext' => $f );
 		$regfee += $f; 
	} else {
		$ret[] = array( 'id' => 'regfee',
				'text' => "Fair Registration (per project)",
				'base' => $config['regfee'],
				'num' => 1,
				'ext' => $config['regfee'] );
		$regfee += $config['regfee'];
	}

	if($config['tshirt_enable']) {
		$tsc = floatval($config['tshirt_cost']);
		if($tsc != 0.0) {
			$f = $n_tshirts * $tsc;
			$regfee += $f;
			if($n_tshirts != 0) {
				$ret[] = array( 'id' => 'tshirt',
						'text' => "T-Shirts",
						'base' => $tsc,
						'num' => $n_tshirts,
						'ext' => $f);
			} 
		}
	}

	/* $sel will be empty if regfee_items is disabled */
	foreach($regfee_items as $rfi) {
		$cnt = 0;
		foreach($sel as $s) if($rfi['id'] == $s) $cnt++;

		if($cnt == 0) continue;

		$tsc = floatval($rfi['cost']);

		/* If it's per project, force the count to 1 */
		if($rfi['per'] == 'project') {
			$cnt = 1;
		}

		$f = $tsc * $cnt;
		$ret[] = array( 'id' => "regfee_item_{$rfi['id']}",
				'text' => "{$rfi['name']} (per {$rfi['per']})" ,
				'base' => $tsc,
				'num' => $cnt,
				'ext' => $f);
		$regfee += $f;
	}
	return array($regfee, $ret);
}

function i18n($text)
{
	return $text;
}

/* Returns:
 * -2 registration isn't open yet
 * -1 registration is closed
 * 1 registration is open
 * 2 preregistration is open */
function sfiab_registration_status($u, $role=NULL)
{
	global $config;
	$prereg_open_date = NULL;
	$reg_close_date = '';

	$now = date( 'Y-m-d H:i:s' );

	if($role !== NULL) {
		if($role == 'student') {
			if($config['preregistration_enable']) {
				$prereg_open_date = $config['date_student_preregistration_opens'];
			}
			$reg_open_date = $config['date_student_registration_opens'];
			$reg_close_date = $config['date_student_registration_closes'];
		} else if ($role == 'judge') {
			$reg_open_date = $config['date_judge_registration_opens'];
			$reg_close_date = $config['date_judge_registration_closes'];
		} else if ($role == 'volunteer') {
			/* Use judge registration */
			$reg_open_date = $config['date_judge_registration_opens'];
			$reg_close_date = $config['date_judge_registration_closes'];
		} else {
			return 1; /* Open */
		}
	} else {
		/* Registration is always open for a committee editting a user */
		if(sfiab_session_is_active()) {
			if(array_key_exists('edit_uid', $_SESSION)) {
				return 1; /* Open */
			}
		}

		/* Get the normal reg close date, if the student's submission
		 * has been accepted, disregard any reg close override and just
		 * return that their reg is closed */
		if(in_array('student', $u['roles'])) {
			if($config['preregistration_enable']) {
				$prereg_open_date = $config['date_student_preregistration_opens'];
			}
			$reg_open_date = $config['date_student_registration_opens'];
			$reg_close_date = $config['date_student_registration_closes'];

			/* Accetped students registrations are closed*/
			if($u['s_accepted']) {
				return -1; /* Closed */
			}

		} else if (in_array('judge', $u['roles'])) {
			$reg_open_date = $config['date_judge_registration_opens'];
			$reg_close_date = $config['date_judge_registration_closes'];
		} else if (in_array('volunteer', $u['roles'])) {
			/* Use judge registration */
			$reg_open_date = $config['date_judge_registration_opens'];
			$reg_close_date = $config['date_judge_registration_closes'];
		} else {
			return 1; /* Open */
		}

		/* If there is an override, set the registration to open, and use the 
		 * specified reg close date */
		if($u['reg_close_override'] !== NULL) {
			$reg_close_date = $u['reg_close_override'];
			if($now < $u['reg_close_override']) {
				/* Registration should be open, fudge the open date too */
				$reg_open_date = $now;
				$prereg_open_date = NULL;
			}
		} 
	}

	if($prereg_open_date !== NULL) {
		if($now > $prereg_open_date && $now < $reg_open_date) {
			return 2; /* Pre registration */
		}
	}		

	if($now < $reg_open_date) {
		return -2; /* Not yet open */
	} else if ($now < $reg_close_date) {
		return 1; /* Open */
	} else {
		return -1; /* Closed */
	}
}

/* Is registration open for this user? reads the customer user override first
 * then uses the global date */

function sfiab_registration_is_closed($u, $role=NULL)
{
	$status = sfiab_registration_status($u, $role);
	if($status < 0) {
		/* Status < 0 means registration is closed */
		return true;
	}
	return false;
}

function sfiab_preregistration_is_open(&$u)
{
	$status = sfiab_registration_status($u);
	if($status == 2) {
		return true;
	}
	return false;
}

function sfiab_check_abort_in_preregistration(&$u, $page_id)
{
	global $pages_disabled_in_preregistration;
	if(sfiab_preregistration_is_open($u)) {
		if(in_array($page_id, $pages_disabled_in_preregistration)) {
			print("This page is closed in pre-registration.");
			exit();
			return false;
		}
	}
	return true;
}


/* antispambot like from wordpress */
function antispambot($email) 
{
	$result = '';
	for($x=0; $x < strlen($email); $x++ ) {
		$c = $email[$x];
		if($c == '@' || rand(0,1) == 0 ) {
			$result .= '&#'.ord($c).';';
		} else {
			$result .= $c;
		}
	}
	return $result;
}

function mailto($email)
{
	return '<a href="mailto:'.antispambot($email).'">'.antispambot($email).'</a>';
}



/* It's kinda important that there be no blank lines AFTER this, or they're sent as newlines.  This messes
 * up login.php */
?>
