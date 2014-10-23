INSERT INTO `cms` ( `id` , `name` , `type` , `text` , `language` , `use`) VALUES (NULL , 'contact_us', 'pagetext', 'Under construction, coming soon!', 'en', '1');

SELECT @year:=val FROM `config` WHERE `var`='year';
INSERT INTO `sfiab_new`.`config` ( `var` , `val` , `category` , `type` , `type_values` , `order` , `description` , `year`) VALUES 
	('email_chair', 'chair@yourfair.com', 'Emails', 'text', '', '', 'Chair Email Address', @year),
	('email_chiefjudge', 'chiefjudge@yourfair.com', 'Emails', 'text', '', '', 'Chief Judge Email Address', @year),
	('email_ethics', 'ethics@yourfair.com', 'Emails', 'text', '', '', 'Ethics Committee Email Address', @year),
	('email_registration', 'registration@yourfair.com', 'Emails', 'text', '', '', 'Registration Coordinator Email Address', @year);



