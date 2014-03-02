ALTER TABLE `projects` DROP `summary_count` ;
ALTER TABLE `projects` ADD `number_sort` INT NOT NULL AFTER `number` ,
			ADD `floor_number` INT NOT NULL AFTER `number_sort` ;

ALTER TABLE `users` ADD `s_paid` BOOLEAN NOT NULL DEFAULT FALSE AFTER `s_complete` ;
			
CREATE TABLE IF NOT EXISTS `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `system_report_id` int(11) NOT NULL,
  `section` tinytext NOT NULL,
  `name` varchar(128) DEFAULT NULL,
  `desc` tinytext,
  `creator` varchar(128) DEFAULT NULL,
  `type` enum('student','judge','award','committee','school','volunteer','tour','fair','fundraising') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=247 ;


CREATE TABLE IF NOT EXISTS `reports_items` (
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
  `on_overflow` enum('nothing','truncate','...','scale') NOT NULL DEFAULT 'nothing',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10478 ;

