-- Pratikraman calendar day type (optional scheduled time, same as vyakhyan).

ALTER TABLE organization_calendar_days
  MODIFY COLUMN category ENUM('holiday', 'paryushan', 'religious', 'other', 'vyakhyan', 'pratikraman') NOT NULL DEFAULT 'other';
