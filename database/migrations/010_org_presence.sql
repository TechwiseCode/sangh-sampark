-- Who is currently at the organization (snapshot + history)
CREATE TABLE IF NOT EXISTS org_presence_lists (
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

CREATE TABLE IF NOT EXISTS org_presence_members (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  presence_list_id INT UNSIGNED NOT NULL,
  display_name VARCHAR(191) NOT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_presence_members_list (presence_list_id, sort_order),
  CONSTRAINT fk_presence_members_list FOREIGN KEY (presence_list_id) REFERENCES org_presence_lists (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
