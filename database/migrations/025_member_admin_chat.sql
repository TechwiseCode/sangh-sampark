-- Optional member → org admin session chat (disable via MEMBER_ADMIN_CHAT=false).
-- Revert: docs/MEMBER_ADMIN_CHAT.md

CREATE TABLE IF NOT EXISTS org_member_admin_chat_threads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  member_user_id INT UNSIGNED NOT NULL,
  session_token VARCHAR(64) NOT NULL,
  status ENUM('open','replied') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_mac_thread_org_status (organization_id, status, updated_at),
  KEY idx_mac_thread_member_session (member_user_id, session_token),
  CONSTRAINT fk_mac_thread_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
  CONSTRAINT fk_mac_thread_member FOREIGN KEY (member_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS org_member_admin_chat_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  thread_id INT UNSIGNED NOT NULL,
  sender_role ENUM('member','admin') NOT NULL,
  sender_user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mac_msg_thread (thread_id, created_at),
  CONSTRAINT fk_mac_msg_thread FOREIGN KEY (thread_id) REFERENCES org_member_admin_chat_threads (id) ON DELETE CASCADE,
  CONSTRAINT fk_mac_msg_sender FOREIGN KEY (sender_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
