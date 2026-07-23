-- Daily tithi is now direct web push only.
-- Remove historical in-app rows/campaigns and the obsolete delivery log.

DELETE FROM notifications WHERE type = 'daily_tithi';
DELETE FROM notification_campaigns WHERE audience = 'daily_tithi';
DROP TABLE IF EXISTS daily_tithi_notification_log;
