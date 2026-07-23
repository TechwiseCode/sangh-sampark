-- Multiple passes can be issued from one receipt (per-person ticket count).

ALTER TABLE event_passes DROP INDEX uq_event_pass_receipt;
