-- Memorable member codes: org_code + initials + serial (e.g. C12-AJ101)
-- users.member_code stores the suffix only (AJ101); display concatenates org_code.

ALTER TABLE organizations MODIFY org_code VARCHAR(12) NOT NULL;

SET @has_mi := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'organizations'
    AND COLUMN_NAME = 'member_initials'
);
SET @sql := IF(@has_mi = 0,
  'ALTER TABLE organizations ADD COLUMN member_initials VARCHAR(4) NULL DEFAULT NULL AFTER org_code',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE users MODIFY member_code VARCHAR(12) NULL DEFAULT NULL;

SET @has_uq := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'organizations'
    AND INDEX_NAME = 'uq_organizations_member_initials'
);
SET @sql := IF(@has_uq = 0,
  'ALTER TABLE organizations ADD UNIQUE KEY uq_organizations_member_initials (member_initials)',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
