-- Separate house / flat number from address line 1.
ALTER TABLE user_profiles
  ADD COLUMN house_number VARCHAR(32) NULL DEFAULT NULL AFTER marital_status;
