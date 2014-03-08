<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('committee/email_lists.inc.php');
require_once('email.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);

$page_id = 'c_communication_queue';
$help = '<p>Communication';


sfiab_page_begin("Email Queue", $page_id, $help);
?>


<div data-role="page" id="<?=$page_id?>_queue"><div data-role="main" class="sfiab_page" > 
	<h3>Current Email Queue</h3>
<?php
	
?>
</div></div>


<?php

sfiab_page_end();
?>
