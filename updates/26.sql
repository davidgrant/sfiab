ALTER TABLE `queue` CHANGE `command` `command` ENUM( 'email', 'push_award', 'push_winner', 'get_stats', 'push_stats', 'judge_scheduler', 'tour_scheduler' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE `queue` ADD `year` INT NOT NULL AFTER `command` ;

INSERT INTO `config` ( `var` , `val` , `category` , `type` , `type_values` , `order` , `name` , `description`) VALUES (
	'date_student_preregistration_opens', '0000-00-00 00:00:00', 'Important Dates', 'text', '', '0', '', 'The date student pre-registration should open'
);


