INSERT INTO `config` ( `var` , `val` , `category` , `type` , `type_values` , `order` , `name`, `description`) VALUES 
('preregistration_enable', '0', 'Student Pre-Registration', 'yesno', '', '0', 'Enable Pre-Registration', 'Allow students to register their project details and go through the ethics and safety forms before official registration starts.  Tour and award selection is disabled in pre-registration, and the signature form may not be generated.  Any open pre-registration is automatically sent an email when registration officially opens.  Set the pre-registration open date on the Important Dates configuration page.'),
('date_student_preregistration_opens', '0000-00-00', 'Important Dates', 'text', '', '0', 'Date Pre-Registration Opens', 'The date pre-registration should open.  This date is only used if Pre-Registration is enabled on the Pre-Registration configuration page.  There is more help on that page about what pre-registration is');

ALTER TABLE `projects` ADD `ethics_approved` TINYINT( 1 ) NOT NULL DEFAULT '0';
