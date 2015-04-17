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
require_once('xml.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('awards.inc.php');
require_once('form.inc.php');
require_once('stats.inc.php');


$mysqli = sfiab_init('committee');

$page_id = "c_ysc_stats";


$fair = array();

$stats = stats_get_export($mysqli, $fair, $config['year']);

function s($a, $i)
{
	global $stats;
	if(array_key_exists($i, $stats[$a])) {
		return $stats[$a][$i];
	}
	return 0;
}

$q = $mysqli->query("SELECT COUNT(`id`) FROM `schools` WHERE year='{$config['year']}'");
$r = $q->fetch_row();
$total_schools = $r[0];

$ysc_stats = array();
$ysc_stats ["numschoolstotal"]=$total_schools;
$ysc_stats ["numschoolsactive"]=$stats['schools'];
$ysc_stats ["numstudents"]=$stats['students_public'] + $stats['students_private'];
$ysc_stats ["numk6m"]=s('male',1) + s('male',2) + s('male',3) + s('male',4) + s('male',5) + s('male',6) ;
$ysc_stats ["numk6f"]=s('female',1) + s('female',2) + s('female',3) + s('female',4) + s('female',5) + s('female',6) ;
$ysc_stats ["num78m"]=s('male',7) + s('male',8);
$ysc_stats ["num78f"]=s('female',7) + s('female',8);
$ysc_stats ["num910m"]=s('male',9) + s('male',10);
$ysc_stats ["num910f"]=s('female',9) + s('female',10);
$ysc_stats ["num11upm"]=s('male',12) + s('male',12) + s('male',13);
$ysc_stats ["num11upf"]=s('female',12) + s('female',12) + s('female',13);
$ysc_stats ["projk6"]=s('project',1) + s('project',2) + s('project',3) + s('project',4) + s('project',5) + s('project',6) ;
$ysc_stats ["proj78"]=s('project',7) + s('project',8);
$ysc_stats ["proj910"]=s('project',9) + s('project',10);
$ysc_stats ["proj11up"]=s('project',12) + s('project',12) + s('project',13);
$ysc_stats ["committee"]=$stats['committee_members'];
$ysc_stats ["judges"]=$stats['judges'];

$ysc_region_id = NULL;
$ysc_region_password = NULL;
$ysc_name = '';

$q = $mysqli->query("SELECT * FROM fairs WHERE abbrv='YSC'");
if($q->num_rows >= 1) {
	 $f = $q->fetch_assoc();
	 $ysc_region_id = $f['username'];
	 $ysc_region_password = $f['password'];
	 $ysc_name = $f['name'];
}

$xml_data=array("affiliation"=>array(
			"ysf_region_id"=>$ysc_region_id,
			"ysf_region_password"=>$ysc_region_password,
			'year'=>$config['year'],
			'stats'=>$ysc_stats
			)
		);

/* xmlcreaterecurse uses $output and $indent as a global variable */
$output="";
$indent = 0;
xmlCreateRecurse($xml_data);
$xml = $output;

$action = array_key_exists('action', $_POST) ? $_POST['action'] : '';

switch($action) {
case 'send':
	if($ysc_region_id === NULL) exit();

 	if(!function_exists('curl_init')) {
		form_ajax_response(array('status'=>1, 'error'=>'Your PHP installation does not support CURL.  You will need to login to the YSC system as the regional coodinator and upload the XML data manually'));
		exit();
	}

//	print_r($_POST['xml']);
			
	$ch = curl_init(); /// initialize a cURL session
	curl_setopt ($ch, CURLOPT_URL,"https://secure.ysf-fsj.ca/registration/xmlaffiliation.php");
	curl_setopt ($ch, CURLOPT_HEADER, 0); /// Header control
	curl_setopt ($ch, CURLOPT_POST, 1);  /// tell it to make a POST, not a GET
	curl_setopt ($ch, CURLOPT_POSTFIELDS, "xml=".$xml);  /// put the query string here starting with "?"
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); /// This allows the output to be set into a variable $datastream
//	curl_setopt ($ch, CURLOPT_POSTFIELDSIZE, 0);
	curl_setopt ($ch, CURLOPT_TIMEOUT, 360);
	curl_setopt ($ch, CURLOPT_SSLVERSION, 3);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
//	$datastream = "deadbeef";
	$datastream = curl_exec ($ch); /// execute the curl session and return the output to a variable $datastream
	$datastream = str_replace(" standalone=\"yes\"","",$datastream);
	// echo "curl close <br />";
	curl_close ($ch); /// close the curl session

//	$response=xml_parsexml($datastream);

	$ret = array();
	$ret['status'] = 0;
	if(strstr('successfully updated', $datastream)) {
		$ret['status'] = 1;
		$ret['happy'] = 'Statistics successfully updated';
	} else {
		$ret['error'] = 'Update failed');
	}
	$ret['info'] = "The YSC Registration Server said: ".$datastream;
	form_ajax_response($ret);
	exit();
 }


$help = '<p>';
sfiab_page_begin("$ysc_name Affiliation Statistics", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>" class="sfiab_page" >

<?php
	$ok = true;
	//make sure we have the ysc_region_id and ysc_region_password
	if($ysc_region_id == '') {
		$error .= "<li>You have not yet specified a username for $ysc_name (your Region ID).  Go to the <a href=\"sciencefairs.php\">Science Fair Management</a> page to set it";
		$ok=false;
	}
	if($ysc_region_password == '') {
		$error .= "<li>You have not yet specified a password for $ysc_name (your Region Password).  Go to the <a href=\"sciencefairs.php\">Science Fair Management</a> page to set it";
		$ok=false;
	}

	form_page_begin($page_id, array(), $ok?'':'Found some problems preventing uploading YSC statistics:');

	if(!$ok) {
?>		<ul><?=$error?></ul>
		</div>
<?php		
		sfiab_page_end();
		exit();
	}
?>


	<h3>Upload Statistics to <?=$ysc_name?></h3>

	<p>The following data will be sent to <?=$ysc_name?>

	<table>

<?php	foreach($ysc_stats as $k=>$v) { ?>
		<tr><td><?=$k?></td><td><b><?=$v?></b></td></tr>
<?php	} ?>		
	</table>

<?php
	form_page_begin($page_id, array());


	$form_id = $page_id.'_form';
	form_begin($form_id, 'c_ysc_stats.php');
	form_submit_enabled($form_id, 'send', 'Send Data to '.$ysc_name, 'Sent');
	form_end($form_id);
?>
	<div data-role=collapsible data-collapsed=true><h3>Raw XML to be Sent to <?=$ysc_name?> Server</h3>
	<pre><?=htmlentities($xml)?></pre>
	</div>


	
</div>
<?php		
sfiab_page_end();

?>
