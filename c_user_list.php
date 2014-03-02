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

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}
switch($action) {
case 'purge':
	$uid = (int)$_POST['id'];
	if($uid > 0) {
		$u = user_load($mysqli, $uid);
		if(in_array('student', $u['roles'])) {
			/* If only one student in project, delete project too */
			$q_in_project = $mysqli->query("SELECT uid FROM users WHERE `s_pid`='{$u['s_pid']}'");
			if($q_in_project->num_rows == 1) {
				$mysqli->real_query("DELETE FROM projects WHERE pid='{$u['s_pid']}'");
				$mysqli->real_query("DELETE FROM mentors WHERE pid='{$u['s_pid']}'");
			}
		}
		/* Do this for all users, doesn't matter if it's a student or not */
		$mysqli->real_query("DELETE FROM partner_requests WHERE to_uid='$uid' OR from_uid='$uid'");
		/* Purge the user */
		$mysqli->real_query("DELETE FROM users WHERE uid='$uid'");
		form_ajax_response(0);
		exit();
	}
	form_ajax_response(1);
	exit();

case 'del':
	$uid = (int)$_POST['id'];
	if($uid > 0) {
		$this_u = user_load($mysqli, $uid);
		$this_u['state'] = 'deleted';
		user_save($mysqli, $this_u);
		form_ajax_response(0);
		exit();
	}
	form_ajax_response(1);
	exit();
}


foreach($_GET as $k=>$v) {
	switch($k) {
	case 'roles':
		$g_roles = explode(',', $v);
		foreach($g_roles as $r) {
			if(!array_key_exists($r, $sfiab_roles)) exit();
			$roles[] = $r;
		}
		$_SESSION['edit_return'] = $roles;
		break;
	case 'edit':
		$uid = (int)$v;
		$new_u = user_load($mysqli, $uid);
		$_SESSION['edit_uid'] = $uid;
		$_SESSION['edit_roles'] = $new_u['roles'];
		$_SESSION['edit_name'] = $new_u['name'];
		header("Location: ".user_homepage($new_u));
		exit();

	case 'return':
		unset($_SESSION['edit_uid']);
		unset($_SESSION['edit_roles']);
		unset($_SESSION['edit_name']);
		$roles = $_SESSION['edit_return'];
		break;
	}
}

if(count($roles) == 0) {
	$roles = array('committee');
	$_SESSION['edit_return'] = $roles;
	
}


function l_users_load_all($mysqli, $year, $roles)
{
	$q_roles = '';
	foreach($roles as $r) 
		$q_roles .= " AND FIND_IN_SET('$r',`roles`)>0 ";

	$q = $mysqli->query("SELECT * FROM users WHERE
				year='$year'
				AND state != 'deleted'
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
	<li id="user_list_<?=$v['uid']?>" data-filtertext="<?=$filter_text?>"><a href="#" class="user_list_item" onclick="user_list_info_toggle(<?=$v['uid']?>)" >
		<h3><?=$v['name']?></h3><span class="ui-li-aside"><?=$status?></span>
		<?=$v['email']?>
		</a>
		<a href="<?=$link?>" data-external="true" data-ajax="false" data-icon="gear" >Edit</a>
		 <div id="user_list_info_<?=$v['uid']?>" class="user_list_info" style='display:none'>
			<div class="ui-grid-a" data-role="fieldcontain">
				<div class="ui-block-a" style="width:80%">
					<table>
					<tr><td>Username:</td><td><?=$v['username']?></td></tr>
					</table>
				</div>
				<div class="ui-block-b" style="width:20%;padding-bottom: 5px">
					<div data-role="controlgroup" data-type="vertical">
					 	<a href="<?=$link?>" data-role="button" data-theme="l" >Edit</a>
					 	<a href="#" data-role="button" data-theme="r" onclick="return user_list_info_delete(<?=$v['uid']?>);" >Delete</a>
					 	<a href="#" data-role="button" data-theme="r" onclick="return user_list_info_purge(<?=$v['uid']?>);" >Purge</a>
					</div>
				</div>
			</div>

		</div>
	</li>
<?php
}
/*
	$( document ).on( "pagecreate", function( event ) {
		$('.user_list_item').click(function(event) {
			var a_e = $(event.target);
			var div_e = a_e.siblings('div.user_list_info');
			div_e.toggle();
			return false;
		});
	});
*/

?>
</ul>

<script>
	function user_list_info_toggle(id) {
		$('#user_list_info_'+id).toggle();
		return false;
	}
	function user_list_info_delete(id) {
		if(confirm('Really delete this user?') == false) return false;
		$.post('c_user_list.php', { action: "del", id: id }, function(data) {
			if(data.status == 0) {
				$("#user_list_"+id).hide();
			}
		}, "json");
		return false;
	}
	function user_list_info_purge(id) {
		if(confirm('Really purge this user?\nPurging user deletes all record of them, their project, their juding info, everything.') == false) return false;
		$.post('c_user_list.php', { action: "purge", id: id }, function(data) {
			if(data.status == 0) {
				$("#user_list_"+id).hide();
			}
		}, "json");
		return false;
	}
	
</script>

			


<?php
sfiab_page_end();
?>
