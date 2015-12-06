INSERT INTO `config` ( `var` , `val` , `category` , `type` , `type_values` , `order` , `name`, `description`) VALUES 
('judge_div_shuffle', '0', 'Judge Scheduler', 'yesno', '', '25', 'Shuffle Judge Assignments', 'Shuffle judges so that no project is visited by the same subset of judges.  No means the same judges will visit ALL the projects.  Yes, means the judges will be shuffled and a different subset will visit each project.  The amount of overlap is number of times each project must be judged minus 1.');

UPDATE `sfiab_new`.`config` SET `var` = 'div_times_each_project_judged',
	`description` = 'Number of times each project must be judged in Round 1 Divisional judging.  If Judge Shuffling is OFF, then this number is also the number of judges on each judging team.   If Judge Shuffling is ON, then there will be up to twice this number of judges on each team depending on the project distribution, so that each project can be visited by a different subset of the judges on the team.'
	WHERE `config`.`var` = 'judge_div_max_team';


