-- Add superadmin role and optional platform user (run on existing szvs DB).

ALTER TABLE users
  MODIFY COLUMN role ENUM('superadmin','admin','member') NOT NULL DEFAULT 'member';

-- Promote first user or set explicitly (adjust email as needed):
-- UPDATE users SET role = 'superadmin' WHERE email = 'super@local.test';
