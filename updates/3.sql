ALTER TABLE `projects` ADD `ethics` TEXT NULL ,
ADD `safety` TEXT NULL ;

ALTER TABLE `users` ADD `s_sa_nom` TINYTEXT NULL AFTER `s_tshirt` ;
