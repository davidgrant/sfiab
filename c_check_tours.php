<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('committee/students.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('awards.inc.php');
require_once('fairs.inc.php');
require_once('sanity.inc.php');

$mysqli = sfiab_init('committee');


$page_id = 'c_check_tours';

$help = '
<ul>
</ul>';

sfiab_page_begin($u, "Sanity Check Tours", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>" class="sfiab_page" > 

<?php

$users = students_load_all($mysqli);

$num_accepted = 0;
$students_accepted_without_tour = sanity_get_accepted_students_without_tour($mysqli, $users, $num_accepted);
$students_not_accepted_with_tour = sanity_get_not_accepted_students_with_tour($mysqli, $users);

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

$send_list = array();

switch($action) {
case 'send_all':

	break;
}

$tours = tour_load_all($mysqli);

form_page_begin($page_id, array());
?>
<h3>Sanity Check Tours</h3>

<p><b><?=count($students_not_accepted_with_tour)?></b> not accepted have been assigned to a tour.
<p><b><?=count($students_accepted_without_tour)?></b> / <b><?=$num_accepted?></b> accepted students do not have a tour.

<h4>Not Accepted Students with a Tour</h4>
<table data-role="table" data-mode="none">
<thead><tr><th>Student</th><th>Assigned Tour</th><th></th></tr></thead>
<tbody>
<?php


foreach($students_not_accepted_with_tour as &$s) {
?>	<tr><td><?=$s['name']?></td>
	<td>#<?=$tours[$s['tour_id']]['num']?> - <?=$tours[$s['tour_id']]['name']?><td>
	<td><a href="c_user_edit.php?uid=<?=$s['uid']?>" data-mini="true"  data-role="button" data-iconpos="notext" data-icon="gear" data-ajax="false">
	</tr>
<?php
}

?>
</tbody>
</table>

<h4>Accepted Students without a Tour</h4>

<p><b><?=count($students_accepted_without_tour)?></b> / <b><?=$num_accepted?></b> accepted students do not have a tour.

<table data-role="table" data-mode="none">
<thead><tr><th>Student</th><th>Tour Choices</th><th>Tour Capacity</th><th></th></tr></thead>
<tbody>

<?php

/* Get tour capacities */
foreach($tours as $tid=>&$t) {
	$t['count'] = 0;
}

foreach($users as $uid=>&$s) {
	if($s['tour_id'] > 0) {
		$tours[$s['tour_id']]['count'] += 1;
	}
}


foreach($students_accepted_without_tour as &$s) {
?>	<tr><td><?=$s['name']?></td>
	<td>
<?php
	$x = 1;
	foreach($s['tour_id_pref'] as $tour_id_pref) { ?>
		<?=$x?>: #<?=$tours[$tour_id_pref]['num']?> - <?=$tours[$tour_id_pref]['name']?><br/>
		
<?php		$x++;
	} ?>
	</td>
	<td>
<?php
	foreach($s['tour_id_pref'] as $tour_id_pref) { ?>
		<b><?=$tours[$tour_id_pref]['count']?></b> / <?=$tours[$tour_id_pref]['capacity_max']?><br/>
<?php		
	} ?>
	</td>
	

	<td><a href="c_user_edit.php?uid=<?=$s['uid']?>" data-mini="true"  data-role="button" data-iconpos="notext" data-icon="gear" data-ajax="false">
	</td></tr>
<?php
}

?>
</tbody>
</table>


</div>

<?php
sfiab_page_end();
?>
