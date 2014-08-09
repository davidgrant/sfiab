<?php

require_once('fairs.inc.php');

$fairs_map = array();


function conv_fairs($mysqli, $mysqli_old)
{
	global $awards_map, $awards_prizes_map;

	print("Converting Fairs\n");

	$mysqli->real_query("DELETE FROM fairs");

	/* */

	$q = $mysqli_old->query("SELECT * FROM fairs");
	print($mysqli_old->error);
	$c = 0;
	while($old_f = $q->fetch_assoc()) {

		
		$fair_id = fair_create($mysqli);
		$f = fair_load($mysqli, $fair_id);

		$f['id'] = (int)$old_f['id'];
		$f['name'] = $old_f['name'];
		$f['abbrv'] = $old_f['abbrv'];
		$type_map = array('feeder' => 'sfiab_feeder', 'sfiab'=>'sfiab_upstream', 'ysc'=>'ysc');
		$f['type'] = $type_map[$old_f['type']];
		$f['url'] = $old_f['url'];
		$f['website'] = $old_f['website'];
		$f['username'] = $old_f['username'];
		$f['password'] = $old_f['password'];

		fair_save($mysqli, $f);

		$c++;
	}
	print("   Converted $c fairs.\n");
}

?>
