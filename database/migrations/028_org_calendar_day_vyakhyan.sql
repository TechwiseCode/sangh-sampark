-- Vyakhyan calendar day type with optional scheduled time.

ALTER TABLE organization_calendar_days
  MODIFY COLUMN category ENUM('holiday', 'paryushan', 'religious', 'other', 'vyakhyan') NOT NULL DEFAULT 'other',
  ADD COLUMN event_time TIME NULL DEFAULT NULL AFTER end_date;
