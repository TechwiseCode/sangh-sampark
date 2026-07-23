-- Member profile photo (self-upload on My profile page).
ALTER TABLE users
  ADD COLUMN photo_path VARCHAR(255) NULL DEFAULT NULL AFTER member_code;
