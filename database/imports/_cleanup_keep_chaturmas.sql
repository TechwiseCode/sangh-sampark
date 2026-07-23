SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @keep_user_id := (
  SELECT id FROM users WHERE role = 'superadmin' ORDER BY id ASC LIMIT 1
);

DROP TEMPORARY TABLE IF EXISTS keep_orgs;
CREATE TEMPORARY TABLE keep_orgs AS
SELECT id FROM organizations WHERE org_code REGEXP '^C[0-9]{2}$';

DELETE m FROM org_presence_members m
INNER JOIN org_presence_lists l ON l.id = m.presence_list_id
WHERE l.organization_id NOT IN (SELECT id FROM keep_orgs);

DELETE FROM org_presence_lists
WHERE organization_id NOT IN (SELECT id FROM keep_orgs);

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

DELETE FROM organizations
WHERE id NOT IN (SELECT id FROM keep_orgs);

DELETE FROM users
WHERE @keep_user_id IS NULL OR id <> @keep_user_id;

UPDATE users
SET organization_id = NULL, role = 'superadmin'
WHERE id = @keep_user_id;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'users' AS kind, COUNT(*) AS cnt FROM users
UNION ALL SELECT 'organizations', COUNT(*) FROM organizations
UNION ALL SELECT 'presence_lists', COUNT(*) FROM org_presence_lists
UNION ALL SELECT 'presence_members', COUNT(*) FROM org_presence_members;

SELECT id, email, role FROM users;
SELECT id, org_code, LEFT(name, 60) AS name FROM organizations ORDER BY org_code;
