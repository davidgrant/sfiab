ALTER TABLE `judging_teams` ADD `prize_id` INT NOT NULL ,
	ADD `cusp_n_up` INT NOT NULL ;

INSERT INTO `config` ( `var` , `val` , `category` , `type` , `type_values` , `order` , `description`) VALUES 
	('judge_ask_dinner', '0', 'Judge Registration', 'yesno', '', '', 'Ask the judges if they will attend the judging dinner.  The time displayed for the dinner is between judging rounds 1 and 2 (assuming there are 2 rounds defined)'); 
