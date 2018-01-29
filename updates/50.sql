INSERT INTO `config` (`var`, `val`, `category`, `type`, `type_values`, `order`, `name`, `description`) VALUES 
	('judge_scheduler_use_detailed_subdivisions', '1', 'Judge Scheduler', 'yesno', '', '1000', 'Use detailed division subdivisions', 'The ISEF detailed divisions are broken into 17 major and over 100 minor divisions.  Setting this to "no" only allows judges and students to choose among the 17 major divisions.  This will result in a lower quality judge matching.');

INSERT INTO `config` (`var`, `val`, `category`, `type`, `type_values`, `order`, `name`, `description`) VALUES 
	('payment_enable_canadahelps', '0', 'PayPal', 'yesno', '', '1000', 'Enable CanadaHelps payment by donation/token', 'Enable CanadaHelps token entry so students can donate the registration amount to CanadaHelps and enter their receipt code into SFIAB to confirm payment.');


