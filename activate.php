<?php
require_once('common.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

output_start();

//data-theme="b" 

function page_begin($id, $text)
{
	$back=false;
	$sel = 'data-theme="b"';

	
?>
	<div data-theme="b" data-role="header">
	<?php if($back == true) { ?>
		<a href="#" data-role="button" data-theme="b" data-rel="back" data-inline="true">Back</a>
	<?php } ?>
	<h3><?=$text?></h3>
	</div>

	<div data-role="content"> 
        	<div class="ui-grid-a">
			<div id="main_menu" class="ui-block-a" style="width:20%">
				<ul data-role="listview" data-inset="true" data-theme="c" data-dividertheme="d">
					<li <?=$id=='welcome'?$sel:''?>><a href="#welcome" data-transition="fade" data-inline="true" >Welcome</a></li>
					<li <?=$id=='important_dates'?$sel:''?>><a href="#important_dates" data-transition="fade" data-inline="true">Important Dates</a></li>
					<li <?=$id=='winners'?$sel:''?>><a href="#winners" data-transition="fade" data-inline="true" >Winners</a></li>
					<li <?=$id=='committee'?$sel:''?>><a href="#committee" data-transition="fade" data-inline="true">Committee</a></li>
					<li <?=$id=='contact'?$sel:''?>><a href="#contact" data-transition="fade" data-inline="true">Contact Us</a></li>
				</ul>
				<ul data-role="listview" data-inset="true" data-theme="c" data-dividertheme="d">
					<li <?=$id=='register'?$sel:''?>><a href="#register" data-transition="fade" data-inline="true" >Registration</a></li>
					<li <?=$id=='login'?$sel:''?>><a href="#login" data-transition="fade" data-inline="true">Login</a></li>
				</ul>
			</div>
			<?php/*top, right, bottom, left */?>
			<div id="main_content" class="ui-block-b" style="width:80%; padding: 10px 0 0 10px;">

<?php
}

function page_end()
{
?>
			</div>
		</div>
	</div>
<?php		
}

?>

<div data-role="page" id="welcome">
	<?=page_begin('welcome', 'Welcome')?>
	Welcome Page!
	<?=page_end()?>
</div>


<div data-role="page" id="winners">
	<?=page_begin('winners', 'Winners')?>
	Winners Page!
	<?=page_end()?>

</div>

<div data-role="page" id="important_dates">
	<?=page_begin('important_dates', 'Dates');?>
	Important Dates
	<?=page_end()?>

</div>

<div data-role="page" id="committee">
	<?=page_begin('committee', 'Committee');?>
	Committee
	<?=page_end()?>

</div>

<div data-role="page" id="register">
	<?=page_begin('register', 'Registration');?>
	<div id="register_msg" class="error error_hidden">
	</div>
	<div>Please select your registration type below and provide an email address and a username.  An email with an activation link will be sent to the email address you provide.  You will be asked to create a password after activating your account</div>

	<form action="/" id="register_form">
	<div data-role="fieldcontain">
		<label for="register_firstname">First Name:</label>
		<input data-clear-btn="true" name="firstname" id="register_firstname" value="" type="text">
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
	<?=page_end()?>
</div>

<div data-role="page" id="register_sent">
	<?=page_begin('register_sent', 'Registration Activation Email');?>
	<div><p>An email with an activation link has been sent to your email address.  Click on the link in that email or copy the link address into the URL of a web browser to activate your account. </div>
	<p>Click on the button below to re-send the activation email.
	<form action="/" id="resend_form">
	<button type="submit" data-inline="true" data-icon="check" data-theme="g">Re-send Activation Email</button>
	</form>
	<?=page_end()?>
</div>



<div data-role="page" id="contact">
	<?=page_begin('contact','Contact Us');?>
	Contact Us
	<?=page_end()?>
</div>

<div data-role="page" id="login">
	<?php 
	page_begin('login', 'Login');
	?>
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
						$.mobile.changePage('#winners');
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
	<?=page_end()?>
</div>

<div data-role="page" id="forgot">
	<?php 
	page_begin('forgot', 'Forgot Username/Password');
	?>
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
		$( "#register_form" ).submit(function( event ) {
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
	<?=page_end()?>
</div>
	


<?php
output_end();
?>
