-- Clear events, payments, receipts, dues, passes, schemes, notifications, history.
-- KEEPS: organizations, users, user_profiles, families, family_members,
--        family_dependents, family_relationship_links, family_membership_requests.
--
-- Usage (backup first!):
--   mysql -u USER -p DATABASE_NAME < database/scripts/clear_transactional_data.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE event_passes;
TRUNCATE TABLE due_payments;
TRUNCATE TABLE due_charges;
TRUNCATE TABLE receipts;
TRUNCATE TABLE due_definitions;
TRUNCATE TABLE scheme_benefits;
TRUNCATE TABLE schemes;
TRUNCATE TABLE whatsapp_notification_queue;
TRUNCATE TABLE notification_campaigns;
TRUNCATE TABLE notifications;
TRUNCATE TABLE family_member_history;

SET FOREIGN_KEY_CHECKS = 1;

-- Optional: clear unused verification tokens
-- TRUNCATE TABLE email_verification_tokens;
