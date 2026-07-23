-- Organization-specific calendar days (org admin-managed)
CREATE TABLE IF NOT EXISTS organization_calendar_days (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  title VARCHAR(191) NOT NULL,
  title_gu VARCHAR(191) NULL DEFAULT NULL,
  category ENUM('holiday', 'paryushan', 'religious', 'other') NOT NULL DEFAULT 'other',
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  notes TEXT NULL DEFAULT NULL,
  created_by INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_org_calendar_days_org (organization_id),
  KEY idx_org_calendar_days_dates (start_date, end_date),
  CONSTRAINT fk_org_calendar_days_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
  CONSTRAINT fk_org_calendar_days_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
