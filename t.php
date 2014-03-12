<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);


// set the default timezone to use. Available since PHP 5.1
date_default_timezone_set('PST8PDT');

$t = @time(NULL);
$now = @date( 'Y-m-d H:i:s', $t);
print($now);


//$mysqli->real_query('ALTER TABLE `projects` ADD `num_mentors` INT( 4 ) NOT NULL ');

#sfiab_session_start($mysqli);

#print('<pre>');
#print_r($_SESSION);
#print('</pre>');
#print phpinfo();




sfiab_page_end();
?>
