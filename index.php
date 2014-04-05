<?php

if(array_key_exists('p', $_GET)) {
	header("location: project_summary.php?p={$_GET['p']}");
	exit();
}

require_once('common.inc.php');
require_once('form.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start();

sfiab_page_begin('Welcome', 'welcome');



//<script>
//	$( document ).on("pagebeforeshow", "#welcome, #important_dates, #committee", function() { $('#leftnav_main').collapsible( "expand" ); });
//</script>

?>


<div data-role="page" id="welcome" class="menu_main">
	<div data-role="main" class="sfiab_page" > 
		<?=cms_get($mysqli, 'main');?>
	</div>
</div>

<div data-role="page"  id="important_dates" class="menu_main">
	<div data-role="main" class="sfiab_page" > 
		<table>
		<tr><td>Student Registration Opens:</td><td>January 28, 2014</td></tr>
		<tr><td>Judge Registration Opens:</td><td>January 28, 2014</td></tr>
		<tr><td>Student Registration Closes:</td><td>March 11, 2014</td></tr>
		<tr><td>Judge Registration Closes:</td><td>March 31, 2014</td></tr>
		<tr><td>Days of the Fair:</td><td>April 10-12, 2014</td></tr>
		<tr><td>Winners Posted on Website:</td><td>April 13, 2014</td></tr>
		</table>
	</div>
</div>


<div data-role="page" id="committee">
	<div data-role="main" class="sfiab_page" > 
		Under construction, coming soon.  
	</div>

</div>

<div data-role="page" id="register">
	<div data-role="main" class="sfiab_page" > 
	<div id="register_msg" class="error error_hidden">
	</div>
	<div>
	To register at the fair, please fill in the information below.  A temporary password will be emailed to the address you provide.  You will be asked to change your password after logging in the first time.
	</div>

	<form action="/" id="register_form">
	<div data-role="fieldcontain">
		<label for="register_firstname">First Name:</label>
		<input data-clear-btn="true" name="firstname" id="register_firstname" value="" type="text" data-icon="check" >
	</div>
	<div data-role="fieldcontain">
		<label for="register_lastname">Last Name:</label>
		<input data-clear-btn="true" name="lastname" id="register_lastname" value="" type="text">
	</div>
<?php
	$s_disabled = '';
	$s_text = '';
	$j_disabled = '';
	$j_text = '';
	if(sfiab_registration_is_closed(NULL, 'student')) {
		/* Only disable if user is not a committeemember */
		if(!sfiab_user_is_a('committee')) 
			$s_disabled = 'disabled="disabled"';
		$s_text = ' (registration closed)';
	}
	if(sfiab_registration_is_closed(NULL, 'judge')) {
		/* Only disable if user is not a committeemember */
		if(!sfiab_user_is_a('committee')) 
			$j_disabled = 'disabled="disabled"';
		$j_text = ' (registration closed)';
	}
?>

	<div data-role="fieldcontain">
		<label for="register_as" class="select">Register as a:</label>
		<select name="register_as" id="register_as" <?php/*<!--data-native-menu="false"*/?> >
		    <option value="student" <?=$s_disabled?> >Student<?=$s_text?></option>
		    <option value="judge" <?=$j_disabled?> >Judge<?=$j_text?></option>
		    <option value="volunteer">Volunteer</option>
<?php		 if(sfiab_user_is_a('committee')) { 
//		    <option value="teacher">Teacher</option>
?>
		    	<option value="committee">Committee Member</option>
<?php		} ?>
		
		</select>
	</div>

	<div data-role="fieldcontain">
		<label for="register_email">Email:</label>
		<input data-clear-btn="true" name="email" id="register_email" value="" type="email">
	</div>
	<div data-role="fieldcontain">
		<label for="register_email2">Email Again:</label>
		<input data-clear-btn="true" name="email2" id="register_email2" value="" type="email">
	</div>
	<div data-role="fieldcontain">
		<label for="register_username">Username:</label>
		<input data-clear-btn="true" name="register_username" pattern="[-_A-Za-z0-9@\.]*" id="register_username" value="" type="text">
	</div>
	<button type="submit" data-inline="true" data-icon="check" data-theme="g">Register</button>
	</form>
	<script>
		// Attach a submit handler to the form
		$( "#register_form" ).submit(function( event ) {
			// Stop form from submitting normally
			event.preventDefault();
			$('#register_msg').addClass("error_hidden");
			// Get some values from elements on the page:
			var u = $('#register_username').val();
			var e = $('#register_email').val();
			var e2 = $('#register_email2').val();
			var a = $('#register_as').val();
			var fn = $('#register_firstname').val();
			var ln = $('#register_lastname').val();

			if(e != e2) {
				$('#register_msg').text("Email address doesn't match");
				$('#register_msg').removeClass("error_hidden");
				return false;
			}
				
			$.post( "login.php", { username: u, email: e, as: a, action: "register", firstname: fn, lastname: ln }, function( data ) {
				if(data == '0') {
					/* Success */
					$.mobile.changePage('#register_sent');
				} else {
					/* Error */
					$('#register_msg').text(data);
					$('#register_msg').removeClass("error_hidden");
					$.mobile.changePage('#register', { allowSamePageTransition: true } );
				}
				return false;
			});
			// Stop any more actions
			return false;
		});
	</script>			
	</div>
</div>

<div data-role="page" id="register_sent">
	<div data-role="main" class="sfiab_page" > 
	<div><p>An email with a temporary password was sent to your email
	address.  Please use this temporary password to login to your account.
	Click on the button below to proceed to the login page.

	<p>If you need the password sent again, use the password recovery link
	on the login page and we will mail you a new temporary password.

	<p>If the email doesn't appear in a few minutes, be sure to check your 
	Junk or Spam mail folder too.  We have recent reports that quite a few emails
	to Hotmail MSN  email addresses are going to spam folders.

	<p>If you think your email address is wrong, register again with
	the same Username and your old registration email address
	will be replaced with the new one.  A new temporary
	password will also be sent to the new email address.

	</div>
	<a href="#login" data-role="button">Proceed to Login</a>
	</div>
</div>

<div data-role="page" id="forgot_password_sent">
	<div data-role="main" class="sfiab_page" > 
	<div><p>An email with a temporary password was sent to your email
	address.  Click on the button below to proceed to the login page.

	<p>
	<p>If the email doesn't appear in a few minutes, be sure to check your 
	Junk or Spam mail folder too.  We have recent reports that quite a few emails
	to hotmail.com and msn.com email addresses are going to spam folders.
	
	</div>
	<a href="#login" data-role="button">Proceed to Login</a>
	</div>
</div>

<div data-role="page" id="forgot_username_sent">
	<div data-role="main" class="sfiab_page" > 
	<div><p>An email with your username was sent to your email
	address.  If you have also forgotten your password, you can use the link
	on the login page again to reset your password.

	<p>If the email doesn't appear in a few minutes, be sure to check your 
	Junk or Spam mail folder too.  We have recent reports that quite a few emails
	to hotmail.com and msn.com email addresses are going to spam folders.

	Click on the button below to proceed to the login page.

	</div>
	<a href="#login" data-role="button">Proceed to Login</a>
	</div>
</div>



<div data-role="page" id="contact">
	<div data-role="main" class="sfiab_page" > 
		Under construction, coming soon.  In the meantime, just email our committee chair.  Brian Le (chair@gvrsf.ca).
	</div>
</div>

<div data-role="page" id="login">
	<div data-role="main" class="sfiab_page" > 
	<div id="login_msg" class="error error_hidden">
	</div>

	<div class="info"><p>Note: We have had reports of users using Safari on
	MacOS being unable to login.  If your password suddenly stops working,
	you're not going crazy.  Try using Google Chrome or Firefox on MacOS.  We
	have our best trained monkeys trying to resolve the problem.
	</div>

	<form action="/" id="login_form">
		<div data-role="fieldcontain">
			<label for="un" >Username:</label>
			<input name="user" id="un" value="" placeholder="Username" type="text">
		</div>			
		<div data-role="fieldcontain">
			<label for="pw" >Password:</label>
			<input name="pass" id="pw" value="" placeholder="Password" type="password">
		</div>			
		<button type="submit" data-inline="true" data-icon="check" data-theme="g">Log in</button>
	</form>

	<p>If you can't remember your username or password, <a href="<?=$config['fair_url']?>/main_forgot.php" data-ajax="false">click here</a> to recover them.
	<script src="scripts/sha512.js"></script>
	<script>
		// Attach a submit handler to the form
		$( "#login_form" ).submit(function( event ) {
			// Stop form from submitting normally
			event.preventDefault();
			$('#login_msg').addClass("error_hidden");
			var u = $('#un').val();
			var p = $('#pw').val();

			$.post( "login.php", { username: u, action: "salt" }, function( data ) {
				var hash = hex_sha512(hex_sha512(p) + data);

				// Send the data using post
				$.post( "login.php", { username: u, password: hash, action: "login" }, function( data ) {
					if(data.s == '0') {
						/* Success */
						window.location.replace(data.m);
					} else if(data.s == '3') {
						/* Account hasn't been activated yet */
						$.mobile.changePage('#register_send');
					} else {
						$('#login_msg').text(data.m);
						$('#login_msg').removeClass("error_hidden");
						$.mobile.changePage('#login', { allowSamePageTransition: true } );
					}
				}, 'json');
			});
			return false;
		});
	</script>		
	</div>
</div>

<div data-role="page" id="account_deleted">
	<div data-role="main" class="sfiab_page" > 
	<div><p>Your account has been deleted.
	</div>
</div>



<?php
output_end();
?>
