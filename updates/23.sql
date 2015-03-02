UPDATE `config` SET `type_values` = 'CCHHXX=CCHHXX (2-digit Category, 2-digit Challenge, 2-digit Number)|CHXX=CHXX (1-digit Category, 1-digit Challenge, 2-digit Number)|X4=XXXX (4-digit sequential numbering)|c_X3_h=c XXX h (Category Shortform, 3-digit sequential, Challenge Shortform)' WHERE `config`.`var` = 'project_number_format';
UPDATE `config` SET val='CCHHXX' WHERE val='CHN';

