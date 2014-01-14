<?php
require_once('common.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start();

/* Check access, but skip the expiry check */
sfiab_check_access($mysqli, array(), true);

sfiab_page_begin("Change Password", "welcome");

$error_class ="error_hidden";
$error_msg = "";
if($_SESSION['password_expired']) {
	$error_class = "";
	$error_msg = "Your password has expired, please enter a new password";
}

?>

<div data-role="page" id="change_password"><div data-role="main" class="jqm-content" > 


	<div id="change_password_msg" class="error <?=$error_class?>">
		<?=$error_msg?>
	</div>

	<form action="/" id="change_password_form">
	<div data-role="fieldcontain">
		<label for="register_email">New Password:</label>
		<input data-clear-btn="true" name="email" id="change_pw" value="" type="password">
	</div>
	<div data-role="fieldcontain">
		<label for="register_email2">New Password Again:</label>
		<input data-clear-btn="true" name="email2" id="change_pw2" value="" type="password">
	</div>
	<button type="submit" data-inline="true" data-icon="check" data-theme="g">Change Password</button>
	</form>
	<script src="scripts/sha512.js"></script>
	<script>
		// Attach a submit handler to the form
		$( "#change_password_form" ).submit(function( event ) {
			// Stop form from submitting normally
			event.preventDefault();
			$('#change_password_msg').addClass("error_hidden");
			$('#change_password_msg').addClass("error");
			$('#change_password_msg').removeClass("happy");
			var p1 = $('#change_pw').val();
			var p2 = $('#change_pw2').val();

			if(p1 != p2) {
				$('#change_password_msg').text("Passwords don't match");
				$('#change_password_msg').removeClass("error_hidden");
				return false;
			}
				
			var hash = hex_sha512(p1);
			$.post( "login.php", { p: hash, action: "change_pw" }, function( data ) {
				if(data == '0') {
					$('#change_password_msg').text("Password Changed");
					$('#change_password_msg').removeClass("error_hidden");
					$('#change_password_msg').removeClass("error");
					$('#change_password_msg').addClass("happy");
					/* Success */
				} else {
					$('#change_password_msg').text(data);
					$('#change_password_msg').removeClass("error_hidden");
					return false;
				}
			});
			// Stop any more actions
			return false;
		});
	</script>			
</div></div>

<?php
sfiab_page_end();
?>
