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
	('email_registration', 'registration@yourfair.com', 'Emails', 'text', '', '', 'Registration Coordinator Email Address'),
	('tshirt_enable', '1', 'Student Registration', 'yesno', '', 0, 'If the tshirt size option should be shown to students'),
	('tours_enable', '1', 'Tours', 'yesno', '', 0, 'If tours should be enabled for students/volunteers'),
	('volunteers_enable', '1', 'Volunteers', 'yesno', '', 0, 'If volunteers should be allowed to register');

ALTER TABLE `config` ADD `name` TINYTEXT NOT NULL AFTER `order` ;

