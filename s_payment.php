<?php

require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('incomplete.inc.php');
require_once('form.inc.php');
require_once('tcpdf.inc.php');

$mysqli = sfiab_init(array('student'));

$page_id = 's_payment';

$u = user_load($mysqli);

sfiab_check_abort_in_preregistration($u, $page_id);

$p = project_load($mysqli, $u['s_pid']);
$closed = sfiab_registration_is_closed($u);

/* Get all users associated with this project */
$users = user_load_all_for_project($mysqli, $u['s_pid']);

/* Check for all complete */
$all_complete = true;
foreach($users as &$user) {
	if($user['s_complete'] == 0) {
		$all_complete = false;
	}
}
$help = "
<p>This page lets you pay registration fees by PayPal.  Once payment is received your project will be considered complete and you will not be able to edit any of your project information.
";


$fee_data = compute_per_user_reg_fee($mysqli, $p, $users);


sfiab_page_begin($u, "Registration Fee Payment", $page_id, $help);

$form_id=$page_id.'_form';

?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
sfiab_page_messages();
?>

<h3>Registration Fee Payment</h3>

<?php if($config['paypal_enable']) { ?>

	<p>Please select the student(s) to pay for below, then click on the PayPal
	button to begin the payment process.

	<p>Your payment details are not stored in our registration system.  All we
	store is a confirmation code from PayPal indicating whether payment was
	successful.

<?php } else { ?>
	<p>Your Registration Number is <b><font size=+2><?=$u['s_pid']?></font></b>.  

	<p><?=cms_get($mysqli, 'student_payment_page', $u)?>

<?php } ?>


<p><b>Disclaimer:</b> Payment does not guarantee admission into the fair.
We reserve the right to reject any project for any reason.  If we are
unfortunately unable to accommodate your project a full refund will be
provided.

<?php
	form_begin($form_id, 'paypal.php/checkout', $closed, false);
	$total = 0;
?>
	<table width=90% border=1>
<?php	$amounts = array();
	foreach($users as &$user) { 

		$confirm_code = '';
		if($user['s_paid'] > 0) {
			$q = $mysqli->query("SELECT receipt_id FROM payments WHERE id={$user['s_paid']}");
			$r = $q->fetch_row();
			$confirm_code = $r[0];
		} ?>

		<tr><td width="5%" align=center>
<?php			if($user['s_paid']) { ?>
				<span class="happy">Paid</span>
<?php			} else {
				$checked = ($u['uid'] == $user['uid']) ? 'checked="checked"' : ''; 
				if($checked) {
					$total += $fee_data[$user['uid']]['subtotal'];
				}
				?>
				<input id="check_<?=count($amounts)?>" type="checkbox" <?=$checked?> name="payfor[]" value="<?=$user['uid']?>"/>
<?php				$amounts[] = $fee_data[$user['uid']]['subtotal'];
			} ?>			
		</td>
		<td width="90%">
			<div>
			<table width="100%" cellspacing="20">
			<tr><td colspan=2><b><?=$user['name']?></b></td></tr>
<?php			foreach($fee_data[$user['uid']] as $index=>$d) { 
				if(!is_array($d)) continue; 
				?>
				<tr><td width="70%"><?=$d['text']?></td>
				<td align=center width="30%">$<?=sprintf("%.02f", $d['base'] * $d['num'])?></td>
				</tr>
<?php			} ?>
			<tr>
<?php			if($user['s_paid']) { ?>			
				<td align=right><span class="happy">Confirmation Code: <?=$confirm_code?></span></td>
				<td></td>
<?php			} else { ?>
				<td align=right>Subtotal</td>
				<td align=center >$<?=sprintf("%.02f", $fee_data[$user['uid']]['subtotal'])?></td>
<?php			} ?>
				
			</tr>
			</table>
			</div>
		</td></tr>
		
<?php	} 


	$display = ($total == 0) ? 'style="display:none;"' : '';
?>
	</table>
	<table width=90% border=0>
	<tr>
	<td width=5% align=right>&nbsp;</td>
	<td width=90%>
		<table width="100%" cellspacing="20">
		<tr><td width="70%" align=right><b>Total</b></td>
		<td width="30%" align=center><b><span id="s_total">$<?=sprintf("%.02f", $total)?></span></b><td>
		</tr>
		<tr><td></td><td align=left  >
<?php			if($config['paypal_enable']) { ?>
				<form <?=$display?> id="paypal-button-35" method="post" action="paypal.php/checkout35">
				</form>
				<div <?=$display?> id="paypal-button"></div>
<?php			} ?>				
		</td></tr>
		</table>
	</td>
	</tr>
	</table>

<?php
	form_end($form_id);

?>	



</div></div>

<?php

unset($_SESSION['paypal_token']);
unset($_SESSION['paypal_token_type']);

$env = $config['paypal_sandbox'] ? 'sandbox' : 'production';

?>
<script src="https://www.paypalobjects.com/api/checkout.js" data-version-4></script>

<script>
/*
window.paypalCheckoutReady = function() {
    paypal.checkout.setup('<?=$config['paypal_merchant_id']?>', {
        environment: '<?=$env?>',
        container: 'paypal-button-35'
    });
};
*/
paypal.Button.render({
        env: '<?=$env?>',
        payment: function(resolve, reject) {
			formdata = $("#<?=$form_id?>").serialize();
			paypal.request.post("<?=$config['fair_url']?>/paypal.php/checkout", { data: formdata } )
		                .then(function(data) { resolve(data.paymentID); })
                		.catch(function(err) { reject(err); });
	        },
        onAuthorize: function(data, actions) {
            		paypal.request.post("<?=$config['fair_url']?>/paypal.php/authorize", { paymentID: data.paymentID, payerID: data.payerID })
		                .then(function(data) { 
					/* data is whatever paypal.php/authorize passes back */
					window.location="s_payment.php";
				})
		                .catch(function(err) { window.location="s_payment.php"; });
		}
}, '#paypal-button');


$( "#<?=$form_id?> :input" ).change(function(event) {
	var total=0;
<?php	for($i=0; $i<count($amounts); $i++) { ?>
		if($("#check_<?=$i?>").is(":checked")) {
			total += <?=$amounts[$i]?>;
		}
<?php	} ?>
	$("#s_total").text("$"+total.toFixed(2));

	if(total == 0) {
		$("#paypal-button").hide();
	} else {
		$("#paypal-button").show();
	}
});
			
</script>

	
<?php
sfiab_page_end();
?>
