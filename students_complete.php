<?php
require_once('common.inc.php');

$mysqli = sfiab_init(NULL);
$year= $config['year'];

$q = $mysqli->query("SELECT * FROM users WHERE 
					year='$year'
					AND FIND_IN_SET('student',`roles`)>0
					AND enabled = '1'
					AND s_accepted = '1'
					ORDER BY schools_id,lastname
					 ");
					 print($mysqli->error);
?>
<pre>
Students Accepted (registration form received and accepted the fair):
<?php while( ($s = $q->fetch_assoc()) ) { ?>
<?=$s['firstname']?> <?=$s['lastname']?><br/>
<?php } ?>

</pre>


	
	



