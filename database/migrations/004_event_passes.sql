-- Event passes: issued when a receipt is linked to an event-type due.

CREATE TABLE IF NOT EXISTS event_passes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  due_definition_id INT UNSIGNED NOT NULL,
  family_id INT UNSIGNED NOT NULL,
  recipient_user_id INT UNSIGNED NOT NULL,
  receipt_id INT UNSIGNED NOT NULL,
  pass_code VARCHAR(32) NOT NULL,
  status ENUM('active','redeemed','cancelled') NOT NULL DEFAULT 'active',
  issued_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  redeemed_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY uq_event_pass_family (due_definition_id, family_id),
  UNIQUE KEY uq_event_pass_code (pass_code),
  UNIQUE KEY uq_event_pass_receipt (receipt_id),
  KEY idx_event_pass_org (organization_id),
  KEY idx_event_pass_recipient (recipient_user_id),
  CONSTRAINT fk_event_pass_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
  CONSTRAINT fk_event_pass_due FOREIGN KEY (due_definition_id) REFERENCES due_definitions (id) ON DELETE CASCADE,
  CONSTRAINT fk_event_pass_family FOREIGN KEY (family_id) REFERENCES families (id) ON DELETE CASCADE,
  CONSTRAINT fk_event_pass_recipient FOREIGN KEY (recipient_user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_event_pass_receipt FOREIGN KEY (receipt_id) REFERENCES receipts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
