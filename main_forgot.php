<?php
require_once('common.inc.php');
require_once('email.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start();

sfiab_page_begin('Forgot', 'forgot');

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'pw':
	$username = $_POST['user'];

	/* Send password */
	$u = user_load_by_username($mysqli, $username);
	if($u != NULL) {
		$uid = $u['uid'];
		$password = substr(hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true)), 0, 9);
        $password_hash = hash('sha512', $password);
		$mysqli->real_query("UPDATE users SET password='$password_hash',password_expired='1' WHERE uid='{$uid}'");
		email_send($mysqli, "Forgot Password", $uid, array('PASSWORD'=>$password) );
	}
	/* Always send the user to the page even if nothign was sent.. we dont' want
	 * people checking for valid usernames this way */
	header("Location: ".$config['fair_url'].'/index.php#forgot_password_sent');
	exit();

case 'un':
	$email = $mysqli->real_escape_string($_POST['em']);
	$q = $mysqli->query("SELECT username FROM users WHERE `email`='$email' AND `year`='{$config['year']}'");
	while($r = $q->fetch_assoc()) {
		$username = $r['username'];
		$usernames[] = $username;
	}

	if(count($usernames) > 0) {
		$u = user_load_by_username($mysqli, $usernames[0]);
		email_send($mysqli, "Forgot Username", $u['uid'], array('USERNAME_LIST'=>join(', ', $usernames)) );
	}
	header("Location: ".$config['fair_url'].'/index.php#forgot_username_sent');
	exit();

}

?>

<div data-role="page" id="forgot">
	<div data-role="main" class="sfiab_page" > 
	<div id="login_forgot_msg" class="error error_hidden">
	</div>

	<h3>Recover Password</h3>
	Enter your Username and we will email you a link to reset your password
	<form action="main_forgot.php" method="post" data-ajax="false" id="forgot_password_form">
		<div data-role="fieldcontain">
			<label for="fp_un" >Username:</label>
			<input name="user" id="fp_un" value="" placeholder="Username" type="text">
		</div>
		<input type="hidden" name="action" value="pw" />
		<button type="submit" disabled="disabled" id="pw_button" data-inline="true" data-theme="g">Reset My Password</button>
	</form>
<?php	$button_id = 'pw_button'; ?>
	<script>
			$( "#forgot_password_form :input" ).change(function() {
					     $('#<?=$button_id?>').removeAttr('disabled');
						 $('#<?=$button_id?>').text('Reset My Password');
				});

					$( "#forgot_password_form :input" ).keyup(function() {
				               $('#<?=$button_id?>').removeAttr('disabled');
						 $('#<?=$button_id?>').text('Reset My Password');
			   		});

		</script>

	<hr/>

	<h3>Recover Username</h3>
	Enter your email address and we will email you your Username
	<form action="main_forgot.php" method="post" data-ajax="false" id="forgot_username_form">
		<div data-role="fieldcontain">
			<label for="fu_em" >Email:</label>
			<input name="em" id="fu_em" value="" placeholder="Email" type="email">
		</div>			
		<input type="hidden" name="action" value="un" />
		<button type="submit" disabled="disabled" id="un_button" data-inline="true" data-theme="g">Recover My Username</button>
	</form>
<?php   $button_id = 'un_button'; ?>
    <script>
		$( "#forgot_username_form :input" ).change(function() {
			$('#<?=$button_id?>').removeAttr('disabled');
			$('#<?=$button_id?>').text('Recover My Username');
		});

		$( "#forgot_username_form :input" ).keyup(function() {
			$('#<?=$button_id?>').removeAttr('disabled');
			$('#<?=$button_id?>').text('Recover My Username');
		});

	</script>



</div>

<?php
output_end();
?>
