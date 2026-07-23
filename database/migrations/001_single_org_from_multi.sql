-- Migrate an existing szvs2-style multi-org database to single-org (one org per DB).
-- Run ONLY on a database that will host a single organization.
-- Backup first. If multiple organizations exist, split data per org before running.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1) Add role/member_code on users (if missing)
SET @has_role := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'
);
SET @sql := IF(@has_role = 0,
  'ALTER TABLE users
     ADD COLUMN role ENUM(\'admin\',\'member\') NOT NULL DEFAULT \'member\' AFTER password,
     ADD COLUMN member_code CHAR(4) NULL DEFAULT NULL AFTER role',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) Copy membership from organization_users
-- SKIP this block if you get #1146 (table organization_users doesn't exist):
--   you already migrated, or you imported database/schema.sql (new single-org).
-- Run steps 3–7 below only in that case.
/*
UPDATE users u
LEFT JOIN (
  SELECT ou.user_id,
    SUBSTRING_INDEX(GROUP_CONCAT(ou.role ORDER BY
      CASE WHEN ou.organization_id = COALESCE(u2.primary_organization_id, 0) THEN 0 ELSE 1 END,
      ou.organization_id ASC), ',', 1) AS role,
    SUBSTRING_INDEX(GROUP_CONCAT(ou.member_code ORDER BY
      CASE WHEN ou.organization_id = COALESCE(u2.primary_organization_id, 0) THEN 0 ELSE 1 END,
      ou.organization_id ASC), ',', 1) AS member_code
  FROM organization_users ou
  INNER JOIN users u2 ON u2.id = ou.user_id
  GROUP BY ou.user_id
) m ON m.user_id = u.id
SET u.role = COALESCE(m.role, u.role, 'member'),
    u.member_code = COALESCE(m.member_code, u.member_code)
WHERE m.user_id IS NOT NULL;
*/

-- 3) Drop multi-org user columns
SET @has_primary := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'fk_users_primary_organization'
);
SET @sql := IF(@has_primary > 0, 'ALTER TABLE users DROP FOREIGN KEY fk_users_primary_organization', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_primary_col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'primary_organization_id'
);
SET @sql := IF(@has_primary_col > 0, 'ALTER TABLE users DROP COLUMN primary_organization_id', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_super := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_superadmin'
);
SET @sql := IF(@has_super > 0, 'ALTER TABLE users DROP COLUMN is_superadmin', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4) Member code unique within this DB
SET @has_uq_mc := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_users_member_code'
);
SET @sql := IF(@has_uq_mc = 0, 'ALTER TABLE users ADD UNIQUE KEY uq_users_member_code (member_code)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5) Drop organization_users
DROP TABLE IF EXISTS organization_users;

-- 6) Keep one organization row (lowest id)
DELETE FROM organizations WHERE id > (SELECT min_id FROM (SELECT MIN(id) AS min_id FROM organizations) t);

-- 7) family_membership_requests: require organization_id
UPDATE family_membership_requests
SET organization_id = (SELECT MIN(id) FROM organizations)
WHERE organization_id IS NULL OR organization_id < 1;

ALTER TABLE family_membership_requests
  MODIFY organization_id INT UNSIGNED NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;
