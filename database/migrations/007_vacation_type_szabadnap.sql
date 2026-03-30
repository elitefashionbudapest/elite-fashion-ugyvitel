-- Migration 007: Kivételes szabadnap típus a szabadság kérvényeknél
ALTER TABLE vacation_requests ADD COLUMN type ENUM('szabadsag','szabadnap') NOT NULL DEFAULT 'szabadsag' AFTER employee_id;
