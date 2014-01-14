<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli);

print('<pre>');
print_r($_SESSION);
print('</pre>');
print phpinfo();


sfiab_page_end();
?>
