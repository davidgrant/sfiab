<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$page_id = 'c_config_cms';

$cms_pages = array('exhibitordeclaration' => "Signature Form: Exhibitor Declaration", 
		   'parentdeclaration'=> "Signature Form: Parent Declaration",
		   'teacherdeclaration'=> "Signature Form: Teacher Declaration",
		   'regfee'=> "Signature Form: Registration Fee Description",
		   'postamble'=> "Signature Form: Post-Amble",
		   'main'=> "Page: Main Page",
		   'contact_us'=> "Page: Contact Us",
		   'v_main'=> "Page: Volunteer Main Page",
		   's_main'=> "Page: Student Main Page (appears just above additional information)"
		   );

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

	$q = $mysqli->query("SELECT text FROM cms WHERE name='$name'");
	$r = $q->fetch_row();
	$text = $r[0];

	$vals = array();
	$vals['text'] = $text;
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

sfiab_page_begin("Edit Page Text", $page_id);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	form_page_begin($page_id, array());

?>	<h3>Page Text and Signature Form Text</h3> 
<?php	
	$form_id = $page_id.'_form';
	$q = $mysqli->query("SELECT text FROM cms WHERE name='main'");
	$r = $q->fetch_row();
	$text = $r[0];

	form_begin($form_id, 'c_config_cms.php');
	$v = 'main';
	form_select($form_id, 'name', "Page/Item", $cms_pages, $v);
	print("<hr/>");
	form_textbox($form_id, "text", "Text", $text);
	form_submit($form_id, 'save', 'Save', 'Saved');
?>	
<?php
	form_end($form_id);
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
