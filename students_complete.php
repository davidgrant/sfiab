<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('committee/students.inc.php');

$mysqli = sfiab_init(array());
$year= $config['year'];

$q = $mysqli->query("SELECT * FROM users WHERE 
					year='$year'
					AND FIND_IN_SET('student',`roles`)>0
					AND enabled = '1'
					ORDER BY schools_id,lastname
					 ");
					 print($mysqli->error);
?>
<pre>
Students with a Completed Registration:
<?php while( ($s = $q->fetch_assoc()) ) { ?>
<?=$s['firstname']?> <?=$s['lastname']?><br/>
<?php } ?>

</pre>


	
	



