<?php
require_once('common.inc.php');
require_once('project.inc.php');
require_once('user.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);


$cats = categories_load($mysqli);
$projects = projects_load_all($mysqli, true);


foreach($cats as $cid=>$c) {
?>
	<h3><?=$cats[$cid]['name']?></h3>

	<table class="table-style style-colorheader" style="width: 100%;" border="0">
	<thead>
		<tr><th class="highlight">Number</th><th>Title</th></tr>
	</thead>
	<tbody>

<?php	$cl = 'odd';
	foreach($projects as $pid=>&$p) {

		if($p['cat_id'] != $cid) continue;

		project_load_students($mysqli, $p);

		$ok = true;
		foreach($p['students'] as $sid=>&$s) {
			if($s['s_accepted'] == 0) $ok = false;
		}
		if(!$ok) continue;

		if($cl == 'odd') $cl = 'even';
		else $cl = 'odd';

		$pn = $p['number'];
		if($pn == '') continue;

?>
		<tr class="<?=$cl?>">
			<td><a href="<?=$config['fair_url']?>/project_summary.php?pn=<?=$pn?>"><?=$pn?></a></td>
			<td><?=$p['title']?></td>
		</tr>
<?php
	} ?>
	</tbody>
	</table>	
<?php
}
?>
