<?php

function sanity_get_accepted_students_without_tour($mysqli, &$students, &$num_accepted)
{
	$ret = array();
	$num_accepted = 0;
	foreach($students as $uid=>&$s) {
		if($s['s_accepted'] != 1) continue;

		$num_accepted += 1;

		if((int)$s['tour_id'] <= 0) {
			$ret[] = &$s;
		}
	}
	return $ret;
}

function sanity_get_not_accepted_students_with_tour($mysqli, &$students)
{
	$ret = array();
	foreach($students as $uid=>&$s) {
		if($s['s_accepted'] != 0) continue;

		if((int)$s['tour_id'] > 0) {
			$ret[] = &$s;
		}
	}
	return $ret;
}

?>
