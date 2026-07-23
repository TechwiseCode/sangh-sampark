-- Per-family vs per-person pricing on dues/events.

ALTER TABLE due_definitions
  ADD COLUMN charge_basis ENUM('per_family','per_person') NOT NULL DEFAULT 'per_family' AFTER amount;

UPDATE due_definitions SET charge_basis = 'per_person' WHERE due_type = 'membership';
UPDATE due_definitions SET charge_basis = 'per_person' WHERE due_type = 'event';

-- Event passes: one pass per person (holder), not per family.

ALTER TABLE event_passes
  ADD COLUMN holder_user_id INT UNSIGNED NULL DEFAULT NULL AFTER recipient_user_id;

UPDATE event_passes SET holder_user_id = recipient_user_id WHERE holder_user_id IS NULL;

ALTER TABLE event_passes
  MODIFY holder_user_id INT UNSIGNED NOT NULL;

ALTER TABLE event_passes DROP INDEX uq_event_pass_family;

ALTER TABLE event_passes
  ADD UNIQUE KEY uq_event_pass_holder (due_definition_id, holder_user_id),
  ADD KEY idx_event_pass_holder (holder_user_id);

ALTER TABLE event_passes
  ADD CONSTRAINT fk_event_pass_holder FOREIGN KEY (holder_user_id) REFERENCES users (id) ON DELETE CASCADE;
