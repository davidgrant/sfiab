<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$timezones = array();
foreach(timezone_identifiers_list() as $tid=>$tz) {
	$timezones[$tz] = $tz;
}

$cfg = array();
$q = $mysqli->query("SELECT * FROM config WHERE category!='system' ORDER BY category,`order`,var");
while($r = $q->fetch_assoc()) {
	if(!array_key_exists($r['category'], $cfg)) $cfg[$r['category']] = array();
	$cfg[$r['category']][$r['var']] = $r;
}

$cfg_tab_names = array();
foreach($cfg as $k=>&$v) {
	$cfg_tab_names[$k] = str_replace(' ', '_', $k);
	//$ksort($v);
}
//ksort($cfg);

$action = array_key_exists('action', $_POST) ? $_POST['action'] : '';
switch($action) {
case 'save':
	/* Scan everythign that was just saved */
	foreach($_POST as $p=>$v) {
		if(substr($p, 0, 4) == 'cfg_') {
			$var = substr($p, 4);
			$val = $mysqli->real_escape_string($v);
			$mysqli->query("UPDATE config SET `val`='$val' WHERE `var`='$var'");
		}
	}
	form_ajax_response(0);
	exit();
}

$page_id = 'c_config_variables';
$help = '<p>SFIAB Configuration';
sfiab_page_begin("SFIAB Configuration", $page_id, $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<p>Don't change these unless you know what you're doing!

	<div data-role="tabs">
	<ul data-role="listview" data-inset="true" class="tablist-left">

<?php	/* Print tabs for main sections */
	foreach(array_keys($cfg) as $k) { ?>
		<li><a href="#<?=$cfg_tab_names[$k]?>" data-ajax="false"><?=$k?></a></li>
<?php	}?>
	</ul>

<?php	foreach($cfg as $k=>&$v) { ?>
		<div id="<?=$cfg_tab_names[$k]?>" class="ui-body-d tablist-content">
			<h3><?=$k?></h3>
<?php			$form_id = $page_id.'_'.$cfg_tab_names[$k].'_form';
			form_begin($form_id, 'c_config_variables.php');
			foreach($v as $var=>&$d) { 
				switch($d['type']) {
				case 'timezone':
					form_select($form_id, 'cfg_'.$d['var'], $d['var'], $timezones, $d['val']);
					break;
				default:
					form_text($form_id, 'cfg_'.$d['var'], $d['var'], $d['val']);
					break;
				}
			}
			form_submit($form_id, 'save', "Save", "Saved");
			form_end($form_id);

			?>
		</div>
<?php	}?>

</div></div>


<style>
.tablist-left {
    width: 15%;
    display: inline-block;
}
.tablist-content {
    width: 80%;
    display: inline-block;
    vertical-align: top;
    margin-left: 1%;
}
</style>

<?php
sfiab_page_end();
?>
