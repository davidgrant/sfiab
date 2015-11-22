CREATE TABLE IF NOT EXISTS `signatures` (
  `key` varchar(32) NOT NULL,
  `uid` int(11) NOT NULL,
  `type` enum('student','parent','teacher','ethics') NOT NULL,
  `name` tinytext NOT NULL,
  `email` tinytext NOT NULL,
  `signed_name` tinytext,
  `date_sent` datetime NOT NULL,
  `date_signed` datetime NOT NULL,
  `year` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO `emails` ( `id` , `name` , `section` , `description` , `from_name` , `from_email` , `subject` , `body` , `bodyhtml`) VALUES 
	('', 'Electronic Signature', 'System', 'Electronic Signature Email', '[FAIRABBR] Registration', '[EMAIL_REGISTRATION]', 'Electronic Signature for the [FAIRNAME]', 'A request for your electronic signature has been made by [STUDENT_NAME].

We are using electronic signatures in place of the old printed signature form.  Here is how the electronic signature form works:
1. Click on the link below and you will be taken to a page in our registration system with information about the student and student\'s project.
2. Review this information as well as the legal statement on that page.
3. If you agree with the information and statement, type in your name in the space provided on that page, and press \'Submit My Electronic Signature\'.

Electronic Signature Link: [SIGNATURE_LINK]

The student will still have a paper copy of the signature form, but you do not have to sign it.  If you prefer to sign the printed form, please request the form from the student and ignore this email.', ''); 

INSERT INTO `config` ( `var` , `val` , `category` , `type` , `type_values` , `order` , `name`, `description`) VALUES
	('enable_electronic_signatures', '0', 'Student Registration', 'yesno', '', '0', 'Enable Electronic Signatures', 'Allow students submit electronic signatures instead of a printed signature form.  The option for printing a signature form is still available.');

INSERT INTO `cms` (`id`, `name`, `type`, `text`, `language`, `use`) VALUES (NULL, 'electronic_exhibitor_declaration', 'signaturepage', 'I certify that:
 1. This project is my own work (and my partner\'s if applicable).
 2. No more than two (2) students have worked on this project.
 3. I agree that the decision of the judges will be final.
 4. For projects using humans or animals, proper ethics approval has been obtained.
', 'en', '1');


INSERT INTO `cms` (`id`, `name`, `type`, `text`, `language`, `use`) VALUES (NULL, 'sig_release_of_information_student', 'signaturepage', 'Pursuant to the freedom of information and protection of privacy, I, [NAME], do hereby grant my permission to take, retain, and publish my photograph and written materials about me and my [YEAR] [FAIRNAME] project on printed materials and on the Internet through the [FAIRNAME], and award sponsor websites.

I hereby give permission to use the materials to promote the Science Fair Program. This would include media, various social media sites, award sponsors, potential sponsors.  I understand that materials on social media sites are in the public domain and these online services may be located outside of Canada.', 'en', '1');

