<?php
function pre_11($mysqli)
{
	global $config;
	$mysqli->real_query("UPDATE config SET year='{$config['year']}' WHERE var='timezone'");
	$mysqli->real_query("UPDATE config SET year='{$config['year']}' WHERE var='fair_name'");
	$mysqli->real_query("UPDATE config SET year='{$config['year']}' WHERE var='fair_abbreviation'");
	$mysqli->real_query("UPDATE config SET category='system' WHERE var='judging_rounds'");
}
