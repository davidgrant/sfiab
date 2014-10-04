ALTER TABLE `awards` ADD `feeder_fair_ids` TINYTEXT NOT NULL ;
ALTER TABLE `fairs` DROP `award_ids` ;
ALTER TABLE `fairs` ADD `token` VARCHAR( 128 ) NOT NULL ;

