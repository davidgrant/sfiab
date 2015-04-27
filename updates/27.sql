ALTER TABLE `fairs` CHANGE `type` `type` ENUM( 'sfiab_feeder', 'sfiab_upstream', 'ysc', 'old_sfiab2_feeder' ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
