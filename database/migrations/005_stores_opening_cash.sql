-- Migration 005: Induló kassza érték boltonként
-- A kassza_nyito pénzmozgás típus ezentúl csak napi ellenőrzés, nem számít az egyenlegbe

ALTER TABLE stores ADD COLUMN opening_cash DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER name;

-- Induló értékek beállítása (2026-03-28-i kassza nyitók)
UPDATE stores SET opening_cash = 327400 WHERE name = 'Vörösmarty';
UPDATE stores SET opening_cash = 300170 WHERE name = 'Selmeci';
UPDATE stores SET opening_cash = 240713 WHERE name = 'Üllői út';
