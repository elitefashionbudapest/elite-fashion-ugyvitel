-- Migration 002: Rögzítési jogosultság szétválasztása
-- Dátum: 2026-03-29
-- can_create mező hozzáadása a tab_permissions táblához
-- Meglévő can_edit jogosultságok átmásolása can_create-be

ALTER TABLE tab_permissions ADD COLUMN can_create TINYINT(1) NOT NULL DEFAULT 0 AFTER can_view;
UPDATE tab_permissions SET can_create = can_edit;
