<?php
require_once('common.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start();

sfiab_page_begin("Winners", "winners");

?>
<div data-role="page" id="winners">
	<div data-role="main" class="sfiab_page" > 
		Winners - Under Construction
	</div>

</div>

<?php

sfiab_page_end();


?>
