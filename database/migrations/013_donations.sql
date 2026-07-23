-- Donation categories (per organization) and donations (commitment + payment rows).

CREATE TABLE IF NOT EXISTS donation_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  name_gu VARCHAR(255) NOT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_donation_cat_org (organization_id, sort_order),
  CONSTRAINT fk_donation_cat_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS donations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  parent_id INT UNSIGNED NULL DEFAULT NULL,
  category_id INT UNSIGNED NOT NULL,
  donor_type ENUM('member', 'guest') NOT NULL DEFAULT 'member',
  user_id INT UNSIGNED NULL DEFAULT NULL,
  family_id INT UNSIGNED NULL DEFAULT NULL,
  donor_name VARCHAR(191) NOT NULL,
  donor_phone VARCHAR(32) NULL DEFAULT NULL,
  committed_amount DECIMAL(12,2) NULL DEFAULT NULL,
  committed_date DATE NULL DEFAULT NULL,
  paid_amount DECIMAL(12,2) NULL DEFAULT NULL,
  payment_date DATE NULL DEFAULT NULL,
  financial_year VARCHAR(9) NOT NULL,
  payment_mode ENUM('cash', 'upi', 'bank', 'cheque') NULL DEFAULT NULL,
  reference_no VARCHAR(100) NULL DEFAULT NULL,
  bank_name VARCHAR(100) NULL DEFAULT NULL,
  cheque_date DATE NULL DEFAULT NULL,
  status ENUM('open', 'partial', 'fulfilled', 'cancelled') NULL DEFAULT NULL,
  notes TEXT NULL,
  created_by_user_id INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_donations_org_fy (organization_id, financial_year),
  KEY idx_donations_parent (parent_id),
  KEY idx_donations_category (category_id),
  KEY idx_donations_status (organization_id, status),
  CONSTRAINT fk_donations_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
  CONSTRAINT fk_donations_category FOREIGN KEY (category_id) REFERENCES donation_categories (id) ON DELETE RESTRICT,
  CONSTRAINT fk_donations_parent FOREIGN KEY (parent_id) REFERENCES donations (id) ON DELETE CASCADE,
  CONSTRAINT fk_donations_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_donations_family FOREIGN KEY (family_id) REFERENCES families (id) ON DELETE SET NULL,
  CONSTRAINT fk_donations_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
