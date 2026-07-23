-- Split user display name into first / middle / last; keep `name` as full display name.

ALTER TABLE users
  ADD COLUMN first_name VARCHAR(100) NULL DEFAULT NULL AFTER name,
  ADD COLUMN middle_name VARCHAR(100) NULL DEFAULT NULL AFTER first_name,
  ADD COLUMN last_name VARCHAR(100) NULL DEFAULT NULL AFTER middle_name;

UPDATE users
SET
  first_name = TRIM(SUBSTRING_INDEX(TRIM(name), ' ', 1)),
  last_name = TRIM(
    IF(
      CHAR_LENGTH(TRIM(name)) - CHAR_LENGTH(REPLACE(TRIM(name), ' ', '')) >= 1,
      SUBSTRING_INDEX(TRIM(name), ' ', -1),
      SUBSTRING_INDEX(TRIM(name), ' ', 1)
    )
  )
WHERE TRIM(name) <> '' AND first_name IS NULL;

UPDATE users
SET middle_name = NULL
WHERE first_name IS NOT NULL AND first_name = last_name;
