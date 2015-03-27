<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('email.inc.php');
require_once('sanity.inc.php');
require_once('committee/students.inc.php');

$mysqli = sfiab_init('committee');

$page_id = "c_main";

$u = user_load($mysqli);

$help = '
<ul><li><b>nothing</b> - no help yet.
</ul>';

sfiab_page_begin("Committee Main", 'c_main', $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<h3>Hello <?=$u['firstname']?></h3>

<?php
	$pending_actions = array();

	$new_users = find_users_needing_registration_email($mysqli);
	if(count($new_users) > 0) {
		$pending_actions['c_register_feeder.php'] = "<font color=red>".count($new_users)."</font> students from feeder fairs have not been sent a registration email, click here to send them";
	} 

	$users = students_load_all($mysqli);
	$num_accepted = 0;
	$students_accepted_without_tour = sanity_get_accepted_students_without_tour($mysqli, $users, $num_accepted);
	$students_not_accepted_with_tour = sanity_get_not_accepted_students_with_tour($mysqli, $users);

	if(count($students_not_accepted_with_tour) > 0 || count($students_accepted_without_tour) > 0) {
		$str = '';
		if(count($students_not_accepted_with_tour) > 0) {
			$str .= "<font color=red>".count($students_not_accepted_with_tour)."</font> not accepted students are assigned to a tour.";
		}
		if(count($students_accepted_without_tour) > 0) {
			$str .= "<font color=red>".count($students_accepted_without_tour)."</font> / $num_accepted accepted students have not been assigned a tour";
		}
		$pending_actions['c_check_tours.php'] = $str;
	}

	if(count($pending_actions) > 0) { ?>
		<h3>Pending Actions</h3>
		<ul data-role="listview" data-inset="true">
<?php		foreach($pending_actions as $link=>$text) { ?>
			<li><a href="<?=$link?>" data-rel="external" data-ajax="false"><?=$text?></a></li>
<?php		} ?>
		</ul>
<?php	} ?>



	<h3>Sanity Checks</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_judge_sanity.php" data-rel="external" data-ajax="false">Display Judging Sanity Checks</a></li>
	<li><a href="c_check_tours.php" data-rel="external" data-ajax="false">Display Tour Sanity Checks</a></li>
	</ul>

	<h3>Committee Members</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_user_list.php?roles[]=committee&years[]=-1" data-rel="external" data-ajax="false">Committee List / Editor</a></li>
	<li><a href="index.php#register" data-rel="external" data-ajax="false">Invite a Committee Member (or Judge or Student)</a></li>
	</ul>

</div></div>

<?php
sfiab_page_end();
?>
