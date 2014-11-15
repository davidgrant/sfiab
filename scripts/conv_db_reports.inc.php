<?php

require_once('reports.inc.php');


function old_report_load($mysqli, $old_prefix, $report_id)
{
	$report = array();

	$q = $mysqli->query("SELECT * FROM {$old_prefix}reports WHERE id='$report_id'");
	$r = $q->fetch_assoc();
	$report['name'] = $r['name'];
	$report['id'] = $r['id'];
	$report['system_report_id'] = $r['system_report_id']; 
	$report['desc'] = $r['desc'];
	$report['creator'] = $r['creator'];
	$report['type'] = $r['type'];

	$report['col'] = array();
	$report['sort'] = array();
	$report['group'] = array();
	$report['distinct'] = array();
	$report['option'] = array();
	$report['filter'] = array();
	$report['loc'] = array();

 	$q = $mysqli->query("SELECT * FROM {$old_prefix}reports_items 
			WHERE reports_id='{$report['id']}' 
			ORDER BY `ord`");
#	print($q->error);
	
	while($a = $q->fetch_assoc()) {
		$f = $a['field'];
		$t = $a['type'];

		switch($t) {
		case 'option':
			/* We dont' care about order, just construct
			 * ['option'][name] = value; */
//			if(!in_array($f, $allow_options)) {
//				print("Type[$type] Field[$f] not allowed.\n");
//				continue;
//			}
			$report['option'][$f] = $a['value'];
			break;
		default:
//			if(!in_array($f, $allow_fields)) {
//				print("Type[$type] Field[$f] not allowed.\n");
//				continue;
//			}
			/* Pull out all the data */
			$val = array();
			$col_fields = array('field', 'x', 'y', 'w', 'h', 'lines', 'face', 'align', 
						'valign', 'value', 'fontname','fontsize','on_overflow','ord');
			foreach($col_fields as $lf) $val[$lf] = $a[$lf];

			/* Remap some fields */
			if($val['field'] == 'summary') $val['field'] = 'abstract';
			if($val['field'] == 'title_summary') $val['field'] = 'title_tagline_abstract';

			/* Set new fields, clear old ones */
			$val['h_rows'] = NULL;
			$val['fontstyle'] = explode(',', $a['fontstyle']);
			/* valign, fontname, fontsize,fontstyle are unused, except in tcpdf reports 
			(i.e. nothign has changed, only adding on */

			if($val['lines'] == 0) $val['lines'] = 1;
			$opts = explode(" ", $val['align']);
			$align_opts = array ('left', 'right', 'center');
			$valign_opts = array ('vtop', 'vbottom', 'vcenter');
			$style_opts = array ('bold');
			foreach($opts as $o) {
				if(in_array($o, $align_opts)) $val['align'] = $o;
				if(in_array($o, $valign_opts)) $val['valign'] = $o;
				if(in_array($o, $valign_opts)) $val['face'] = $o;
			}

//			print("[$t][{$val['ord']}]\n");
			$report[$t][$val['ord']] = $val;
			break;
		}
	}
//rint_r($report);
	return $report;
}

function old_report_load_all($mysqli, $old_prefix) 
{
	print("   - Loading old reports...");
	$ret = array();
	$q = $mysqli->query("SELECT id FROM {$old_prefix}reports");
	while($r = $q->fetch_assoc()) {
		$report = old_report_load($mysqli, $old_prefix, $r['id']);
		$ret[] = $report;
		print("      - {$report['name']}\n");
	}
	return $ret; 
}


function conv_reports($mysqli, $old_prefix)
{
	$c = 0;
	print("Converting reports\n");
	$reports = old_report_load_all($mysqli, $old_prefix);

	printf("   - Deleting all reports in new database...\n");
	$mysqli->real_query("DELETE FROM reports");
	$mysqli->real_query("DELETE FROM reports_items");


	foreach($reports as &$r) {

		$r['use_abs_coords'] = 0;

		foreach($r['col'] as &$i) {
			$i['h_rows'] = (int)$i['lines'];

			if($i['face'] == 'bold' and !in_array('bold', $i['fontstyle'])) 
				$i['fontstyle'][] = 'bold';

		}

		$r['section'] = 'Uncategorized';
		$p = strpos($r['name'], '--');
		if($p > 0) {
			$r['section'] = trim(substr($r['name'], 0, $p));
			$r['name'] = trim(substr($r['name'], $p+2));
		}

		/* Need to probably remap fields that have been renamed, but I have no idea which ones they are */


//		print("   [{$r['section']}] {$r['name']}\n");

		/* Create a report id so we can save an existing report, instead of making a new one with 
		 * a new id */
		$mysqli->query("INSERT INTO reports (`id`) VALUES ('{$r['id']}')");
		report_save($mysqli, $r);

		/* The report_save doesn't touch the system_report_id, so save that now too */
		if($r['system_report_id'] > 0) {
			$mysqli->query("UPDATE reports SET system_report_id='{$r['system_report_id']}' WHERE `id`='{$r['id']}'");
		}
		$c++;
	}
	printf("   - Converted $c reports.\n");
}

?>
