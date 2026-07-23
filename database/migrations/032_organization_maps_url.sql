-- Manual Google Maps / navigation URL per organization (paste share/directions link).

SET @has_maps := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'organizations'
    AND COLUMN_NAME = 'maps_url'
);
SET @sql := IF(@has_maps = 0,
  'ALTER TABLE organizations ADD COLUMN maps_url VARCHAR(512) NULL DEFAULT NULL AFTER address',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
