ALTER TABLE `config` CHANGE `type` `type` ENUM( '', 'yesno', 'number', 'text', 'select', 'multisel', 'language', 'theme', 'timezone' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;

INSERT INTO `config` ( `var` , `val` , `category` , `type` , `type_values` , `order` , `description`) VALUES 
	('project_number_format', 'CHN', 'Floorplanning', 'select', 'CHN=CCHHXX (2-digit Category, 2-digit Challenge, 2-digit Number)|X4=XXXX (4-digit sequential numbering)|c_X3_h=c XXX h (Category Shortform, 3-digit sequential, Challenge Shortform)', '', 'Project Numbering Format');

INSERT INTO `cms` (`id` , `name` , `type` , `text` , `language` , `use`) VALUES 
	(NULL , 's_main', 'pagetext', 'Extra information for the student main page', 'en', '1');

ALTER TABLE `categories` CHANGE `id` `cat_id` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `categories` DROP PRIMARY KEY;
ALTER TABLE `categories` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ;

ALTER TABLE `challenges` CHANGE `id` `chal_id` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `challenges` DROP PRIMARY KEY;
ALTER TABLE `challenges` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ;


