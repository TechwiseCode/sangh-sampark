-- Run on existing DB (MySQL 5.7+ / 8.x). Safe to run once.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS family_membership_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  family_id INT UNSIGNED NOT NULL,
  target_user_id INT UNSIGNED NOT NULL,
  requested_by_user_id INT UNSIGNED NOT NULL,
  requested_role VARCHAR(64) NOT NULL,
  related_to_user_id INT UNSIGNED NULL DEFAULT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  responded_at TIMESTAMP NULL DEFAULT NULL,
  responded_by_user_id INT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_fmr_family_target (family_id, target_user_id),
  KEY idx_fmr_target_status (target_user_id, status),
  CONSTRAINT fk_fmr_family FOREIGN KEY (family_id) REFERENCES families (id) ON DELETE CASCADE,
  CONSTRAINT fk_fmr_target FOREIGN KEY (target_user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_fmr_requester FOREIGN KEY (requested_by_user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_fmr_related FOREIGN KEY (related_to_user_id) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_fmr_responder FOREIGN KEY (responded_by_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type VARCHAR(50) NOT NULL DEFAULT 'relationship_request',
  reference_id INT UNSIGNED NULL DEFAULT NULL COMMENT 'family_membership_requests.id',
  title VARCHAR(255) NOT NULL,
  message TEXT NULL,
  read_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notif_user (user_id),
  KEY idx_notif_user_unread (user_id, read_at),
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
