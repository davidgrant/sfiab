<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('email.inc.php');
require_once('awards.inc.php');
require_once('committee/judges.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);
sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);

$awards = award_load_all($mysqli);
$projects = projects_load_all($mysqli);
$jteams = jteams_load_all($mysqli);

/* Link div1 jteams to projects */
foreach($jteams as &$jteam) {
	if($jteam['round'] == 1 && $awards[$jteam['award_id']]['type'] == 'divisional') {
		foreach($jteam['project_ids'] as $pid) {
			$projects[$pid]['round_1_jteam'] = &$jteam;
		}
	}
}

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}


$scores = array();
/* Load all scores, index by pid */
foreach($projects as $pid=>&$project) {
	$q = $mysqli->query("SELECT * FROM judging_scores WHERE pid='$pid'");
	if($q->num_rows == 0) {
		$scores[$pid] = array('scientific'=>'', 'originality'=>'', 'communication'=>'', 'total'=>0);
	} else {
		$scores[$pid] = $q->fetch_assoc();
		filter_int($scores[$pid]['scientific']);
		filter_int($scores[$pid]['originality']);
		filter_int($scores[$pid]['communication']);

		$map = array(0=> '', 1=>'1L', 2=>'1M', 3=>'1H',
				4=>'2L', 5=>'2M', 6=>'2H',
				7=>'3L', 8=>'3M', 9=>'3H',
				10=>'4L', 11=>'4M', 12=>'4H');

		$scores[$pid]['scientific'] = $map[$scores[$pid]['scientific']];
		$scores[$pid]['originality'] = $map[$scores[$pid]['originality']];
		$scores[$pid]['communication'] = $map[$scores[$pid]['communication']];
	}
}



function check_score($score) 
{
	$int_score = 0;
	if(strlen($score) != 2) return NULL;

	$n = (int)substr($score, 0, 1);
	$lmh = strtolower(substr($score, 1, 1));

	if($n < 1 || $n > 4) return NULL;
	switch($lmh) {
	case 'l': $x = 1; break;
	case 'm': $x = 2; break;
	case 'h': $x = 3; break;
	default:
		return NULL;
	}

	/* map to 1L=1, 1M=2, ... 4H=12 */
	$int_score = ($n - 1) * 3 + $x;
	return $int_score;
}


switch($action) {
case 'save':
	/* Add a project to a prize */
	$pid = (int)$_POST['pid'];

	$sc = '';
	$or = '';
	$co = '';
	post_text($sc, 'scientific');
	post_text($or, 'originality');
	post_text($co, 'communication');

	if($sc == '' && $or == '' && $co == '') {
		$mysqli->query("DELETE FROM judging_scores WHERE pid='$pid'");
		form_ajax_response(array('status'=>0, 'val'=>array('total'=>"<font color=red>--</font>")));
		exit();
	}


	/* Error check */
	$scientific = check_score($sc);
	$originality = check_score($or);
	$communication = check_score($co);

	if($scientific === NULL || $originality === NULL || $communication === NULL) {
		form_ajax_response(1);
		return;
	}

	$total = ($scientific * 3) + ($originality * 2)  + ($communication * 1);

	/* Does it exist? */
	$q = $mysqli->query("SELECT * FROM judging_scores WHERE pid='$pid'");
	if($q->num_rows != 1) {
		$mysqli->query("DELETE FROM judging_scores WHERE pid='$pid'");
		$mysqli->query("INSERT INTO judging_scores (`pid`,`scientific`,`originality`,`communication`,`total`) VALUES('$pid','0','0','0','0')");

	}
	$mysqli->query("UPDATE judging_scores SET `scientific`='$scientific',`originality`='$originality',
				`communication`='$communication',`total`='$total' WHERE pid='$pid'");

	form_ajax_response(array('status'=>0, 'val'=>array('total'=>"<font color=green>$total</font>")));
	exit();
}

$page_id = 'c_judge_scores';
$help = '<p>Enter Judging Scores';
sfiab_page_begin("Enter Judging Scores", $page_id, $help);
?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<p>You can search by judging team number using #number. e.g., #6 for judging team 6.
	<p>You can reload this page to force a search cache update and search for "missing" to see all the projects with no scores yet

	<ul data-role="listview" data-filter="true" data-filter-placeholder="Search for judging team number, project numbers or titles, or missing..." data-inset="true">
<?php
	foreach($projects as $pid=>&$project) {
		$filter_text = $project['title'].' '.$project['number'];
		$form_id = $page_id.'_'.$pid;

		if(!array_key_exists('round_1_jteam', $project)) {
			print("coulnd't find round 1 jteam for proejct: ".print_r($project));
			continue;
		}
		$jteam = &$project['round_1_jteam'];
		$filter_text .= ' #'.$jteam['num'];
?>
		<li id="project_<?=$pid?>" data-filtertext="<?=$filter_text?>">
			<h3><?=$project['number']?> - <?=$project['title']?></h3>
<?php			form_begin($form_id, 'c_judge_score_entry.php');
			$form_show_data_clear_buttons = false;

			form_hidden($form_id,'pid', $pid);
?>
			<table><tr>
			<td width="30%">
				<?=$jteam['num']?> - <?=$jteam['name']?><br/>
			</td>
			<td width="10%">
				<b><font size=+2><span id="<?=$form_id?>_total">
<?php				$total = $scores[$pid]['total'];
				if($total == 0) { ?>
					<font color=red>--</font>
<?php				} else { ?>
					<font color=green><?=$scores[$pid]['total']?></font>
<?php				} ?>	
				</span></font></b>

			</td>
			<td width="5%">
				<?=form_text($form_id, 'scientific', NULL, $scores[$pid]) ?>
			</td>
			<td width="5%">
				<?=form_text($form_id, 'originality', NULL, $scores[$pid]) ?>
			</td>
			<td width="5%">
				<?=form_text($form_id, 'communication', NULL, $scores[$pid]) ?>
			</td>
			<td>
				<?=form_submit($form_id, 'save', 'Save', 'Saved') ?>
			</td>
			</tr></table>
			<?=form_end($form_id);?>
		</li>
<?php		
		} ?>
	</ul>

</div></div>


<script>
var current_prize_id = -1;


function prize_enable_edit(prize_id)
{
	if(current_prize_id != -1) {
		prize_cancel_edit();
	}
	current_prize_id = prize_id;
	$("#prize_editor_"+prize_id).show();
	$("#prize_editor").detach().appendTo("#prize_editor_"+prize_id);
	$("#prize_editor").show();

	$("#prize_"+prize_id+" tr").each(function( index ) {
		var pid = $(this).attr('id');
		$( this ).append("<td id='X'><a href=\"#\" onclick=\"prize_pdel("+pid+");\">[X]</a></td>");
	});

	return false;
}
function prize_cancel_edit()
{
	$("#prize_editor_"+current_prize_id).hide();
	$("#prize_editor").hide();

	$("#prize_"+current_prize_id+" tr td[id='X']").remove();

	current_prize_id = -1;
	return false;
}



function prize_pdel(id)
{
	$.post('c_award_winners.php', { action: "pdel", prize_id: current_prize_id, pid: id }, function(data) {
		if(data.status == 0) {
			/* Remove from award */
			$("#prize_"+current_prize_id+' tr[id="'+id+'"]').remove();
			$("#prize_count_"+current_prize_id).html(data.happy);
		}
	}, "json");
	return false;
}

function prize_padd()
{
	var id = $("#prize_editor_all_psel option:selected").val();
	$.post('c_award_winners.php', { action: "padd", prize_id: current_prize_id, pid: id }, function(data) {
		if(data.status == 0) {
			/* Add to award list */
			$('#p_tr tr[id="'+id+'"]').appendTo('#prize_'+current_prize_id);
			/* Append the [X] to the new tr in the award table */
			$( "#prize_"+current_prize_id+' tr[id="'+id+'"]').append("<td id='X'><a href=\"#\" onclick=\"prize_pdel("+id+");\">[X]</a></td>");
			$("#prize_count_"+current_prize_id).html(data.happy);
		}
	}, "json");
	return false;
}



</script>

<?php
sfiab_page_end();
?>
