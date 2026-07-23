-- Organization committee members (display only — not login roles).
-- Same designation may appear multiple times (e.g. several Committee Members).

CREATE TABLE IF NOT EXISTS organization_committee_members (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL DEFAULT NULL,
  person_name VARCHAR(191) NOT NULL,
  designation_key VARCHAR(64) NOT NULL,
  created_by INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_org_committee_org (organization_id, id),
  KEY idx_org_committee_designation (organization_id, designation_key),
  KEY idx_org_committee_user (user_id),
  CONSTRAINT fk_org_committee_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
  CONSTRAINT fk_org_committee_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_org_committee_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
