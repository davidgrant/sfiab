<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);


// set the default timezone to use. Available since PHP 5.1
date_default_timezone_set('PST8PDT');


$fstart = "2014-04-12";
$s = date('Y-m-d H:i:s', strtotime($fstart));

print("Fair start: $fstart, back and forth: $s\n");

$fstart = "2014-04-12";
$s = date('Y-m-d H:i:s', strtotime($fstart)+ (120 * 60)) ;

print("Fair start: $fstart, back and forth: $s\n");


$d = date_parse($fstart);
print_r($d);


$q = $mysqli->query('SHOW CREATE TABLE `users`');
while($r = $q->fetch_assoc()) {
print "newline\n";
	print_r($r);
	}


#sfiab_session_start($mysqli);

#print('<pre>');
#print_r($_SESSION);
#print('</pre>');
#print phpinfo();




sfiab_page_end();
?>
