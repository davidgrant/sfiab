ALTER TABLE `log` ADD `pid` INT NOT NULL AFTER `uid`,
	ADD `email_id` INT NOT NULL AFTER `fair_id` ,
	ADD `award_id` INT NOT NULL AFTER `email_id` ,
	ADD `prize_id` INT NOT NULL AFTER `award_id` ,
	ADD `message` TEXT NOT NULL AFTER `data`;

ALTER TABLE `awards` ADD `upstream_register_winners` BOOLEAN NOT NULL AFTER `upstream_award_id` ;
ALTER TABLE `award_prizes` DROP `upstream_register_winners` ;

