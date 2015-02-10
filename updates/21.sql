DELETE FROM `config` WHERE `var` = 'judge_cusp_min_team';
DELETE FROM `config` WHERE `var` = 'judge_div_min_projects';
DELETE FROM `config` WHERE `var` = 'judge_div_min_team';
DELETE FROM `config` WHERE `var` = 'judge_sa_min_projects';

UPDATE `config` SET `order`='40', `name`='Judges Per CUSP Team', description='Number of judges to put on each CUSP team' WHERE `var`='judge_cusp_max_team';
UPDATE `config` SET `order`='20', `name`='Projects Per Divisional Judge', description='Maximum number of projects for each Round 1 Divisional judge' WHERE `var`='judge_div_max_projects';
UPDATE `config` SET `order`='10', `name`='Judges Per Divisional Team', description='Number of judges on each Round 1 Divisional judging team.  Each project will be judged by every judge on the team' WHERE `var`='judge_div_max_team';
UPDATE `config` SET `order`='30', `name`='Projects Per Special Awards Judge', description='Number of projects for each special awards judge.    Judges are added to a special awards judging team until the number of judges * (this number) is greater than the number of projects' WHERE `var`='judge_sa_max_projects';

INSERT INTO `config` ( `var` , `val` , `category` , `type` , `type_values` , `order` , `name`, `description`) VALUES 
('judge_cusp_projects_per_team', '6', 'Judge Scheduler', 'number', '', '50', 'Projects Per CUSP Team', 'Number of projects to schedule on each CUSP judging team after Round 1 scores are entered.'),
('judge_divisional_prizes', 'Gold,Silver,Bronze,Honourable Mention', 'Judge Scheduler', 'text', '', '60', 'Divisional Prizes', 'Comma-separated list of prize names for each divisional award, in order of the best prize first, e.g., "Gold, Silver, Bronze, Honourable Mention".'),
('judge_divisional_distribution', '5,10,15,20', 'Judge Scheduler', 'text', '', '70', 'Divisional Prize Distribution', 'Comma-separated list percentages (without the percent sign) for the percent of projects to be assigned to each of the Divisional Prizes (defined in judge_divisional_prizes".  e.g., for 5% Gold, 10% Silver, 15% Bronze, and 20% Honourable Mention, set this to "5,10,15,20".');


