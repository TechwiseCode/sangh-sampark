-- Organization nickname and address (superadmin-managed)
ALTER TABLE organizations
  ADD COLUMN nickname VARCHAR(191) NULL DEFAULT NULL AFTER name,
  ADD COLUMN address TEXT NULL DEFAULT NULL AFTER nickname;
