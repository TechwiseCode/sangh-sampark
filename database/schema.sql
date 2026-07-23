-- SZVS platform schema (MySQL 5.7+ / 8.x)
-- One database, many organizations. Each org has its own user accounts (same email allowed per org).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS due_payments;
DROP TABLE IF EXISTS due_charges;
DROP TABLE IF EXISTS due_definitions;
DROP TABLE IF EXISTS receipts;
DROP TABLE IF EXISTS push_subscriptions;
DROP TABLE IF EXISTS whatsapp_notification_queue;
DROP TABLE IF EXISTS notification_campaigns;
DROP TABLE IF EXISTS org_presence_members;
DROP TABLE IF EXISTS org_presence_lists;
DROP TABLE IF EXISTS family_member_history;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS scheme_benefits;
DROP TABLE IF EXISTS schemes;
DROP TABLE IF EXISTS email_verification_tokens;
DROP TABLE IF EXISTS user_profiles;
DROP TABLE IF EXISTS family_membership_requests;
DROP TABLE IF EXISTS family_dependents;
DROP TABLE IF EXISTS family_relationship_links;
DROP TABLE IF EXISTS family_members;
DROP TABLE IF EXISTS families;
DROP TABLE IF EXISTS organizations;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NULL DEFAULT NULL,
  name VARCHAR(191) NOT NULL,
  first_name VARCHAR(100) NULL DEFAULT NULL,
  middle_name VARCHAR(100) NULL DEFAULT NULL,
  last_name VARCHAR(100) NULL DEFAULT NULL,
  email VARCHAR(191) NULL DEFAULT NULL,
  email_verified_at TIMESTAMP NULL DEFAULT NULL,
  phone VARCHAR(32) NULL DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('superadmin','admin','member') NOT NULL DEFAULT 'member',
  member_code VARCHAR(12) NULL DEFAULT NULL,
  photo_path VARCHAR(255) NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_org_email (organization_id, email),
  UNIQUE KEY uq_users_org_phone (organization_id, phone),
  UNIQUE KEY uq_users_org_member_code (organization_id, member_code),
  KEY idx_users_role (role),
  KEY idx_users_organization_id (organization_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE organizations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  nickname VARCHAR(191) NULL DEFAULT NULL,
  address TEXT NULL DEFAULT NULL,
  maps_url VARCHAR(512) NULL DEFAULT NULL,
  org_code VARCHAR(12) NOT NULL,
  member_initials VARCHAR(4) NULL DEFAULT NULL,
  created_by INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_organizations_org_code (org_code),
  UNIQUE KEY uq_organizations_member_initials (member_initials),
  KEY idx_organizations_created_by (created_by),
  CONSTRAINT fk_organizations_created_by
    FOREIGN KEY (created_by) REFERENCES users (id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users
  ADD CONSTRAINT fk_users_organization
  FOREIGN KEY (organization_id) REFERENCES organizations (id)
  ON DELETE CASCADE;

CREATE TABLE user_profiles (
  user_id INT UNSIGNED PRIMARY KEY,
  dob DATE NOT NULL,
  gender ENUM('Male', 'Female', 'Other') NULL DEFAULT NULL,
  marital_status ENUM('Single', 'Married', 'Widowed', 'Divorced') NULL DEFAULT NULL,
  house_number VARCHAR(32) NULL DEFAULT NULL,
  address_line1 VARCHAR(255) NOT NULL,
  address_line2 VARCHAR(255) NULL DEFAULT NULL,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(100) NOT NULL,
  pincode VARCHAR(10) NOT NULL,
  area VARCHAR(50) NULL DEFAULT NULL,
  occupation VARCHAR(120) NOT NULL,
  blood_group VARCHAR(8) NULL DEFAULT NULL,
  highest_education VARCHAR(191) NULL DEFAULT NULL,
  profession_type ENUM('job','business','homemaker','professional','student','retired') NULL DEFAULT NULL,
  job_title VARCHAR(191) NULL DEFAULT NULL,
  company_name VARCHAR(191) NULL DEFAULT NULL,
  industry_sector VARCHAR(191) NULL DEFAULT NULL,
  company_website VARCHAR(255) NULL DEFAULT NULL,
  is_married TINYINT(1) NOT NULL,
  native_pincode VARCHAR(10) NOT NULL,
  native_city VARCHAR(100) NOT NULL,
  native_state VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_profiles_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE families (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  head_user_id INT UNSIGNED NOT NULL,
  created_by INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_families_org (organization_id),
  KEY idx_families_head (head_user_id),
  CONSTRAINT fk_families_org
    FOREIGN KEY (organization_id) REFERENCES organizations (id)
    ON DELETE CASCADE,
  CONSTRAINT fk_families_head
    FOREIGN KEY (head_user_id) REFERENCES users (id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_families_created_by
    FOREIGN KEY (created_by) REFERENCES users (id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE family_members (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  family_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  role VARCHAR(64) NOT NULL,
  related_to_user_id INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_family_user (family_id, user_id),
  KEY idx_family_members_user (user_id),
  KEY idx_family_members_related (related_to_user_id),
  CONSTRAINT fk_family_members_family
    FOREIGN KEY (family_id) REFERENCES families (id)
    ON DELETE CASCADE,
  CONSTRAINT fk_family_members_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE,
  CONSTRAINT fk_family_members_related
    FOREIGN KEY (related_to_user_id) REFERENCES users (id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE family_dependents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  family_id INT UNSIGNED NOT NULL,
  name VARCHAR(191) NOT NULL,
  role VARCHAR(64) NOT NULL,
  related_to_user_id INT UNSIGNED NULL DEFAULT NULL,
  dob DATE NOT NULL,
  pincode VARCHAR(10) NOT NULL,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_family_dependents_family (family_id),
  KEY idx_family_dependents_related_user (related_to_user_id),
  CONSTRAINT fk_family_dependents_family
    FOREIGN KEY (family_id) REFERENCES families (id)
    ON DELETE CASCADE,
  CONSTRAINT fk_family_dependents_related_user
    FOREIGN KEY (related_to_user_id) REFERENCES users (id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE family_relationship_links (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  family_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  relationship_role VARCHAR(64) NOT NULL,
  related_to_user_id INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_family_relationship_link (family_id, user_id),
  KEY idx_family_relationship_user (user_id),
  KEY idx_family_relationship_related (related_to_user_id),
  CONSTRAINT fk_family_relationship_family
    FOREIGN KEY (family_id) REFERENCES families (id)
    ON DELETE CASCADE,
  CONSTRAINT fk_family_relationship_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE,
  CONSTRAINT fk_family_relationship_related
    FOREIGN KEY (related_to_user_id) REFERENCES users (id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- In-org family invites only (same database).
CREATE TABLE family_membership_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  family_id INT UNSIGNED NULL DEFAULT NULL,
  target_user_id INT UNSIGNED NOT NULL,
  requested_by_user_id INT UNSIGNED NOT NULL,
  requested_role VARCHAR(64) NOT NULL,
  related_to_user_id INT UNSIGNED NULL DEFAULT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  responded_at TIMESTAMP NULL DEFAULT NULL,
  responded_by_user_id INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_fmr_org_target (organization_id, target_user_id),
  KEY idx_fmr_family_target (family_id, target_user_id),
  KEY idx_fmr_target_status (target_user_id, status),
  CONSTRAINT fk_fmr_organization FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
  CONSTRAINT fk_fmr_family FOREIGN KEY (family_id) REFERENCES families (id) ON DELETE CASCADE,
  CONSTRAINT fk_fmr_target FOREIGN KEY (target_user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_fmr_requester FOREIGN KEY (requested_by_user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_fmr_related FOREIGN KEY (related_to_user_id) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_fmr_responder FOREIGN KEY (responded_by_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type VARCHAR(50) NOT NULL DEFAULT 'relationship_request',
  reference_id INT UNSIGNED NULL DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NULL,
  read_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notif_user (user_id),
  KEY idx_notif_user_unread (user_id, read_at),
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_verification_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at TIMESTAMP NOT NULL,
  used_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_evt_token_hash (token_hash),
  KEY idx_evt_user (user_id),
  CONSTRAINT fk_evt_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE schemes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  name VARCHAR(191) NOT NULL,
  description TEXT NULL,
  benefit_scope ENUM('family','member') NOT NULL,
  benefit_type VARCHAR(64) NOT NULL,
  benefit_value VARCHAR(191) NULL DEFAULT NULL,
  starts_at DATE NULL DEFAULT NULL,
  ends_at DATE NULL DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_schemes_org_scope (organization_id, benefit_scope),
  KEY idx_schemes_active (organization_id, is_active),
  CONSTRAINT fk_schemes_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
  CONSTRAINT fk_schemes_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE scheme_benefits (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  scheme_id INT UNSIGNED NOT NULL,
  organization_id INT UNSIGNED NOT NULL,
  family_id INT UNSIGNED NULL DEFAULT NULL,
  user_id INT UNSIGNED NULL DEFAULT NULL,
  beneficiary_user_id INT UNSIGNED NOT NULL,
  status ENUM('eligible','claimed','rejected','expired') NOT NULL DEFAULT 'eligible',
  claimed_at TIMESTAMP NULL DEFAULT NULL,
  claimed_by_user_id INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_sb_scheme (scheme_id),
  KEY idx_sb_org (organization_id),
  KEY idx_sb_family (family_id),
  KEY idx_sb_user (user_id),
  KEY idx_sb_beneficiary (beneficiary_user_id),
  KEY idx_sb_status (status),
  UNIQUE KEY uq_sb_scheme_family (scheme_id, family_id),
  UNIQUE KEY uq_sb_scheme_user (scheme_id, user_id),
  CONSTRAINT fk_sb_scheme FOREIGN KEY (scheme_id) REFERENCES schemes (id) ON DELETE CASCADE,
  CONSTRAINT fk_sb_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
  CONSTRAINT fk_sb_family FOREIGN KEY (family_id) REFERENCES families (id) ON DELETE CASCADE,
  CONSTRAINT fk_sb_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_sb_beneficiary FOREIGN KEY (beneficiary_user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_sb_claimed_by FOREIGN KEY (claimed_by_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE due_definitions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  title VARCHAR(191) NOT NULL,
  due_type ENUM('membership','event','occasion','other') NOT NULL DEFAULT 'other',
  amount DECIMAL(12,2) NOT NULL,
  charge_basis ENUM('per_family','per_person') NOT NULL DEFAULT 'per_family',
  financial_year VARCHAR(9) NOT NULL,
  event_date DATE NULL DEFAULT NULL,
  is_compulsory TINYINT(1) NOT NULL DEFAULT 1,
  created_by_user_id INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_due_def_org_fy (organization_id, financial_year),
  KEY idx_due_def_org_event_date (organization_id, event_date),
  CONSTRAINT fk_due_def_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
  CONSTRAINT fk_due_def_user FOREIGN KEY (created_by_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE receipts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  family_id INT UNSIGNED NOT NULL,
  recipient_user_id INT UNSIGNED NOT NULL,
  receipt_no INT UNSIGNED NOT NULL DEFAULT 0,
  due_definition_id INT UNSIGNED NULL DEFAULT NULL,
  purpose VARCHAR(255) NOT NULL,
  description TEXT NULL,
  amount DECIMAL(12,2) NOT NULL,
  receipt_date DATE NOT NULL,
  financial_year VARCHAR(9) NOT NULL,
  created_by_user_id INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_receipts_org_fy (organization_id, financial_year),
  KEY idx_receipts_recipient (recipient_user_id),
  UNIQUE KEY uq_receipts_org_fy_no (organization_id, financial_year, receipt_no),
  CONSTRAINT fk_receipts_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
  CONSTRAINT fk_receipts_family FOREIGN KEY (family_id) REFERENCES families (id) ON DELETE CASCADE,
  CONSTRAINT fk_receipts_recipient FOREIGN KEY (recipient_user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_receipts_due_def FOREIGN KEY (due_definition_id) REFERENCES due_definitions (id) ON DELETE SET NULL,
  CONSTRAINT fk_receipts_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE due_charges (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  due_definition_id INT UNSIGNED NOT NULL,
  organization_id INT UNSIGNED NOT NULL,
  family_id INT UNSIGNED NOT NULL,
  recipient_user_id INT UNSIGNED NOT NULL,
  amount_due DECIMAL(12,2) NOT NULL,
  amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  last_paid_at DATE NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_due_charge (due_definition_id, recipient_user_id),
  KEY idx_due_charge_org_status (organization_id, status),
  CONSTRAINT fk_due_charge_def FOREIGN KEY (due_definition_id) REFERENCES due_definitions (id) ON DELETE CASCADE,
  CONSTRAINT fk_due_charge_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
  CONSTRAINT fk_due_charge_family FOREIGN KEY (family_id) REFERENCES families (id) ON DELETE CASCADE,
  CONSTRAINT fk_due_charge_user FOREIGN KEY (recipient_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE due_payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  due_charge_id INT UNSIGNED NOT NULL,
  receipt_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_due_payment_receipt (receipt_id),
  KEY idx_due_payment_charge (due_charge_id),
  CONSTRAINT fk_due_pay_charge FOREIGN KEY (due_charge_id) REFERENCES due_charges (id) ON DELETE CASCADE,
  CONSTRAINT fk_due_pay_receipt FOREIGN KEY (receipt_id) REFERENCES receipts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_passes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  due_definition_id INT UNSIGNED NOT NULL,
  family_id INT UNSIGNED NOT NULL,
  recipient_user_id INT UNSIGNED NOT NULL,
  holder_user_id INT UNSIGNED NOT NULL,
  receipt_id INT UNSIGNED NOT NULL,
  pass_code VARCHAR(32) NOT NULL,
  status ENUM('active','redeemed','cancelled') NOT NULL DEFAULT 'active',
  issued_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  redeemed_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY uq_event_pass_code (pass_code),
  KEY idx_event_pass_receipt (receipt_id),
  KEY idx_event_pass_family_event (due_definition_id, family_id, status),
  KEY idx_event_pass_org (organization_id),
  KEY idx_event_pass_recipient (recipient_user_id),
  KEY idx_event_pass_holder (holder_user_id),
  CONSTRAINT fk_event_pass_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
  CONSTRAINT fk_event_pass_due FOREIGN KEY (due_definition_id) REFERENCES due_definitions (id) ON DELETE CASCADE,
  CONSTRAINT fk_event_pass_family FOREIGN KEY (family_id) REFERENCES families (id) ON DELETE CASCADE,
  CONSTRAINT fk_event_pass_recipient FOREIGN KEY (recipient_user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_event_pass_holder FOREIGN KEY (holder_user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_event_pass_receipt FOREIGN KEY (receipt_id) REFERENCES receipts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notification_campaigns (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  audience VARCHAR(32) NOT NULL,
  recipient_filters VARCHAR(255) NULL DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NULL,
  channels VARCHAR(64) NOT NULL DEFAULT 'in_app',
  total_recipients INT UNSIGNED NOT NULL DEFAULT 0,
  in_app_sent_count INT UNSIGNED NOT NULL DEFAULT 0,
  whatsapp_queued_count INT UNSIGNED NOT NULL DEFAULT 0,
  push_sent_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_by_user_id INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notif_campaign_org (organization_id, created_at),
  CONSTRAINT fk_notif_campaign_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
  CONSTRAINT fk_notif_campaign_user FOREIGN KEY (created_by_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE whatsapp_notification_queue (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT UNSIGNED NULL DEFAULT NULL,
  organization_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  phone VARCHAR(32) NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NULL,
  status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  last_error VARCHAR(255) NULL DEFAULT NULL,
  sent_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_wa_queue_org_status (organization_id, status),
  KEY idx_wa_queue_campaign (campaign_id),
  CONSTRAINT fk_wa_queue_campaign FOREIGN KEY (campaign_id) REFERENCES notification_campaigns (id) ON DELETE SET NULL,
  CONSTRAINT fk_wa_queue_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
  CONSTRAINT fk_wa_queue_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE push_subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  endpoint VARCHAR(512) NOT NULL,
  p256dh_key VARCHAR(255) NOT NULL,
  auth_key VARCHAR(255) NOT NULL,
  user_agent VARCHAR(255) NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_push_endpoint (endpoint),
  KEY idx_push_user (user_id),
  CONSTRAINT fk_push_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE org_presence_lists (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  effective_from DATETIME NOT NULL,
  effective_until DATETIME NULL DEFAULT NULL,
  created_by_user_id INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_presence_org_current (organization_id, effective_until),
  KEY idx_presence_org_from (organization_id, effective_from),
  CONSTRAINT fk_presence_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
  CONSTRAINT fk_presence_user FOREIGN KEY (created_by_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE org_presence_members (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  presence_list_id INT UNSIGNED NOT NULL,
  display_name VARCHAR(191) NOT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_presence_members_list (presence_list_id, sort_order),
  CONSTRAINT fk_presence_members_list FOREIGN KEY (presence_list_id) REFERENCES org_presence_lists (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE family_member_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  family_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL DEFAULT NULL,
  actor_user_id INT UNSIGNED NULL DEFAULT NULL,
  event_type VARCHAR(40) NOT NULL,
  event_label VARCHAR(120) NOT NULL,
  details TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_fmh_org_family (organization_id, family_id),
  KEY idx_fmh_user (user_id),
  CONSTRAINT fk_fmh_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
  CONSTRAINT fk_fmh_family FOREIGN KEY (family_id) REFERENCES families (id) ON DELETE CASCADE,
  CONSTRAINT fk_fmh_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_fmh_actor FOREIGN KEY (actor_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo org (optional). Org admins and members are created via superadmin / org portal.
INSERT INTO organizations (id, name, org_code, created_by)
VALUES (1, 'Demo Organization', 'DEM4721', NULL);

-- Platform superadmin — password: Super@123 (change before production)
INSERT INTO users (id, organization_id, name, email, phone, password, role, member_code)
VALUES (
  1,
  NULL,
  'Platform Superadmin',
  'super@local.test',
  NULL,
  '$2y$10$pUxuPwfvnPBZfqhfGIdenekKUq2H8wCZoj9TM4CdP1LQTnh/I3LYi',
  'superadmin',
  NULL
);

UPDATE organizations SET created_by = 1 WHERE id = 1;
