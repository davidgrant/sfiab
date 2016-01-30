INSERT INTO `config` ( `var` , `val` , `category` , `type` , `type_values` , `order` , `name` , `description`) VALUES 
	('smtp_type', 'webserver', 'Emails', 'select', 'webserver=Send mail directly from SFIAB|gmail=Send mail through a GMail Account|smtp=Send mail through a specified SMTP server', '100', '', 'SMTP Setting.  By default, choose "webserver".  If you get a lot of complaints about rejected mail, or mail not being delivered, you may need to send mail through the SMTP server for your hosted domain.<br/>For <b>gmail</b> accounts, you only need to specify the smtp_username and smtp_password, the other smtp_ fields will be ignored'),
	('smtp_host', '', 'Emails', 'text', '', '110', '', 'The SMTP host. Probably something like smtp.gmail.com'),
	('smtp_port', '', 'Emails', 'number', '', '120', '', 'The SMTP port. Probably 25 or 587'),
	('smtp_encryption', 'none', 'Emails', 'select', 'none=No Encryption|tls=TLS Encryption', '130', '', 'The SMTP encryption to use.  TLS is preferred'),
	('smtp_username', '', 'Emails', 'text', '', '140', '', 'Username for authentication.  If blank, no authentication is attempted'),
	('smtp_password', '', 'Emails', 'text', '', '150', '', 'Password for authentication.  If blank, no authentication is attempted'),
	('judge_require_all_rounds', '0', 'Judge Registration', 'yesno', '', '10', '', 'Require that judges be present for ALL rounds of judging (removes the option for judges to select individual rounds they are available for).');

