<?php
require_once('common.inc.php');

if(array_key_exists('SERVER_ADDR', $_SERVER)) {
	echo "This script must be run from the command line";
	exit;
}


$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);


/* Start here.
 *
 * Orientation:   180
 *              270 90
 *                 0
 */
$loc = array();
$sx = 0.4;
$sy = 22;
$so = 90;

/* Left: 1-13 */
$loc[] = array('eh'=>0, 'e'=>'1');
$loc[] = array('y'=>-1.2, 'r'=>12);

/* Top 14-35 */
$loc[] = array('x'=>0, 'y'=>-6.0, 'o'=>90);
$loc[] = array('x'=>1.2, 'r'=>21);

/* Row 1 36-61 */
$loc[] = array('reset'=>true, 'x'=>4, 'y'=>5, 'o' => 270);
$loc[] = array('y'=>1.2, 'r'=>12);
$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>-1.2, 'r'=>12);

/* Row 2 61-87 */
$loc[] = array('x'=>3, 'o'=>180);
$loc[] = array('y'=>1.2, 'r'=>12);
$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>-1.2, 'r'=>12);

/* Row 3 88-113 */
$loc[] = array('x'=>3, 'o'=>180);
$loc[] = array('y'=>1.2, 'r'=>12);
$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>-1.2, 'r'=>12);

/* Row 4 114-139 */
$loc[] = array('x'=>3, 'o'=>180);
$loc[] = array('y'=>1.2, 'r'=>12);
$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>-1.2, 'r'=>12);

/* Row 5 140-165 */
$loc[] = array('x'=>3, 'o'=>180);
$loc[] = array('y'=>1.2, 'r'=>12);
$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>-1.2, 'r'=>12);

/* Row 6 166-191 */
$loc[] = array('x'=>3, 'o'=>180, 'e'=>0);
$loc[] = array('y'=>1.2, 'r'=>12);
$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>-1.2, 'r'=>12);

/* Row 7 192-218 */
$loc[] = array('x'=>3, 'o'=>180);
$loc[] = array('y'=>1.2, 'r'=>12);
$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>-1.2, 'r'=>12);

/* Top right 218-220 */
$loc[] = array('x'=>0,  'y'=>'-3.5', 'o'=>-90);
$loc[] = array('x'=>1.2, 'r'=>2);

/* Right wall 221-233 */
$loc[] = array('x'=>1, 'y'=>'1', 'o'=>270);
$loc[] = array('y'=>1.2, 'r'=>12);


/* Order of priority to remvoe floor locations to fit projects 
 * Good for 233 down to 223 projects*/
$priority = array();
$priority[219] = 50;
$priority[233] = 45;
$priority[232] = 40;
$priority[14]  = 35;
$priority[13]  = 30;
$priority[218] = 25;
$priority[220] = 20;
$priority[221] = 15;
$priority[35]  = 10;
$priority[231] = 5;


$objects = array();

$floornumber = 0;
$oid = 0;

$x = $sx;
$y = $sy;
$o = $so;
$has_electricity = 1;
$eh = 1;
foreach($loc as $l) {
	$r = array_key_exists('r', $l) ? $l['r'] : 1;
	if(array_key_exists('eh', $l)) $eh = $l['eh'];
	while($r > 0) {
		$pri = 0;
		if(array_key_exists('reset', $l) && $l['reset'] == true) {
			$x = $l['x'];
			$y = $l['y'];
			$o = $l['o'];
			if(array_key_exists('floornumber',$l)) 
				$floornumber = $l['floornumber'];
			if(array_key_exists('pri',$l)) 	$pri = $l['pri'];
			$has_electricity = array_key_exists('e', $l) ? $l['e'] : 1;
		} else {
			if(array_key_exists('x', $l)) $x += $l['x'];
			if(array_key_exists('y', $l)) $y += $l['y'];
			if(array_key_exists('o', $l)) $o += $l['o'];
			if(array_key_exists('e', $l)) $has_electricity = $l['e'];
			if(array_key_exists('pri', $l)) $pri = $l['pri'];
		}

		$floornumber += 1;
		if(array_key_exists($floornumber, $priority)) {
			$pri = $priority[$floornumber];
		} 
			
		$objects[$oid] = array( 'name' => "Location ".($oid+1),
			'id' => $oid,
			'w' => 1.2,
			'h' => 0.8,
			'x' => $x,
			'y' => $y,
			'o' => $o,
			'type' => 'project',
			'floor_number' => $floornumber,
			'eh_id' => $eh,
			'challenges' => array(),
			'cats' => array(),
			'has_electricity' => $has_electricity,
			'priority' => $pri,
		);
	//rint_r($objects[$oid]);
		$oid++;
		$r--;
	}
}

echo "Defined {$oid} projects.\n";

echo "Saving to database...\n";

$mysqli->query("DELETE FROM exhibithall WHERE type='exhibithall'");
/*
1 	Ballroom 	exhibithall 	0 	0 	16.4 	35 	0 	0 	0 	1,2,3,4,5,6,7	1,2	1
2 	Partyroom 	exhibithall 	0 	0 	8.2 	26.4 	0 	0 	0 	1,2,3,4,5,6,7	2,3	1
*/
$mysqli->query("INSERT INTO exhibithall(`id`,`name`,`type`,`x`,`y`,`w`,`h`,`orientation`,`exhibithall_id`,`floornumber`,`challenges`,`cats`,`has_electricity`,`priority`)
		VALUES('', 'Nest','exhibithall',
			'0','0','38.1','22.2','0','0','0','1,2,3,4,5,6,7','1,2,3','1','0')");
$base_eh_id = $mysqli->insert_id;

/* Save to DB */
$mysqli->query("DELETE FROM exhibithall WHERE type='project'");
foreach($objects as $oid=>$o) {
	$divs = serialize($o['challenges']);
	$cats = serialize($o['cats']);
	$eid = $base_eh_id+$o['eh_id'];
	$mysqli->query("INSERT INTO exhibithall(`id`,`name`,`type`,`x`,`y`,`w`,`h`,`orientation`,`exhibithall_id`,`floornumber`,`challenges`,`cats`,`has_electricity`,`priority`)
		VALUES('', '{$o['name']}','{$o['type']}',
			'{$o['x']}','{$o['y']}','{$o['w']}','{$o['h']}',
			'{$o['o']}',
			'{$eid}',
			'{$o['floor_number']}','$divs','$cats',
			'{$o['has_electricity']}',
			'{$o['priority']}')");
}
echo "Done.\n";

