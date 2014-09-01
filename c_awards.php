<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('committee/judges.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$page_id = 'c_awards';

sfiab_page_begin("Awards", $page_id);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php	form_page_begin($page_id, array());

?>	
	<h3>Awards</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_awards_list.php" data-rel="external" data-ajax="false">Award List / Editor</a></li>
	<li><a href="c_user_list.php?roles[]=sponsor" data-rel="external" data-ajax="false">Sponsor Editor</a></li>
	</ul>

	<h3>Winners</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_award_winners.php" data-rel="external" data-ajax="false">Enter Winning Projects</a></li>
	</ul>

	<h3>External</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_award_cwsf.php" data-rel="external" data-ajax="false">Upload CWSF Winners</a></li>
	</ul>

</div></div>
	

<?php
sfiab_page_end();
?>
