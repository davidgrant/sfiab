CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `method` enum('cash','cheque','paypal') NOT NULL,
  `payer_firstname` tinytext NOT NULL,
  `payer_lastname` tinytext NOT NULL,
  `payer_email` tinytext NOT NULL,
  `payer_country` varchar(8) NOT NULL,
  `amount` float NOT NULL,
  `fees` float NOT NULL,
  `token` varchar(32) NOT NULL,
  `transaction_id` varchar(32) NOT NULL,
  `receipt_id` varchar(32) NOT NULL,
  `status` ENUM( 'success', 'pending', 'failed' ) NOT NULL,
  `order_time` datetime NOT NULL,
  `time` datetime NOT NULL,
  `items` text NOT NULL,
  `notes` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `config` (`var`, `val`, `category`, `type`, `type_values`, `order`, `name`, `description`) VALUES 
	('paypal_enable', '0', 'PayPal', 'yesno', '', '10', 'Enable PayPal', 'Enable the PayPal payment processing module'),
	('paypal_sandbox', '0', 'PayPal', 'yesno', '', '20', 'Enable PayPal Sandbox Mode', 'In Sandbox mode, no payments are processed'),
	('paypal_merchant_id', '', 'PayPal', 'text', '', '30', 'PayPal Marchant ID', 'Paypal Merchant ID'),
	('paypal_user', '', 'PayPal', 'text', '', '40', 'PayPal USER', 'Paypal API username (NOT your paypal login!)'),
	('paypal_pwd', '', 'PayPal', 'text', '', '50', 'PayPal PWD', 'Paypal API password  (NOT your paypal password!)'),
	('paypal_signature', '', 'PayPal', 'text', '', '60', 'PayPal SIGNATURE', 'Paypal API Signature'),
	('paypal_sandbox_user', '', 'PayPal', 'text', '', '70', 'PayPal USER', 'Paypal Sandbox API username (NOT your paypal login!)'),
	('paypal_sandbox_pwd', '', 'PayPal', 'text', '', '80', 'PayPal PWD', 'Paypal Sandbox API password  (NOT your paypal password!)'),
	('paypal_sandbox_signature', '', 'PayPal', 'text', '', '90', 'PayPal SIGNATURE', 'Paypal Sandbox API Signature');

ALTER TABLE `users` CHANGE `s_paid` `s_paid` INT NOT NULL DEFAULT '0';
