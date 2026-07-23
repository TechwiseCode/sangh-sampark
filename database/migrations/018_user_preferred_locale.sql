-- Preferred UI language per user (en, gu). Run in phpMyAdmin if not auto-applied.
ALTER TABLE users
    ADD COLUMN preferred_locale VARCHAR(5) NULL DEFAULT NULL AFTER photo_path;
