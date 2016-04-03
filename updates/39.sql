INSERT INTO `config` ( `var` , `val` , `category` , `type` , `type_values` , `order` , `name` , `description`) VALUES 
	('report_split_ceremony', '0', 'Reports', 'yesno', '', '0', '', 'Split the ceremony script and presentation into Junior and Int+Sr');

UPDATE `reports_items` SET `value`='label' WHERE `value`='tcpdf_label';
UPDATE `reports` SET `use_abs_coords`=1 WHERE `name`='Table Labels';


