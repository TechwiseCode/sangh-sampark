-- Soft-disable members and whole organizations (no deletes).
-- Safe to run once. Ignore "Duplicate column" if already applied.

ALTER TABLE users
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE organizations
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;
