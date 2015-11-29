
ALTER TABLE `queue` CHANGE `command` `command` ENUM( 'email', 'push_award', 'push_winner', 'get_stats', 'push_stats', 'judge_scheduler', 'tour_scheduler', 'timeslot_scheduler', 'exhibithall_scheduler' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
