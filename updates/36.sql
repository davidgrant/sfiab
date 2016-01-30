INSERT INTO `config` ( `var` , `val` , `category` , `type` , `type_values` , `order` , `name` , `description`) VALUES 
	('smtp_type', 'webserver', 'Emails', 'select', 'webserver=Send mail directly from SFIAB|gmail=Send mail through a GMail Account|smtp=Send mail through a specified SMTP server', '100', '', 'SMTP Setting.  By default, choose "webserver".  If you get a lot of complaints about rejected mail, or mail not being delivered, you may need to send mail through the SMTP server for your hosted domain.'),

