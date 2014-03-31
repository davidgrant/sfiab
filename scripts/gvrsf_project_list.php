<?php
require_once('common.inc.php');
require_once('project.inc.php');
require_once('user.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);



if(array_key_exists('SERVER_ADDR', $_SERVER)) {
	echo "This script must be run from the command line";
	exit;
}

$cats = categories_load($mysqli);
$projects = projects_load_all($mysqli);


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

		$students = project_load_students($mysqli, $p);

		$ok = true;
		foreach($students as &$s) {
			if($s['s_accepted'] == 0) $ok = false;
		}
		if(!$ok) continue;

		if($cl == 'odd') $cl = 'even';
		else $cl = 'odd';

		$pn = $p['number'];
		if($pn == '') continue;

?>
		<tr class="<?=$cl?>">
			<td><a href="https://secure.youthscience.ca/sfiab/gvrsf/project_summary.php?pn=<?=$pn?>"><?=$pn?></a></td>
			<td><?=$p['title']?></td>
		</tr>
<?php
	} ?>
	</tbody>
	</table>	
<?php
}
?>
