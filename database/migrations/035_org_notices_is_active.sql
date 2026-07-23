-- Allow admins to deactivate notices (hidden from members, still visible to admins).

ALTER TABLE org_notices
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER is_pinned,
  ADD KEY idx_org_notices_active (organization_id, is_active, is_pinned, created_at);
