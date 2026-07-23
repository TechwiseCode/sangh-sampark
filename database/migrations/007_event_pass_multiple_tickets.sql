-- Allow multiple passes (tickets) per person per event.

ALTER TABLE event_passes DROP INDEX uq_event_pass_holder;

ALTER TABLE event_passes ADD KEY idx_event_pass_family_event (due_definition_id, family_id, status);
