-- Profile: gender, full marital status, and area (locality).

ALTER TABLE user_profiles
  ADD COLUMN gender ENUM('Male', 'Female', 'Other') NULL DEFAULT NULL AFTER dob,
  ADD COLUMN marital_status ENUM('Single', 'Married', 'Widowed', 'Divorced', 'Separated') NULL DEFAULT NULL AFTER gender,
  ADD COLUMN area VARCHAR(50) NULL DEFAULT NULL AFTER pincode;

UPDATE user_profiles SET marital_status = 'Married' WHERE is_married = 1 AND marital_status IS NULL;
UPDATE user_profiles SET marital_status = 'Single' WHERE is_married = 0 AND marital_status IS NULL;
