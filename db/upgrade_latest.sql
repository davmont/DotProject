#
# $Id: upgrade_latest.sql 6177 2012-08-14 07:51:05Z ajdonnison $
#
# DO NOT USE THIS SCRIPT DIRECTLY - USE THE INSTALLER INSTEAD.
#
# All entries must be date stamped in the correct format.
#

# Extend value_charvalue from 250 to 1000 characters
ALTER TABLE `%dbprefix%custom_fields_values` MODIFY `value_charvalue` `value_charvalue` VARCHAR( 1000 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;

# 20261009
# Widen user_password for password_hash() output and add password reset token columns
ALTER TABLE `%dbprefix%users` MODIFY `user_password` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `%dbprefix%users` ADD `user_reset_token` VARCHAR(255) NULL DEFAULT NULL, ADD `user_reset_expiry` DATETIME NULL DEFAULT NULL;

# Move the phpGACL id sequences past the existing ids. Fresh installs seeded the gacl tables
# without them, so module installs and new users failed with duplicate keys.
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_acl_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_acl_seq`;
INSERT INTO `%dbprefix%gacl_acl_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_acl`;
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_aco_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_aco_seq`;
INSERT INTO `%dbprefix%gacl_aco_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_aco`;
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_aro_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_aro_seq`;
INSERT INTO `%dbprefix%gacl_aro_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_aro`;
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_axo_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_axo_seq`;
INSERT INTO `%dbprefix%gacl_axo_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_axo`;
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_aco_sections_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_aco_sections_seq`;
INSERT INTO `%dbprefix%gacl_aco_sections_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_aco_sections`;
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_aro_sections_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_aro_sections_seq`;
INSERT INTO `%dbprefix%gacl_aro_sections_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_aro_sections`;
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_axo_sections_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_axo_sections_seq`;
INSERT INTO `%dbprefix%gacl_axo_sections_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_axo_sections`;
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_aro_groups_id_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_aro_groups_id_seq`;
INSERT INTO `%dbprefix%gacl_aro_groups_id_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_aro_groups`;
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_axo_groups_id_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_axo_groups_id_seq`;
INSERT INTO `%dbprefix%gacl_axo_groups_id_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_axo_groups`;
