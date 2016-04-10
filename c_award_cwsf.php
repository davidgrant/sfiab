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

$mysqli = sfiab_init('committee');

$cwsf_divisions = array(
	1=>"Discovery",
	2=>"Energy",
	3=>"Environment",
	4=>"Health",
	5=>"Information",
	6=>"Innovation",
	7=>"Resources"
);

$page_id = "c_award_cwsf";

$challenges = challenges_load($mysqli);

foreach($challenges as &$c) {
	foreach($cwsf_divisions as $did=>$d) {
		if(strcmp($d, $c['name']) == 0) $c['cwsf_division_id'] = $did;
	}

	if(!array_key_exists('cwsf_division_id', $c)) {
		print("Couldn't match cwsf challenge $did=>$d with anything in ");
		print_r($challenges);
		exit();
	}

}

$award = award_load_cwsf($mysqli);

if($award === NULL) {
	print("Unable to find CWSF award!");
	exit();
}

/* Get the first and only prize */
$prize = NULL;
foreach($award['prizes'] as &$p) {
	$prize = &$p;
	break;
}

$winners = prize_load_winners($mysqli, $prize);


function get_cwsf_winners_for_xml($mysqli, &$winners)
{
	global $config, $challenges;

	$xml_data = array();
	foreach($winners as $pid=>&$project) {
		$students=array();
		$cwsf_agecategory=0;

		if(!array_key_exists('students', $project)) {
			print("Project $pid has no sudents");
			print_r($project);
		}
		foreach($project['students'] as &$s) {
			switch($s['grade']) {
			case 7: case 8:
				$cat = 1; break;
			case 9: case 10:
				$cat = 2; break;
			case 11: case 12: case 13: 
				$cat = 3; break;
			default:
				print("Can't handle student in grade {$s['grade']}:");
				print_r($s);
				exit();
			}
			if($cwsf_agecategory<$cat) $cwsf_agecategory=$cat;

			$students[]=array(
					"xml_type"=>"student",
					"firstname"=>$s['firstname'],
					"lastname"=>$s['lastname'],
					"email"=>$s['email'],
					"gender"=>$s['sex'],
					"grade"=>$s['grade'],
					"language"=>$s['language'],
					"birthdate"=>$s['birthdate'],
					"address1"=>$s['address'],
					"address2"=>"",
					"city"=>$s['city'],
					"province"=>$s['province'],
					"postalcode"=>$s['postalcode'],
					"homephone"=>$s['phone1'],
					"cellphone"=>"",
				);
		}

		$xml_data[]=array(
				"xml_type"=>"project",
				"projectid"=>$project['pid'],
				"projectnumber"=>$project['number'],
				"title"=>$project['title'],
				"abstract"=>$project['abstract'],
				"category_id"=>$cwsf_agecategory,
				"division_id"=>$challenges[$project['challenge_id']]['cwsf_division_id'],
				"projectdivisions_id"=>$project['challenge_id'],
				"students"=>$students,
				);
		//print_r($award);
	}


	return $xml_data;
 }


$action = array_key_exists('action', $_POST) ? $_POST['action'] : '';

switch($action) {
case 'cwsfdivision':
 	foreach($_POST['cwsfdivision'] AS $p=>$d)
	{
		mysql_query("UPDATE projects SET cwsfdivisionid='$d' WHERE id='$p'");
	}
	echo happy(i18n("CWSF Project Divisions saved"));
	exit();


case 'register':

 	if(!function_exists('curl_init')) {
		form_ajax_response(array('status'=>1, 'error'=>'Your PHP installation does not support CURL.  You will need to login to the YSC system as the regional coodinator and upload the XML data manually'));
		exit();
	}

//	print_r($_POST['xml']);

	$ch = curl_init(); /// initialize a cURL session
	curl_setopt ($ch, CURLOPT_URL,"https://secure.youthscience.ca/registration/xmlregister.php");
	curl_setopt ($ch, CURLOPT_HEADER, 0); /// Header control
	curl_setopt ($ch, CURLOPT_POST, 1);  /// tell it to make a POST, not a GET
	curl_setopt ($ch, CURLOPT_POSTFIELDS, "xml=".$_POST['xml']);  /// put the query string here starting with "?"
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); /// This allows the output to be set into a variable $datastream
//	curl_setopt ($ch, CURLOPT_POSTFIELDSIZE, 0);
	curl_setopt ($ch, CURLOPT_TIMEOUT, 360);
	curl_setopt ($ch, CURLOPT_SSLVERSION, 3);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, false);
//	$datastream = "deadbeef";
	$datastream = curl_exec ($ch); /// execute the curl session and return the output to a variable $datastream
	$datastream = str_replace(" standalone=\"yes\"","",$datastream);
	// echo "curl close <br />";
	curl_close ($ch); /// close the curl session

	form_ajax_response(array('status'=>0, 'happy'=> 'The YSC Registration Server said: '.$datastream));
	exit();
 }


$help = '<p>';
sfiab_page_begin($u, "CWSF Registration", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" >

<?php
	 $ok=true;
	 $error = '';

	 /* Load the YSC fair */
	 $q = $mysqli->query("SELECT * FROM fairs WHERE abbrv='YSC'");
	 if($q->num_rows < 1) {
	 	$error = "<li>You have not defined the YSC upstream fair in the Science Fair Management area.";
		$ok = false;
	 } else {
		 $f = $q->fetch_assoc();
		 $ysc_region_id = $f['username'];
		 $ysc_region_password = $f['password'];
	 }
	 //make sure we have the ysc_region_id and ysc_region_password
	 if($ysc_region_id == '') {
		$error .= "<li>You have not yet specified a username for YSC (your Region ID).  Go to the <a href=\"sciencefairs.php\">Science Fair Management</a> page to set it";
		$ok=false;
	 }
	 if($ysc_region_password == '') {
		$error .= "<li>You have not yet specified a password for YSC (your Region Password).  Go to the <a href=\"sciencefairs.php\">Science Fair Management</a> page to set it";
		$ok=false;
	 }

	 if($award == NULL)  {
		$error .= "<li>Cannot find an award, or there is more than one award, that is specified as the Canada-Wide Science Fair Award. Please go to the awards manager and select which award identifies your CWSF students";
		$ok=false;

	} 

	if(count($award['prizes']) != 1) {
		$error .= "<li>The cwsf award has ".count($award['prizes'])." prizes.  Should have exactly one.";
		$ok=false;

	} 

	form_page_begin($page_id, array(), $ok?'':'Found some problems preventing CWSF registration:');


	if(!$ok) {
?>		<ul><?=$error?></ul>
		</div></div>
<?php		
		sfiab_page_end();
		exit();
	}

//	print('<pre>');
//	print_r($winners);

	$reg=array("registration"=>array(
			"ysf_region_id"=>$ysc_region_id,
			"ysf_region_password"=>$ysc_region_password,
			"projects"=>get_cwsf_winners_for_xml($mysqli, $winners),
			)
		);
	/* xmlcreaterecurse uses $output and $indent as a global variable */
	$output="";
	$indent = 0;
	xmlCreateRecurse($reg);
	$xmldata=$output;

?>


	<h3>Upload to CWSF</h3>

	<p><b>CWSF Award: <?=$award['name']?></b></br>

	<p>Please review the list of winning projects/students below.  If it is
	all correct then you can click the 'Register for CWSF' button at the
	bottom of the page to send the information to YSC

	<p>Found <?=count($winners)?> winning projects in the CWSF award


	<table>
		<thead>
		<tr><th>Project Information</th>
		<th>Project Division / CWSF Project Division</th>
		</tr>
		</thead>

<?php	foreach($winners AS &$project) { ?>
		<tr><td><b><?=$project['number']?> - <?=$project['title']?></b>
		<br/>

<?php		foreach($project['students'] as $s) { ?>
			&nbsp;&nbsp;&nbsp;&nbsp;Name: <?=$s['name']?><br/>
			&nbsp;&nbsp;&nbsp;&nbsp;Email: <?=$s['email']?><br/>
			&nbsp;&nbsp;&nbsp;&nbsp;Grade: <?=$s['grade']?><br/>
<?php		} ?>
		</td>
		<td>
		Fair Challenge: <?=$challenges[$project['challenge_id']]['name']?><br/>
		CWSF Challenge: <?=$cwsf_divisions[$challenges[$project['challenge_id']]['cwsf_division_id']]?><br/>
		</td></tr>
<?php	} ?>
	</table>

	<p><b>You only get one shot at this.</b>  Once you submit this data for
	this year, it will not work again.  If any changes need to be made,
	your regional coordinator will need to login to the YSC system and make
	the changes.

<?php
	form_page_begin($page_id, array());


	$form_id = $page_id.'_form';
	form_begin($form_id, 'c_award_cwsf.php');
	form_hidden($form_id, 'xml', $xmldata);
	form_button($form_id, 'register', 'Send Data to YSC');
	form_end($form_id);
?>

	<div data-role=collapsible data-collapsed=true><h3>Raw XML to be Sent to YSC Server</h3>
	<pre><?=htmlentities($xmldata)?></pre>
	</div>
	
</div></div>
<?php		
sfiab_page_end();

?>
