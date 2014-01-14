<?php
require_once('common.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start();

$page = $config['fair_url'] . '/';
if(sfiab_user_is_a('student')) {
	$page .= 'student_main.php';
} else {
	$page .= 'index.php';
}

header("Location: $page");

/*
<html><head>
<script>
	window.location = "<?=$page?>";
</script>
</head></html>
*/
?>

