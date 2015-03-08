ALTER TABLE `schools` DROP `district` ;
ALTER TABLE `schools` CHANGE `schoollevel` `grades` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
ALTER TABLE `schools` CHANGE `common_id` `identifier` VARCHAR( 32 ) NOT NULL ;
ALTER TABLE `schools` CHANGE `schoollang` `language` CHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
ALTER TABLE `schools` CHANGE `designate` `type` ENUM( '', 'public', 'independent', 'home' ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;

