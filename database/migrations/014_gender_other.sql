-- Ensure gender ENUM includes Other (for DBs created before Other was added).

ALTER TABLE user_profiles
  MODIFY COLUMN gender ENUM('Male', 'Female', 'Other') NULL DEFAULT NULL;
