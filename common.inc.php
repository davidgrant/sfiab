<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

require_once('config.inc.php');

/* It's kinda important that there be no blank lines BEFORE this, or they're sent as newlines.  This messes
 * up login.php */

$config = array();

$sfiab_roles = array(	'student' => array(),
			'teacher' => array(),
			'judge' => array(),
			'committee' => array(),
			'volunteer' => array()
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
		print("Access Denied1<br/>");
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
			print("Access Denied3<br/>");
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
{ ?>	
<?php 	foreach($menu as $id=>$d) {
			$sel = ($current_page_id == $id) ? 'data-theme="a"' : ''; 
			$e = 'data-ajax="false"';//'rel="external"';

			$count = sfiab_left_nav_incomplete_count($id);
			$style = ($count == 0) ? 'style="display: none;"' : '';
			$incomplete = "<span $style class=\"ui-li-count\">$count</span>";
?>
			<li data-icon="false" <?=$sel?>><a id="left_nav_<?=$id?>" href="<?=$d[1]?>" <?=$e?> data-transition="fade" data-inline="true" ><?=$d[0]?><?=$incomplete?></a></li>
<?php	} 
}

function sfiab_print_left_nav_menu($menu_id, $text, $current_page_id, $menu)
{
	/* Count all incomplete */
	$count = 0;
	foreach($menu as $id=>$d) {
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
			      's_reg_options' => array('Registration Options', 'student_reg_options.php'),
			      's_partner' => array('Partner', 'student_partner.php'),
			      's_project' => array('Project Info', 'student_project.php'),
			      's_ethics' => array('Project Ethics', 'student_ethics.php'),
			      's_safety' => array('Project Safety', 'student_safety.php'),
			      's_mentor' => array('Mentor Info', 'student_mentor.php'),
			      's_awards' => array('Awards', 'student_awards.php'),
			      's_signature' => array('Signature Form', 'student_signature.php'),
			      );

	$judge_menu = array('j_home' => array('Judge Home', 'judge_main.php'),
			    'j_personal' => array('Personal Info', 'judge_personal.php'),
			    'j_options' => array('Judging Options', 'judge_options.php'),
			    'j_expertise' => array('Judging Expertise', 'judge_expertise.php'),
			    'j_mentorship' => array('Mentorship', 'judge_mentorship.php'),
			    );

	$committee_menu = array('c_main' => array('Committee Home', 'c_main.php'),
			    'c_awards' => array('Edit Awards', 'c_awards.php'),
		);

	$login_menu = array('register' => array('Registration', 'index.php#register'),
			    'login' => array('Login', 'index.php#login'),
		);

	$account_menu = array('a_change_password' => array('Change Password', 'a_change_password.php'),
			      'a_delete_account' => array('Delete Account', 'a_delete_account.php'),
		);
	$logout_menu = array( 'logout' => array('Logout', 'login.php?action=logout'),
		);

?>

	<div id="leftnav" data-role="panel" class="leftnav_panel" data-position="left" data-display="overlay" data-theme="a">
	<br/><br/>

<?php
	sfiab_print_left_nav_menu('leftnav_main', 'Main Menu', $current_page_id, $main_menu);
	if(sfiab_user_is_a('student'))
		sfiab_print_left_nav_menu('leftnav_student', 'Student Menu', $current_page_id, $student_menu);

	if(sfiab_user_is_a('judge'))
		sfiab_print_left_nav_menu('leftnav_judge', 'Judge Menu', $current_page_id, $judge_menu);

	if(sfiab_user_is_a('committee')) 
		sfiab_print_left_nav_menu('leftnav_committee', 'Committee Menu', $current_page_id, $committee_menu);

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
?>
	</ul>
	</div>
	<script>
//		.mobile.changePage( ".sfiab_page", { allowSamePageTransition:true } );
	</script>

<?php
}

function sfiab_print_header()
{
	global $config;
?>
	<div id="header" data-theme="a" data-role="header"  >
		<a href="#leftnav" data-icon="bars" data-iconpos="notext" class="leftnav_button ui-nodisc-icon ui-alt-icon">Menu</a>
		<h3><?=$config['fair_name']?></h3>
		<a href="#help_panel" data-icon="info" data-iconpos="notext" class="ui-nodisc-icon ui-alt-icon">Help</a>
	</div>
<?php
}

function sfiab_print_help_panel($text)
{
?>
	<div id="help_panel" data-theme="a" data-role="panel" data-position="right" data-display="overlay">
		<h2>Help</h2>
		<?=$text?>
	</div>
<?php
}

function output_start()
{
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black">
  <title></title>
  <link rel="stylesheet" href="scripts/jquery.mobile-1.4.0.min.css" />
  <script src="scripts/jquery-1.9.1.min.js"></script>
<?php /* This allows the same page to be reloaded by default... it must be done before jquerymobile is loaded */ ?>
  <script>
    $(document).on("mobileinit", function(){ 
//    	$.mobile.changePage.defaults.allowSamePageTransition = true;
//	$.mobile.page.prototype.options.domCache = false;
//    	$.mobile.changePage.defaults.reloadPage = true; 
	});
  </script>
  <script src="scripts/jquery.mobile-1.4.0.min.js"></script>
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
	output_start();
	sfiab_print_header();
	sfiab_print_left_nav($title, $page_id);
	if($help != "") {
		sfiab_print_help_panel($help);
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

/* It's kinda important that there be no blank lines AFTER this, or they're sent as newlines.  This messes
 * up login.php */
?>
