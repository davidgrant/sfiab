CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `method` enum('cash','cheque','paypal') DEFAULT NULL,
  `payer_firstname` tinytext,
  `payer_lastname` tinytext,
  `payer_email` tinytext,
  `payer_country` varchar(8) DEFAULT NULL,
  `amount` float DEFAULT NULL,
  `token` varchar(32) DEFAULT NULL,
  `transaction_id` varchar(32) DEFAULT NULL,
  `receipt_id` varchar(32) DEFAULT NULL,
  `status` enum('created','completed','pending','failed','cancelled') DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `completed_time` datetime DEFAULT NULL,
  `fees` float DEFAULT NULL,
  `items` text,
  `notes` text,
  `year` int(11) NOT NULL,
  `payfor_uids` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


INSERT INTO `config` (`var`, `val`, `category`, `type`, `type_values`, `order`, `name`, `description`) VALUES 
	('paypal_enable', '0', 'PayPal', 'yesno', '', '10', 'Enable PayPal', 'Enable the PayPal payment processing module'),
	('paypal_sandbox', '0', 'PayPal', 'yesno', '', '20', 'Enable PayPal Sandbox Mode', 'In Sandbox mode, no payments are processed'),
	('paypal_merchant_id', '', 'PayPal', 'text', '', '30', 'PayPal Merchant ID', 'Paypal Merchant ID'),
	('paypal_account', '', 'PayPal', 'text', '', '40', 'PayPal REST API Account', 'Paypal REST API Account (NOT your paypal login!).  See https://developer.paypal.com/developer/applications and the REST API apps section'),
	('paypal_client_id', '', 'PayPal', 'text', '', '50', 'PayPal REST API ClientID', 'Paypal REST API ClientID'),
	('paypal_secret', '', 'PayPal', 'text', '', '60', 'PayPal REST API Secret', 'Paypal REST API Secret'),
	('paypal_sandbox_acount', '', 'PayPal', 'text', '', '70', 'PayPal Sandbox REST API Secret', 'Paypal Sandbox REST API Account (NOT your paypal login!)'),
	('paypal_sandbox_client_id', '', 'PayPal', 'text', '', '80', 'PayPal Sandbox REST API Secret', 'Paypal Sandbox REST API ClientID'),
	('paypal_sandbox_secret', '', 'PayPal', 'text', '', '90', 'PayPal Sandbox REST API Secret', 'Paypal Sandbox REST API Secret');

ALTER TABLE `users` CHANGE `s_paid` `s_paid` INT NOT NULL DEFAULT '0';
