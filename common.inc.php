<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

require_once('config.inc.php');

date_default_timezone_set('PST8PDT');

/* It's kinda important that there be no blank lines BEFORE this, or they're sent as newlines.  This messes
 * up login.php */

$config = array();

$sfiab_roles = array(	'student' => array('name' => 'Student'),
//			'teacher' => array(),
			'judge' => array('name' => 'Judge'),
			'committee' => array('name' => 'Committee'),
			'volunteer' => array('name' => 'Volunteer')
		);

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
	$year = -1;
	$q = $mysqli->prepare("SELECT var,val FROM config WHERE `year` = ?");
	$q->bind_param('i', $year); /* Bind year == -1 to load system config */
	$q->execute(); 
	$q->store_result();
	$q->bind_result($db_var,$db_val); 
	while($q->fetch()) {
		$config[$db_var] = $db_val;
	}

	$q->bind_param('i', $config['year']); /* Now bind with the newly loaded fair year */
	$q->execute(); 
	$q->store_result();
	$q->bind_result($db_var,$db_val); 
	while($q->fetch()) {
		$config[$db_var] = $db_val;
	}

/*
	if(array_key_exists('HTTPS', $_SERVER)) {
		$proto = 'https://';
	else
		$proto = 'http://';
*/	
	$config['fair_url'] = $config['fair_host'].$config['document_root'];

	$config['provincestate'] = 'Province';
	$config['postalzip'] = 'Postal Code';
}

function sfiab_log($mysqli, $type, $data, $uid=-1)
{
	$ip = $_SERVER['REMOTE_ADDR'];
	if ($uid == -1 && sfiab_session_is_active()) {
		if(array_key_exists('uid', $_SESSION)) {
			$uid = $_SESSION['uid'];
		}
	}
	$type = $mysqli->real_escape_string($type);
	$data = $mysqli->real_escape_string($data);

	$mysqli->query("INSERT INTO log (`ip`,`uid`,`time`,`type`,`data`) VALUES('$ip',$uid,NOW(),'$type','$data')");
}

function sfiab_session_start($mysqli = NULL, $roles = array()) 
{
        $session_name = 'sfiab';
        $secure = false; 
        $httponly = true; /* Don't let javascript get the session id */
 
        ini_set('session.use_only_cookies', 1); 
        $cookieParams = session_get_cookie_params(); 
        session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httponly); 
        session_name($session_name);
        session_start(); 
        session_regenerate_id();

	if($mysqli != NULL) {
		sfiab_check_access($mysqli, $roles);
	}
}

function sfiab_logged_in()
{
	if(!isset($_SESSION['unique_uid'], $_SESSION['username'], $_SESSION['session_hash'])) 
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

function sfiab_check_access($mysqli, $roles = array(), $skip_expiry_check = false) 
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
	$unique_id = $_SESSION['unique_uid'];
	$session_hash = $_SESSION['session_hash'];
	$username = $_SESSION['username'];
	$user_browser = $_SERVER['HTTP_USER_AGENT']; 
 
	$q = $mysqli->prepare("SELECT roles,password FROM users WHERE uid = ? LIMIT 1");
	$q->bind_param('i', $uid);
	$q->execute(); 
	$q->store_result();
	if($q->num_rows != 1) {
		print("Access Denied<br/>");
		exit();
	}
	$q->bind_result($db_roles, $db_password); // get variables from result.
	$q->fetch();

	$hash_check = hash('sha512', $db_password.$user_browser);
	if($hash_check != $session_hash) {
//		print("Access Denied2<br/>");
//		print_r($_SESSION);
//		print("But computed hash $hash_check<br/>");

//		exit();
	}

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
function sfiab_print_left_nav_menu_entries($current_page_id, $menu)
{
	foreach($menu as $id=>$d) {

		if($d === NULL) continue;
		$sel = ($current_page_id == $id) ? 'data-theme="a"' : ''; 
		$e = 'data-rel="external" data-ajax="false"';

		$theme = ($id == 'c_editing') ? 'data-theme="l"' : '';

		$count = sfiab_left_nav_incomplete_count($id);
		$style = ($count == 0) ? 'style="display: none;"' : '';
		$incomplete = "<span $style class=\"ui-li-count\">$count</span>";
?>
		<li data-icon="false" <?=$sel?> <?=$theme?> >
			<a id="left_nav_<?=$id?>" href="<?=$d[1]?>" <?=$e?> data-transition="fade" data-inline="true" >
				<?=$d[0]?><?=$incomplete?>
			</a>
		</li>
<?php	} 
}

function sfiab_print_left_nav_menu($menu_id, $text, $current_page_id, $menu)
{
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
		<h3><?=$text?><?=$incomplete?></span></h3>
		<ul data-role="listview" class="jqm-list ui-alt-icon ui-nodisc-icon" data-inset="false">
			<?=sfiab_print_left_nav_menu_entries($current_page_id, $menu);?>
		</ul>
	</div>
<?php
}

function sfiab_print_left_nav($menu, $current_page_id="")
{
	global $config;
	$main_menu = array(
			'welcome' => array('Welcome', 'index.php#welcome'),
			'important_dates' => array('Important Dates', 'index.php#important_dates'),
			'winners' => array('Winners', 'main_winners.php'),
			'committee' => array('Committee', 'index.php#committee'),
			'contact' => array('Contact Us', 'index.php#contact'),
		);

	$student_menu = array('s_home' => array('Student Home', 'student_main.php'),
			      's_personal' => array('Personal Info', 'student_personal.php'),
			      's_emergency' => array('Emergency Contact', 'student_emergency.php'),
			      's_reg_options' => array('Options', 'student_reg_options.php'),
			      's_tours' => array('Tours', 'student_tours.php'),
			      's_partner' => array('Partner', 'student_partner.php'),
			      's_project' => array('Project Info', 'student_project.php'),
			      's_ethics' => array('Project Ethics', 'student_ethics.php'),
			      's_safety' => array('Project Safety', 'student_safety.php'),
			      's_mentor' => array('Mentor Info', 'student_mentor.php'),
			      's_awards' => array('Award Selection', 'student_awards.php'),
			      's_signature' => array('Signature Form', 'student_signature.php'),
			      );

	$judge_menu = array('j_home' => array('Judge Home', 'judge_main.php'),
			    'j_personal' => array('Personal Info', 'judge_personal.php'),
			    'j_options' => array('Judging Options', 'judge_options.php'),
			    'j_expertise' => array('Judging Expertise', 'judge_expertise.php'),
			    'j_mentorship' => array('Mentorship', 'judge_mentorship.php'),
			    );

	$volunteer_menu = array('v_home' => array('Volunteer Home', 'v_main.php'),
				'v_personal' => array('Personal Info', 'v_personal.php'),
				'v_options' => array('Options', 'v_options.php'),
				'v_tours' => array('Tours', 'v_tours.php'),
		);

	$committee_menu = array('c_main' => array('Committee Home', 'c_main.php'),
			    'c_awards' => array('Awards', 'c_awards.php'),
			    'c_judging' => array('Judging', 'c_judging.php'),
			    'c_reports' => array('Reports', 'c_reports.php'),
			    'c_communication' => array('Send Emails', 'c_communication.php'),
			    'c_students' => array('Students / Projects', 'c_students.php'),
			    'c_tours' => array('Tours', 'c_tours.php'),
			    'c_volunteers' => array('Volunteers', 'c_volunteers.php'),
			    'c_user_list' => NULL,
			    'c_user_edit' => NULL,
			    'c_report_editor' => NULL,
			    'c_timeslots' => NULL,
			    'c_jteam_edit' => NULL,
			    'c_jteam_list' => NULL,
			    'c_input_signature_forms' => NULL,
			    'c_communication_send' => NULL,
			    'c_communication_queue' => NULL,
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
		<?=sfiab_print_left_nav_menu_entries($current_page_id, $user_edit_menu, true);?>
		</ul>
<?php
		$roles = $_SESSION['edit_roles'];
	} else  {
		sfiab_print_left_nav_menu('leftnav_main', 'Main Menu', $current_page_id, $main_menu);
		if(!sfiab_logged_in()) 
			$roles = array();
		else 
			$roles = $_SESSION['roles'];
	}

	if(in_array('student', $roles)) 
		sfiab_print_left_nav_menu('leftnav_student', 'Student Menu', $current_page_id, $student_menu);

	if(in_array('judge', $roles)) 
		sfiab_print_left_nav_menu('leftnav_judge', 'Judge Menu', $current_page_id, $judge_menu);

	if(in_array('committee', $roles)) 
		sfiab_print_left_nav_menu('leftnav_committee', 'Committee Menu', $current_page_id, $committee_menu);

	if(in_array('volunteer', $roles)) 
		sfiab_print_left_nav_menu('leftnav_committee', 'Volunteer Menu', $current_page_id, $volunteer_menu);

	if($editing_another_user == 0) {

		if(sfiab_logged_in())
			sfiab_print_left_nav_menu('leftnav_account', 'Account Menu', $current_page_id, $account_menu);
?>
		<ul data-role="listview" data-inset="true" class="jqm-list ui-alt-icon ui-nodisc-icon">
<?php
		if(sfiab_logged_in()) {
			sfiab_print_left_nav_menu_entries($current_page_id, $logout_menu, true);
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
			sfiab_print_left_nav_menu_entries($current_page_id, $login_menu, true);
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
			<h3><?=$config['fair_name']?></h3>
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
  <link rel="stylesheet" href="scripts/jquery.mobile-1.4.2.min.css" />
  <script src="scripts/jquery-1.9.1.min.js"></script>
<?php /* This allows the same page to be reloaded by default... it must be done before jquerymobile is loaded */ ?>
  <script>
    $(document).on("mobileinit", function(){ 
//    	$.mobile.changePage.defaults.allowSamePageTransition = true;
//	$.mobile.page.prototype.options.domCache = false;
//    	$.mobile.changePage.defaults.reloadPage = true; 
	});
  </script>
  <script src="scripts/jquery.mobile-1.4.2.min.js"></script>
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

function sfiab_page_begin($title, $page_id="", $help="")
{
	output_start($title);
	sfiab_print_header($page_id);
	sfiab_print_left_nav($title, $page_id);
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

function challenges_load($mysqli, $year = false) {
	global $config;
	if($year == false) $year = $config['year'];
	$chals = array();
	$q = $mysqli->query("SELECT * FROM challenges WHERE year='$year'");
	while($c=$q->fetch_assoc()) $chals[$c['id']] = $c;
	return $chals;
}

function categories_load($mysqli, $year = false) {
	global $config;
	if($year == false) $year = $config['year'];
	$cats = array();
	$q = $mysqli->query("SELECT * FROM categories WHERE year='$year'");
	while($c=$q->fetch_assoc()) $cats[$c['id']] = $c;
	return $cats;
}

function cms_get($mysqli, $name) {
	$q = $mysqli->query("SELECT `text`,`use` FROM `cms` WHERE `name`='$name'");
	print($mysqli->error);
	$r = $q->fetch_assoc();
	if($r['use'] == 1) {
		return $r['text'];
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
		if($u['tshirt'] != 'none') $n_tshirts++;

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

	if(1) { /* Tshirts enabled */
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


/* Is registration open for this user? reads the customer user override first
 * then uses the global date */
function sfiab_registration_is_closed($u, $role=NULL)
{
	global $config;
	$reg_close_date = '';

	$now = date( 'Y-m-d H:i:s' );

	if($role !== NULL) {
		if($role == 'student') {
			$reg_close_date = $config['date_student_registration_closes'];
		} else if ($role == 'judge') {
			$reg_close_date = $config['date_judge_registration_closes'];
		} else {
			return false;
		}
	} else {
		/* Registration is never closed for a committee editting a user */
		if(sfiab_session_is_active()) {
			if(array_key_exists('edit_uid', $_SESSION)) {
				return false;
			}
		}

		/* Get the normal reg close date, if the student's submission
		 * has been accepted, disregard any reg close override and just
		 * return that their reg is closed */
		if(in_array('student', $u['roles'])) {
			$reg_close_date = $config['date_student_registration_closes'];

			/* Accetped students are cannot make changes */
			if($u['s_accepted']) {
				return true;
			}

		} else if (in_array('judge', $u['roles'])) {
			$reg_close_date = $config['date_judge_registration_closes'];

		} else {
			return false;
		}

		/* If there is an override, use that date instead */
		if($u['reg_close_override'] !== NULL) {
			$reg_close_date = $u['reg_close_override'];
		} 
	}

	if($now < $reg_close_date)
		return false;
	else 
		return true;
}



/* It's kinda important that there be no blank lines AFTER this, or they're sent as newlines.  This messes
 * up login.php */
?>
