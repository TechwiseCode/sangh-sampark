-- Upgrade single-org tenant DB to multi-org platform (one app, many organizations).
-- Backup first. Run on your szvs database after 001/002 if applicable.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1) organization_id on users
SET @has_org_col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'organization_id'
);
SET @sql := IF(@has_org_col = 0,
  'ALTER TABLE users ADD COLUMN organization_id INT UNSIGNED NULL DEFAULT NULL AFTER id',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE users SET organization_id = 1 WHERE role IN ('admin', 'member') AND (organization_id IS NULL OR organization_id = 0);
UPDATE users SET organization_id = NULL WHERE role = 'superadmin';

-- 2) Drop global unique keys; add per-organization unique keys
SET @has_uq_email := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_users_email'
);
SET @sql := IF(@has_uq_email > 0, 'ALTER TABLE users DROP INDEX uq_users_email', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_uq_phone := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_users_phone'
);
SET @sql := IF(@has_uq_phone > 0, 'ALTER TABLE users DROP INDEX uq_users_phone', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_uq_mc := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_users_member_code'
);
SET @sql := IF(@has_uq_mc > 0, 'ALTER TABLE users DROP INDEX uq_users_member_code', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_uq_org_email := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_users_org_email'
);
SET @sql := IF(@has_uq_org_email = 0,
  'ALTER TABLE users
     ADD UNIQUE KEY uq_users_org_email (organization_id, email),
     ADD UNIQUE KEY uq_users_org_phone (organization_id, phone),
     ADD UNIQUE KEY uq_users_org_member_code (organization_id, member_code),
     ADD KEY idx_users_organization_id (organization_id)',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_fk := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'fk_users_organization'
);
SET @sql := IF(@has_fk = 0,
  'ALTER TABLE users ADD CONSTRAINT fk_users_organization FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3) Remove extra org rows only if you previously ran single-org migration that deleted them (optional)
-- Do NOT run if you already have multiple organizations.

SET FOREIGN_KEY_CHECKS = 1;
