-- Termék adatbázis (vonalkód alapú kereséshez)
CREATE TABLE IF NOT EXISTS products (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(500) NOT NULL,
    sku             VARCHAR(255) DEFAULT NULL,
    barcode         VARCHAR(100) NOT NULL,
    product_type    VARCHAR(100) DEFAULT NULL,
    net_price       DECIMAL(12,2) DEFAULT 0.00,
    vat_rate        VARCHAR(50) DEFAULT NULL,
    gross_price     DECIMAL(12,2) DEFAULT 0.00,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_barcode (barcode),
    INDEX idx_sku (sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- Selejt tételekhez termék adatok mentése (szkenneléskor rögzített ár)
ALTER TABLE defect_items
    ADD COLUMN product_name VARCHAR(500) DEFAULT NULL AFTER barcode,
    ADD COLUMN product_price DECIMAL(12,2) DEFAULT NULL AFTER product_name;
