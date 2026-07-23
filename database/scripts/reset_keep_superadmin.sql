-- Full database reset: remove ALL organizations, members, families, transactions.
-- Keeps ONE superadmin user (lowest id with role = superadmin).
--
-- BACK UP YOUR DATABASE FIRST (phpMyAdmin export or mysqldump).
--
-- Usage (phpMyAdmin or mysql CLI):
--   mysql -u USER -p DATABASE_NAME < database/scripts/reset_keep_superadmin.sql
--
-- To keep a specific superadmin by email, set @keep_email before running:
--   SET @keep_email = 'your@email.com';
--
-- Note: uses DELETE (not TRUNCATE) — MySQL blocks TRUNCATE on FK-linked tables (#1701).

SET NAMES utf8mb4;

SET @keep_email := NULL;

SET @keep_user_id := (
    SELECT id FROM users
    WHERE role = 'superadmin'
      AND (@keep_email IS NULL OR email = @keep_email)
    ORDER BY id ASC
    LIMIT 1
);

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM org_presence_members;
DELETE FROM org_presence_lists;
DELETE FROM family_member_history;
DELETE FROM whatsapp_notification_queue;
DELETE FROM notification_campaigns;
DELETE FROM notifications;
DELETE FROM event_passes;
DELETE FROM due_payments;
DELETE FROM due_charges;
DELETE FROM receipts;
DELETE FROM due_definitions;
DELETE FROM scheme_benefits;
DELETE FROM schemes;
DELETE FROM email_verification_tokens;
DELETE FROM user_profiles;
DELETE FROM family_membership_requests;
DELETE FROM family_dependents;
DELETE FROM family_relationship_links;
DELETE FROM family_members;
DELETE FROM families;
DELETE FROM organizations;

DELETE FROM users
WHERE @keep_user_id IS NULL OR id <> @keep_user_id;

SET FOREIGN_KEY_CHECKS = 1;

-- If no superadmin existed, create default (password: Super@123 — change immediately)
INSERT INTO users (organization_id, name, email, phone, password, role, member_code)
SELECT NULL, 'Platform Superadmin', 'super@local.test', NULL,
  '$2y$10$pUxuPwfvnPBZfqhfGIdenekKUq2H8wCZoj9TM4CdP1LQTnh/I3LYi',
  'superadmin', NULL
FROM DUAL
WHERE @keep_user_id IS NULL
  AND NOT EXISTS (SELECT 1 FROM users WHERE role = 'superadmin' LIMIT 1);

UPDATE users
SET organization_id = NULL, role = 'superadmin'
WHERE role = 'superadmin';

SELECT id, name, email, role FROM users WHERE role = 'superadmin';
