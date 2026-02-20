-- =============================================================
-- dotProject phpGACL permissions initialisation
-- Translated from db/upgrade_permissions.php
-- Database prefix: dotp_
-- Run against the "dotproject" database.
-- Safe to run on empty gacl tables (clears tables first).
-- =============================================================

SET NAMES utf8;
SET foreign_key_checks = 0;

-- Clear existing data so this is idempotent
TRUNCATE TABLE `dotp_gacl_aco_sections`;
TRUNCATE TABLE `dotp_gacl_aco`;
TRUNCATE TABLE `dotp_gacl_aro_sections`;
TRUNCATE TABLE `dotp_gacl_aro`;
TRUNCATE TABLE `dotp_gacl_aro_groups`;
TRUNCATE TABLE `dotp_gacl_axo_sections`;
TRUNCATE TABLE `dotp_gacl_axo`;
TRUNCATE TABLE `dotp_gacl_axo_groups`;
TRUNCATE TABLE `dotp_gacl_acl`;
TRUNCATE TABLE `dotp_gacl_aco_map`;
TRUNCATE TABLE `dotp_gacl_aro_map`;
TRUNCATE TABLE `dotp_gacl_aro_groups_map`;
TRUNCATE TABLE `dotp_gacl_axo_map`;
TRUNCATE TABLE `dotp_gacl_axo_groups_map`;
TRUNCATE TABLE `dotp_gacl_groups_aro_map`;
TRUNCATE TABLE `dotp_gacl_groups_axo_map`;
TRUNCATE TABLE `dotp_dotpermissions`;

-- -------------------------------------------------------------
-- 1. ACO Sections  (what you can do)
-- -------------------------------------------------------------
INSERT INTO `dotp_gacl_aco_sections` (`id`, `value`, `order_value`, `name`, `hidden`) VALUES
    (1, 'system',      1, 'System',      0),
    (2, 'application', 2, 'Application', 0);

-- -------------------------------------------------------------
-- 2. ACO Objects  (the actual permission types)
-- -------------------------------------------------------------
INSERT INTO `dotp_gacl_aco` (`id`, `section_value`, `value`, `order_value`, `name`, `hidden`) VALUES
    (1, 'system',      'login',  1, 'Login',  0),
    (2, 'application', 'access', 1, 'Access', 0),
    (3, 'application', 'view',   2, 'View',   0),
    (4, 'application', 'add',    3, 'Add',    0),
    (5, 'application', 'edit',   4, 'Edit',   0),
    (6, 'application', 'delete', 5, 'Delete', 0);

-- -------------------------------------------------------------
-- 3. ARO Sections  (who makes requests)
-- -------------------------------------------------------------
INSERT INTO `dotp_gacl_aro_sections` (`id`, `value`, `order_value`, `name`, `hidden`) VALUES
    (1, 'user', 1, 'Users', 0);

-- -------------------------------------------------------------
-- 4. AXO Sections  (what is being accessed)
-- -------------------------------------------------------------
INSERT INTO `dotp_gacl_axo_sections` (`id`, `value`, `order_value`, `name`, `hidden`) VALUES
    (1, 'sys', 1, 'System',      0),
    (2, 'app', 2, 'Application', 0);

-- -------------------------------------------------------------
-- 5. AXO Objects  (the modules / resources)
-- -------------------------------------------------------------
INSERT INTO `dotp_gacl_axo` (`id`, `section_value`, `value`, `order_value`, `name`, `hidden`) VALUES
    (1,  'sys', 'acl',          1,  'ACL Administration',    0),
    (2,  'app', 'admin',        1,  'User Administration',   0),
    (3,  'app', 'calendar',     2,  'Calendar',              0),
    (4,  'app', 'events',       2,  'Events',                0),
    (5,  'app', 'companies',    3,  'Companies',             0),
    (6,  'app', 'contacts',     4,  'Contacts',              0),
    (7,  'app', 'departments',  5,  'Departments',           0),
    (8,  'app', 'files',        6,  'Files',                 0),
    (9,  'app', 'file_folders', 6,  'File Folders',          0),
    (10, 'app', 'forums',       7,  'Forums',                0),
    (11, 'app', 'help',         8,  'Help',                  0),
    (12, 'app', 'projects',     9,  'Projects',              0),
    (13, 'app', 'system',       10, 'System Administration', 0),
    (14, 'app', 'tasks',        11, 'Tasks',                 0),
    (15, 'app', 'task_log',     11, 'Task Logs',             0),
    (16, 'app', 'ticketsmith',  12, 'Tickets',               0),
    (17, 'app', 'public',       13, 'Public',                0),
    (18, 'app', 'roles',        14, 'Roles Administration',  0),
    (19, 'app', 'users',        15, 'User Table',            0);

-- -------------------------------------------------------------
-- 6. ARO Groups  (Role tree, using nested-set lft/rgt)
--    root "role" (id=1) → children: admin(2), anon(3), guest(4), normal(5)
-- -------------------------------------------------------------
INSERT INTO `dotp_gacl_aro_groups` (`id`, `parent_id`, `lft`, `rgt`, `name`, `value`) VALUES
    (1, 0, 1,  12, 'Roles',          'role'),
    (2, 1, 2,  3,  'Administrator',  'admin'),
    (3, 1, 4,  5,  'Anonymous',      'anon'),
    (4, 1, 6,  7,  'Guest',          'guest'),
    (5, 1, 8,  9,  'Project worker', 'normal');

-- -------------------------------------------------------------
-- 7. AXO Groups  (Module groups tree)
--    root "mod" (id=1) → children: all(2), admin(3), non_admin(4)
-- -------------------------------------------------------------
INSERT INTO `dotp_gacl_axo_groups` (`id`, `parent_id`, `lft`, `rgt`, `name`, `value`) VALUES
    (1, 0, 1,  10, 'Modules',          'mod'),
    (2, 1, 2,  3,  'All Modules',      'all'),
    (3, 1, 4,  5,  'Admin Modules',    'admin'),
    (4, 1, 6,  7,  'Non-Admin Modules','non_admin');

-- -------------------------------------------------------------
-- 8. Group → AXO object memberships
--    gacl_groups_axo_map links group_id → axo.id
-- -------------------------------------------------------------

-- group_id=2 (All Modules) gets all app modules
INSERT INTO `dotp_gacl_groups_axo_map` (`group_id`, `axo_id`)
SELECT 2, id FROM `dotp_gacl_axo` WHERE `section_value` = 'app';

-- group_id=3 (Admin Modules): admin, system, roles, users
INSERT INTO `dotp_gacl_groups_axo_map` (`group_id`, `axo_id`)
SELECT 3, id FROM `dotp_gacl_axo`
WHERE `section_value` = 'app' AND `value` IN ('admin','system','roles','users');

-- group_id=4 (Non-Admin Modules): everything except admin, system, roles, users
INSERT INTO `dotp_gacl_groups_axo_map` (`group_id`, `axo_id`)
SELECT 4, id FROM `dotp_gacl_axo`
WHERE `section_value` = 'app' AND `value` NOT IN ('admin','system','roles','users');

-- -------------------------------------------------------------
-- 9. ACLs  (the actual grants)
--    gacl_acl: id, section_value, allow, enabled, return_value, note, updated_date (unix ts)
-- -------------------------------------------------------------
INSERT INTO `dotp_gacl_acl` (`id`, `section_value`, `allow`, `enabled`, `return_value`, `note`, `updated_date`) VALUES
    (1, 'system', 1, 1, 'user', 'Roles group: Login permission',              UNIX_TIMESTAMP()),
    (2, 'system', 1, 1, 'user', 'Administrator: ALL permissions on ALL modules', UNIX_TIMESTAMP()),
    (3, 'system', 1, 1, 'user', 'Administrator: access on ACL object',        UNIX_TIMESTAMP()),
    (4, 'system', 1, 1, 'user', 'Guest: access+view on non-admin modules',    UNIX_TIMESTAMP()),
    (5, 'system', 1, 1, 'user', 'Anonymous: access on non-admin modules',     UNIX_TIMESTAMP()),
    (6, 'system', 1, 1, 'user', 'Worker: ALL permissions on non-admin modules',UNIX_TIMESTAMP()),
    (7, 'system', 1, 1, 'user', 'Worker+Guest: access+view on users object',  UNIX_TIMESTAMP());

-- ACO (permission type) mappings per ACL
INSERT INTO `dotp_gacl_aco_map` (`acl_id`, `section_value`, `value`) VALUES
    -- ACL 1: login
    (1, 'system',      'login'),
    -- ACL 2: all perms
    (2, 'application', 'access'),
    (2, 'application', 'add'),
    (2, 'application', 'edit'),
    (2, 'application', 'view'),
    (2, 'application', 'delete'),
    -- ACL 3: access only
    (3, 'application', 'access'),
    -- ACL 4: access + view
    (4, 'application', 'access'),
    (4, 'application', 'view'),
    -- ACL 5: access only
    (5, 'application', 'access'),
    -- ACL 6: all perms
    (6, 'application', 'access'),
    (6, 'application', 'add'),
    (6, 'application', 'edit'),
    (6, 'application', 'view'),
    (6, 'application', 'delete'),
    -- ACL 7: access + view
    (7, 'application', 'access'),
    (7, 'application', 'view');

-- ARO group mappings (which role gets each grant)
INSERT INTO `dotp_gacl_aro_groups_map` (`acl_id`, `group_id`) VALUES
    (1, 1),  -- ACL 1 → Roles (root, id=1) — all roles can login
    (2, 2),  -- ACL 2 → Administrator
    (3, 2),  -- ACL 3 → Administrator
    (4, 4),  -- ACL 4 → Guest
    (5, 3),  -- ACL 5 → Anonymous
    (6, 5),  -- ACL 6 → Worker
    (7, 5),  -- ACL 7 → Worker
    (7, 4);  -- ACL 7 → Guest also

-- AXO group mappings (which module group each ACL applies to)
INSERT INTO `dotp_gacl_axo_groups_map` (`acl_id`, `group_id`) VALUES
    -- ACL 1: no group restriction (login applies to everything)
    (2, 2),  -- ACL 2 → All Modules
    (4, 4),  -- ACL 4 → Non-Admin Modules
    (5, 4),  -- ACL 5 → Non-Admin Modules
    (6, 4);  -- ACL 6 → Non-Admin Modules

-- AXO specific-object mappings
INSERT INTO `dotp_gacl_axo_map` (`acl_id`, `section_value`, `value`) VALUES
    (3, 'sys', 'acl'),   -- ACL 3: Admin → system/acl object
    (7, 'app', 'users'); -- ACL 7: Worker+Guest → app/users object

-- -------------------------------------------------------------
-- 10. Add admin user ARO entry and assign to Administrator group
--     The admin user created during dotProject install has user_id=1.
--     If your admin user_id differs, adjust the WHERE clause below.
-- -------------------------------------------------------------
INSERT INTO `dotp_gacl_aro` (`id`, `section_value`, `value`, `order_value`, `name`, `hidden`)
SELECT
    u.user_id,
    'user',
    u.user_id,
    1,
    u.user_username,
    0
FROM `dotp_users` u
WHERE u.user_id = 1
ON DUPLICATE KEY UPDATE `name` = u.user_username;

-- Link admin ARO to Administrator group (group_id=2)
INSERT IGNORE INTO `dotp_gacl_groups_aro_map` (`group_id`, `aro_id`)
SELECT 2, id FROM `dotp_gacl_aro` WHERE `section_value` = 'user' AND `value` = '1';

-- -------------------------------------------------------------
-- 11. phpGACL metadata
-- -------------------------------------------------------------
INSERT IGNORE INTO `dotp_gacl_phpgacl` (`name`, `value`) VALUES
    ('version',        '3.3.2'),
    ('schema_version', '2.1');

-- -------------------------------------------------------------
-- Done.
-- After running this, go to Admin → System → Rebuild Permissions
-- in dotProject (or trigger $perms->regeneratePermissions()).
-- -------------------------------------------------------------

SET foreign_key_checks = 1;
