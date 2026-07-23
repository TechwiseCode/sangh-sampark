-- Expand profession_type: job, business, homemaker, professional, student, retired.

ALTER TABLE user_profiles
  MODIFY COLUMN profession_type ENUM('job','business','homemaker','professional','student','retired') NULL DEFAULT NULL;
