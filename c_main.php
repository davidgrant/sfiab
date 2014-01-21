<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('committee'));

$page_id = "c_main";

$u = user_load($mysqli);

$help = '
<ul><li><b>nothing</b> - no help yet.
</ul>';

sfiab_page_begin("Committee Main", 'c_main', $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<h3>Hello <?=$u['firstname']?></h3>

	This is the incomplete committee page.  All I've implemented is the awards editor.
	<hr/>

</div></div>

<?php
sfiab_page_end();
?>
