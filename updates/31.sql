UPDATE emails SET `body` = 'A request for your electronic signature has been made by [STUDENT_NAME].

We are using electronic signatures in place of the printed signature form, here is how the electronic signature form works:
1. Click on the link below and you will be taken to a page in our registration system with information about the student and student\'s project.
2. Review this information as well as the legal statement(s) on that page.
3. If you agree with the information and statement(s), type in your name in the space provided on that page, and press \'Submit My Electronic Signature\'.

Electronic Signature Link: [SIGNATURE_LINK]

The student will still have a paper copy of the signature form, but you do not have to sign it.  If you prefer to sign the printed form, please request the form from the student and ignore this email.' WHERE `name`='Electronic Signature';

ALTER TABLE `cms` ADD `head` TINYTEXT NOT NULL AFTER `type` ,
		  ADD `desc` TEXT NOT NULL AFTER `head` ;

INSERT INTO `cms` (`id`, `name`, `type`, `head`,`desc`,`text`, `language`, `use`) VALUES (NULL, 'sig_form_instructions', 'signaturepage', 'Signature Form: Paper Form Instructions', 'This is only for the printed version of the signature form.  If defined, it creates an "Instructions" section right after the student information and displays the text entered here.', NULL, 'en', '1');

INSERT INTO `cms` (`id`, `name`, `type`, `head`,`desc`,`text`, `language`, `use`) VALUES (NULL, 'sig_release_of_information_student', 'signaturepage', 'Signature Form: Student Release of Information', 'This text only appears on the student electronic signature page.  You can use words like "my" in here to refer to the student (whereas on the parent/guardian version that wouldn\'t make sense).' ,
'Pursuant to the freedom of information and protection of privacy, I, <b>[NAME]</b>, do hereby grant my permission to take, retain, and publish my photograph and written materials about my [YEAR] [FAIRNAME] project on printed materials and on the Internet through the [FAIRNAME], and award sponsor websites.

I hereby give permission to use the materials to promote the Science Fair Program. This would include media, various social media sites, award sponsors, potential sponsors.  I understand that materials on social media sites are in the public domain and these online services may be located outside of Canada.', 'en', '1');


UPDATE `cms` SET `name`='sig_student_declaration', `head`='Signature Form: Student Declaration', `desc`='Student declaration for both the printed signature form and the electronic signature page.' WHERE `name`='exhibitordeclaration';
UPDATE `cms` SET `name`='sig_parent_declaration', `head`='Signature Form: Parent/Guardian Declaration', `desc`='Parent/Guardian declaration for both the printed signature form and the electronic signature page' WHERE `name`='parentdeclaration';
UPDATE `cms` SET `name`='sig_teacher_declaration', `head`='Signature Form: Teacher Declaration', `desc`='Teacher declaration for both the printed signature form and the electronic signature page' WHERE `name`='teacherdeclaration';

UPDATE `cms` SET `name`='sig_form_postamble', `head`='Signature Form: Paper Form Post-Amble', `desc`='If defined, creates an "Additional Information" section at the end of the signature page and includes the entered text.  Useful for including mailing instructions or other bits of information that doesn\'t need to be front and center in the instructions' WHERE `name`='postamble';
UPDATE `cms` SET `name`='sig_form_regfee', `head`='Signature Form: Paper Form Registration Fee', `desc`='If defined, creates an "Registration Fee" section at the end of the signature page and includes the entered text along with a table of the registration fee summary.' WHERE `name`='regfee';
UPDATE `cms` SET `head`='Page: Main Page', `desc`='The text on the main registration system page.' WHERE `name`='main';
UPDATE `cms` SET `head`='Page: Contact Us', `desc`='The text on the contact us page' WHERE `name`='contact_us';
UPDATE `cms` SET `head`='Page: Student Main Page', `desc`='The text on the main student registration page when a student logs in.' WHERE `name`='s_main';
UPDATE `cms` SET `head`='Page: Volunteer Main Page', `desc`='The text on the main volunteer registration page when a volunteer logs in.' WHERE `name`='v_main';

UPDATE `cms` SET `name`='sig_release_of_information_parent', `head`='Signature Form: Parent/Guardian Release of Information', `desc`='This text is the "Release of Information" section of the signature form.  If not-empty, it creates a Release of Information section for each student with spots for parent and student signatures.  It is also shown to both the parents on the electronic signature page.' WHERE `name`='sig_release_of_information';


ALTER TABLE `users` ADD `j_heard_about` TEXT NULL AFTER `j_avoid_project_ids` ;
