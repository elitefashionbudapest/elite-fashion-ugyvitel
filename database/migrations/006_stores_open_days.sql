-- Migration 006: Nyitvatartási napok boltonként
ALTER TABLE stores ADD COLUMN open_days VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5,6' AFTER opening_cash;
-- Alapértelmezés: hétfő-szombat (1=hétfő, 7=vasárnap, ISO 8601)
