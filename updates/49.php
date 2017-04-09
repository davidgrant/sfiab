<?php

$report_options = array();

function pre_49($mysqli)
{
	global $report_options;
	/* Load all report options */
	$q = $mysqli->query("SELECT * FROM reports_items WHERE `type`='option'");
	while(($r = $q->fetch_assoc())) {
		if(!array_key_exists($r['report_id'], $report_options)) {
			$report_options[$r['report_id']] = array();
		}
		$report_options[$r['report_id']][$r['field']] = $r['value'];
	}
	print_r($report_options);
}

function post_49($mysqli)
{
	global $report_options;
	/* Translate the options into the report table fields */
	foreach($report_options as $report_id=>$opts) {
		foreach($opts as $var=>$val) {
			/* yes/no become 1/0 */
			if($val == 'no') $val = '0';
			if($val == 'yes') $val = '1';
			switch($var) {
			case 'type':
				/* var type is now report['format'] */
				$var = 'format';
				break;
			case 'group_new_page':
				/* yes becomes '9', assumign there will never be more than 9 grouping fields */
				if($val == '1') $val = '9';
				break;
			}
			$var = $mysqli->real_escape_string($var);
			$val = $mysqli->real_escape_string($val);
			$mysqli->real_query("UPDATE reports SET `$var`='$val' WHERE id='$report_id'");
		}
	}
}


?>
