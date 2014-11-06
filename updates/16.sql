INSERT INTO `cms` (`id` , `name` , `type` , `text` , `language` , `use`) VALUES 
	(NULL , 'contact_us', 'pagetext', 'Under construction, coming soon!', 'en', '1'),
	(NULL, 'v_main', 'pagetext', 'Currently we are only accepting fair volunteers for help with tours.  Please see the tour menu on the left.', 'en', '1');

SELECT @year:=val FROM `config` WHERE `var`='year';
DELETE FROM `config` WHERE `year`>=0 AND `year`!=@year;
ALTER TABLE `config` DROP `year` ;

INSERT INTO `config` ( `var` , `val` , `category` , `type` , `type_values` , `order` , `description`) VALUES 
	('email_chair', 'chair@yourfair.com', 'Emails', 'text', '', '', 'Chair Email Address'),
	('email_chiefjudge', 'chiefjudge@yourfair.com', 'Emails', 'text', '', '', 'Chief Judge Email Address'),
	('email_ethics', 'ethics@yourfair.com', 'Emails', 'text', '', '', 'Ethics Committee Email Address'),
	('email_registration', 'registration@yourfair.com', 'Emails', 'text', '', '', 'Registration Coordinator Email Address');


