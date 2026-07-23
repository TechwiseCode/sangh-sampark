-- Store member filter snapshot on notification campaigns.

ALTER TABLE notification_campaigns
  ADD COLUMN recipient_filters VARCHAR(255) NULL DEFAULT NULL AFTER audience;
