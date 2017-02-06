INSERT INTO `config` (`var`, `val`, `category`, `type`, `type_values`, `order`, `name`, `description`) VALUES 
	('student_enable_cwsf_eligibility', '0', 'Student Registration', 'yesno', '', '400', 'Enable CWSF Eligibility Questions', 'Ask the students if they have attended or plan to attend another regional fair that selects projects for the upcoming CWSF');

ALTER TABLE  `projects` ADD  `cwsf_rsf_has_competed` BOOLEAN NULL DEFAULT NULL ,
			ADD  `cwsf_rsf_will_compete` BOOLEAN NULL DEFAULT NULL ;

