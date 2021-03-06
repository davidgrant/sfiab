<?php

function conv_categories($mysqli, $old_prefix, $year)
{
	print("Convert Categories for $year...\n");

	/* Delete existing */
	$mysqli->query("DELETE FROM categories WHERE year='$year' )");

	$q = $mysqli->query("SELECT * FROM {$old_prefix}projectcategories WHERE year='$year'");
	while($c = $q->fetch_assoc()) {
		$mysqli->query("INSERT INTO categories(`id`,`name`,`shortform`,`min_grade`,`max_grade`,`year`)
				VALUES('{$c['id']}','{$c['category']}','{$c['category_shortform']}',
				'{$c['mingrade']}','{$c['maxgrade']}','$year')");
	}

	print("Convert Divisions (into Challenges) for $year...\n");
	$mysqli->query("DELETE FROM challenges WHERE year='$year' )");
	/* Convert challenges or divisions */
	$q = $mysqli->query("SELECT * FROM {$old_prefix}projectdivisions WHERE year='$year'");
	while($c = $q->fetch_assoc()) {
		$mysqli->query("INSERT INTO challenges (`id`,`name`,`shortform`,`cwsfchallengeid`,`year`) VALUES (
				'{$c['id']}',
				'{$c['division']}','{$c['division_shortform']}',
				'{$c['cwsfdivisionid']}','$year')");
	}	

}

?>
