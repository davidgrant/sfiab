-- phpMyAdmin SQL Dump
-- version 4.0.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 23, 2014 at 05:10 AM
-- Server version: 5.6.20-log
-- PHP Version: 5.5.9-pl0-gentoo

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `sfiab_new`
--

-- --------------------------------------------------------

--
-- Table structure for table `awards`
--
DROP TABLE IF EXISTS `awards`;
CREATE TABLE IF NOT EXISTS `awards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` tinytext NOT NULL,
  `s_desc` text NOT NULL,
  `j_desc` text NOT NULL,
  `c_desc` text NOT NULL,
  `categories` tinytext NOT NULL,
  `year` int(11) NOT NULL,
  `schedule_judges` tinyint(1) NOT NULL,
  `include_in_script` tinyint(1) NOT NULL,
  `self_nominate` tinyint(1) NOT NULL,
  `type` enum('divisional','special','grand','other') NOT NULL,
  `ord` int(11) NOT NULL,
  `presenter` tinytext NOT NULL,
  `sponsor_uid` int(11) NOT NULL,
  `cwsf_award` tinyint(1) NOT NULL,
  `upstream_fair_id` int(11) NOT NULL,
  `upstream_award_id` int(11) NOT NULL,
  `feeder_fair_ids` tinytext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-----

--
-- Table structure for table `award_prizes`
--

DROP TABLE IF EXISTS `award_prizes`;
CREATE TABLE IF NOT EXISTS `award_prizes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `award_id` int(11) NOT NULL,
  `name` tinytext NOT NULL,
  `cash` decimal(10,2) NOT NULL,
  `scholarship` decimal(10,2) NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `trophies` set('keeper','return','school_keeper','school_return') NOT NULL,
  `ord` int(11) NOT NULL,
  `number` int(11) NOT NULL,
  `upstream_prize_id` int(11) NOT NULL,
  `upstream_register_winners` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(64) DEFAULT NULL,
  `shortform` char(3) DEFAULT NULL,
  `min_grade` tinyint(4) NOT NULL DEFAULT '0',
  `max_grade` tinyint(4) NOT NULL DEFAULT '0',
  `year` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`year`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `shortform`, `min_grade`, `max_grade`, `year`) VALUES
(1, 'Junior', 'J', 7, 8, 0),
(2, 'Intermediate', 'M', 9, 10, 0),
(3, 'Senior', 'S', 11, 12, 0);

-- --------------------------------------------------------

--
-- Table structure for table `challenges`
--

DROP TABLE IF EXISTS `challenges`;
CREATE TABLE IF NOT EXISTS `challenges` (
  `id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(64) DEFAULT NULL,
  `shortform` char(3) DEFAULT NULL,
  `cwsfchallengeid` int(11) DEFAULT NULL,
  `year` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`year`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `challenges`
--

INSERT INTO `challenges` (`id`, `name`, `shortform`, `cwsfchallengeid`, `year`) VALUES
(1, 'Discovery', 'D', 0, 0),
(2, 'Energy', 'E', 0, 0),
(3, 'Health', 'H', 0, 0),
(4, 'Information', 'F', 0, 0),
(5, 'Environment', 'V', 0, 0),
(6, 'Innovation', 'N', 0, 0),
(7, 'Resources', 'R', 0, 0),

-- --------------------------------------------------------

--
-- Table structure for table `cms`
--

DROP TABLE IF EXISTS `cms`;
CREATE TABLE IF NOT EXISTS `cms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` tinytext,
  `type` tinytext,
  `text` text,
  `language` varchar(4) DEFAULT NULL,
  `use` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `cms`
--

INSERT INTO `cms` (`id`, `name`, `type`, `text`, `language`, `use`) VALUES
(NULL, 'exhibitordeclaration', 'signaturepage', 'Your application for registration requires that you send your payment with this Signature Form.  Please check that you have done the following:\r\n\r\n[  ] Signed this form (and your partner has signed this form, if partnered project).\r\n[  ] A parent/guardian has signed this form (and partner''s parent/guardian has signed this form).\r\n[  ] My teacher has signed this form.\r\n[  ] The Release of Information form is included and signed my myself and my parent/guardian.\r\n[  ] If partnered project, my partner''s Release of information formed is included too.\r\n[  ] Enclosed Payment.\r\n\r\nYour application for registration will not be considered complete\nuntil this form and payment is received. Acceptance to the Science Fair is at the sole discretion of the Committee. If your project is accepted to participate in the Science Fair, a complete confirmation package will be mailed to your school to the attention of your sponsor teacher. This package contains important information regarding the schedule of the Fair and safety regulations. \r\n\r\nI/We certify that:\r\n - The preparation of this project is mainly my/our own work.\r\n - I/We agree\nagree that the decision of the judges will be final.', 'en', 1),
(NULL, 'parentdeclaration', 'signaturepage', 'As a parent/guardian I certify to the best of my knowledge and believe the information contained in this application is correct, and the project is the work of the student(s).  I  understand that the sponsors of the Science Fair do not assume responsibility for loss or injury to any person, display or part thereof.   I further understand that all exhibits entered must be left on display until the end of the Fair. If my son/daughter does not remove the exhibit at the end of the Fair, the fair organizers or the owner of the exhibition hall cannot be responsible for the disposal of the exhibit. ', 'en', 1),
(NULL, 'teacherdeclaration', 'signaturepage', 'I certify that:\r\n - The preparation of this project is mainly the student(s)'' own work.\r\n - I agree that the decision of the judges will be final.', 'en', 1),
(NULL, 'postamble', 'signaturepage', '', 'en', 1),
(NULL, 'regfee', 'signaturepage', '', 'en', 1),
(NULL, 'main', 'pagetext', '<p>Welcome to the [FAIRNAME] registration site.</p>\r\n<p>Please contact our chair at [CHAIR_EMAIL] if you have any questions.</p>', 'en', 1),
(NULL, 'contact_us', 'pagetext', '<p>Please contact our chair at [CHAIR_EMAIL] if you have any questions', 'en', 1);
(NULL, 'v_main', 'pagetext', 'Currently we are only accepting fair volunteers for help with tours.  Please see the tour menu on the left.', 'en', '1');
-- --------------------------------------------------------

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
CREATE TABLE IF NOT EXISTS `config` (
  `var` varchar(64) NOT NULL,
  `val` text NOT NULL,
  `category` varchar(64) NOT NULL,
  `type` enum('','yesno','number','text','enum','multisel','language','theme','timezone') NOT NULL,
  `type_values` tinytext NOT NULL,
  `order` int(11) NOT NULL,
  `name` TINYTEXT NOT NULL,
  `description` text NOT NULL,
  UNIQUE KEY `var` (`var`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `config`
--

INSERT INTO `config` (`var`, `val`, `category`, `type`, `type_values`, `order`, `description`) VALUES
('date_fair_begins', '0000-04-11 14:00:00', 'Important Dates', 'text', '', 0, 'The date the fair starts'),
('date_fair_ends', '0000-04-13 20:00:00', 'Important Dates', 'text', '', 0, 'The date the fair ends'),
('date_judge_registration_closes', '0000-03-20 00:00:00', 'Important Dates', 'text', '', 0, 'The date judge registration should close'),
('date_judge_registration_opens', '0000-01-01 00:00:00', 'Important Dates', 'text', '', 0, 'The date judge registration should open'),
('date_student_registration_closes', '0000-03-10 12:00:00', 'Important Dates', 'text', '', 0, 'The date student registration should close'),
('date_student_registration_opens', '0000-01-01 00:00:00', 'Important Dates', 'text', '', 0, 'The date student registration should open'),
('db_version', '16', 'system', 'number', '', 0, ''),
('email_chair', 'chair@yourfair.com', 'Emails', 'text', '', 0, 'Chair Email Address'),
('email_chiefjudge', 'chiefjudge@yourfair.com', 'Emails', 'text', '', 0, 'Chief Judge Email Address'),
('email_ethics', 'ethics@yourfair.com', 'Emails', 'text', '', 0, 'Ethics Committee Email Address'),
('email_registration', 'registration@yourfair.com', 'Emails', 'text', '', 0, 'Registration Coordinator Email Address'),
('fair_abbreviation', 'DRSF', 'General', '', '', 0, ''),
('fair_name', 'Default Regional Science Fair', 'General', '', '', 0, ''),
('judge_cusp_max_team', '6', 'Judge Scheduler', '', '', 0, ''),
('judge_cusp_min_team', '6', 'Judge Scheduler', '', '', 0, ''),
('judge_div_max_projects', '7', 'Judge Scheduler', '', '', 0, ''),
('judge_div_max_team', '3', 'Judge Scheduler', '', '', 0, ''),
('judge_div_min_projects', '3', 'Judge Scheduler', '', '', 0, ''),
('judge_div_min_team', '3', 'Judge Scheduler', '', '', 0, ''),
('judge_sa_max_projects', '15', 'Judge Scheduler', '', '', 0, ''),
('judge_sa_min_projects', '0', 'Judge Scheduler', '', '', 0, ''),
('judging_rounds', '2', 'system', '', '', 0, ''),
('queue_lock', '', 'system', '', '', 0, ''),
('queue_stop', '0', 'system', '', '', 0, ''),
('regfee', '50', 'Student Registration', 'number', '', 0, 'Per-Student Registration Fee'),
('s_abstract_max_words', '500', 'Student Registration', 'number', '', 0, 'Maximum number of words for the student project summary'),
('s_abstract_min_words', '200', 'Student Registration', 'number', '', 0, 'Minimum number of words for the student project summary'),
('s_tagline_max_words', '200', 'Student Registration', 'number', '', 0, 'Maximum number of words for the student project one-sentence summary'),
('s_tagline_min_words', '25', 'Student Registration', 'number', '', 0, 'Minimum number of words for the student project one-sentence summary'),
('timezone', 'America/Vancouver', 'General', 'timezone', '', 0, 'Timezone'),
('tshirt_enable', '1', 'Student Registration', 'yesno', '', 0, 'If the tshirt size option should be shown to students'),
('tshirt_cost', '15', 'Student Registration', 'number', '', 0, 'Cost of each tshirt. 0 if they''re free'),
('tours_enable', '1', 'Tours', 'yesno', '', 0, 'If tours should be enabled for students/volunteers'),
('volunteers_enable', '1', 'Volunteers', 'yesno', '', 0, 'If volunteers should be allowed to register'),
('version', '3.0.0', 'system', 'text', '', 0, ''),
('year', '0', 'system', '', '', 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `emails`
--

DROP TABLE IF EXISTS `emails`;
CREATE TABLE IF NOT EXISTS `emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` tinytext NOT NULL,
  `section` tinytext NOT NULL,
  `description` text NOT NULL,
  `from_name` tinytext NOT NULL,
  `from_email` tinytext NOT NULL,
  `subject` text NOT NULL,
  `body` text NOT NULL,
  `bodyhtml` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `emails`
--

INSERT INTO `emails` (`id`, `name`, `section`, `description`, `from_name`, `from_email`, `subject`, `body`, `bodyhtml`) VALUES
(1, 'New Registration', 'System', 'Sent when someone creates a new account', '[FAIRNAME] Registration', '[EMAIL_REGISTRATION]', '[FAIRNAME] Registration', 'Hello [NAME],\r\n\r\nA new account has been created for you at the [FAIRNAME]. To access your account, use the following username and temporary password.\r\n\r\nYou will be prompted to create a new password when you login.\r\n\r\nUsername: [USERNAME]\r\nTemporary Password: [PASSWORD]\r\nLogin Page: http://reg.gvrsf.ca/#login\r\n\r\n\r\nThank You,\r\n\r\nThe [YEAR] [FAIRNAME] Committee\r\n\r\n', NULL),
(2, 'Forgot Password', 'System', 'Sent when someone requests a new password ', '[FAIRNAME] Registration', '[EMAIL_REGISTRATION]', '[FAIRNAME] Registration', 'Hello [NAME],\r\n\r\nWe have received a request to reset your password.  Your new temporary password is shown below. \r\n\r\nYou will be prompted to create a new password when you login.\r\n\r\nTemporary Password: [PASSWORD]\r\n\r\nWe recommend typing the above password rather than copy+paste, as copy+paste tends to add a space-character after the password.\r\n\r\nThank You,\r\n\r\nThe [YEAR] [FAIRNAME] Committee\r\n\r\n ', NULL),
(3, 'Forgot Username', 'System', 'Sent when someone requests a username be sent to them ', '[FAIRNAME] Registration', '[EMAIL_REGISTRATION]', '[FAIRNAME] Registration', 'Hello [NAME],\r\n\r\nWe have received a request to retrieve your username. The following list of usernames is associated with this email address:\r\n\r\nUsernames: [USERNAME_LIST]\r\n\r\nThank You,\r\n\r\nThe [YEAR] [FAIRNAME] Committee\r\n\r\n ', NULL),
(4, 'Test Email', 'System', 'Test Email ', '[FAIRNAME] Registration', '[EMAIL_REGISTRATION]', '[FAIRNAME] Test Email', 'This is a test email from the [FAIRNAME] registration system.  We occasionally tweak thing and might send this out from time-to-time.  We try to avoid sending it to existing users but if you get this email just ignore it. ', NULL),

-- --------------------------------------------------------

--
-- Table structure for table `emergency_contacts`
--

DROP TABLE IF EXISTS `emergency_contacts`;
CREATE TABLE IF NOT EXISTS `emergency_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `firstname` varchar(64) NOT NULL,
  `lastname` varchar(64) NOT NULL,
  `relation` varchar(16) NOT NULL,
  `email` tinytext NOT NULL,
  `phone1` varchar(32) NOT NULL,
  `phone2` varchar(32) NOT NULL,
  `phone3` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


--
-- Table structure for table `exhibithall`
--

DROP TABLE IF EXISTS `exhibithall`;
CREATE TABLE IF NOT EXISTS `exhibithall` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) DEFAULT NULL,
  `type` enum('wall','exhibithall','project') DEFAULT NULL,
  `x` float NOT NULL,
  `y` float NOT NULL,
  `w` float NOT NULL,
  `h` float NOT NULL,
  `orientation` int(11) NOT NULL,
  `exhibithall_id` int(11) NOT NULL,
  `floornumber` int(11) NOT NULL,
  `challenges` tinytext,
  `cats` tinytext,
  `has_electricity` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `exhibithall`
--


--
-- Table structure for table `fairs`
--

DROP TABLE IF EXISTS `fairs`;
CREATE TABLE IF NOT EXISTS `fairs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` tinytext,
  `abbrv` varchar(16) DEFAULT NULL,
  `type` enum('sfiab_feeder','sfiab_upstream','ysc') DEFAULT NULL,
  `url` tinytext,
  `website` tinytext,
  `username` varchar(32) DEFAULT NULL,
  `password` varchar(128) DEFAULT NULL,
  `gather_stats` set('participation','schools_ext','minorities','guests','sffbc_misc','info','next_chair','scholarships','delegates') DEFAULT NULL,
  `divmap` tinytext,
  `token` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `fairs`
--

INSERT INTO `fairs` (`id`, `name`, `abbrv`, `type`, `url`, `website`, `username`, `password`, `gather_stats`, `divmap`, `token`) VALUES
(1, 'Youth Science Canada', 'YSC', 'ysc', 'https://secure.ysf-fsj.ca/awarddownloader/index.php', 'http://apps.ysf-fsj.ca/awarddownloader/help.php', '', '', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `fair_stats`
--

DROP TABLE IF EXISTS `fair_stats`;
CREATE TABLE IF NOT EXISTS `fair_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fair_id` int(11) NOT NULL DEFAULT '0',
  `year` int(11) NOT NULL DEFAULT '0',
  `start_date` date NOT NULL DEFAULT '0000-00-00',
  `end_date` date NOT NULL DEFAULT '0000-00-00',
  `male_0` int(11) NOT NULL DEFAULT '0',
  `male_4` int(11) NOT NULL DEFAULT '0',
  `male_5` int(11) NOT NULL DEFAULT '0',
  `male_6` int(11) NOT NULL DEFAULT '0',
  `male_7` int(11) NOT NULL DEFAULT '0',
  `male_8` int(11) NOT NULL DEFAULT '0',
  `male_9` int(11) NOT NULL DEFAULT '0',
  `male_10` int(11) NOT NULL DEFAULT '0',
  `male_11` int(11) NOT NULL DEFAULT '0',
  `male_12` int(11) NOT NULL DEFAULT '0',
  `female_0` int(11) NOT NULL DEFAULT '0',
  `female_4` int(11) NOT NULL DEFAULT '0',
  `female_5` int(11) NOT NULL DEFAULT '0',
  `female_6` int(11) NOT NULL DEFAULT '0',
  `female_7` int(11) NOT NULL DEFAULT '0',
  `female_8` int(11) NOT NULL DEFAULT '0',
  `female_9` int(11) NOT NULL DEFAULT '0',
  `female_10` int(11) NOT NULL DEFAULT '0',
  `female_11` int(11) NOT NULL DEFAULT '0',
  `female_12` int(11) NOT NULL DEFAULT '0',
  `project_0` int(11) NOT NULL DEFAULT '0',
  `project_4` int(11) NOT NULL DEFAULT '0',
  `project_5` int(11) NOT NULL DEFAULT '0',
  `project_6` int(11) NOT NULL DEFAULT '0',
  `project_7` int(11) NOT NULL DEFAULT '0',
  `project_8` int(11) NOT NULL DEFAULT '0',
  `project_9` int(11) NOT NULL DEFAULT '0',
  `project_10` int(11) NOT NULL DEFAULT '0',
  `project_11` int(11) NOT NULL DEFAULT '0',
  `project_12` int(11) NOT NULL DEFAULT '0',
  `students` int(11) NOT NULL,
  `schools` int(11) NOT NULL,
  `students_public` int(11) NOT NULL,
  `schools_public` int(11) NOT NULL DEFAULT '0',
  `students_private` int(11) NOT NULL,
  `schools_private` int(11) NOT NULL DEFAULT '0',
  `schools_districts` int(11) NOT NULL DEFAULT '0',
  `students_atrisk` int(11) NOT NULL DEFAULT '0',
  `schools_atrisk` int(11) NOT NULL,
  `committee_members` int(11) NOT NULL,
  `judges` int(11) NOT NULL,
  `scholarships` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `judging_scores`
--

DROP TABLE IF EXISTS `judging_scores`;
CREATE TABLE IF NOT EXISTS `judging_scores` (
  `pid` int(11) NOT NULL,
  `scientific` tinyint(4) NOT NULL,
  `originality` tinyint(4) NOT NULL,
  `communication` tinyint(4) NOT NULL,
  `total` smallint(6) NOT NULL,
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `judging_scores`
--



--
-- Table structure for table `judging_teams`
--

DROP TABLE IF EXISTS `judging_teams`;
CREATE TABLE IF NOT EXISTS `judging_teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `num` int(11) NOT NULL,
  `name` tinytext NOT NULL,
  `autocreated` tinyint(1) NOT NULL,
  `round` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `award_id` int(11) NOT NULL,
  `user_ids` tinytext NOT NULL,
  `project_ids` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
CREATE TABLE IF NOT EXISTS `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(39) NOT NULL,
  `uid` int(11) NOT NULL,
  `fair_id` int(11) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `year` int(11) NOT NULL,
  `type` varchar(16) NOT NULL,
  `data` text NOT NULL,
  `result` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;



--
-- Table structure for table `mentors`
--

DROP TABLE IF EXISTS `mentors`;
CREATE TABLE IF NOT EXISTS `mentors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `firstname` tinytext,
  `lastname` tinytext,
  `email` tinytext,
  `phone` tinytext,
  `organization` tinytext,
  `position` tinytext,
  `desc` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `mentors`
--
-- --------------------------------------------------------

--
-- Table structure for table `partner_requests`
--

DROP TABLE IF EXISTS `partner_requests`;
CREATE TABLE IF NOT EXISTS `partner_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_uid` int(11) NOT NULL,
  `to_uid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
CREATE TABLE IF NOT EXISTS `projects` (
  `pid` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL DEFAULT '0',
  `cat_id` int(11) DEFAULT NULL,
  `challenge_id` int(11) DEFAULT NULL,
  `isef_id` int(11) DEFAULT NULL,
  `title` tinytext,
  `tagline` text,
  `abstract` text,
  `req_electricity` tinyint(1) DEFAULT NULL,
  `number` varchar(16) DEFAULT NULL,
  `number_sort` int(11) NOT NULL,
  `floor_number` int(11) NOT NULL,
  `language` varchar(2) DEFAULT NULL,
  `feeder_fair_id` int(11) NOT NULL DEFAULT '0',
  `feeder_fair_pid` int(11) NOT NULL,
  `num_students` int(4) DEFAULT NULL,
  `num_mentors` int(4) DEFAULT NULL,
  `ethics` text,
  `safety` text,
  `accepted` tinyint(1) NOT NULL DEFAULT '0',
  `unavailable_timeslots` tinytext NOT NULL,
  `sa_nom` tinytext NOT NULL,
  `disqualified_from_awards` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pid`),
  UNIQUE KEY `pid` (`pid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


--
-- Table structure for table `queue`
--

DROP TABLE IF EXISTS `queue`;
CREATE TABLE IF NOT EXISTS `queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `command` enum('email','push_award','push_winner','get_stats','') NOT NULL,
  `fair_id` int(11) NOT NULL,
  `award_id` int(11) NOT NULL,
  `prize_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `emails_id` int(11) NOT NULL,
  `email_summary_id` int(11) NOT NULL,
  `to_uid` int(11) NOT NULL,
  `to_email` tinytext NOT NULL,
  `to_name` tinytext NOT NULL,
  `additional_replace` text NOT NULL,
  `sent` datetime NOT NULL,
  `result` enum('queued','ok','rejected','bounced','failed') NOT NULL DEFAULT 'queued',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4381 ;


-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `system_report_id` int(11) NOT NULL,
  `section` tinytext NOT NULL,
  `name` varchar(128) DEFAULT NULL,
  `desc` tinytext,
  `creator` varchar(128) DEFAULT NULL,
  `type` enum('student','judge','award','committee','school','volunteer','tour','fair','fundraising') DEFAULT NULL,
  `use_abs_coords` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=247 DEFAULT CHARSET=utf8;
INSERT INTO `reports` (`id`,`system_report_id`,`section`,`name`,`desc`,`creator`,`type`,`use_abs_coords`) VALUES 
   ('1','1','Students','All Students, Sorted by Lastname','Student Name, Project Number and Title, Category, Division short form sorted by Last Name','The Grant Brothers','student','0'),
   ('2','2','Students','All Students, Sorted by Project Number','Student Name, Project Number and Title, Category sorted by Project Number','The Grant Brothers','student','0'),
   ('4','4','Students','All Students, Grouped by City (for MLAs, VIPs, and Media)','Student Name, Project Num, School Name sorted by Last Name ','The Grant Brothers','student','0'),
   ('9','9','Setup','Front Desk Check-In and T-Shirt Lists','List of students grouped by school.  For the front desk to check-in students and also for tshirts. ','The Grant Brothers','student','0'),
   ('11','11','Students','All Students, Grouped by School','Individual Students, Project Name/Num Grouped by School','The Grant Brothers','student','0'),
   ('17','17','Students','Student Emergency Contact Information','Emergency Contact Names, Relationship, and Phone Numbers for each student.','The Grant Brothers','student','0'),
   ('20','20','Accounting and Ordering','Judges Attending Dinner (look at the row count on the last page)','Count of judges coming to dinner','The Grant Brothers','judge','0'),
   ('25','25','Students','Envelope Labels for all Students','Just the students names and project name/number on a label.','The Grant Brothers','student','0'),
   ('27','27','Judges','Judge Team Assignments','Team assignments for all judges','The Grant Brothers','judge','0'),
   ('28','28','Name Tags','Committee Members stick-on nametags','Name Tags for Committee Members','The Grant Brothers','committee','0');
INSERT INTO `reports` (`id`,`system_report_id`,`section`,`name`,`desc`,`creator`,`type`,`use_abs_coords`) VALUES 
   ('41','0','Winners','CWSF Winners Project List','A list of all awards, and the students/projects that won them ','Dave','student','0'),
   ('42','0','Winners','Award Winners Grouped by School','A list of all awards, and the students/projects that won them ','Dave','student','0'),
   ('47','0','Winners','CWSF Winners Contact Info','CWSF students and contact info  ','Dave','student','0'),
   ('53','37','Awards','Award Sponsor Information (csv)','Sponsor information for each award.  This is a large report so the default format is CSV.','The Grant Brothers','award','0'),
   ('59','43','Accounting and Ordering','T-Shirt Size Count','A list of tshirt sizes (the blank entry is those students who have selected "none"), and the number of tshirts of each size.  ','The Grant Brothers','student','0'),
   ('63','0','Awards','Award Matrix (11x17) For the Committee Room Wall','A big list of reports ','The Grant Brothers','award','0'),
   ('67','0','Winners','Student winner list for the engravers','Award Name, Winning Project Number, students and school name.  Only for awards that have a trophy.','Dave','student','0'),
   ('69','0','Committee','Committee Emergency Response Information','Emergency Contact Info for all committee members.  Their cell phone and first aid/cpr training.','Dave','committee','0'),
   ('77','0','Tours','Student Tour Assignments by Tour','Participant and Tour Assignments grouped by tour, sorted by project number. ','The Grant Brothers','student','0'),
   ('79','0','Winners','Envelope Labels for CWSF Winners','Just the students names and project name/number on a label.','The Grant Brothers','student','0');
INSERT INTO `reports` (`id`,`system_report_id`,`section`,`name`,`desc`,`creator`,`type`,`use_abs_coords`) VALUES 
   ('80','0','Winners','Student Contact Information for All Scholarships','','Dave','student','0'),
   ('86','0','Teacher','Teacher Name, Email, Phone List','Teacher Email/Phone List.  The teacher names and email are entered by the students.  The phone is linked to the shcool info.','Dave','student','0'),
   ('93','0','Winners','All Divisional Winners Contact Info','All Divisional Winners.','Dave','student','0'),
   ('101','0','Judges','Judges Who Mentored a Student','List of Judges that claim to have mentored a student ','Dave','judge','0'),
   ('105','0','Winners','CWSF Winners School/Teacher Contact Info','A list of all awards, and the students/projects that won them ','Dave','student','0'),
   ('109','0','Accounting and Ordering','Registration Fee Totals',' Registration fee totals for all accepted projects ','The Grant Brothers','student','0'),
   ('110','0','CWSF Selection','Project Summaries','','The Grant Brothers','student','1'),
   ('111','0','Ethics','Projects needing ethics forms','       ','','student','0'),
   ('121','0','Name Tags','Judges stick-on nametags','Name Tags for Judges on Avery approx 4 x 3 peelable label stock ','Kit Doan','judge','0'),
   ('122','0','Setup','Stick-on Table Lables to go on cue cards','Sticky labels to go on a card for each table','Kit','student','0');
INSERT INTO `reports` (`id`,`system_report_id`,`section`,`name`,`desc`,`creator`,`type`,`use_abs_coords`) VALUES 
   ('124','0','Certificates','Participation Certificates','Participation certificates for printing on the pre-printed certificate template','Dave','student','1'),
   ('126','0','Certificates','Divisional Gold Certificates','Divisional Award certificates for printing on the pre-printed award certificate template','Dave','student','1'),
   ('127','0','Certificates','Divisional Silver Certificates','Divisional Award certificates for printing on the pre-printed award certificate template','Dave','student','1'),
   ('129','0','Certificates','Divisional Bronze Certificates','Divisional Award certificates for printing on the pre-printed award certificate template','Dave','student','1'),
   ('130','0','Certificates','Divisional Honourable Mention Certificates','Divisional Award certificates for printing on the pre-printed award certificate template','Dave','student','1');
#TABLE: reports_items
DROP TABLE IF EXISTS `reports_items`;
CREATE TABLE `reports_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL DEFAULT '0',
  `type` enum('col','sort','group','distinct','option','filter') DEFAULT NULL,
  `ord` int(11) NOT NULL DEFAULT '0',
  `field` varchar(64) DEFAULT NULL,
  `value` varchar(64) DEFAULT NULL,
  `x` float NOT NULL DEFAULT '0',
  `y` float NOT NULL DEFAULT '0',
  `w` float NOT NULL DEFAULT '0',
  `min_w` float DEFAULT NULL,
  `h` float NOT NULL DEFAULT '0',
  `h_rows` float NOT NULL,
  `fontname` varchar(32) DEFAULT NULL,
  `fontstyle` set('bold','italic','underline','strikethrough') DEFAULT NULL,
  `fontsize` float NOT NULL,
  `align` enum('center','left','right','full') DEFAULT NULL,
  `valign` enum('top','middle','bottom') DEFAULT NULL,
  `on_overflow` enum('nothing','wrap','truncate','...','scale') NOT NULL DEFAULT 'nothing',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','1','col','0','pn','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','1','col','1','name','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','1','col','2','title','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','1','col','3','school','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','1','col','4','grade','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','1','sort','0','last_name','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','1','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','1','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','1','option','2','allow_multiline','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','2','col','0','pn','','0','0','0',NULL,'0','1','','','0','center','top','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','2','col','1','name','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','2','col','2','title','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','2','col','3','school','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','2','col','4','grade','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','2','sort','0','pn','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','2','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','2','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','2','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','11','col','0','pn','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','11','col','1','name','','0','0','0',NULL,'0','1','','','0','center','top','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','11','col','2','title','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','11','col','3','city','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','11','col','4','grade','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','11','group','0','school','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','11','sort','0','pn','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','11','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','11','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','11','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','17','col','0','pn','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','17','col','1','name','','0','0','0',NULL,'0','1','','','0','center','top','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','17','col','2','emerg_name','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','17','col','3','emerg_relation','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','17','col','4','emerg_phone','','0','0','0',NULL,'0','1','','','0','center','top','wrap'),
   ('','17','sort','0','last_name','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','17','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','17','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','17','option','2','allow_multiline','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','17','option','3','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','17','option','4','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','17','option','5','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','17','option','6','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','25','col','0','namefl','','5','5','90',NULL,'28','2','','','0','center','top','truncate'),
   ('','25','col','1','title','','1','35','98',NULL,'27','3','','','0','center','top','truncate'),
   ('','25','col','2','pn','','1','68','98',NULL,'8','1','','','0','center','top','truncate'),
   ('','25','col','3','categorydivision','','1','80','98',NULL,'12','2','','','0','center','top','truncate'),
   ('','25','col','4','school','','1','90','98',NULL,'5','1','','','0','center','top','truncate'),
   ('','25','sort','0','pn','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','25','option','0','type','label','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','25','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','25','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','25','option','3','label_box','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','25','option','4','label_fairname','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','25','option','5','label_logo','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','25','option','6','stock','5164','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','28','col','0','name','','1','15','98',NULL,'24','2','','bold','0','center','top','truncate'),
   ('','28','col','1','static_text','Committee','1','40','98',NULL,'10','1','','','0','center','top','truncate'),
   ('','28','col','2','organization','','1','70','98',NULL,'16','2','','','0','center','top','truncate'),
   ('','28','sort','0','name','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','28','option','0','type','label','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','28','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','28','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','28','option','3','label_box','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','28','option','4','label_fairname','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','28','option','5','label_logo','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','28','option','6','stock','nametag','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','53','col','0','name','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','53','col','1','sponsor_organization','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','53','col','2','sponsor_phone','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','53','col','3','sponsor_address','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','53','col','4','sponsor_city','','0','0','0',NULL,'0','1','','','0','','top','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','53','col','5','sponsor_province','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','53','col','6','sponsor_postal','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','53','col','7','sponsor_notes','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','53','sort','0','name','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','53','option','0','type','csv','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','53','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','53','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','53','option','3','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','53','option','4','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','53','option','5','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','53','option','6','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','col','0','tshirt','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','59','col','1','special_tshirt_count','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','59','sort','0','tshirt','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','59','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','3','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','4','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','5','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','59','option','6','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','filter','0','tshirt','none','5','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','67','col','0','award_name','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','67','col','1','pn','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','67','col','2','bothnames','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','67','col','3','school','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','67','sort','0','award_name','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','67','sort','1','pn','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','67','distinct','0','pn','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','67','option','0','type','csv','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','67','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','67','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','67','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','67','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','67','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','67','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','67','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','67','option','8','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','67','filter','0','award_prize_trophy_any','Yes','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','69','col','0','name','','0','0','0',NULL,'0','1','','','0','','top','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','69','col','1','phone_cel','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','69','col','2','firstaid','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','69','col','3','cpr','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','69','sort','0','last_name','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','69','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','69','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','69','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','69','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','69','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','69','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','69','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','69','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','69','option','8','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','79','col','0','namefl','','5','5','90',NULL,'28','2','','','0','center','','wrap'),
   ('','79','col','1','title','','1','35','98',NULL,'27','3','','','0','center','','wrap'),
   ('','79','col','2','pn','','1','68','98',NULL,'8','1','','','0','center','','wrap'),
   ('','79','col','3','categorydivision','','1','80','98',NULL,'12','2','','','0','center','','wrap'),
   ('','79','col','4','school','','1','90','98',NULL,'5','1','','','0','center','','wrap'),
   ('','79','col','5','award_type','','80','0','19',NULL,'5','1','','bold','8','right','middle','nothing'),
   ('','79','sort','0','pn','','0','0','0',NULL,'0','1','','','0','','','wrap');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','79','option','0','type','label','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','79','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','79','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','79','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','79','option','4','label_box','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','79','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','79','option','6','label_fairname','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','79','option','7','label_logo','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','79','option','8','default_font_size','10','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','79','option','9','stock','5164','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','79','filter','0','award_type','Grand','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','86','col','0','teacher','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','86','col','1','teacheremail','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','86','col','2','school','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','86','col','3','school_phone','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','86','col','4','school_fax','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','86','sort','0','teacher','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','86','distinct','0','teacher','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','86','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','86','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','86','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','86','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','86','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','86','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','86','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','86','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','86','option','8','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','93','col','0','pn','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','93','col','1','namefl','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','93','col','2','phone','','0','0','0',NULL,'0','1','','','0','','top','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','93','col','3','email','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','93','sort','0','pn','','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','93','option','0','type','csv','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','93','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','93','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','93','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','93','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','93','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','93','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','93','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','93','option','8','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','93','filter','0','award_type','Divisional','0','0','0',NULL,'0','1','','','0','','top','truncate'),
   ('','109','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','109','option','8','default_font_size','10','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','9','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','122','col','0','bothnames','','1','70','98',NULL,'14','2','','','0','center','middle','scale'),
   ('','122','col','1','title','','1','5','98',NULL,'24','3','','','0','center','middle','scale'),
   ('','122','col','2','pn','','1','26','98',NULL,'35','1','','','0','center','middle','scale'),
   ('','122','col','3','categorydivision','','1','85','98',NULL,'7','1','','','0','center','middle','scale'),
   ('','122','sort','0','pn','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','122','distinct','0','pn','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','122','option','0','type','label','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','122','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','122','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','122','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','122','option','4','label_box','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','122','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','122','option','6','label_fairname','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','122','option','7','label_logo','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','122','option','8','default_font_size','10','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','122','option','9','stock','5164','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','124','col','0','namefl','','149','50.8','112',NULL,'20','0','scriptin','','34','center','middle','scale'),
   ('','124','col','1','title','','149','76.2','112',NULL,'20','0','tempsitc','','20','center','middle','scale');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','124','col','2','pn','','264','207','10',NULL,'3','1','','','6','right','bottom','scale'),
   ('','124','col','3','fair_year','','135','191','50',NULL,'15','1','segoesb','','28','left','top','scale'),
   ('','124','sort','0','pn','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','124','option','0','type','label','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','124','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','124','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','124','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','124','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','124','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','124','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','124','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','124','option','8','default_font_size','10','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','124','option','9','stock','fullpage_landscape_full','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','126','col','0','namefl','','153','35','112',NULL,'26','0','scriptin','','34','center','middle','scale'),
   ('','126','col','1','title','','153','81','112',NULL,'21','0','tempsitc','','20','center','middle','scale'),
   ('','126','col','2','fair_year','','135','191','50',NULL,'15','1','segoesb','','28','left','top','scale'),
   ('','126','col','3','pn','','264','205','10',NULL,'4','1','','','6','right','bottom','scale'),
   ('','126','col','4','award_prize_name','','153','60','112',NULL,'21','0','tempsitc','','14','center','middle','wrap'),
   ('','126','sort','0','pn','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','126','option','0','type','label','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','126','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','126','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','126','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','126','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','126','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','126','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','126','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','126','option','8','default_font_size','10','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','126','option','9','stock','fullpage_landscape_full','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','126','filter','0','award_type','Divisional','0','0','0',NULL,'0','1','','','0','','','wrap');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','126','filter','1','award_prize_name','Gold','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','127','col','0','namefl','','153','35','112',NULL,'26','0','scriptin','','34','center','middle','scale'),
   ('','127','col','1','title','','153','81','112',NULL,'21','0','tempsitc','','20','center','middle','scale'),
   ('','127','col','2','fair_year','','135','191','50',NULL,'15','1','segoesb','','28','left','top','scale'),
   ('','127','col','3','pn','','264','205','10',NULL,'4','1','','','6','right','bottom','scale'),
   ('','127','col','4','award_prize_name','','153','60','112',NULL,'21','0','tempsitc','','14','center','middle','wrap'),
   ('','127','sort','0','pn','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','127','option','0','type','label','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','127','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','127','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','127','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','127','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','127','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','127','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','127','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','127','option','8','default_font_size','10','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','127','option','9','stock','fullpage_landscape_full','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','127','filter','0','award_type','Divisional','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','127','filter','1','award_prize_name','Silver','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','129','col','0','namefl','','153','35','112',NULL,'26','0','scriptin','','34','center','middle','scale');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','129','col','1','title','','153','81','112',NULL,'21','0','tempsitc','','20','center','middle','scale'),
   ('','129','col','2','fair_year','','135','191','50',NULL,'15','1','segoesb','','28','left','top','scale'),
   ('','129','col','3','pn','','264','205','10',NULL,'4','1','','','6','right','bottom','scale'),
   ('','129','col','4','award_prize_name','','153','60','112',NULL,'21','0','tempsitc','','14','center','middle','wrap'),
   ('','129','sort','0','pn','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','129','option','0','type','label','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','129','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','129','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','129','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','129','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','129','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','129','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','129','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','129','option','8','default_font_size','10','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','129','option','9','stock','fullpage_landscape_full','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','129','filter','0','award_type','Divisional','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','129','filter','1','award_prize_name','Bronze','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','130','col','0','namefl','','153','35','112',NULL,'26','0','scriptin','','34','center','middle','scale'),
   ('','130','col','1','title','','153','81','112',NULL,'21','0','tempsitc','','20','center','middle','scale'),
   ('','130','col','2','fair_year','','135','191','50',NULL,'15','1','segoesb','','28','left','top','scale');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','130','col','3','pn','','264','205','10',NULL,'4','1','','','6','right','bottom','scale'),
   ('','130','col','4','award_prize_name','','153','60','112',NULL,'21','0','tempsitc','','14','center','middle','wrap'),
   ('','130','sort','0','pn','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','130','option','0','type','label','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','130','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','130','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','130','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','130','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','130','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','130','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','130','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','130','option','8','default_font_size','10','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','130','option','9','stock','fullpage_landscape_full','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','130','filter','0','award_type','Divisional','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','130','filter','1','award_prize_name','Honourable Mention','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','109','sort','0','pn','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','109','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','109','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','8','default_font_size','','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','10','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','col','0','tshirt','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','59','col','1','special_tshirt_count','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','59','sort','0','tshirt','','0','0','0',NULL,'0','1','','','0','','','wrap');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','59','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','8','default_font_size','','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','59','option','10','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','filter','0','tshirt','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','109','col','0','pn','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','109','col','1','allnames','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','109','col','2','regfee','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','109','sort','0','pn','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','109','distinct','0','pn','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','109','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','109','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','8','default_font_size','','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','109','option','10','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','col','0','tshirt','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','59','col','1','special_tshirt_count','','0','0','0',NULL,'0','1','','','0','','','wrap');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','59','sort','0','tshirt','','0','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','59','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','8','default_font_size','','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','59','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','option','10','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','59','filter','0','tshirt','none','5','0','0',NULL,'0','1','','','0','','','wrap'),
   ('','110','col','5','static_text','Title:','0','15','30',NULL,'5','1','','','0','right','','nothing'),
   ('','110','col','6','static_text','Student(s):','0','5','30',NULL,'5','1','','','0','right','','nothing'),
   ('','110','col','7','static_text','School:','0','10','30',NULL,'5','1','','','0','right','','nothing'),
   ('','110','col','8','ethics_forms_required','','130','10','45',NULL,'5','1','','','0','right','middle','nothing'),
   ('','110','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','110','option','10','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','110','option','8','default_font_size','11','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','110','option','7','label_logo','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','110','option','6','label_fairname','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','110','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','110','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','110','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','110','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','110','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','110','option','0','type','label','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','110','sort','0','pn','','0','0','0',NULL,'0','0','','','0','','','wrap'),
   ('','110','col','4','abstract','','0','25','175',NULL,'200','0','','','12','','','scale');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','110','col','3','title','','32','15','100',NULL,'5','1','','','0','','','wrap'),
   ('','110','col','2','school','','32','10','100',NULL,'5','1','','','0','','','wrap'),
   ('','110','col','1','allnames','','32','5','100',NULL,'5','1','','','0','','','wrap'),
   ('','110','col','0','pn','','130','0','45',NULL,'7','1','','','0','right','','wrap'),
   ('','27','col','0','name','','1','15','98',NULL,'24','2','','bold','0','center','top','truncate'),
   ('','27','col','1','team_round','','1','40','98',NULL,'10','1','','','0','center','top','truncate'),
   ('','27','col','2','team_num','','1','70','98',NULL,'16','2','','','0','center','top','truncate'),
   ('','27','col','3','team_name','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','27','col','4','team_award_name','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','27','group','0','team_round','','0','0','0',NULL,'0','0','','','0','','','');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','27','sort','0','last_name','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','27','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','27','option','1','group_new_page','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','27','option','2','allow_multiline','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','27','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','27','option','4','label_box','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','27','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','27','option','6','label_fairname','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','27','option','7','label_logo','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','27','option','8','default_font_size','','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','27','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','27','option','10','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','63','col','0','matrix_data','','0','0','0',NULL,'0','1','','','0','left','top','wrap'),
   ('','63','col','1','empty_winner_box','','0','0','0',NULL,'0','1','','','0','left','top','wrap'),
   ('','63','group','0','type','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','63','sort','0','name','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','63','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','63','option','1','group_new_page','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','63','option','2','allow_multiline','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','63','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','63','option','4','label_box','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','63','option','5','field_box','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','63','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','63','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','63','option','8','default_font_size','12','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','63','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','63','option','10','stock','ledger_landscape','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','9','col','0','pn','','0','0','0',NULL,'0','1','','','0','center','top','nothing'),
   ('','9','col','1','title','','0','0','0',NULL,'0','1','','','0','center','top','...'),
   ('','9','col','2','name','','0','0','0',NULL,'0','1','','','0','center','top','nothing');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','9','col','3','ethics_forms_required','','0','0','0',NULL,'0','1','','','0','center','top','nothing'),
   ('','9','col','4','tshirt','','0','0','0',NULL,'0','1','','','0','center','top','nothing'),
   ('','9','group','0','school','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','9','sort','0','pn','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','9','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','9','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','9','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','9','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','9','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','9','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','9','option','6','label_fairname','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','9','option','7','label_logo','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','9','option','8','default_font_size','10','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','9','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','9','option','10','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','4','col','0','pn','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','4','col','1','name','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','4','col','2','school','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','4','col','3','grade','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','4','group','0','city','','0','0','0',NULL,'0','0','','','0','','','');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','4','sort','0','last_name','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','4','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','4','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','4','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','4','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','4','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','4','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','4','option','6','label_fairname','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','4','option','7','label_logo','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','4','option','8','default_font_size','10','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','4','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','4','option','10','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','77','col','0','pn','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','77','col','1','namefl','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','77','group','0','tour_assign_numname','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','77','sort','0','pn','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','77','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','77','option','1','group_new_page','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','77','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','77','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','77','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','77','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','77','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','77','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','77','option','8','default_font_size','','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','77','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','77','option','10','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','101','col','0','namefl','','0','0','0',NULL,'0','1','','','0','left','top','wrap'),
   ('','101','col','1','organization','','0','0','0',NULL,'0','1','','','0','left','top','wrap'),
   ('','101','col','2','email','','0','0','0',NULL,'0','1','','','0','left','top','wrap');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','101','col','3','phone_home','','0','0','0',NULL,'0','1','','','0','left','top','wrap'),
   ('','101','sort','0','last_name','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','101','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','101','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','101','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','101','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','101','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','101','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','101','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','101','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','101','option','8','default_font_size','','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','101','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','101','option','10','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','101','filter','0','j_mentored','1','0','0','0',NULL,'0','0','','','0','','',''),
   ('','20','col','0','name','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','20','col','1','j_dinner','','0','0','0',NULL,'0','1','','','0','center','top','truncate'),
   ('','20','sort','0','name','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','20','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','20','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','20','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','20','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','20','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','20','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','20','option','6','label_fairname','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','20','option','7','label_logo','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','20','option','8','default_font_size','','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','20','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','20','option','10','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','20','filter','0','j_dinner','1','0','0','0',NULL,'0','0','','','0','','',''),
   ('','121','col','0','namefl','','1','15','98',NULL,'24','2','','','0','center','top','wrap');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','121','col','1','static_text','','1','40','98',NULL,'10','1','','','0','center','top','wrap'),
   ('','121','col','2','organization','','1','70','98',NULL,'16','2','','','0','center','top','wrap'),
   ('','121','sort','0','namefl','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','121','option','0','type','label','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','121','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','121','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','121','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','121','option','4','label_box','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','121','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','121','option','6','label_fairname','yes','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','121','option','7','label_logo','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','121','option','8','default_font_size','10','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','121','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','121','option','10','stock','5164','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','111','col','0','pn','','0','0','0',NULL,'0','1','','','0','left','top','wrap'),
   ('','111','col','1','allnames','','0','0','0',NULL,'0','1','','','0','left','top','wrap'),
   ('','111','col','2','ethics_forms_required','','0','0','0',NULL,'0','1','','','0','left','top','wrap'),
   ('','111','group','0','fair_year','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','111','sort','0','pn','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','111','distinct','0','pn','','0','0','0',NULL,'0','0','','','0','','','');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','111','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','111','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','111','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','111','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','111','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','111','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','111','option','6','label_fairname','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','111','option','7','label_logo','yes','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','111','option','8','default_font_size','','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','111','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','111','option','10','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','111','filter','0','ethics_forms_required','','5','0','0',NULL,'0','0','','','0','','',''),
   ('','41','col','0','pn','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','41','col','1','namefl','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','41','col','2','title','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','41','col','3','school','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','41','sort','0','pn','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','41','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','41','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','41','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','41','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','41','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','41','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','41','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','41','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','41','option','8','default_font_size','','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','41','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','41','option','10','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','41','filter','0','award_type','Grand','0','0','0',NULL,'0','0','','','0','','',''),
   ('','105','col','0','pn','','0','0','0',NULL,'0','1','','','0','left','top','wrap');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','105','col','1','bothnames','','0','0','0',NULL,'0','1','','','0','left','top','wrap'),
   ('','105','col','2','title','','0','0','0',NULL,'0','1','','','0','left','top','wrap'),
   ('','105','col','3','school_phone','','0','0','0',NULL,'0','1','','','0','left','top','wrap'),
   ('','105','col','4','teacher','','0','0','0',NULL,'0','1','','','0','left','top','wrap'),
   ('','105','group','0','school','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','105','sort','0','pn','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','105','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','105','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','105','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','105','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','105','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','105','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','105','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','105','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','105','option','8','default_font_size','','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','105','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','105','option','10','stock','fullpage_landscape','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','105','filter','0','award_type','Grand','0','0','0',NULL,'0','0','','','0','','',''),
   ('','42','col','0','pn','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','42','col','1','namefl','','0','0','0',NULL,'0','1','','','0','left','top','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','42','col','2','award_name','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','42','col','3','award_prize_name','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','42','group','0','school','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','42','sort','0','pn','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','42','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','42','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','42','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','42','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','42','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','42','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','42','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','42','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','42','option','8','default_font_size','','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','42','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','42','option','10','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','80','col','0','award_name','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','80','col','1','award_prize_name','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','80','col','2','pn','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','80','col','3','namefl','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','80','col','4','title','','0','0','0',NULL,'0','1','','','0','left','top','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','80','col','5','phone','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','80','col','6','address_full','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','80','col','7','email','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','80','col','8','school','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','80','col','9','schooladdr','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','80','col','10','school_board','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','80','col','11','award_prize_scholarship','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','80','sort','0','award_name','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','80','option','0','type','csv','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','80','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','80','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','80','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','80','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','80','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','80','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','80','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','80','option','8','default_font_size','','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','80','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','80','option','10','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','80','filter','0','award_prize_scholarship','','5','0','0',NULL,'0','0','','','0','','','');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','47','col','0','pn','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','47','col','1','namefl','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','47','col','2','email','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','47','col','3','phone','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','47','col','4','city','','0','0','0',NULL,'0','1','','','0','left','top','truncate'),
   ('','47','col','5','birthdate','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','47','sort','0','pn','','0','0','0',NULL,'0','0','','','0','','',''),
   ('','47','option','0','type','pdf','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','47','option','1','group_new_page','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','47','option','2','allow_multiline','no','0','0','0',NULL,'0','0','','','0','','','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','47','option','3','fit_columns','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','47','option','4','label_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','47','option','5','field_box','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','47','option','6','label_fairname','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','47','option','7','label_logo','no','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','47','option','8','default_font_size','','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','47','option','9','include_registrations','complete','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','47','option','10','stock','fullpage','0','0','0',NULL,'0','0','','','0','','','truncate'),
   ('','47','filter','0','award_type','Grand','0','0','0',NULL,'0','0','','','0','','',''),
   ('','93','group','0','award_name','','0','0','0',NULL,'0','1','',NULL,'0','','top','truncate');
INSERT INTO `reports_items` (`id`,`report_id`,`type`,`ord`,`field`,`value`,`x`,`y`,`w`,`min_w`,`h`,`h_rows`,`fontname`,`fontstyle`,`fontsize`,`align`,`valign`,`on_overflow`) VALUES 
   ('','93','group','1','award_prize_name','','0','0','0',NULL,'0','1','',NULL,'0','','top','truncate');

--
-- Table structure for table `schools`
--

DROP TABLE IF EXISTS `schools`;
CREATE TABLE IF NOT EXISTS `schools` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `common_id` int(11) NOT NULL,
  `school` varchar(64) DEFAULT NULL,
  `schoollang` char(2) DEFAULT NULL,
  `schoollevel` varchar(32) DEFAULT NULL,
  `board` varchar(64) DEFAULT NULL,
  `district` varchar(64) DEFAULT NULL,
  `phone` varchar(16) DEFAULT NULL,
  `fax` varchar(16) DEFAULT NULL,
  `address` varchar(64) DEFAULT NULL,
  `city` varchar(32) DEFAULT NULL,
  `province` char(2) DEFAULT NULL,
  `postalcode` varchar(7) DEFAULT NULL,
  `designate` enum('','public','independent','home') DEFAULT NULL,
  `principal` varchar(64) DEFAULT NULL,
  `principal_email` varchar(128) DEFAULT NULL,
  `principal_phone` varchar(32) DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  `sciencehead` varchar(64) DEFAULT NULL,
  `sciencehead_email` varchar(128) DEFAULT NULL,
  `sciencehead_phone` varchar(32) DEFAULT NULL,
  `year` int(10) unsigned NOT NULL DEFAULT '0',
  `junior` tinyint(4) NOT NULL DEFAULT '0',
  `intermediate` tinyint(4) NOT NULL DEFAULT '0',
  `senior` tinyint(4) NOT NULL DEFAULT '0',
  `registration_password` varchar(32) DEFAULT NULL,
  `projectlimit` int(10) NOT NULL DEFAULT '0',
  `projectlimitper` enum('total','agecategory') DEFAULT NULL,
  `atrisk` enum('no','yes') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Table structure for table `timeslots`
--

DROP TABLE IF EXISTS `timeslots`;
CREATE TABLE IF NOT EXISTS `timeslots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` tinytext NOT NULL,
  `year` int(11) NOT NULL,
  `round` int(11) NOT NULL,
  `start` int(11) NOT NULL,
  `num_timeslots` int(11) NOT NULL,
  `timeslot_length` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `timeslots`
--

INSERT INTO `timeslots` (`id`, `name`, `year`, `round`, `start`, `num_timeslots`, `timeslot_length`) VALUES
(1, 'Round 1', 0, 0, 120, 9, 20),
(2, 'Round 2', 0, 1, 360, 9, 20);

--
-- Table structure for table `timeslot_assignments`
--

DROP TABLE IF EXISTS `timeslot_assignments`;
CREATE TABLE IF NOT EXISTS `timeslot_assignments` (
  `id` int(11) NOT NULL,
  `timeslot_id` int(11) NOT NULL,
  `timeslot_num` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `judging_team_id` int(11) NOT NULL,
  `judge_id` int(11) NOT NULL,
  `type` enum('divisional','special','free') NOT NULL,
  `year` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `tours`
--

DROP TABLE IF EXISTS `tours`;
CREATE TABLE IF NOT EXISTS `tours` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year` int(10) unsigned NOT NULL DEFAULT '0',
  `name` tinytext,
  `num` int(11) NOT NULL,
  `description` text,
  `capacity_min` int(11) NOT NULL,
  `capacity_max` int(11) NOT NULL DEFAULT '0',
  `grade_min` int(11) NOT NULL DEFAULT '7',
  `grade_max` int(11) NOT NULL DEFAULT '12',
  `contact` tinytext,
  `location` tinytext,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

 --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `unique_uid` int(11) NOT NULL,
  `username` varchar(32) DEFAULT NULL,
  `email` tinytext NOT NULL,
  `password` varchar(128) NOT NULL,
  `salt` varchar(128) NOT NULL,
  `password_expired` tinyint(1) NOT NULL,
  `new` tinyint(1) NOT NULL DEFAULT '1',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `attending` tinyint(1) NOT NULL DEFAULT '1',
  `year` int(11) NOT NULL,
  `roles` set('student','judge','committee','volunteer','fair','teacher','sponsor') DEFAULT NULL,
  `reg_close_override` datetime DEFAULT NULL,
  `salutation` varchar(8) DEFAULT NULL,
  `firstname` varchar(64) DEFAULT NULL,
  `lastname` varchar(64) DEFAULT NULL,
  `pronounce` varchar(64) DEFAULT NULL,
  `sex` enum('male','female') DEFAULT NULL,
  `phone1` varchar(32) DEFAULT NULL,
  `phone2` varchar(32) DEFAULT NULL,
  `organization` varchar(64) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `language` varchar(2) DEFAULT NULL,
  `address` varchar(64) DEFAULT NULL,
  `address2` varchar(64) DEFAULT NULL,
  `city` varchar(64) DEFAULT NULL,
  `province` varchar(3) DEFAULT NULL,
  `postalcode` varchar(8) DEFAULT NULL,
  `website` tinytext,
  `schools_id` int(11) DEFAULT NULL,
  `grade` int(11) DEFAULT NULL,
  `s_teacher` tinytext,
  `s_teacher_email` tinytext,
  `firstaid` tinyint(1) DEFAULT NULL,
  `cpr` tinyint(1) DEFAULT NULL,
  `medicalert` tinytext,
  `food_req` tinytext,
  `s_complete` tinyint(1) NOT NULL DEFAULT '0',
  `s_paid` tinyint(1) NOT NULL DEFAULT '0',
  `s_accepted` tinyint(1) NOT NULL DEFAULT '0',
  `s_pid` int(11) DEFAULT NULL,
  `s_web_firstname` tinyint(1) NOT NULL DEFAULT '1',
  `s_web_lastname` tinyint(1) NOT NULL DEFAULT '1',
  `s_web_photo` tinyint(1) NOT NULL DEFAULT '1',
  `tshirt` varchar(8) DEFAULT NULL,
  `tour_id_pref` tinytext,
  `tour_id` int(11) DEFAULT NULL,
  `j_complete` tinyint(1) NOT NULL DEFAULT '0',
  `j_psd` tinytext,
  `j_years_school` int(4) DEFAULT NULL,
  `j_years_regional` int(4) DEFAULT NULL,
  `j_years_national` int(4) DEFAULT NULL,
  `j_rounds` tinytext,
  `j_willing_lead` tinyint(1) DEFAULT NULL,
  `j_dinner` tinyint(1) DEFAULT NULL,
  `j_languages` tinytext,
  `j_sa_only` tinyint(1) DEFAULT NULL,
  `j_sa` tinytext,
  `j_cat_pref` int(11) DEFAULT NULL,
  `j_div_pref` tinytext,
  `j_mentored` tinyint(1) DEFAULT NULL,
  `v_complete` tinyint(1) NOT NULL DEFAULT '0',
  `v_relation` tinytext,
  `v_tour_match_username` tinyint(1) DEFAULT NULL,
  `v_tour_username` tinytext,
  `v_reason` text,
  `fair_id` int(11) NOT NULL DEFAULT '0',
  `fair_uid` int(11) NOT NULL,
  `notes` text,
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

INSERT INTO `users` (`uid`, `unique_uid`, `username`, `email`, `password`, `salt`, `password_expired`, `new`, `enabled`, `attending`, `year`, `roles`, `reg_close_override`, `salutation`, `firstname`, `lastname`, `pronounce`, `sex`, `phone1`, `phone2`, `organization`, `birthdate`, `language`, `address`, `address2`, `city`, `province`, `postalcode`, `website`, `schools_id`, `grade`, `s_teacher`, `s_teacher_email`, `firstaid`, `cpr`, `medicalert`, `food_req`, `s_complete`, `s_paid`, `s_accepted`, `s_pid`, `s_web_firstname`, `s_web_lastname`, `s_web_photo`, `tshirt`, `tour_id_pref`, `tour_id`, `j_complete`, `j_psd`, `j_years_school`, `j_years_regional`, `j_years_national`, `j_rounds`, `j_willing_lead`, `j_dinner`, `j_languages`, `j_sa_only`, `j_sa`, `j_cat_pref`, `j_div_pref`, `j_mentored`, `v_complete`, `v_relation`, `v_tour_match_username`, `v_tour_username`, `v_reason`, `fair_id`, `fair_uid`, `notes`) VALUES
(1, 1, 'admin', 'admin@yourfair.com', 'dc4f6cb2cdc9e3a95055392dada70ce780a965922850e4ea7d478a572aa67436fa00920ef97efdd7c2d4ac621f9eebc795978517b28e6d2b677d022e4bc68fce', '0', 0, 0, 1, 0, 0, 'committee', NULL, '', 'Admin', '', '', 'male', NULL, NULL, NULL, '0000-00-00', 'en', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '', '', NULL, NULL, '', NULL, 0, 0, 0, 0, 1, 1, 1, '', NULL, NULL, 1, 'bachelor', 2, 2, 3, '0', 1, 1, 'en', 0, '', 1, '', 0, 0, NULL, NULL, NULL, NULL, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `winners`
--

DROP TABLE IF EXISTS `winners`;
CREATE TABLE IF NOT EXISTS `winners` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `award_prize_id` int(10) unsigned NOT NULL DEFAULT '0',
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `year` int(10) unsigned NOT NULL DEFAULT '0',
  `fair_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `award_prize_id` (`award_prize_id`,`pid`,`year`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
