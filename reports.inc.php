<?php
/* 
   This file is part of the 'Science Fair In A Box' project
   SFIAB Website: http://www.sfiab.ca

   Copyright (C) 2005 Sci-Tech Ontario Inc <info@scitechontario.org>
   Copyright (C) 2005 James Grant <james@lightbox.org>

   This program is free software; you can redistribute it and/or
   modify it under the terms of the GNU General Public
   License as published by the Free Software Foundation, version 2.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program; see the file COPYING.  If not, write to
   the Free Software Foundation, Inc., 59 Temple Place - Suite 330,
   Boston, MA 02111-1307, USA.
*/

require_once("reports_students.inc.php");  /* $report_students_fields */
// require_once("reports_judges.inc.php");  /* $report_students_fields */
// require_once("reports_awards.inc.php");  /* $report_students_fields */
// require_once("reports_committees.inc.php");  /* $report_students_fields */
// require_once("reports_volunteers.inc.php"); /* $report_volunteers_fields */
// require_once("reports_schools.inc.php");
// require_once("reports_tours.inc.php");
// require_once("reports_fairs.inc.php");
// require_once("reports_fundraising.inc.php");

# require_once('../lcsv.php');
 require_once('tcpdf.inc.php');

 $report_filter_ops = array(	0 => '=',
 			1 => '<=',
			2 => '>=',
			3 => '<',
			4 => '>',
			5 => '!=',
			6 => 'IS',
			7 => 'IS NOT',
			8 => 'LIKE',
			9 => 'NOT LIKE ',
		);

 $report_font_styles = array('bold','italic','underline','strikethrough');
 
 $report_options = array();
 $report_options['type'] = array('desc' => 'Report Format',
 				'values' => array('pdf'=>'PDF', 'csv'=>'CSV', 'label'=>'Label'),
				'default' => 'pdf'
		);
 $report_options['group_new_page'] = array('desc' => 'Start each new grouping on a new page',
 					'values' => array('no'=>'No', 'yes'=>'Yes'),
					'default' => 'no'
		);
 $report_options['allow_multiline'] = array('desc' => 'Allow table rows to span multiple lines',
 					'values' => array('no'=>'No', 'yes'=>'Yes'),
					'default' => 'no'
		);
 $report_options['fit_columns'] = array('desc' => 'Scale column widths to fit on the page width',
 					'values' => array('no'=>'No', 'yes'=>'Yes'),
					'default' => 'no'
		);
 $report_options['label_box'] = array('desc' => 'Draw a box around each label',
 					'values' => array('no'=>'No', 'yes'=>'Yes'),
					'default' => 'no'
		);
 $report_options['field_box'] = array('desc' => 'Draw a box around each text field on the label',
 					'values' => array('no'=>'No', 'yes'=>'Yes'),
					'default' => 'no'
		);
 $report_options['label_fairname'] = array('desc' => 'Print the fair name at the top of each label',
 					'values' => array('no'=>'No', 'yes'=>'Yes'),
					'default' => 'yes'
		);
 $report_options['label_logo'] = array('desc' => 'Print the fair logo at the top of each label',
 					'values' => array('no'=>'No', 'yes'=>'Yes'),
					'default' => 'yes'
		);
 $report_options['default_font_size'] = array('desc' => 'Default font size to use in the report',
 					'values' => array(
					'10'=>'10', 
					'11'=>'11', '12'=>'12', 
					'13'=>'13', '14'=>'14', '15'=>'15', '16'=>'16', '18'=>'18',
					'20'=>'20', '22'=>'22', '24'=>'24'),
					'default' => '11'
		);
$report_options['include_registrations'] = array('desc' => 'Include data from complete registrations',
 					'values' => array('all'=>'All registered users, regardless of status', 
							'almost'=>'(Students only) that are accepted or complete (pending sig form, but not accepted)', 
							'complete'=>'Only accepted registrations'),
					'default' => 'complete'
		);
//$report_options['total'] = array('desc' => 'Sum the value of table items at te bottom of each table',
 //					'values' => array('no'=>'No', 'yes'=>'Yes'),
//					'default' => 'no'
//		);

  

/*
Viceroy		Grand	Avery	rows?	w x h"		per page
		& Toy	
LRP 130		99180	5960	3	2 5/8 x 1	30
LRP 120		99189	5961	2	4 x 1		20
LRP 114		99179	5959	7	4 x 1 1/2	14
LRP 214		99190	5962	7	4 x 1 1/3	14
LRP 110		99181	5963	5	4 x 2		10
LRP 106		99763	5964	3	4 x 3 1/3	6
LRP 100		99764	5965	1	8 1/2 x 11	1
LRP 180		99765	5967	4	1 3/4 x 1/2 	80 */


/* FIXME: put these in a databse */
 $report_stock = array();
 $report_stock['fullpage'] = array('name' => 'Letter 8.5 x 11 (3/4" margin)',
			'page_width' => 8.5,
			'page_height' => 11,
			'label_width' => 7,
			'x_spacing' => 0,
			'cols' => 1,
			'label_height' => 9.5,
			'y_spacing' => 0,
			'rows' => 1,
			'page_format' => 'LETTER',
			'page_orientation' => 'P',
			);

 $report_stock['fullpage_landscape'] = array('name' => 'Letter 8.5 x 11 Landscape (3/4" margin)',
			'page_width' => 11,
			'page_height' => 8.5,
			'label_width' => 9.5,
			'x_spacing' => 0,
			'cols' => 1,
			'label_height' => 7,
			'y_spacing' => 0,
			'rows' => 1,
			'page_format' => 'LETTER',
			'page_orientation' => 'L',
			);

 $report_stock['fullpage_full'] = array('name' => 'Letter 8.5 x 11 (no margin)',
			'page_width' => 8.5,
			'page_height' => 11,
			'label_width' => 8.5,
			'x_spacing' => 0,
			'cols' => 1,
			'label_height' => 11,
			'y_spacing' => 0,
			'rows' => 1,
			'page_format' => 'LETTER',
			'page_orientation' => 'P',
			);

 $report_stock['fullpage_landscape_full'] = array('name' => 'Letter 8.5 x 11 Landscape (no margin)',
			'page_width' => 11,
			'page_height' => 8.5,
			'label_width' => 11,
			'x_spacing' => 0,
			'cols' => 1,
			'label_height' => 8.5,
			'y_spacing' => 0,
			'rows' => 1,
			'page_format' => 'LETTER',
			'page_orientation' => 'L',
			);

 $report_stock['5161'] = array('name' => 'Avery 5161/5261/5961/8161, G&T 99189 (1"x4")',
			'page_width' => 8.5,
			'page_height' => 11,
			'label_width' => 4,
			'x_spacing' => 0.15,
			'cols' => 2,
			'label_height' => 1,
			'y_spacing' => 0.00,
			'y_padding' => 0.05,
			'rows' => 10,
			'page_format' => 'LETTER',
			'page_orientation' => 'P',
			);
	
 $report_stock['5162'] = array('name' => 'Avery 5162/5262/5962/8162/8462, G&T 99190 (1 1/3"x4")',
			'page_width' => 8.5,
			'page_height' => 11,
			'label_width' => 3.99,
			'x_spacing' => 0.187,
			'cols' => 2,
			'label_height' => 1.326,
			'y_spacing' => 0.00,
			'y_padding' => 0.30,
			'rows' => 7,
			'page_format' => 'LETTER',
			'page_orientation' => 'P',
			);
 $report_stock['5163'] = array('name' => 'Avery 5163/5263/5963/8163/8463, G&T 99181 (2"x4")',
			'page_width' => 8.5,
			'page_height' => 11,
			'label_width' => 4,
			'x_spacing' => 0.1719,
			'cols' => 2,
			'label_height' => 2,
			'y_spacing' => 0.00,
			'rows' => 5,
			'page_format' => 'LETTER',
			'page_orientation' => 'P',
			);

/* This is combined with 5161
 $report_stock['5961'] = array('name' => 'Avery 5961, G&T 99189 (1"x4")',
			'page_width' => 8.5,
			'page_height' => 11,
			'label_width' => 4,
			'x_spacing' => 0.08,
			'cols' => 2,
			'label_height' => 1,
			'y_spacing' => 0.08,
			'rows' => 10,
			);
*/		

 $report_stock['5164'] = array('name' => 'Avery 5164/5264/5964/8164, G&T 99763 (4"x3 1/3")',
			'page_width' => 8.5,
			'page_height' => 11,
			'label_width' => 4,
			'x_spacing' => 3/16,
			'cols' => 2,
			'label_height' => 3 + 1/3,
			'y_spacing' => 0,
			'rows' => 3,
			'page_format' => 'LETTER',	/* tcpdf format */
			'page_orientation' => 'P',	/* tcpdf orientation */

			);
 $report_stock['nametag'] = array('name' => 'Cards 4"x3"',
			'page_width' => 8.5,
			'page_height' => 11,
			'label_width' => 4,
			'x_spacing' => 0,
			'cols' => 2,
			'label_height' => 3,
			'y_spacing' => 0,
			'rows' => 3,
			'page_format' => 'LETTER',
			'page_orientation' => 'P',
			);

 $report_stock['letter_4up'] = array('name' => 'Fullpage, 4up',
			'page_width' => 8.5,
			'page_height' => 11,
			'label_width' => 4,
			'x_spacing' => 0.25,
			'cols' => 2,
			'label_height' => 5,
			'y_spacing' => 0.25,
			'rows' => 2,
			'page_format' => 'LETTER',
			'page_orientation' => 'P',
			);

 $report_stock['ledger'] = array('name' => 'Ledger/Tabloid 11 x 17',
			'page_width' => 11,
			'page_height' => 17,
			'label_width' => 11,
			'x_spacing' => 0,
			'cols' => 1,
			'label_height' => 17,
			'y_spacing' => 0,
			'rows' => 1,
			'page_format' => 'LETTER',
			'page_orientation' => 'P',
			);
	
 $report_stock['ledger_landscape'] = array('name' => 'Ledger/Tabloid 11 x 17 Landscape',
			'page_width' => 17,
			'page_height' => 11,
			'label_width' => 17,
			'x_spacing' => 0,
			'cols' => 1,
			'label_height' => 11,
			'y_spacing' => 0,
			'rows' => 1,
			'page_format' => 'LETTER',
			'page_orientation' => 'P',
			);


/* Add stock optiosn to the report options array */
$report_options['stock'] = array('desc' => "Paper Type",
                                'values' => array(),
				'default' => 'fullpage');
foreach($report_stock as $n=>$v) {
	$report_options['stock']['values'][$n] = $v['name'];
}

function report_save_field($mysqli, $report, $type)
{
	global $report_students_fields; /*$report_judges_fields, $report_awards_fields;
	global $report_committees_fields, $report_schools_fields;
	global $report_volunteers_fields, $report_fairs_fields;
	global $report_tours_fields, $report_fundraisings_fields;
*/	

	$fieldvar = "report_{$report['type']}s_fields";
	$allow_fields = array_keys($$fieldvar);

	/* First delete all existing fields */
	$mysqli->real_query("DELETE FROM reports_items 
			WHERE `reports_id`='{$report['id']}'
			AND `type`='$type'");
	/* Now add new ones */

	if(count($report[$type]) == 0) return;
	
	$q = '';
	$x = 0;
	foreach($report[$type] as $k=>$v) {
		
		if($type == 'option') {
			/* field, value, x, y, w, min_w, h, h_rows, align, valign, fn, fs, fsize, overflow */
			$vals = "'$k','$v','0','0','0',NULL,'0','0','','','','','0','truncate'";
		} else {
			$f_field = array_key_exists('field', $v) ? "'".$mysqli->real_escape_string($v['field'])."'" : "''";
			$f_value = array_key_exists('value', $v) ? "'".$mysqli->real_escape_string($v['value'])."'" : "''";
			$f_x = array_key_exists('x', $v) ? "'".((float)$v['x'])."'" : "'0'";
			$f_y = array_key_exists('y', $v) ? "'".((float)$v['y'])."'" : "'0'";
			$f_w = array_key_exists('w', $v) ? "'".((float)$v['w'])."'" : "'0'";
			$f_h = array_key_exists('h', $v) ? "'".((float)$v['h'])."'" : "'0'";
			if(array_key_exists('h_rows', $v)) {
				$f_h_rows = (int)$v['h_rows'];
				if($f_h_rows == 0) $f_h_rows = 1;
			} else {
				$f_h_rows = 1;
			}
			$f_min_w = (array_key_exists('min_w', $v) && $v['min_w'] != NULL) ? "'".((float)$v['min_w'])."'" : "NULL";
			$f_align = array_key_exists('align', $v) ? "'".$mysqli->real_escape_string($v['align'])."'" : "''";
			$f_valign = array_key_exists('valign', $v) ? "'".$mysqli->real_escape_string($v['valign'])."'" : "''";
			$f_fontname = array_key_exists('fontname', $v) ? "'".$mysqli->real_escape_string($v['fontname'])."'" : "''";
			$f_fontsize = array_key_exists('fontsize', $v) ? "'".((float)$v['fontsize'])."'" : "'0'";
			$f_on_overflow = array_key_exists('on_overflow', $v) ? "'".$mysqli->real_escape_string($v['on_overflow'])."'" : "''";
			if(array_key_exists('fontstyle', $v)) {
				$a = array();
				foreach($v['fontstyle'] as $s) {
					$a[] = "'".$mysqli->real_escape_string($s)."'";
				}
				$f_fontstyle = implode(',', $a);
			} else {
				$f_fontstyle = "''";
			}
			$vals = "$f_field, $f_value, $f_x, $f_y, $f_w, $f_min_w, $f_h, $f_h_rows, $f_align, $f_valign, $f_fontname, $f_fontstyle, $f_fontsize, $f_on_overflow";
		}
		if($q != '') $q .= ',';
		$q .= "({$report['id']},'$type','$x',$vals)";
		$x++;
	}
	
	$mysqli->real_query("INSERT INTO reports_items(`report_id`,`type`,`ord`,
				`field`,`value`,`x`, `y`, `w`, `min_w`,`h`,
				`h_rows`,`align`,`valign`,
				`fontname`,`fontstyle`,`fontsize`,`on_overflow`) 
			VALUES $q;");
	print($mysqli->error);
}
	
 function report_load($mysqli, $report_id)
 {
 	global $report_options;
 	global $report_students_fields, $report_judges_fields;
	global $report_committees_fields, $report_awards_fields;
	global $report_schools_fields, $report_volunteers_fields;
	global $report_tours_fields, $report_fairs_fields;

	$report = array();

	$q = $mysqli->query("SELECT * FROM reports WHERE id='$report_id'");
	$report = $q->fetch_assoc();

	$report['col'] = array();
	$report['sort'] = array();
	$report['group'] = array();
	$report['distinct'] = array();
	$report['option'] = array();
	$report['filter'] = array();

	$fieldvar = "report_{$report['type']}s_fields";
/*	if(is_array($$fieldvar)) 
		$allow_fields = array_keys($$fieldvar);
	else
		$allow_fields=array();
*/
 	$q = $mysqli->query("SELECT * FROM reports_items 
			WHERE report_id='{$report['id']}' 
			ORDER BY `ord`");
	print($mysqli->error);
	
	if($q->num_rows == 0) return $report;

	while($a = $q->fetch_assoc()) {
		$f = $a['field'];
		$t = $a['type'];
		switch($t) {
		case 'option':
			/* We dont' care about order, just construct
			 * ['option'][name] = value; */
//			if(!array_key_exists($f, $report_options)) {
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
			$val = $a;
			filter_float_or_null($val['ord']);
			filter_float_or_null($val['x']);
			filter_float_or_null($val['y']);
			filter_float_or_null($val['w']);
			filter_float_or_null($val['h']);
			filter_float_or_null($val['min_w']);
			filter_float_or_null($val['h_rows']);
			filter_float_or_null($val['fontsize']);
			if($val['fontstyle'] == '')
				$val['fontstyle'] = array();
			else
				$val['fontstyle'] = explode(',', $val['fontstyle']);


			/* Check sanity of options just because we can */
			$align_opts = array ('left', 'right', 'center', 'full');
			$valign_opts = array ('vtop', 'vbottom', 'vcenter');
			$style_opts = array ('bold');
			if(!in_array($val['align'], $align_opts)) $val['align'] = 'left';
			if(!in_array($val['valign'], $valign_opts)) $val['valign'] = 'top';
			foreach($val['fontstyle'] as $s) {
				if(!in_array($s, $style_opts)) {
					print("Unknown style '$s'");
					exit();
				}
			}
			

			/* Save this column in the right order spot */
			$report[$t][$val['ord']] = $val;
			break;
		}
	}

	foreach($report_options as $o=>$d) {
		if(!array_key_exists($o, $report['option'])) {
			$report['option'][$o] = $d['default'];
		}
	}

	unset($report['original']);
	$original = $report;
	$report['original'] = $original;

	return $report;
 }

 function report_save($mysqli, $report)
 {
 	if($report['id'] == 0) {
		/* New report */
		$mysqli->query("INSERT INTO reports (`id`) VALUES ('')");
		$report['id'] = $mysqli->insert_id;
	} else {
		/* if the report['id'] is not zero, see if this is a
		 * system report before doing anything. */
		$q = $mysqli->query("SELECT system_report_id FROM reports WHERE id='{$report['id']}'");
		$i = $q->fetch_assoc();
		if(intval($i['system_report_id']) != 0) {
			/* This is a system report, the editor (should)
			 * properly setup the editor pages so that the user
			 * cannot save this report.  The only way to get here
			 * is by directly modifying the POST variables.. so..
			 * we don't have to worry about being user friendly. */
			echo "ERROR: attempt to save a system report (reports.id={$report['id']})";
			exit;
		}
	}


/*	print("<pre>");
	print_r($_POST);
	print_r($report)_t
	print("</pre>");
*/

 	$mysqli->real_query("UPDATE reports SET 
			`name`='".$mysqli->real_escape_string($report['name'])."',
			`section`='".$mysqli->real_escape_string($report['section'])."',
			`desc`='".$mysqli->real_escape_string($report['desc'])."',
			`creator`='".$mysqli->real_escape_string($report['creator'])."',
			`type`='".$mysqli->real_escape_string($report['type'])."'
			WHERE `id`={$report['id']}");
	print($mysqli->error);

	report_save_field($mysqli, $report, 'col');
	report_save_field($mysqli, $report, 'group');
	report_save_field($mysqli, $report, 'sort');
	report_save_field($mysqli, $report, 'distinct');
	report_save_field($mysqli, $report, 'option');
	report_save_field($mysqli, $report, 'filter');
	return $report['id'];
 }

 function report_load_all($mysqli) 
 {
 	$ret = array();
 	$q = $mysqli->query("SELECT * FROM reports ORDER BY `section`,`name`");

	while($r = $q->fetch_assoc()) {
		$report = array();
	        $report['name'] = $r['name'];
	        $report['section'] = $r['section'];
	        $report['id'] = $r['id'];
	        $report['desc'] = $r['desc'];
	        $report['creator'] = $r['creator'];
	        $report['type'] = $r['type'];
		$ret[] = $report;
	}
	return $ret; 
 }

 function report_delete($mysqli, $report_id)
 {
 	$r = intval($report_id);
	/* if the report['id'] is not zero, see if this is a
	 * systeim report before doing anything. */
	$q = mysql_query("SELECT system_report_id FROM reports WHERE id='$r'");
	$i = mysql_fetch_assoc($q);
	if(intval($i['system_report_id']) != 0) {
		/* This is a system report, the editor (should)
		 * properly setup the editor pages so that the user
		 * cannot delete this report.  The only way to get here
		 * is by directly modifying the POST variables.. so..
		 * we don't have to worry about being user friendly. */
		echo "ERROR: attempt to delete a system report (reports.id=$r)";
		exit;
	}
 	mysql_query("DELETE FROM reports WHERE `id`=$r");
	mysql_query("DELETE FROM reports_items WHERE `reports_id`=$r");
 }

 function report_gen($mysqli, $report) 
 {
 	global $config, $report_students_fields, $report_judges_fields, $report_awards_fields, $report_schools_fields;
	global $report_stock, $report_committees_fields, $report_volunteers_fields;
	global $report_tours_fields, $report_fairs_fields;
	global $report_fundraisings_fields;
	global $filter_ops;

//	print("<pre>");
//	print_r($report);

	$fieldvar = "report_{$report['type']}s_fields";
	$fields = $$fieldvar;

	$gen_mode = '';
	$fieldname = array();

	$table['header']=array();
	$table['col']=array();
	$table['widths']=array();
	$table['fields'] = array();
	$table['data'] = array();
	$table['total']=0;

	/* Validate the stock */
	if($report['option']['stock'] != '') {
		if(!array_key_exists($report['option']['stock'], $report_stock)) {
			print("Invalid stock [{$report['option']['stock']}]");
			exit();
		}
	}

	$show_box = ($report['option']['label_box'] == 'yes') ? true : false;
	$show_fair = ($report['option']['label_fairname'] == 'yes') ? true : false;
	$show_logo = ($report['option']['label_logo'] == 'yes') ? true : false;

	switch($report['option']['type']) {
	case 'csv':
		$rep=new lcsv(i18n($report['name']));
		$gen_mode = 'table';
		break;

	case 'pdf': case '':
		/* FIXME: handle landscape pages in here */
		$label_stock = $report_stock[$report['option']['stock']];
		$rep=new pdf("{$report['section']} -- {$report['name']}", $report['year'], $label_stock['page_format'], $label_stock['page_orientation']);
		$rep->setup_for_tables($show_box, $show_fair, $show_logo, 
				$label_stock['label_width'] * 25.4, $label_stock['label_height'] * 25.4,
				$label_stock['x_spacing'] * 25.4, $label_stock['y_spacing'] * 25.4,
				$label_stock['rows'], $label_stock['cols']);
		$gen_mode = 'label';
/*
		if($report['option']['default_font_size']) {
			$rep->setDefaultFontSize($report['option']['default_font_size']);
			$rep->setFontSize($report['option']['default_font_size']);
		}
		else {
			$rep->setDefaultFontSize(11);
			$rep->setFontSize(11);
		}
*/
		$gen_mode = 'table';
		break;

	case 'label':
		$label_stock = $report_stock[$report['option']['stock']];
		$show_box = ($report['option']['label_box'] == 'yes') ? true : false;
		$show_fair = ($report['option']['label_fairname'] == 'yes') ? true : false;
		$show_logo = ($report['option']['label_logo'] == 'yes') ? true : false;

		$rep=new pdf("{$report['section']} -- {$report['name']}", $report['year'],$label_stock['page_format'], $label_stock['page_orientation']);
		$rep->setup_for_labels($show_box, $show_fair, $show_logo, 
				$label_stock['label_width'] * 25.4, $label_stock['label_height'] * 25.4,
				$label_stock['x_spacing'] * 25.4, $label_stock['y_spacing'] * 25.4,
				$label_stock['rows'], $label_stock['cols']);
		$gen_mode = 'label';
		break;

	default:
		echo "Invalid type [{$report['option']['type']}]";
		exit;
	}
	
	$sel = array();
	$x=0;
	$group_by = array();
	$post_group_by = array();
	$components = array();
	$order = array();

	$total_width = 0;
	$scale_width = 0;
	/* Add up the column widths, and figure out which
	 * ones are scalable, just in case */
	foreach($report['col'] as $o=>$d) {
		$f = $d['field'];
		$total_width += $fields[$f]['width'];

		$scalable = array_key_exists('scalable', $fields[$f]) ? $fields[$f]['scalable'] : false;
		if($scalable) 
			$scale_width += $fields[$f]['width'];
	}

	/* Determine the scale factor (use the label width so
	 * we can enforce margins) */
	if($report['option']['fit_columns'] == 'yes') { // && $total_width > $label_stock['label_width'])  {
		$static_width = $total_width - $scale_width;
        if($scale_width) 
            $scale_factor = ($label_stock['label_width'] - $static_width) / $scale_width;
        else
            $scale_factor = 1.0;
	} else {
		$scale_factor = 1.0;
	}

	/* Select columns to display */
	foreach($report['col'] as $o=>$d) {
		$f = $d['field'];

		$table['fields'][] = $f;
		$table['col'][$f] = $d;

		$scalable = array_key_exists('scalable', $fields[$f]) ? $fields[$f]['scalable'] : false;
		$sf = ($scalable) ? $scale_factor : 1.0;

		/* Scale width and convert to mm */
		$table['widths'][$f] = $fields[$f]['width'] * $sf * 25.4;
		$table['header'][$f] = $fields[$f]['header'];

		$sel[] = "{$fields[$f]['table']} AS C$x";
		$fieldname[$f] = "C$x";
		/* We want to add these to group by, but AFTER all the other group bys */
		if(array_key_exists('group_by', $fields[$f]))
			$post_group_by = array_merge($group_by, $fields[$f]['group_by']);

		if(array_key_exists('components', $fields[$f])) {
			$components = array_merge($components, $fields[$f]['components']);
		}
		$x++;
	}

	/* We also want to select any column groupings, but we won't display them */
	foreach($report['group'] as $o=>$d) {
		$f = $d['field'];
		if(!isset($fieldname[$f])) {
			$sel[] = "{$fields[$f]['table']} AS G$o";
			$fieldname[$f] = "G$o";
		}

		if(isset($fields[$f]['table_sort']))
			$order[] = $fields[$f]['table_sort'];
		else
			$order[] = $fieldname[$f];

		if(array_key_exists('components', $fields[$f])) { 
			$components = array_merge($components, 
					$fields[$f]['components']);
		}
	}

	/* If no sort order is specified, make the first field the order */
	if(count($report['sort']) == 0) 
		$report['sort'] = array(0 => array('field' => $report['col'][0]['field']));

	foreach($report['sort'] as $o=>$d) {
		$f = $d['field'];
		if(!isset($fieldname[$f])) {
			$sel[] = "{$fields[$f]['table']} AS S$o";
			$fieldname[$f] = "S$o";
		}

		if(isset($fields[$f]['table_sort']))
			$order[] = $fields[$f]['table_sort'];
		else
			$order[] = $fieldname[$f];
	}
	
	foreach($report['distinct'] as $o=>$d) {
		$f = $d['field'];
		if(!isset($fieldname[$f])) {
			$sel[] =  "{$fields[$f]['table']} AS D$o";
			$fieldname[$f] = "D$o";
		}
		$group_by[] = $fieldname[$f];
	}

	$filter = array();
	foreach($report['filter'] as $o=>$d) {
		$f = $d['field'];
		if(!isset($fieldname[$f])) {
			$sel[] =  "{$fields[$f]['table']} AS F$o";
			$fieldname[$f] = "F$o";
		}
		$t = $filter_ops[$d['x']];
		$filter[] = "{$fields[$f]['table']} $t '{$d['value']}'";
		if(array_key_exists('components', $fields[$f])) { 
			$components = array_merge($components, 
					$fields[$f]['components']);
		}
	}

	$sel = implode(",", $sel);
	$order = implode(",", $order);
		
	
	if(!array_key_exists('year', $report)) {
		$report['year'] = $config['year'];
	}
	
	$group_by = array_merge($group_by, $post_group_by);
	$group_query = "";
	if(count($group_by)) {
		$group_query = "GROUP BY ".implode(",", $group_by);
	}

	$filter_query = "";
	if(count($filter)) {
		$filter_query = " AND ".implode(" AND ", $filter);
	}
	
	$func = "report_{$report['type']}s_fromwhere";
	$q = call_user_func_array($func, array($report, $components));

	$q = "SELECT $sel  $q  $filter_query $group_query ORDER BY $order";
	$r = $mysqli->query($q);

//	print("<pre>");
//	print_r($report);
//	print_r($report['filter']);
//<F2>	echo "$q";

	if($r == false) {
		echo "The report database query has failed.  This is
		unfortunate but not your fault.  Please send the following to
		your fair administrator, or visit <a
		href=\"http://www.sfiab.ca\">http://www.sfiab.ca</a> and submit
		a bug report so we can get this fixed.<br />"; 
		echo "<pre>";
		echo "Query: [$q]<br />";
		echo "Error: [".$mysqli->error."]<br />";
		echo "</pre>";
		exit;
	}
	echo $mysqli->error;

	$ncols = count($report['col']);
	$n_groups = count($report['group']);
	$last_group_data = array();

//	echo "<pre>";print_r($rep);

	while($i = $r->fetch_assoc()) {

//		echo "<pre>"; print_r($i);

		if($n_groups > 0) {
			$group_change = false;
			/* See if any of the "group" fields have changed */
			foreach($report['group'] as $x=>$g) {
				$c = $fieldname[$g['field']];

				if(array_key_exists('exce_function', $fields[$g['field']]))
					$i_c=call_user_func_array($fields[$g['field']]['exec_function'], array($mysqli,$report,$f,$i[$c]));
				else
					$i_c=$i[$c];

				if(!array_key_exists($c, $last_group_data) || $last_group_data[$c] != $i_c)
					$group_change = true;

				$last_group_data[$c] = $i_c;
			}

			if($group_change) {
				/* Dump the last table */
				if(count($table['data'])) {
				//	print_r($table);
					$rep->add_table($table);
					$table['data'] = array();
					$table['total'] = 0;
					/* Start a new page AFTER a table is
					* dumped, so the first page doesn't
					* end up blank */
					if($report['option']['group_new_page'] == 'yes') {
//						$rep->br();
					} else {
						$rep->hr();
						$rep->vspace(-0.1);
					}
				}
				
				/* Construct a new header */
				$h = implode(" -- ", $last_group_data);
				$rep->heading($h);
			}
			
		}

		$data = array();

		if($gen_mode == 'label') {
			$rep->label_new();
		}

		foreach($report['col'] as $o=>$d) {
			$f = $d['field'];

			/* Get the final value through a value map, funciton, or directly
			 * from the SQL query */
			if(array_key_exists('value_map', $fields[$f])) {
				if(array_key_exists($i["C$o"], $fields[$f]['value_map']))
					$v = $fields[$f]['value_map'][$i["C$o"]];
				else
					$v = 'n/a';
			} else if(array_key_exists('exec_function', $fields[$f])) {
				$v = call_user_func_array($fields[$f]['exec_function'], array($mysqli, &$report, $f, $i["C$o"]));
//			} else if(isset($fields[$f]['exec_code'])) {
//				Somethign like this, how do we pass $i["C$o"] in?
//				$v = exec($fields[$f]['exec_code']);
			} else {
				$v =  $i["C$o"];
			}

			/* Before reformatting (i.e., while the value might still be a number), 
			 * add to total */
			if(array_key_exists('total', $fields[$f])  && $fields[$f]['total'] == true) {
				if(array_key_exists('format', $fields[$f])) 
					$table['total_format'] = $fields[$f]['format'];
				$table['total'] += $v;
			}

			/* Reformat if requested after we have the value */
			if(array_key_exists('format', $fields[$f])) {
				$v = sprintf($fields[$f]['format'], $v);
			}

			/* Format as apporpriate for the report type */
			if($gen_mode == 'table') {
				$data[$f] = $v;
			} else if($gen_mode == 'label') {
				/* Setup additional options */
				$show_box = ($report['option']['field_box'] == 'yes') ? true : false;

//				echo "<pre>"; print_r($d);

				switch($f) {
				case 'static_box':
					$rep->label_rect($d['x'], $d['y'], $d['w'], $d['h']);
					break;
				case 'fair_logo':
					$rep->label_fair_logo($d['x'], $d['y'], $d['w'], $d['h'], $show_box);
					break;
				case "projectbarcode":
					$style = array(
					'border' => 2,
					'vpadding' => 'auto',
					'hpadding' => 'auto',
					'fgcolor' => array(0,0,0),
					'bgcolor' => false, //array(255,255,255)
					'module_width' => 2, // width of a single module in points
					'module_height' => 2 // height of a single module in points
					);
					$rep->label_barcode($v, 'QRCODE,H', $d['x'], $d['y'], $d['w'], $d['h'], $style, 'N');
					break;
					
				default:
					if($f == 'static_text') 
						$v = $d['value'];

					$v = iconv("ISO-8859-1","UTF-8",$v);

					$rep->label_text($d['x'], $d['y'], $d['w'], $d['h'],
							$v, $show_box, $d['align'], $d['valign'],
							$d['fontname'],$d['fontstyle'],$d['fontsize'],
							$d['on_overflow']);

					break;
				}
			}

		}
		if(count($data)) $table['data'][] = $data;
	}
	if(count($table['data'])) {
		$rep->add_table($table);
	}


	$rep->output();
}

?>
