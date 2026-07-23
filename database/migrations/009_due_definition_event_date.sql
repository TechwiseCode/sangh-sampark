-- Event/occasion schedule date for organization calendar
ALTER TABLE due_definitions
  ADD COLUMN event_date DATE NULL DEFAULT NULL AFTER financial_year;
