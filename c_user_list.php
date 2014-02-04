<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);
sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);

$roles = array();

foreach($_GET as $k=>$v) {
	switch($k) {
	case 'roles':
		$g_roles = explode(',', $v);
		foreach($g_roles as $r) {
			if(!array_key_exists($r, $sfiab_roles)) exit();
			$roles[] = $r;
		}
		break;
	case 'edit':
		$uid = (int)$v;
		$new_u = user_load($mysqli, $uid);
		$_SESSION['edit_uid'] = $uid;
		$_SESSION['edit_roles'] = $new_u['roles'];
		$_SESSION['edit_name'] = $new_u['name'];
		header("Location: v_main.php");
		exit();

	case 'return':
		unset($_SESSION['edit_uid']);
		unset($_SESSION['edit_roles']);
		unset($_SESSION['edit_name']);
		break;
	}
}

if(count($roles) == 0) {
	$roles = array('committee');
}


function l_users_load_all($mysqli, $year, $roles)
{
	$q_roles = '';
	foreach($roles as $r) 
		$q_roles .= " AND FIND_IN_SET('$r',`roles`)>0 ";

	$q = $mysqli->query("SELECT * FROM users WHERE
				year='$year'
				$q_roles
				");
	$users = array();
	while($j = $q->fetch_assoc()) {
		$users[] = user_load($mysqli, -1, -1, NULL, $j);
	}
	return $users;
}


$page_id = 'c_user_list';

sfiab_page_begin("User List", $page_id);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 


	<div data-role="collapsible" data-collapsed="true" data-iconpos="right" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" >
		<h3>User List Options</h3>
		Work in progress, eventually be able to select which roles to see, only complete/incomplete, year, etc.
	</div>


	<ul data-role="listview" data-filter="true" data-filter-placeholder="Search..." data-inset="true">

<?php


$users = l_users_load_all($mysqli, $config['year'], $roles);

foreach($users as &$v) {

	$roles_str = implode(' ', $v['roles']);
	$filter_text = "{$v['name']} {$v['organization']} $roles_str {$v['email']}";

	$status = '';
	foreach($v['roles'] as $r) {
		if($status != '') $status .= ', ';
		$status .= $sfiab_roles[$r]['name'];
		if(!$v['not_attending']) {
			switch($r) {
			case 'judge': $complete = $v['j_complete']; break;
			case 'student': $complete = $v['s_complete']; break;
			case 'volunteer': $complete = $v['v_complete']; break;
			default: $complete = NULL;
			}

			if($complete === NULL) {
				; // Nothing.
			} else if($complete == true) {
				$status .= ' (<font color="green">Complete</font>)';
			} else {
				$status .= ' (<font color="red">Incomplete</font>)';
			}
		}
	}
	if($v['not_attending']) {
		$status .= ' - <font color="blue">Not Attending</font>';
	}

	$link = "c_user_list.php?edit={$v['uid']}";

?>
	<li data-filtertext="<?=$filter_text?>"><a href="<?=$link?>" data-external="true" data-ajax="false">
		<h3><?=$v['name']?></h3><span class="ui-li-aside"><?=$status?></span>
		<?=$v['email']?>
	</a></li>
<?php
}
?>
</ul>



<?php
sfiab_page_end();
?>
