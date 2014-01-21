<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start();

/* Check access, but skip the expiry check */
sfiab_check_access($mysqli, array(), true);
$u = user_load($mysqli);

sfiab_page_begin("Account", "account");


$fields = incomplete_fields($mysqli, 'account', $u, true);

?>

<div data-role="page" id="change_password"><div data-role="main" class="sfiab_page" > 
<?php
	$form_id = 'account_change_password_form';
	
	//form_messages($form_id, "Your password has expired, please enter a new password");
	form_begin($form_id, 'login.php');
	$pw1 = '';
	$pw2 = '';
	form_text($form_id, 'pw1','New Password',$pw1, 'password');
	form_text($form_id, 'pw2','New Password Again',$pw2, 'password');
	form_submit($form_id, 'change_pw', "Change Password", 'Password Saved');
	form_end($form_id);
/*
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
					window.location = "<?=$config['fair_url']?>/main.php";
:wq
/*
					$('#change_password_msg').text("Password Changed");
					$('#change_password_msg').removeClass("error_hidden");
					$('#change_password_msg').removeClass("error");
					$('#change_password_msg').addClass("happy");*/
					/* Success 
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
*/
?>

</div></div>

<?php
sfiab_page_end();
?>
