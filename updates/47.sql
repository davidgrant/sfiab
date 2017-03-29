DELETE FROM `reports_items` WHERE `field`='req_table';
ALTER TABLE  `categories` CHANGE  `shortform`  `shortform` CHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '';
ALTER TABLE  `categories` CHANGE  `name`  `name` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '';

