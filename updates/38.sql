ALTER TABLE `users` ADD `checked_in` BOOLEAN NOT NULL AFTER `food_req` ,
		    ADD `tshirt_given` BOOLEAN NOT NULL AFTER `checked_in` ;
