ALTER TABLE `reports` ADD `group_new_page` VARCHAR(1) NOT NULL DEFAULT '0' AFTER `use_abs_coords`;
ALTER TABLE `reports` ADD `format` ENUM('pdf','csv','label') NOT NULL DEFAULT 'pdf' AFTER `type`;
ALTER TABLE `reports` ADD `default_font_size` INT NOT NULL DEFAULT '11' AFTER `group_new_page`, ADD `include_registrations` ENUM('all','almost','complete') NOT NULL DEFAULT 'complete' AFTER `default_font_size`, ADD `allow_multiline` BOOLEAN NOT NULL DEFAULT FALSE AFTER `include_registrations`, ADD `fit_columns` BOOLEAN NOT NULL DEFAULT FALSE AFTER `allow_multiline`, ADD `label_box` BOOLEAN NOT NULL DEFAULT FALSE AFTER `fit_columns`, ADD `field_box` BOOLEAN NOT NULL DEFAULT FALSE AFTER `label_box`, ADD `label_fairname` BOOLEAN NOT NULL DEFAULT TRUE AFTER `field_box`, ADD `label_logo` BOOLEAN NOT NULL DEFAULT TRUE AFTER `label_fairname`;
ALTER TABLE `reports` ADD `stock` TINYTEXT NOT NULL AFTER `format`;
DELETE FROM `reports_items` WHERE `type`='option';
ALTER TABLE `reports_items` CHANGE `type` `type` ENUM('col','sort','group','distinct','filter') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

