<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$page_id = 'c_config_cms';

/* Load CMS pages */
$cms_pages = array();
$q = $mysqli->query("SELECT * FROM `cms` ORDER BY `type`,`name`");
while($r = $q->fetch_assoc()) {
	$cms_pages[$r['name']] = $r['head'];
}

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}
switch($action) {
case 'get':
	$name = $_POST['name'];
	if(!array_key_exists($name, $cms_pages)) {
		exit();
	}

	$q = $mysqli->query("SELECT `text`,`desc`,`type` FROM cms WHERE name='$name'");
	$r = $q->fetch_row();

	$vals = array();
	$vals['text'] = $r[0];
	$vals['desc'] = $r[1];

	form_ajax_response(array('status'=>0, 'val'=>$vals));
	exit();

case 'save':
	$name = $_POST['name'];
	if(!array_key_exists($name, $cms_pages)) {
		exit();
	}

	$text = '';
	post_text($text,'text');

	$text = $mysqli->real_escape_string($text);

	$mysqli->real_query("UPDATE cms SET `text`='$text', `language`='en', `use`='1' WHERE `name`='$name'");
	form_ajax_response(0);
	exit();

}

sfiab_page_begin($u, "Edit Page Text", $page_id);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	form_page_begin($page_id, array());

?>	<h3>Page Text and Signature Form Text</h3> 
<?php	
	$form_id = $page_id.'_form';
	$q = $mysqli->query("SELECT `text`,`desc` FROM cms WHERE name='main'");
	$r = $q->fetch_row();
	$text = $r[0];
	$desc = $r[1];

	form_begin($form_id, 'c_config_cms.php');
	$v = 'main';
	form_select($form_id, 'name', "Page/Item", $cms_pages, $v);
?>
	<hr/>
	<div data-role="collapsible" data-collapsed="true" data-iconpos="right" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" >
	<h3>Email Replacement Keys</h3>
	<?=print_replace_vars_table($u);?>
	</div>
<?php	
	form_textbox($form_id, "text", "Text", $text);
	form_label($form_id, "desc", "Description", $desc);
	form_submit($form_id, 'save', 'Save', 'Saved');
	form_end($form_id);

?>
	<hr/>
	<div data-role="collapsible" data-collapsed="true" data-iconpos="right" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" >
		<h3>Download Sample Signature Form / Electronic Signature Pages</h3>
<?php		$cats = categories_load($mysqli);
		/* Highest grade by default */
		$max_grade = 0;
		$max_cat = (int)0;
		foreach($cats as $cat_id=>$c) {
			if($c['max_grade'] > $max_grade) {
				$max_grade = $c['max_grade'];
				$max_cat = (int)$c['cat_id'];
			}
		}

		$sig_form_id = $page_id.'_sig_form';
		form_begin($sig_form_id, 's_signature.php', false, false);
		form_hidden($sig_form_id, 'pdf', 1);
		form_radio_h($sig_form_id, 'cat_id', "Category", $cats, $max_cat);
		$d = 1;
		form_radio_h($sig_form_id, 'num_students', "Number of Students", array(1=>'1', 2=>'2') , $d);
		form_button($sig_form_id, 'sample', "Download Sample Paper Signature Form");
		form_end($sig_form_id); ?>
		<br/>
		<a href="signature.php?k=sample_student" data-inline="true" data-role="button" data-theme="g" data-ajax="false">View Sample Student Electronic Signature Page</a>
		<a href="signature.php?k=sample_parent" data-inline="true" data-role="button" data-theme="g" data-ajax="false">View Sample Parent/Guardian Electronic Signature Page</a>
		<a href="signature.php?k=sample_teacher" data-inline="true" data-role="button" data-theme="g" data-ajax="false">View Sample Teacher Electronic Signature Page</a>
	</div>

<?php
?>

</div></div>
	
<script>
	$( "#<?=$form_id?>_name" ).change(function() {
		var new_name = $("#<?=$form_id?>_name option:selected").val();
		$.post('c_config_cms.php', { action: "get", name: new_name }, function(data) {
			sfiab_form_update_vals("<?=$form_id?>", data.val);
			/* keyup hack forces the textbox to resize */
			$("#<?=$form_id?>_text").keyup();
		}, "json");
	});
</script>



<?php
sfiab_page_end();
?>
