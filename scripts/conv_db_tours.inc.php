<?php


$tours_map = array();
function conv_tours($mysqli, $mysqli_old, $year)
{
	global $tours_map;
	print("Convert Tours for $year...\n");

	/* Delete existing */
	$mysqli->query("DELETE FROM tours WHERE year='$year' )");

	$q=$mysqli_old->query("SELECT * FROM tours WHERE year='$year'");
	while($r=$q->fetch_assoc()) {

		$tid = tour_create($mysqli, $year);
		$t = tour_load($mysqli, $tid);

		$t['name'] = $mysqli->real_escape_string($r['name']);
		$t['num'] = (int)$r['num'];
		$t['description'] = $mysqli->real_escape_string($r['description']);
		$t['capacity_min'] = (int)$r['capacity_min'];
		$t['capacity_max'] = (int)$r['capacity'];
		$t['grade_min'] = (int)$r['grade_min'];
		$t['grade_max'] = (int)$r['grade_max'];
		$t['contact'] = $mysqli->real_escape_string($r['contact']);
		$t['location'] = $mysqli->real_escape_string($r['location']);

		tour_save($mysqli, $t);
		$tours_map[(int)$r['id']] = $tid;
	}
}

?>
