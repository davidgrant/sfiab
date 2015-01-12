INSERT INTO `cms` (`id`, `name`, `type`, `text`, `language`, `use`) VALUES (NULL, 'sig_release_of_information', 'signaturepage', 'Pursuant to the freedom of information and protection of privacy, I, as the parent or legal guardian of:
<br>
<br>
<table border="0" cellpadding="2">
<tr>
<td align="right" width="100">Participant Name:</td>
<td ><b>[NAME]</b></td>
</tr>
</table><br><br>
do hereby grant my permission to take, retain, and publish [HISHER] photograph and written materials about [HIMHER] and [HISHER] [YEAR] [FAIRNAME] project to be displayed on print materials and on the Internet through the [FAIRNAME] and award sponsor websites.<br/>
<br/>
I hereby give permission to use the materials to promote the Science Fair Program. This would include media, various social media sites, award sponsors, potential sponsors.  I understand that materials on social media sites are in the public domain and these online services may be located outside of Canada.<br/>
<br>
Please SIGN and return this form, along with other forms in this package, to the [FAIRNAME].<br/>', 'en', '1');


INSERT INTO `config` ( `var` , `val` , `category` , `type` , `type_values` , `order` , `name` , `description`) VALUES 
	('sig_enable_senior_marks_form', '1', 'Student Registration', 'yesno', '', '0', 'Enable Senior Marks Validation Form', 'Enable the Senior Marks Validation Form on the Student Signature Page. This form will be included for all students in grade 11 or 12.'),
	('sig_enable_release_of_information', '1', 'Student Registration', 'yesno', '', '0', 'Enable Release of Information Form', 'Enable the Release of Information Form on the Student Signature Page. This form gives permission to the fair to use the student\'s project information for promotional purposes.');

