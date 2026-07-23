-- Log daily tithi broadcasts (one per org per calendar day).
CREATE TABLE IF NOT EXISTS daily_tithi_notification_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  gregorian_date DATE NOT NULL,
  campaign_id INT UNSIGNED NULL DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NULL,
  recipient_count INT UNSIGNED NOT NULL DEFAULT 0,
  push_sent_count INT UNSIGNED NOT NULL DEFAULT 0,
  sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_daily_tithi_org_date (organization_id, gregorian_date),
  KEY idx_daily_tithi_date (gregorian_date),
  CONSTRAINT fk_daily_tithi_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
  CONSTRAINT fk_daily_tithi_campaign FOREIGN KEY (campaign_id) REFERENCES notification_campaigns (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
