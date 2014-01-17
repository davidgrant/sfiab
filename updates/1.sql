ALTER TABLE `users` CHANGE `phonehome` `phone1` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
CHANGE `phonework` `phone2` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
CHANGE `lang` `language` VARCHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;

ALTER TABLE `users` DROP `phonecell` ;

ALTER TABLE `users` CHANGE `teacher` `s_teacher` TINYTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
CHANGE `teacheremail` `s_teacheremail` TINYTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
CHANGE `student_status` `s_status` ENUM( 'inprogress', 'paymentpending', 'complete' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'inprogress',
CHANGE `student_pid` `s_pid` INT( 11 ) NULL DEFAULT NULL ,
CHANGE `tshirt` `s_tshirt` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;

ALTER TABLE `users` ADD `j_psd` TINYTEXT NULL ,
ADD `j_years_school` INT( 4 ) NULL ,
ADD `j_years_regional` INT( 4 ) NULL ,
ADD `j_years_national` INT( 4 ) NULL ,
ADD `j_rounds` SET( '1', '2','3','4','5') NULL ,
ADD `j_willing_lead` BOOLEAN NULL ,
ADD `j_dinner` BOOLEAN NULL ,
ADD `j_languages` TINYTEXT NULL ,
ADD `j_sa` TINYTEXT NULL ,
ADD `j_pref_cat` INT NULL ,
ADD `j_pref_div1` INT NULL ,
ADD `j_pref_div2` INT NULL ,
ADD `j_pref_div3` INT NULL ;

ALTER TABLE `users` ADD `j_status` ENUM( 'incomplete', 'notattending', 'complete' ) NOT NULL DEFAULT 'incomplete' AFTER `emerg2_phone3`

ALTER TABLE `users` ADD `j_mentored` BOOLEAN NULL ;

ALTER TABLE `users` CHANGE `j_rounds` `j_rounds` SET( '0', '1', '2', '3', '4', '5' ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
