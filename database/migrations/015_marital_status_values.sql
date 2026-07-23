-- Marital status: Single, Married, Widowed, Divorced (remove Separated).

UPDATE user_profiles SET marital_status = 'Divorced' WHERE marital_status = 'Separated';

ALTER TABLE user_profiles
  MODIFY COLUMN marital_status ENUM('Single', 'Married', 'Widowed', 'Divorced') NULL DEFAULT NULL;
