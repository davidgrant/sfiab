<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');

$mysqli = sfiab_init('committee');

$page_id = "c_main";

$u = user_load($mysqli);

$help = '
<ul><li><b>nothing</b> - no help yet.
</ul>';

sfiab_page_begin("Committee Main", 'c_main', $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<h3>Hello <?=$u['firstname']?></h3>


	<h3>Sanity Checks</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_judge_sanity.php" data-rel="external" data-ajax="false">Display Judging Sanity Checks</a></li>
	</ul>

	<h3>Committee Members</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="index.php#register" data-rel="external" data-ajax="false">Invite a Committee Member</a></li>
	<li><a href="c_user_list.php?roles[]=committee&years=-1" data-rel="external" data-ajax="false">Committee List / Editor</a></li>
	</ul>

</div></div>

<?php
sfiab_page_end();
?>
