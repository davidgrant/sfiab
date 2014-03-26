<?php
require_once('common.inc.php');

if(array_key_exists('SERVER_ADDR', $_SERVER)) {
	echo "This script must be run from the command line";
	exit;
}


$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);





/* Create ballroom projects */
$loc = array();
$sx = 0.4;
$sy = 5 + 0.6+18;
$so = 90;

/* Left: 1-12, no electricity */
$loc[] = array('eh'=>1, 'e'=>0);
$loc[] = array('y'=>-1.2, 'r'=>3);
$loc[] = array('y'=>-1.2*3);
$loc[] = array('y'=>-1.2, 'r'=>3);
$loc[] = array('y'=>-1.2*3);
$loc[] = array('y'=>-1.2, 'r'=>3);

/* Row 1: 13-30, 31-48: this can have 4 more projects at the bottom */
$loc[] = array('x'=>3, 'y'=>1.2, 'o'=>180, 'e'=>1);
$loc[] = array('y'=>1.2, 'r'=>17);

$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>-1.2, 'r'=>17);

/* Row 2: 49-68, 69-88 */
$loc[] = array('x'=>3, 'o'=>180);
$loc[] = array('y'=>1.2, 'r'=>19);

$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>-1.2, 'r'=>19);

/* Row 3: 89-108, 109-128 */
$loc[] = array('x'=>3, 'o'=>180);
$loc[] = array('y'=>1.2, 'r'=>19);

$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>-1.2, 'r'=>19);

/* Right: 129-139  (4, 3, 2, 2)*/
$loc[] = array('x'=>3, 'o'=>180);
$loc[] = array('y'=>1.2, 'r'=>3);
$loc[] = array('y'=>1.2*3);
$loc[] = array('y'=>1.2, 'r'=>2);
$loc[] = array('y'=>1.2*3);
$loc[] = array('y'=>1.2, 'r'=>1);
$loc[] = array('y'=>1.2*5);
$loc[] = array('y'=>1.2, 'r'=>1);

/* Bottom: 140-145: (6, 4), 2014 only using first 6, (146,147 are spares) */
$loc[] = array('reset'=>true, 'x'=>13.4, 'y'=>34.6, 'o' => 180);
$loc[] = array('x'=>-1.2, 'r'=>5);
//$loc[] = array('x'=>-2.4);
//$loc[] = array('x'=>-1.2, 'r'=>1); // Can put up to 4 along here


/* And the party room */
/* Right: 175-194 */
$loc[] = array('reset'=>true, 'x'=>7.8, 'y'=>26.2, 'o' => 270, 'eh'=>2,'floornumber'=>175);
$loc[] = array('y'=>-1.2, 'r'=>19);

/* middle: 195-212, 213-230 */
$loc[] = array('x'=>-3.3, 'y'=>1.2, 'o'=>-180);
$loc[] = array('y'=>1.2, 'r'=>17);
$loc[] = array('x'=>-.8, 'o'=>180);
$loc[] = array('y'=>-1.2, 'r'=>17);

/* Left: 231-250, 2014, only use up to 247, 249,250 are spares */

$loc[] = array('x'=>-3.3, 'y'=>-1.2, 'o'=>-180);
$loc[] = array('y'=>1.2, 'r'=>17);

/* Bottom, up to 4 */
/*
$loc[] = array('reset'=>true, 'x'=>2, 'y'=>25.8, 'o' => 180 );
$loc[] = array('x'=>1.2, 'r'=>3);
*/

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
/* Save to DB */
$mysqli->query("DELETE FROM exhibithall WHERE type='project'");
foreach($objects as $oid=>$o) {
	$divs = serialize($o['challenges']);
	$cats = serialize($o['cats']);
	$mysqli->query("INSERT INTO exhibithall(`id`,`name`,`type`,`x`,`y`,`w`,`h`,`orientation`,`exhibithall_id`,`floornumber`,`challenges`,`cats`,`has_electricity`)
		VALUES('', '{$o['name']}','{$o['type']}',
			'{$o['x']}','{$o['y']}','{$o['w']}','{$o['h']}',
			'{$o['o']}',
			'{$o['eh_id']}',
			'{$o['floor_number']}','$divs','$cats',
			'{$o['has_electricity']}')");
}
echo "Done.\n";

