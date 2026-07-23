-- Widen push endpoint for Apple Web Push (URLs can exceed 512 chars).

ALTER TABLE push_subscriptions
  MODIFY endpoint VARCHAR(2048) NOT NULL;
