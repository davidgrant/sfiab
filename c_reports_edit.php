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
require_once('common.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('reports.inc.php');

$mysqli = sfiab_init('committee');

/* Define all the report field globals, depends on mysqli and $config */
report_init($mysqli);

$u = user_load($mysqli);

$page_id = 'c_report_editor';
$help = '<p>Edit Reports';

$rid = 0;
if(array_key_exists('rid', $_GET)) {
	$rid = (int)$_GET['rid'];
}

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

function post_report()
{
	global $report_font_styles, $report_options;

	$report = array();
	post_int($report['id'], 'rid');
	post_text($report['name'], 'name');
	post_text($report['section'], 'section');
	post_text($report['creator'], 'creator');
	post_text($report['desc'], 'desc');
	post_text($report['type'], 'type');
	$report['use_abs_coords'] = 0;

	/* For these, just do $report['col'][$i]['field'] = $_POST['col'][$i] */
	foreach(array('col') as $c) {
		$report[$c] = array();
		if(!array_key_exists($c, $_POST)) continue;

		$num = count($_POST[$c]);
		for($i=0; $i<$num; $i++) {
			if(trim($_POST[$c][$i]['field']) == '') continue;
			$report[$c][$i] = array();
			post_float($report[$c][$i]['x'], 'x', $_POST[$c][$i]);
			post_float($report[$c][$i]['y'], 'y', $_POST[$c][$i]);
			post_float($report[$c][$i]['w'], 'w', $_POST[$c][$i]);
			post_float($report[$c][$i]['h'], 'h', $_POST[$c][$i]);
			post_float($report[$c][$i]['min_w'], 'min_w', $_POST[$c][$i]);
			post_float($report[$c][$i]['h_rows'], 'h_rows', $_POST[$c][$i]);
			post_text($report[$c][$i]['field'], 'field', $_POST[$c][$i]);
			post_text($report[$c][$i]['value'], 'value', $_POST[$c][$i]);
			post_text($report[$c][$i]['fontname'], 'fontname', $_POST[$c][$i]);
			post_array($report[$c][$i]['fontstyle'], 'fontstyle', $report_font_styles, $_POST[$c][$i]);
			post_float($report[$c][$i]['fontsize'], 'fontsize', $_POST[$c][$i]);
			post_text($report[$c][$i]['align'], 'align', $_POST[$c][$i]);
			post_text($report[$c][$i]['valign'], 'valign', $_POST[$c][$i]);
			post_text($report[$c][$i]['on_overflow'], 'on_overflow', $_POST[$c][$i]);
		}
	}
	foreach(array('group','sort','distinct') as $c) {
		$report[$c] = array();
		if(!array_key_exists($c, $_POST)) continue;

		$num = count($_POST[$c]);
		for($i=0; $i<$num; $i++) {
			if(trim($_POST[$c][$i]) == '') continue;
			$report[$c][$i] = array();
			post_text($report[$c][$i]['field'], $i, $_POST[$c]);
//			post_text($report[$c][$i]['value'], 'value', $_POST[$c][$i]);
		}
	}

	/* Full parse */
	foreach(array('filter') as $c) {
		$report[$c] = array();
		if(!array_key_exists($c, $_POST)) continue;

		$num = count($_POST[$c]);
//		print_r($_POST);
		for($i=0; $i<$num; $i++) {
			if(trim($_POST[$c][$i]['field']) == '') continue;
			$report[$c][$i] = array();
			post_float($report[$c][$i]['x'], 'x', $_POST[$c][$i]);
			post_text($report[$c][$i]['field'], 'field', $_POST[$c][$i]);
			post_text($report[$c][$i]['value'], 'value', $_POST[$c][$i]);
		}
	}

	$report['option'] = array();
	if(array_key_exists('option', $_POST)) {
		foreach($_POST['option'] as $o=>$v) {
			if(!array_key_exists($o, $report_options)) continue;
			post_text($report['option'][$o], $o, $_POST['option']);
		}
	}
	return $report;
}

switch($action) {
case 'save':
	$r = post_report();
	report_save($mysqli, $r);
	form_ajax_response(array('status'=>0));
	exit();
}



sfiab_page_begin("Edit Reports", $page_id, $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
	<h3>Edit Reports</h3>
<?php
	$form_id = $page_id.'_form';
	$reports = report_load_all($mysqli);
	$report_sec = array();
	foreach($reports as $r) {
		$sec = $r['section'] ;
		if(!array_key_exists($sec, $report_sec)) {
			$report_sec[$sec] = array();
		}
		$report_sec[$sec][$r['id']] = $r;
	}

	$val = '';
?>
	<form action="c_reports_edit.php" id="<?=$form_id?>" method="GET" data-ajax="false" >
	<input type="hidden" name="action" value="" class="sfiab_form_action" />
<?php
	form_select_optgroup($form_id, 'rid', 'Report', $report_sec, $rid);
	form_button($form_id, 'load', 'Load');
//	form_button($form_id, 'dupe', 'Duplicate');
//	form_button($form_id, 'try', 'Try');
//	form_button($form_id, 'delete', 'Delete');

	form_end($form_id);

	if($rid > 0) {
		$form_id = $page_id.'form_edit';
		$r = report_load($mysqli, $rid);
		$fieldvar = "report_{$r['type']}s_fields";
		$fields = $$fieldvar;

		print("<div data-role=collapsible data-collapsed=true><h3>debug</h3><pre>");
		print_r($r);
		print("</pre></div>");
?>
		<hr/>

		<h4>Report Information</h4>

<?php

		$report_types = array('student' => 'Student Report', 'judge' => 'Judge Report', 
 			'award' => 'Award Report', 'committee' => 'Committee Member Report',
			'school' => 'School Report', 'volunteer' => 'Volunteer Report',
			'tour' => 'Tour Report', 'fair' => 'Feeder Fair Report' );

		$report_fields = array();
		$gr = '';
		foreach($fields as $k=>$f) {
			if(array_key_exists('editor_disabled', $f)) continue;
			if(array_key_exists('start_option_group', $f)) {
				$gr = $f['start_option_group'];
				$report_fields[$gr] = array();
			}
			$report_fields[$gr][$k] = $f['name'];
		}

		form_begin($form_id, 'c_reports_edit.php' );
		form_hidden($form_id, 'rid', $r['id']);
		form_text($form_id, 'name', 'Name', $r);
		form_text($form_id, 'section', 'Section', $r);
		form_textbox($form_id, 'desc', 'Description', $r);
		form_text($form_id, 'creator', 'Creator', $r);
		form_select($form_id, 'type', 'Type', $report_types, $r);

?>		<h4>Report Data</h4>
<?php		if(count($r['col'])) {
			foreach($r['col'] as $index=>$d) {
				$i = $index + 1;
				form_select_optgroup($form_id, "col[$index][field]", "Column $i Data", $report_fields, $d['field']);
				form_radio_h($form_id, "col[$index][align]", "Column $i Align", $report_col_align, $d['align'], '', false, false, true);
				form_radio_h($form_id, "col[$index][valign]", "Column $i V-Align", $report_col_valign, $d['valign'], '', false, false, true);
				form_radio_h($form_id, "col[$index][on_overflow]", "Column $i Overflow", $report_col_on_overflow, $d['on_overflow'], '', false, false, true);
//					form_text_inline($form_id, "col_fontname[$index]", $d['fontname'], 'text', 'max-width="10"');
//					$n = array("col_fontstyle[$index]" => $d['fontstyle']);
//					form_select($form_id, "col_fontstyle[$index]", NULL, $report_font_styles, $n, '', false, true, true);
//					form_text_inline($form_id, "col_fontsize[$index]", $d['fontsize']);
				form_hidden($form_id, "col[$index][fontname]", $d['fontname']);
				foreach($d['fontstyle'] as $s) {
					form_hidden($form_id, "col[$index][fontstyle][]", $s);
				}
				form_hidden($form_id, "col[$index][fontsize]", $d['fontsize']);
				form_hidden($form_id, "col[$index][x]", $d['x']);
				form_hidden($form_id, "col[$index][y]", $d['y']);
				form_hidden($form_id, "col[$index][w]", $d['w']);
				form_hidden($form_id, "col[$index][h]", $d['h']);
				form_hidden($form_id, "col[$index][min_w]", $d['min_w']);
				form_hidden($form_id, "col[$index][h_rows]", $d['h_rows']);
?>				
<?php			}
		}

		/* 3 more just for adding columns */
		for($i = 0; $i < 3; $i++) {
			$index = count($r['col']) + $i;
			$v = '';
			form_select_optgroup($form_id, "col[$index][field]","Column $index Data", $report_fields, $v);
			form_radio_h($form_id, "col[$index][align]", "Column $index Align" , $report_col_align, $v, '', false, false, true);
			form_radio_h($form_id, "col[$index][valign]", "Column $index V-Align", $report_col_valign, $v, '', false, false, true);
			form_radio_h($form_id, "col[$index][on_overflow]", "Column $index Overflow", $report_col_on_overflow, $v, '', false, false, true);
			form_hidden($form_id, "col[$index][fontname]", $v);
			form_hidden($form_id, "col[$index][fontstyle][]", $v);
			form_hidden($form_id, "col[$index][fontsize]", $v);
			form_hidden($form_id, "col[$index][x]", $v);
			form_hidden($form_id, "col[$index][y]", $v);
			form_hidden($form_id, "col[$index][w]", $v);
			form_hidden($form_id, "col[$index][h]", $v);
			form_hidden($form_id, "col[$index][min_w]", $v);
			form_hidden($form_id, "col[$index][h_rows]", $v);
		}


?>		<h4>Sort By</h4>
<?php		for($x=0; $x<3; $x++) {
			$f = (array_key_exists($x, $r['sort'])) ? $r['sort'][$x]['field'] : '';
			form_select_optgroup($form_id, "sort[$x]", 'Sort '.($x+1), $report_fields, $f);
		}

?>		<h4>Group By</h4>
<?php		for($x=0; $x<2; $x++) {
			$f = (array_key_exists($x, $r['group'])) ? $r['group'][$x]['field'] : '';
			form_select_optgroup($form_id, "group[$x]", 'Group '.($x+1), $report_fields, $f);
		}

?>		<h4>Distinct Column</h4>
<?php		$x=0;
		$f = (array_key_exists($x, $r['distinct'])) ? $r['distinct'][$x]['field'] : '';
		form_select_optgroup($form_id, "distinct[$x]", 'Distinct' , $report_fields, $f);


?>		<h4>Filter By</h4>
<?php		for($x=0; $x<3; $x++) {
			if(array_key_exists($x, $r['filter'])) {
				$f = $r['filter'][$x]['field'];
				$op = (int)$r['filter'][$x]['x'];
				$v = $r['filter'][$x]['value'];
			} else {
				$f = ''; $op = ''; $v = '';
			}
			form_select_optgroup($form_id, "filter[$x][field]", 'Filter '.($x+1).' Column', $report_fields, $f);
			form_select($form_id, "filter[$x][x]", 'Filter '.($x+1).' Op', $report_filter_ops, $op);
			form_text($form_id, "filter[$x][value]", 'Filter '.($x+1).' Value', $v);
		}

?>		<h4>Options</h4>
<?php		foreach($report_options as $o=>$d) {
			$v = array_key_exists($o, $r['option']) ? $r['option'][$o] : $d['default'];
			form_select($form_id, "option[$o]", $d['desc'], $d['values'], $v);
		}
		

		form_button($form_id, 'save', 'Save');
//		form_button($form_id, 'dupe', 'Duplicate');
//		form_button($form_id, 'try', 'Try');
//		form_button($form_id, 'delete', 'Delete');
		form_end($form_id);

	}

?>

</div></div>

<?php


sfiab_page_end();

exit();

?>



<script type="text/javascript">
function reportReload()
{
	document.forms.report.reloadaction.value = 'reload';
	document.forms.report.submit();
}

var canvasWidth=0;
var canvasHeight=0;
var canvasObjectIndex=0;
var labelWidth=0;
var labelHeight=0;

function initCanvas(w,h,lw,lh) {
	canvasWidth=w;
	canvasHeight=h;
	labelWidth=lw;
	labelHeight=lh;
}

function createData(x,y,w,h,l,face,align,valign,value) {
	var canvas=document.getElementById('layoutcanvas');
	var newdiv=document.createElement('div');
	if(valign=="vcenter") verticalAlign="middle";
	else if(valign=="vtop") verticalAlign="top";
	else if(valign=="vbottom") verticalAlign="bottom";
	else verticalAlign="top";
//	alert(verticalAlign);

	//convert x,y,w,h from % to absolute

	var dx=Math.round(x*canvasWidth/100);
	var dy=Math.round(y*canvasHeight/100);
	var dw=Math.round(w*canvasWidth/100);
	var dh=Math.round(h*canvasHeight/100);
//	alert(dx+','+dy+','+dw+','+dh);

	var fontheight=Math.round(dh/l);

	newdiv.setAttribute('id','o_'+canvasObjectIndex);
	newdiv.style.display="table-cell";
	newdiv.style.position="absolute";
	newdiv.style.width=dw+"px";
	newdiv.style.height=dh+"px";
	newdiv.style.left=dx+"px";
	newdiv.style.top=dy+"px";
	newdiv.style.textAlign=align;
	newdiv.style.verticalAlign=verticalAlign;
	newdiv.style.padding="0 0 0 0";
	newdiv.style.margin="0 0 0 0";
//	newdiv.style.vertical-align=valign;
	newdiv.style.border="1px solid blue";
	newdiv.style.fontSize=fontheight+"px";
	newdiv.style.lineHeight=fontheight+"px";
	newdiv.style.fontFamily="Verdana";
	newdiv.style.fontSizeAdjust=0.65;

	var maxlength=Math.floor(dw/(fontheight*0.7))*l;
	if(value.length>maxlength) value=value.substring(0,maxlength);
	newdiv.innerHTML=value; //"Maple Test xxxx"; //value;

	canvas.appendChild(newdiv);

	canvasObjectIndex++;
}

function createDataTCPDF(x,y,w,h,align,valign,fontname,fontstyle,fontsize,value) {

	var canvas=document.getElementById('layoutcanvas');
	var newdiv=document.createElement('div');

	var dx = Math.round(x * canvasWidth / labelWidth);
	var dy = Math.round(y * canvasHeight / labelHeight);
	var dw = Math.round(w * canvasWidth / labelWidth);
	var dh = Math.round(h * canvasHeight / labelHeight);

	
	var fontheight=(fontsize * 25.4 / 72) * canvasHeight / labelHeight;
	var l = Math.floor(h/fontheight);
	if(fontheight == 0) fontheight=10;
	if(l==0) l=1;

//	alert(dh + ", fh="+fontheight);

	newdiv.setAttribute('id','o_'+canvasObjectIndex);
	newdiv.style.display="table-cell";
	newdiv.style.position="absolute";
	newdiv.style.width=dw+"px";
	newdiv.style.height=dh+"px";
	newdiv.style.left=dx+"px";
	newdiv.style.top=dy+"px";
	newdiv.style.textAlign=align;
	newdiv.style.verticalAlign=valign;
	newdiv.style.padding="0 0 0 0";
	newdiv.style.margin="0 0 0 0";
//	newdiv.style.vertical-align=valign;
	newdiv.style.border="1px solid blue";
	newdiv.style.fontSize=fontheight+"px";
	newdiv.style.lineHeight=fontheight+"px";
	newdiv.style.fontFamily=fontname;
	newdiv.style.fontSizeAdjust=0.65;

	var maxlength=Math.floor(dw/(fontheight*0.7))*l;
	if(value.length>maxlength) value=value.substring(0,maxlength);

	newdiv.innerHTML=value; 

	canvas.appendChild(newdiv);

	canvasObjectIndex++;
}

</script>
<?

 if($repaction == 'save') {
 	/* Save the report */
	$report['id'] = report_save($report);
	echo happy(i18n("Report Saved"));
 }

 if($repaction == 'del') {
 	report_delete($report['id']);
	echo happy(i18n("Report Deleted"));
 }

 if($repaction == 'dupe') {
 	$report['id'] = 0;
 	$report['id'] = report_save($report);
	echo happy(i18n("Report Duplicated"));
 }

 if($repaction == 'export') {
 	echo "<pre>";
	$q = mysql_query("SELECT system_report_id FROM reports WHERE 1 ORDER BY system_report_id DESC");
	$r = mysql_fetch_assoc($q);
	$sid = $r['system_report_id'] + 1;
	$n = mysql_escape_string($report['name']);
	$c = mysql_escape_string($report['creator']);
	$d = mysql_escape_string($report['desc']);
	$t = mysql_escape_string($report['type']);

 	echo "INSERT INTO `reports` (`id`, `system_report_id`, `name`, `desc`, `creator`, `type`) VALUES\n";
	echo "\t('', '$sid', '$n', '$d', '$c', '$t');\n";

	echo "INSERT INTO `reports_items` (`id`, `reports_id`, `type`, `ord`, `field`, `value`, `x`, `y`, `w`, `h`, `lines`, `face`, `align`) VALUES ";

	/* Do the options */
	$x = 0;
	foreach($report['option'] as $k=>$v) {
		echo "\n\t('', LAST_INSERT_ID(), 'option', $x, '$k', '$v', 0, 0, 0, 0, 0, '', ''),";
		$x++;
	}
	/* Do the fields */
	$fs = array('col', 'group', 'sort', 'distinct', 'filter');
	$first = true;
	foreach($fs as $f) {
		foreach($report[$f] as $x=>$v) {
			$k = $v['field'];
			$vx = intval($v['x']);
			$vy = intval($v['y']);
			$vw = intval($v['w']);
			$vh = intval($v['h']);
			$vlines = intval($v['lines']);
			if($vlines == 0) $vlines = 1;
			$face = $v['face'];
			$align = $v['align']. ' ' . $v['valign'];
			$value=mysql_escape_string(stripslashes($v['value']));
			if(!$first) echo ',';
			$first = false;
			echo "\n\t('', LAST_INSERT_ID(), '$f', $x, '$k', '$value', $vx, $vy, $vw, $vh, $vlines, '$face', '$align')";
		}
	}
	echo ";\n";
	echo "</pre>";
 }
 	


 /* ---- Setup  ------ */

 $n_columns = intval($_POST['ncolumns']);
 $n = count($report['col']) + 1;
 if($n > $n_columns) $n_columns = $n;
 if($colaction == 'add') $n_columns+=3;

 $fieldvar = "report_{$report['type']}s_fields";
 if(isset($$fieldvar)) $fields = $$fieldvar;


 echo "<br />";

 echo "<form method=\"post\" name=\"reportload\" action=\"reports_editor.php\" onChange=\"document.reportload.submit()\">";
 echo "<input type=\"hidden\" name=\"loadaction\" value=\"load\" />";
 echo "<select name=\"id\" id=\"report\">";
 echo "<option value=\"0\">".i18n("Create New Report")."</option>\n";

 $reports = report_load_all();
 $x=0;
 foreach($reports as $r) {
 	$sel = ($report['id'] == $r['id']) ? 'selected=\"selected\"' : '';
 	echo "<option value=\"{$r['id']}\" $sel>{$r['name']}</option>\n";
}
 echo "</select>";
 echo "<input type=\"submit\" value=\"Load\"></form>";
 echo "<hr />";
 

 echo "<form method=\"post\" name=\"report\" action=\"reports_editor.php\">";
 echo "<input type=\"hidden\" name=\"id\" value=\"{$report['id']}\" />";
 echo "<input type=\"hidden\" name=\"ncolumns\" value=\"$n_columns\" />";

 echo "<h4>Report Information</h4>";
 echo "<table>";
 echo "<tr><td>Name: </td>";
 echo "<td><input type=\"text\" name=\"name\" size=\"80\" value=\"{$report['name']}\" /></td>";
 echo "</tr>";
 echo "<tr><td>Created By: </td>";
 echo "<td><input type=\"text\" name=\"creator\" size=\"80\" value=\"{$report['creator']}\" /></td>";
 echo "</tr>";
 echo "<tr><td>Description: </td>";
 echo "<td><textarea name=\"desc\" rows=\"3\" cols=\"60\">{$report['desc']}</textarea></td>";
 echo "</tr>";
 echo "<tr><td>Type: </td>";
 echo "<td>";
 selector('type', array('student' => 'Student Report', 'judge' => 'Judge Report', 
 			'award' => 'Award Report', 'committee' => 'Committee Member Report',
			'school' => 'School Report', 'volunteer' => 'Volunteer Report',
			'tour' => 'Tour Report', 'fair' => 'Feeder Fair Report',
		),
		$report['type'],
		"onChange=\"reportReload();\"");
 echo "<input type=\"hidden\" name=\"reloadaction\" value=\"\">";
 echo "</td>";
 echo "</tr></table>";
 
 echo "<h4>Report Data</h4>";
 echo "<table>";
 $x=0;
 //only go through the columns if there are columns to go through
 if(count($report['col'])) {
	 foreach($report['col'] as $o=>$d) {
		echo "<tr><td>Column&nbsp;".($x + 1).": </td>";
		echo "<td>";
		if(intval($x) != intval($o)) {
			echo ("WARNING, out of order!");
		}
		field_selector("col[$o][field]", "col$o", $d['field']);
		echo "</td></tr>"; 
		$x++;
		$canvasLabels[]=$fields[$report['col'][$o]['field']]['name']; //['field'];
	 }
 }
 for(;$x<$n_columns;$x++) {
	echo "<tr><td>Column&nbsp;".($x + 1).": </td>";
	echo "<td>";
	field_selector("col[$x][field]", "col$x", '');
	echo "</td></tr>"; 

 }
 echo "<tr><td></td>";
 echo "<td align=\"right\">";
 echo "<select name=\"colaction\"><option value=\"\"></option><option value=\"add\">Add more columns</option></select>";
 echo "<input type=\"submit\" value=\"Go\">";
 echo "</td></tr>";
 echo "</table>\n";
 
$doCanvasSample = false;
$doCanvasSampletcpdf = false;
 $l_w=$report_stock[$report['option']['stock']]['label_width'];
 $l_h=$report_stock[$report['option']['stock']]['label_height'];
 if($l_w && $l_h && $report['option']['type']=="label") {
     echo "<h4>Label Data Locations</h4>";

	$doCanvasSample=true;
	$ratio=$l_h/$l_w;
	$canvaswidth=600;
	$canvasheight=round($canvaswidth*$ratio);
	echo "<div id=\"layoutcanvas\" style=\"border: 1px solid red; position: relative; width: {$canvaswidth}px; height: {$canvasheight}px;\">";
	echo "</div>\n";
	echo "<script type=\"text/javascript\">initCanvas($canvaswidth,$canvasheight,$l_w,$l_h)</script>\n";
 }

 if($l_w && $l_h && $report['option']['type']=="tcpdf_label") {
     echo "<h4>Label Data Locations - TCPDF</h4>";

	$l_w *= 25.4; 
	$l_h *= 25.4; 
	$doCanvasSampletcpdf=true;
	$ratio=$l_h/$l_w;
	$canvaswidth=600;
	$canvasheight=round($canvaswidth*$ratio);
	echo "<div id=\"layoutcanvas\" style=\"border: 1px solid red; position: relative; width: {$canvaswidth}px; height: {$canvasheight}px;\">";
	echo "</div>\n";
	echo "<script type=\"text/javascript\">initCanvas($canvaswidth,$canvasheight,$l_w,$l_h)</script>\n";
 }


 echo "<table>";
 $x=0;

 
 if($report['option']['type'] == 'label' || $report['option']['type'] == 'tcpdf_label') {
 	$fontlist = array('' => 'Default');
	$fl = PDF::getFontList();
	foreach($fl as $f) $fontlist[$f] = $f;
//	print_r($fl);
			  
 	foreach($report['col'] as $o=>$d) {
		$f = $d['field'];
		echo "<tr><td align=\"right\">Loc ".($o+1).": </td>";
		echo "<td>";
		$script="";
		foreach($locs as $k=>$v) {
			if($k=='Lines' && $report['option']['type'] != 'label') continue;
			echo "$k=<input type=\"text\" size=\"3\" name=\"col[$x][$v]\" value=\"{$d[$v]}\">";
			$script.="{$d[$v]},";
		}

		if($report['option']['type'] == 'label') {
			echo 'Face=';
			selector("col[$x][face]", array('' => '', 'bold' => 'Bold'), $d['face']);
		}
		echo 'Align';
		selector("col[$x][align]", array('center' => 'Center', 'left' => 'Left', 'right' => 'Right'), 
				$d['align']);
		echo 'vAlign';
		if($report['option']['type'] == 'label') {
			selector("col[$x][valign]", array('vcenter' => 'Center', 'vtop' => 'Top', 'vbottom' => 'Bottom'), 
					$d['valign']);
		} else {
			selector("col[$x][valign]", array('middle' => 'Middle', 'top' => 'Top', 'bottom' => 'Bottom'), 
					$d['valign']);
			
			echo 'Font=';
			selector("col[$x][fontname]", $fontlist, $d['fontname']);
			selector("col[$x][fontstyle]", array('' => '', 'bold' => 'Bold'), $d['fontstyle']);
			echo "<input type=\"text\" size=\"3\" name=\"col[$x][fontsize]\" value=\"{$d['fontsize']}\">";
			echo 'pt  ';
			echo 'OnOverflow=';
			selector("col[$x][on_overflow]", array('tuncate'=>'Truncate','...'=>'Add ...', 'scale'=>'Scale'), $d['on_overflow']);
		}

		if($f == 'static_text') {
			echo "<br />Text=<input type=\"text\" size=\"40\" name=\"col[$x][value]\" value=\"{$d['value']}\">";
		} else {
			echo "<input type=\"hidden\" name=\"col[$x][value]\" value=\"\">";
		}
		if($doCanvasSample)
			echo "<script type=\"text/javascript\">createData({$script}'{$d['face']}','{$d['align']}','{$d['valign']}','{$canvasLabels[$x]}')</script>\n";
		if($doCanvasSampletcpdf)
			echo "<script type=\"text/javascript\">createDataTCPDF({$script}'{$d['align']}','{$d['valign']}','{$d['fontname']}','{$d['fontstyle']}','{$d['fontsize']}','{$canvasLabels[$x]}')</script>\n";

		$x++;
	}
 	for(;$x<$n_columns;$x++) {
		echo "<tr><td align=\"right\">Loc ".($x+1).": </td>";
		echo "<td>";
		foreach($locs as $k=>$v) {
			if($k=='Lines' && $report['option']['type'] != 'label') continue;
			echo "$k=<input type=\"text\" size=\"3\" name=\"col[$x][$v]\" value=\"0\">";
		}
		if($report['option']['type'] == 'label') {
			echo 'Face=';
			selector("col[$x][face]", array('' => '', 'bold' => 'Bold'), '');
		}

		echo 'Align';
		selector("col[$x][align]", array('center' => 'Center', 'left' => 'Left', 'right' => 'Right'), 
				'center');
		echo 'vAlign';
		if($report['option']['type'] == 'label') {
			selector("col[$x][valign]", array('vcenter' => 'Center', 'vtop' => 'Top', 'vbottom' => 'Bottom'), 
					'top');
		} else {
			selector("col[$x][valign]", array('middle' => 'Middle', 'top' => 'Top', 'bottom' => 'Bottom'), 'middle');
			
			echo 'Font=';
			selector("col[$x][fontname]", $fontlist, '');
			selector("col[$x][fontstyle]", array('' => '', 'bold' => 'Bold'), '');
			echo "<input type=\"text\" size=\"3\" name=\"col[$x][fontsize]\" value=\"\">";
			echo 'pt  ';
			echo 'OnOverflow=';
			selector("col[$x][on_overflow]", array('Truncate'=>'truncate','Add ...'=>'...', 'Scale'=>'scale'),'');
		}
		echo "<input type=\"hidden\" name=\"col[$x][value]\" value=\"\">";
		echo "</td></tr>"; 
	}
 }
 echo "</table>\n";
 echo "<h4>Grouping</h4>";
 for($x=0;$x<2;$x++) {
	echo "Group By".($x + 1).": ";
	$f = $report['group'][$x]['field'];
	field_selector("group[$x]", "group$x", $f);
	echo "<br />"; 
 }
 echo "<h4>Sorting</h4>";
 for($x=0;$x<3;$x++) {
	echo "Sort By".($x + 1).": ";
	$f = $report['sort'][$x]['field'];
	field_selector("sort[$x]", "sort$x",$f); 
	echo "<br />"; 
 }
 echo "<h4>Distinct</h4>";
 echo "Distinct Column:   ";
 $x=0;
 $f = $report['distinct'][$x]['field'];
 field_selector("distinct[$x]", "distinct0", $f);

 echo "<h4>Filtering</h4>";
 echo "<table>";
 for($x=0;$x<3;$x++) {
	echo "<tr><td>Filter".($x + 1).":</td><td>";
	field_selector("filter[$x][field]", "filter$x",$report['filter'][$x]['field']); 
	echo "<br />";
	selector("filter[$x][x]", $filter_ops,$report['filter'][$x]['x']); 
	$v = $report['filter'][$x]['value'];
	echo "Text=<input type=\"text\" size=\"20\" name=\"filter[$x][value]\" value=\"$v\">";
	echo "</td></tr>"; 
 }
 echo "</table>";

 echo "<h4>Options</h4>";
 foreach($report_options as $ok=>$o) {
 	echo "{$o['desc']}: <select name=\"option[$ok]\" id=\"$ok\">";
	foreach($o['values'] as $k=>$v) {
		$sel = ($report['option'][$ok] == $k) ? 'selected=\"selected\"' : '';
		echo "<option value=\"$k\" $sel>$v</option>";
	}
	echo "</select><br />\n";
 } 

 echo "<br />";
 if($report['system_report_id'] != 0) {
 	echo notice(i18n('This is a system report, it cannot be changed or deleted.  To save changes you have made to it, please select the \'Save as a new report\' option.'));
 }
 echo "<select name=\"repaction\">";
 if($report['system_report_id'] == 0) {
	$sel = ($repaction_save == 'save') ? "selected=\"selected\"" : '';
	echo " <option value=\"save\" $sel>Save this report</option>";
	$sel = ($repaction_save == 'try') ? "selected=\"selected\"" : '';
	echo " <option value=\"try\" $sel>Try this report</option>";
	echo " <option value=\"export\">Export this report</option>";
	echo " <option value=\"\" ></option>";
	echo " <option value=\"dupe\" >Save as a new report(duplicate)</option>";
	echo " <option value=\"\" ></option>";
	echo " <option value=\"del\" >Delete this report</option>";
 } else {
 	echo " <option value=\"dupe\" >Save as a new report(duplicate)</option>";
	$sel = ($repaction_save == 'try') ? "selected=\"selected\"" : '';
	echo " <option value=\"try\" $sel>Try this report</option>";
	echo " <option value=\"export\">Export this report</option>";
 }
 	
 echo "</select>";
 echo "<input type=\"submit\" value=\"Go\">";

 echo "</form>";

 send_footer();
?>
