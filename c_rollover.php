<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('db.inc.php');

$mysqli = sfiab_init('committee');


$page_id = 'c_rollover';

$help = '
<ul>
</ul>';

sfiab_page_begin($u, "Rollover Fair Year", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

if($action == 'rollover') {
	/* Check the current year with the post value, if they dont' match, don't do a rollover (someone just realoaded the rollover page) */
	$curr_year = (int)$_POST['current_year'];
	if($curr_year != $config['year']) {
		$action = '';
	}
}

switch($action) {
case 'rollover':
	$new_year = (int)$_POST['year'];
	db_roll($mysqli, $new_year);
?>
	<a href="c_rollover.php" data-ajax="false" data-role="button" data-icon="back" data-theme="g" >All Done.  Go Back</a>
<?php

	break;

default:

?>
	<p>Rolling over the fair year prepares the fair for a new year.  The
	current year is <?=$config['year']?>.  The new year is whatever you'd like,
	usually the current year + 1, so <?=$config['year']+1?>.  The rollover process
	copies data for the new year, leaving a copy in the
	current year so that reports generated for past years make sense and
	reflect the data as it was.  

	<p>The following data is duplicated for the new year:  Awards and Prizes, Categories, Challenges, Configuration Dates, Schools, Sponsors, Timeslots, and Tours
	<ul>
	<li>52 weeks (for each year) are added to the Configuration Dates so they fall on the same day of the week after the rollover.
	</ul>

	<p>The following data doesn't change year-over-year, so is not copied: CMS (page contents), Configuration (all except Dates), Emails, Reports

	<p>The following data is NOT rolled over because it is not needed for a new year: Judging Assignments, Judging Teams, Projects

	<p>Users are a special case.  Users are copied to the new year one-by-one when a user logs in (provided the last activity on the account wasn't to delete or disable it).


<?php
	$form_id = $page_id.'_form';
	form_page_begin($page_id, array());

	$new_year = $config['year'] + 1;
	/* No ajax on this form */
	form_begin($form_id, 'c_rollover.php', false, false);
	form_label($form_id, 'cyear', "Current Fair Year", $config['year']);
	form_hidden($form_id, 'current_year', $config['year']);
	form_int($form_id, 'year', "New Fair Year", $new_year, $new_year, 9999);

	form_button($form_id, 'rollover', 'Rollover Fair Year', 'g', 'check', 'Really rollover? This cannot be undone' );
	form_end($form_id);
	break;

}		
?>

</div></div>
	
<?php
sfiab_page_end();
?>
