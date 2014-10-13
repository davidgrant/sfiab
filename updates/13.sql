ALTER TABLE `projects` CHANGE `fair_id` `feeder_fair_id` INT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE `projects` ADD `feeder_fair_pid` INT NOT NULL AFTER `feeder_fair_id` ;
