<?php
require_once('common.inc.php');
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
		Welcome Page!
	</div>
</div>

<div data-role="page"  id="important_dates" class="menu_main">
	<div data-role="main" class="sfiab_page" > 
		Important Dates
	</div>
</div>


<div data-role="page" id="committee">
	<div data-role="main" class="sfiab_page" > 
		Committee
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
	<div data-role="fieldcontain">
		<label for="register_as" class="select">Register as a:</label>
		<select name="register_as" id="register_as" <?php/*<!--data-native-menu="false"*/?> >
		    <option value="student">Student</option>
		    <option value="judge">Judge</option>
		    <option value="volunteer">Volunteer</option>
		    <option value="teacher">Teacher</option>
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

	<p>If you think your email address is wrong, register again with
	the same Username and your old registration email address
	will be replaced with the new one.  A new temporary
	password will also be sent to the new email address.

	</div>
	<a href="#login" data-role="button">Proceed to Login</a>
	</div>
</div>



<div data-role="page" id="contact">
	<div data-role="main" class="sfiab_page" > 
	Contact Us
	</div>
</div>

<div data-role="page" id="login">
	<div data-role="main" class="sfiab_page" > 
	<div id="login_msg" class="error error_hidden">
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

	<p>If you can't remember your username or password, <a href="#forgot">click here</a> to recover them.
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
					if(data == '0') {
						/* Success */
						window.location.replace('<?=$config['fair_url']?>/main.php');
					} else if(data == '1') {
						/* Account hasn't been activated yet */
						$.mobile.changePage('#register_send');
					} else {
						$('#login_msg').text(data);
						$('#login_msg').removeClass("error_hidden");
						$.mobile.changePage('#login', { allowSamePageTransition: true } );
					}
				});
			});
			return false;
		});
	</script>		
	</div>
</div>

<div data-role="page" id="forgot">
	<div data-role="main" class="sfiab_page" > 
	<div id="login_forgot_msg" class="error error_hidden">
	</div>

	<h3>Recover Password</h3>
	Enter your Username and we will email you a link to reset your password
	<form action="/" id="forgot_password_form">
		<div data-role="fieldcontain">
			<label for="fp_un" >Username:</label>
			<input name="user" id="fp_un" value="" placeholder="Username" type="text">
		</div>			
		<button type="submit" data-inline="true" data-icon="check" data-theme="b">Reset My Password</button>
	</form>
	<hr/>

	<h3>Recover Username</h3>
	Enter your email address and we will email you your Username
	<form action="/" id="forgot_username_form">
		<div data-role="fieldcontain">
			<label for="fu_em" >Email:</label>
			<input name="user" id="fu_em" value="" placeholder="Email" type="email">
		</div>			
		<button type="submit" data-inline="true" data-icon="check" data-theme="b">Recover Username</button>
	</form>

	<script>
		$( "#forgot_password_form" ).submit(function( event ) {
			// Stop form from submitting normally
			event.preventDefault();
			$('#register_msg').addClass("error_hidden");
			// Get some values from elements on the page:
			var u = $('#register_username').val();
			var e = $('#register_email').val();
			var a = $('#register_as').val();
			var fn = $('#register_firstname').val();
			var ln = $('#register_lastname').val();

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
			});
			// Stop any more actions
			return false;
		});

	</script>
	</div>
</div>



<?php
output_end();
?>
