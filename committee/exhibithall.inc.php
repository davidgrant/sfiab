<?
require_once('../common.inc.php');
if($_SERVER['SERVER_ADDR']) {
	echo "This script must be run from the command line";
	exit;
}



/* Create ballroom projects */
$loc = array();
$sx = 0.4;
$sy = 5 + 0.6+18;
$so = 90;

$loc[] = array('eh'=>1);
$loc[] = array('y'=>-1.2, 'r'=>3);
$loc[] = array('y'=>-1.2*3);
$loc[] = array('y'=>-1.2, 'r'=>3);
$loc[] = array('y'=>-1.2*3);
$loc[] = array('y'=>-1.2, 'r'=>3);

$loc[] = array('x'=>3, 'y'=>1.2, 'o'=>180);
$loc[] = array('y'=>1.2, 'r'=>17);

$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>-1.2, 'r'=>17);

$loc[] = array('x'=>3, 'o'=>180);
$loc[] = array('y'=>1.2, 'r'=>17);

$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>-1.2, 'r'=>17);

$loc[] = array('x'=>3, 'o'=>180);
$loc[] = array('y'=>1.2, 'r'=>17);

$loc[] = array('x'=>.8, 'o'=>-180);
$loc[] = array('y'=>-1.2, 'r'=>17);

$loc[] = array('x'=>3, 'y'=>-1.2, 'o'=>180);
$loc[] = array('y'=>1.2, 'r'=>17);
//$loc[] = array('x'=>.8, 'o'=>-180);

/*
$loc[] = array('y'=>1.2, 'r'=>3);
$loc[] = array('y'=>1.2*3);
$loc[] = array('y'=>1.2, 'r'=>2);
$loc[] = array('y'=>1.2*3);

$loc[] = array('y'=>1.2, );
$loc[] = array('y'=>1.2*5);
$loc[] = array('y'=>1.2);
*/

/* Along the bottom */
/*$loc[] = array('reset'=>true, 'x'=>13.4, 'y'=>34.6, 'o' => 180);
$loc[] = array('x'=>-1.2, 'r'=>5);
$loc[] = array('x'=>-2.4);
$loc[] = array('x'=>-1.2, 'r'=>3);
*/

/* And the party room */
$loc[] = array('reset'=>true, 'x'=>7.8, 'y'=>26.2, 'o' => 270, 'eh'=>2,'floornumber'=>175);
$loc[] = array('y'=>-1.2, 'r'=>19);

$loc[] = array('x'=>-3.3, 'y'=>1.2, 'o'=>-180);
$loc[] = array('y'=>1.2, 'r'=>17);

$loc[] = array('x'=>-.8, 'o'=>180);
$loc[] = array('y'=>-1.2, 'r'=>17);

$loc[] = array('x'=>-3.3, 'y'=>-1.2, 'o'=>-180);
$loc[] = array('y'=>1.2, 'r'=>17);

/* Along the bottom */
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
$eh = 1;
foreach($loc as $l) {
	$r = $l['r'];
	if($l['eh'] != 0) $eh = $l['eh'];
	if($r == 0) $r = 1;
	while($r > 0) {
		if($l['reset'] == true) {
			$x = $l['x'];
			$y = $l['y'];
			$o = $l['o'];
			if(array_key_exists('floornumber',$l)) 
				$floornumber = $l['floornumber'];
		} else {
			$x += $l['x'];
			$y += $l['y'];
			$o += $l['o'];
		}

		$objects[$oid] = array( 'name' => "Location $oid",
			'id' => $oid,
			'w' => 1.2,
			'h' => 0.8,
			'x' => $x,
			'y' => $y,
			'o' => $o,
			'type' => 'project',
			'floor_number' => $floornumber++,
			'eh_id' => $eh,
			'divs' => array(),
			'cats' => array(),
			'has_electricity' => 'yes',
		);
	//rint_r($objects[$oid]);
		$oid++;
		$r--;
	}
}

echo "Defined {$oid} projects.\n";

echo "Saving to database...\n";
/* Save to DB */
mysql_query("DELETE FROM exhibithall WHERE type='project'");
foreach($objects as $oid=>$o) {
	$divs = serialize($o['divs']);
	$cats = serialize($o['cats']);
	mysql_query("INSERT INTO exhibithall(`id`,`name`,`type`,`x`,`y`,`w`,`h`,`orientation`,`exhibithall_id`,`floornumber`,`divs`,`cats`,`has_electricity`)
		VALUES('', '{$o['name']}','{$o['type']}',
			'{$o['x']}','{$o['y']}','{$o['w']}','{$o['h']}',
			'{$o['o']}',
			'{$o['eh_id']}',
			'{$o['floor_number']}','$divs','$cats',
			'{$o['has_electricity']}')");
}
echo "Done.\n";

