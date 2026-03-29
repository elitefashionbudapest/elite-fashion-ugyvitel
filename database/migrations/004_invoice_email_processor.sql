-- Migration 004: Automatikus számla email feldolgozás
-- Dátum: 2026-03-29

-- Cégbeállítások tábla
CREATE TABLE IF NOT EXISTS company_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- Email feldolgozási napló
CREATE TABLE IF NOT EXISTS invoice_email_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email_uid VARCHAR(255) NOT NULL,
    email_subject VARCHAR(500),
    email_from VARCHAR(255),
    email_date DATETIME,
    status ENUM('processed','skipped','error','duplicate','not_invoice') NOT NULL,
    invoice_id INT UNSIGNED DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_uid (email_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- Számla tábla bővítés
ALTER TABLE invoices ADD COLUMN needs_review TINYINT(1) NOT NULL DEFAULT 0 AFTER is_paid;
ALTER TABLE invoices ADD COLUMN auto_imported TINYINT(1) NOT NULL DEFAULT 0 AFTER needs_review;
