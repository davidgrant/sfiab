<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('email.inc.php');
require_once('schools.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$roles = array();
$years = array();
$status = array();
$attending = array();
$filter_collapsed = "true";

foreach($_GET as $k=>$v) {
	switch($k) {
	case 'roles':
		if(!is_array($_GET['roles'])) exit();
		foreach($_GET['roles'] as $r) {
			if(!array_key_exists($r, $sfiab_roles)) exit();
			$roles[] = $r;
		}
		$_SESSION['edit_return'] = $roles;
		break;

	case 'years':
		if(!is_array($_GET['years'])) exit();
		foreach($_GET['years'] as $y) {
			$year = (int)$y;
			if($year > 0 && $year < 9999) {
				$years[] = $year;
			} else if($year == -1) {
				$years = array(-1);
				break;
			}
		}
		break;

	case 'status':
		if(!is_array($_GET['status'])) exit();
		foreach($_GET['status'] as $s) {
			if(in_array($s, array('complete', 'active','new'))) {
				$status[] = $s;
			}
		}
		break;

	case 'attending':
		if(!is_array($_GET['attending'])) exit();
		foreach($_GET['attending'] as $s) {
			if(in_array($s, array('attending', 'not_attending'))) {
				$attending[] = $s;
			}
		}
		break;

	case 'show_filter':
		$filter_collapsed = "false";
		break;


	case 'edit':
		$uid = (int)$v;
		$new_u = user_load($mysqli, $uid);

		/* Create a project */
		if($new_u['s_pid'] === NULL && in_array('student', $new_u['roles'])) {
			$new_u['s_pid'] = project_create($mysqli);
			user_save($mysqli, $new_u);
		}
		
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

if(count($years) == 0) {
	$years[] = $config['year'];
}

if(count($status) == 0) {
	$status[] = 'complete';
	$status[] = 'active';
}

if(count($attending) == 0) {
	$attending = array('attending', 'not_attending');
}


$page_id = 'c_user_list';

sfiab_page_begin("User List", $page_id);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<div data-role="collapsible" data-collapsed="<?=$filter_collapsed?>" data-iconpos="right" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" >
		<h3>User List Options</h3>
<?php
		$form_id = $page_id.'_form';
		$roles_sel = array();
		foreach($sfiab_roles as $type=>$data) {
			$roles_sel[$type] = $data['name'];
		}

		form_begin($form_id, "c_user_list.php", false, false, "get");
		form_check_group($form_id, 'roles', "Show Roles", $roles_sel, $roles);
		form_hidden($form_id, "show_filter", "1");

		/* Find the full range of years */
		$q = $mysqli->query("SELECT DISTINCT(`year`) FROM `users` ORDER BY `year` DESC");
		$years_sel = array();
		while($r = $q->fetch_row()) {
			$y = (int)$r[0];
			$years_sel[$y] = $y;
		}
		$years_sel["-1"] = "Latest for each user";
		form_check_group($form_id, 'years', "Show Years", $years_sel, $years);

		$status_sel = array('complete' => 'Complete', 'active' => 'Active', 'new' => 'New');
		form_check_group($form_id, 'status', "Show Status", $status_sel, $status);

		$attending_sel = array('attending' => "Attending", 'not_attending'=>'Not Attending');
		form_check_group($form_id, 'attending', "Show Attending", $attending_sel, $attending);


		form_button($form_id, 'filter', 'Apply Filters');
		form_end($form_id);
?>
		
	</div>

<?php
//	<ul data-role="listview" data-filter="true" data-filter-placeholder="Search..." data-inset="true">

$hide_columns = array();
$hide_columns['pid'] = true;
$hide_columns['school'] = true;
$hide_columns['project_number'] = true;

$q_roles_array = array();
foreach($roles as $r) {
	$q_roles_array[] = "FIND_IN_SET('$r',`roles`)>0";
	if($r == 'student') {
		$hide_columns['school'] = false;
	}
		
}
$q_roles = "( ".join(' OR ', $q_roles_array)." )";

if(count($status) == 0) {
	/* Can't happen */
	$q_status = '1';
} else {
	$a = array();
	if(in_array('complete', $status)) $a[] = "(`enabled`='1' AND (`j_complete`='1' OR `s_complete`='1' OR `v_complete`='1' OR FIND_IN_SET('committee',`roles`)>0 ) )";
	if(in_array('accepted', $status)) $a[] = "(`enabled`='1' AND `s_accepted`='1')";
	if(in_array('active', $status)) $a[] = "`enabled`='1'";
	if(in_array('new', $status))	$a[] = "(`new`='1' AND `enabled`='1')";
	$q_status = "(".join(' OR ', $a).")";
}
$c = count($attending);
if($c == 0 || $c == 2) {
	/* Nothing (can't happen) or both selected, return all attending status */
	$q_attending = '1';
} else {
	$q_attending = in_array('attending', $attending) ? "`attending`='1'" : "`attending`='0'";
}

if(count($years) == 0 || $years[0] == -1) {
	/* This returns all rows that match the inner query, so if there's a deleted an non-deleted user in the max_year, 
	 * this returns a single line, and the INNER JOIN creates two lines.  The enabled=1 filters it back down to one */
	$q_join = "INNER JOIN (
		    SELECT max(year) max_year, username
		    FROM users
		    GROUP BY username
		) u2
		ON `u`.username = `u2`.username
		AND `u`.year = `u2`.max_year";
	$q_year = "1";
} else {
	/* Not trying to find the max year for each user, just filter directly by year */
	$q_year = "year IN ('".join("','", $years)."')";
	$q_join = '';
}

$query = "SELECT * FROM users u 
			$q_join
		WHERE
			$q_year
			AND $q_roles
			AND $q_status
			AND $q_attending
			";
$q = $mysqli->query($query);
//print($query);
print($mysqli->error);

$schools = school_load_all($mysqli);
$projects = projects_load_all($mysqli, false);

$users = array();
while($user_data = $q->fetch_assoc()) {
	$users[] = user_load($mysqli, -1, -1, NULL, $user_data);
}


?>

<?php /*Use overflow x scroll in a containing div to make the whole table scroll <div style="overflow-x: scroll;"> */ ?>


<table style="width: 100%">
<tr>
	<td width="100%"> <input id="user_list_filter_input" data-type="search" /> </td>
	<td id="user_list_column_toggle" style="padding-top: 4px;" ></td>
</tr></table>

<table data-role="table" id="user_list" data-filter="true" data-filter-placeholder="Search..." 
		data-input="#user_list_filter_input"
		data-inset="true" data-mode="columntoggle" class="ui-responsive table-stroke"
		data-column-btn-text="Columns..." >

<thead><tr>
	<th >Name / Email</th>
	<th data-priority="1">Username</th>
	<th data-priority="1">School</th>
	<th data-priority="2">Reg ID</th>
	<th data-priority="2">Proj Num</th>
	<th data-priority="1">Role / Status</th>
	<th data-priority="1">Year</th>
	<th ></th>
</tr></thead>
<tbody>
<?php
foreach($users as &$user) {

	$roles_str = implode(' ', $user['roles']);
	$filter_text = "{$user['name']} {$user['organization']} $roles_str {$user['email']}";

	$status = '';
	$role = '';
	foreach($user['roles'] as $r) {
		if($role != '') $role .= ', ';
		$role .= $sfiab_roles[$r]['name'];

		if($status != '') $status .= ', ';
		if($user['attending']) {
			switch($r) {
			case 'judge': $complete = $user['j_complete']; break;
			case 'student': $complete = $user['s_complete']; break;
			case 'volunteer': $complete = $user['v_complete']; break;
			default: $complete = NULL;
			}

			if($complete === NULL) {
				; // Nothing.
			} else if($complete == true) {
				$status .= '<font color="green">Complete</font>';
			} else {
				$status .= '<font color="orange">Incomplete</font>';
			}
		}
	}
	if($user['new'] == 1) {
		$status = '<font color="blue">New</font>';
	} else if($user['enabled'] == 0) {
		$status = '<font color="red">Deleted</font>';
	} else if(!$user['attending']) {
		$status = '<font color="blue">Not Attending</font>';
	}

	$link = "c_user_list.php?edit={$user['uid']}";

	$org = '';
	if(in_array('sponsor', $user['roles'])) {
		$org = $user['organization'].' - ';
	}

	$school_str = $user['schools_id'] > 0 ? $schools[$user['schools_id']]['school'] : '';

	$project_number = $user['s_pid'] > 0 ? $projects[$user['s_pid']]['number'] : '';

?>
	<tr>
	<td style="word-break: break-all; min-width:20%;"><b><?=$org.$user['name']?></b><br/><?=$user['email']?></td>
	<td align="center" style="word-break: break-all; min-width:20%; " ><?=$user['username']?></td>
	<td align="center" style="min-width:15%; " ><?=$school_str?></td>
	<td><?=$user['s_pid']?></td>
	<td><?=$project_number?></td>
	<td><?=$role?><br/><?=$status?></td>
	<td><?=$user['year']?></td>
	<td><div data-role="controlgroup" data-type="horizontal"  data-mini="true">
		<a href="c_user_edit.php?uid=<?=$user['uid']?>" data-mini="true"  data-role="button" data-iconpos="notext" data-icon="gear" data-ajax="false">Edit</a>
		<a href="<?=$link?>" data-mini="true" data-role="button" data-iconpos="notext" data-icon="user" data-ajax="false">Edit</a>
	</div></td>
	</tr>
<?php	
}
?>

</tbody></table>

<br/><br/>

<script>
/* Move the columns toggle button into the table beside the search bar */
$(document).on("pagecreate", function () {
		$(".ui-table-columntoggle-btn").appendTo($("#user_list_column_toggle"));
		/* Hide some columns by default */
<?php		foreach($hide_columns as $col=>$val) {
			if($val == false) continue;
			switch($col) {
			case 'pid': $i = 2; break;
			case 'school': $i = 1; break;
			case 'project_number': $i = 3; break;
			}
?>			$("#user_list-popup .ui-checkbox label")[<?=$i?>].click();
<?php		} ?>
	});
</script>


<?php
sfiab_page_end();
?>
