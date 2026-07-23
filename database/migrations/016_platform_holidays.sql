-- Platform-wide holidays, Paryushan, and religious days (superadmin-managed)
CREATE TABLE IF NOT EXISTS platform_holidays (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(191) NOT NULL,
  title_gu VARCHAR(191) NULL DEFAULT NULL,
  category ENUM('holiday', 'paryushan', 'religious') NOT NULL DEFAULT 'religious',
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  notes TEXT NULL DEFAULT NULL,
  created_by INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_platform_holidays_dates (start_date, end_date),
  CONSTRAINT fk_platform_holidays_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
