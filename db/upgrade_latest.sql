#
# $Id: upgrade_latest.sql 6177 2012-08-14 07:51:05Z ajdonnison $
#
# DO NOT USE THIS SCRIPT DIRECTLY - USE THE INSTALLER INSTEAD.
#
# All entries must be date stamped in the correct format.
#

# 20120814
# Extend value_charvalue from 250 to 1000 characters
ALTER TABLE `%dbprefix%custom_fields_values` MODIFY `value_charvalue` `value_charvalue` VARCHAR( 1000 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;

# 20260220
# Journal Module structure
DROP TABLE IF EXISTS `%dbprefix%journal`;
CREATE TABLE `%dbprefix%journal` (
  `journal_id` int(10) unsigned NOT NULL auto_increment,
  `journal_user` int(10) NOT NULL default '0',
  `journal_module` int(10) NOT NULL default '0',
  `journal_project` int(10) NOT NULL default '0',
  `journal_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `journal_description` text,
  PRIMARY KEY (`journal_id`),
  UNIQUE KEY (`journal_id`)
);
