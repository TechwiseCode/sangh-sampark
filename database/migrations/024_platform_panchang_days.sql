-- Gujarati panchang tithi per Gregorian date (platform-wide, CSV import).

CREATE TABLE IF NOT EXISTS platform_panchang_days (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  gregorian_date DATE NOT NULL,
  weekday VARCHAR(16) NULL DEFAULT NULL,
  gujarati_month VARCHAR(32) NULL DEFAULT NULL,
  paksha VARCHAR(16) NULL DEFAULT NULL,
  tithi VARCHAR(64) NOT NULL,
  festival_notes TEXT NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_panchang_gregorian_date (gregorian_date),
  KEY idx_panchang_gregorian_date (gregorian_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
