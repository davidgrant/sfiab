<?php

require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('schools.inc.php');
require_once('csv.inc.php');

$mysqli = sfiab_init("committee");
sfiab_load_config($mysqli);


$csv = new csv('sfiab_payments');

$table = array(	'fields' => array(),
		'header' => array(	'completed_time' => 'Date',
					'uid' => 'UserID',
					's_pid' => 'ProjectID',
					'amount' => 'Amount',
					'fees' => 'Fees',
					'transaction_id' => 'Confirmation',
					'receipt_id' => 'Receipt',
					'Method' => 'Method',
					'payer_firstname' => 'Payer_Firstname',
					'payer_lastname' => 'Payer_Lastname',
					'payer_email' => 'Payer_Email',
					'payer_country' => 'Payer_Country',
					'notes' => 'Notes' ),
		'data' => array(),
		);
$table['fields'] = array_keys($table['header']);

$q = $mysqli->query("SELECT * FROM payments WHERE year='{$config['year']}' AND status='completed' ORDER BY completed_time");
while( ($r = $q->fetch_assoc()) ) {
	$u = user_load($mysqli, $r['uid']);
	$table['data'][] = array(	'completed_time' => $r['completed_time'],
					'uid' => $r['uid'],
					's_pid' => $u['s_pid'],
					'amount' => $r['amount'],
					'fees' => $r['fees'],
					'transaction_id' => $r['transaction_id'],
					'receipt_id' => $r['receipt_id'],
					'Method' => $r['method'],
					'payer_firstname' => $r['payer_firstname'],
					'payer_lastname' => $r['payer_lastname'],
					'payer_email' => $r['payer_email'],
					'payer_country' => $r['payer_country'],
					'notes' => $r['notes']);
}

$csv->add_table($table);

$csv->output();

?>
