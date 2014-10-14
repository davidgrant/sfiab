ALTER TABLE `email_queue` ADD `command` ENUM( 'email', 'push_award', 'push_winner', '' ) NOT NULL AFTER `id` ;
ALTER TABLE `email_queue` ADD `fair_id` INT NOT NULL AFTER `command` ,
	ADD `award_id` INT NOT NULL AFTER `fair_id` ,
	ADD `prize_id` INT NOT NULL AFTER `award_id` ,
	ADD `project_id` INT NOT NULL AFTER `prize_id` ;
ALTER TABLE `email_queue`  RENAME TO `queue`;

UPDATE config SET `var`='queue_lock' WHERE `var`='email_queue_lock';
UPDATE config SET `var`='queue_stop' WHERE `var`='email_queue_stop';

