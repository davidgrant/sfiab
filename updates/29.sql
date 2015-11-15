INSERT INTO `config` ( `var` , `val` , `category` , `type` , `type_values` , `order` , `name`, `description`) VALUES 
('preregistration_enable', '0', 'Student Pre-Registration', 'yesno', '', '0', 'Enable Pre-Registration', 'Allow students to register their project details and go through the ethics and safety forms before official registration starts.  Tour and award selection is disabled in pre-registration, and the signature form may not be generated.  Any open pre-registration is automatically sent an email when registration officially opens.  Set the pre-registration open date on the Important Dates configuration page.');

UPDATE `config` SET `description`='The date pre-registration should open.  This date is only used if Pre-Registration is enabled on the Pre-Registration configuration page.  There is more help on that page about what pre-registration is on the pre-registration configuration page' WHERE `var`='date_student_preregistration_opens';

