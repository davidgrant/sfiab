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

/* Left: 1-16, no electricity */
$loc[] = array('eh'=>0, 'e'=>'0');
$loc[] = array('y'=>-1.2, 'r'=>12);
$loc[] = array('y'=>-1.2*2);
$loc[] = array('y'=>-1.2, 'r'=>2);

/* Top 17-35 */
$loc[] = array('x'=>3, 'y'=>-1.2, 'o'=>90);
$loc[] = array('x'=>1.2, 'r'=>18);

/* Row 1 36-61 */
$loc[] = array('reset'=>true, 'x'=>4, 'y'=>18, 'o' => 270, 'e'=>1);
$loc[] = array('y'=>-1.2, 'r'=>12);
$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>1.2, 'r'=>12);

/* Row 2 61-87 */
$loc[] = array('x'=>3, 'o'=>180);
$loc[] = array('y'=>-1.2, 'r'=>12);
$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>1.2, 'r'=>12);

/* Row 3 61-87 */
$loc[] = array('x'=>3, 'o'=>180);
$loc[] = array('y'=>-1.2, 'r'=>12);
$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>1.2, 'r'=>12);

/* Row 4 61-87 */
$loc[] = array('x'=>3, 'o'=>180);
$loc[] = array('y'=>-1.2, 'r'=>12);
$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>1.2, 'r'=>12);

/* Row 5 140-165 */
$loc[] = array('x'=>3, 'o'=>180);
$loc[] = array('y'=>-1.2, 'r'=>12);
$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>1.2, 'r'=>12);

/* Row 6 166-193 */
$loc[] = array('x'=>3, 'y'=>1.2, 'o'=>180);
$loc[] = array('y'=>-1.2, 'r'=>13);
$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>1.2, 'r'=>13);

/* Row 7 194-121 */
$loc[] = array('x'=>3, 'o'=>180);
$loc[] = array('y'=>-1.2, 'r'=>13);
$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>1.2, 'r'=>13);

/* Right wall */
$loc[] = array('x'=>3, 'y'=>'-1', 'o'=>180, 'e'=>0);
$loc[] = array('y'=>-1.2, 'r'=>12);

/* Top right 235-237 */
$loc[] = array('x'=>-2, 'y'=>'-2.5', 'o'=>90);
$loc[] = array('x'=>-1.2, 'r'=>2);


$objects = array();

$floornumber = 1;
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
		if(array_key_exists('reset', $l) && $l['reset'] == true) {
			$x = $l['x'];
			$y = $l['y'];
			$o = $l['o'];
			if(array_key_exists('floornumber',$l)) 
				$floornumber = $l['floornumber'];
			$has_electricity = array_key_exists('e', $l) ? $l['e'] : 1;
		} else {
			if(array_key_exists('x', $l)) $x += $l['x'];
			if(array_key_exists('y', $l)) $y += $l['y'];
			if(array_key_exists('o', $l)) $o += $l['o'];
			if(array_key_exists('e', $l)) $has_electricity = $l['e'];
		}

		$objects[$oid] = array( 'name' => "Location ".($oid+1),
			'id' => $oid,
			'w' => 1.2,
			'h' => 0.8,
			'x' => $x,
			'y' => $y,
			'o' => $o,
			'type' => 'project',
			'floor_number' => $floornumber++,
			'eh_id' => $eh,
			'challenges' => array(),
			'cats' => array(),
			'has_electricity' => $has_electricity,
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
$mysqli->query("INSERT INTO exhibithall(`id`,`name`,`type`,`x`,`y`,`w`,`h`,`orientation`,`exhibithall_id`,`floornumber`,`challenges`,`cats`,`has_electricity`)
		VALUES('', 'Nest','exhibithall',
			'0','0','38.1','22.2','0','0','0','1,2,3,4,5,6,7','1,2,3','1')");
$base_eh_id = $mysqli->insert_id;

/* Save to DB */
$mysqli->query("DELETE FROM exhibithall WHERE type='project'");
foreach($objects as $oid=>$o) {
	$divs = serialize($o['challenges']);
	$cats = serialize($o['cats']);
	$eid = $base_eh_id+$o['eh_id'];
	$mysqli->query("INSERT INTO exhibithall(`id`,`name`,`type`,`x`,`y`,`w`,`h`,`orientation`,`exhibithall_id`,`floornumber`,`challenges`,`cats`,`has_electricity`)
		VALUES('', '{$o['name']}','{$o['type']}',
			'{$o['x']}','{$o['y']}','{$o['w']}','{$o['h']}',
			'{$o['o']}',
			'{$eid}',
			'{$o['floor_number']}','$divs','$cats',
			'{$o['has_electricity']}')");
}
echo "Done.\n";

