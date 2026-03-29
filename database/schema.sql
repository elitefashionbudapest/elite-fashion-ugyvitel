-- Elite Fashion Ügyviteli Rendszer - Adatbázis séma
-- Charset: utf8mb4, Collation: utf8mb4_hungarian_ci
--
-- FIGYELEM: Ezt a fájlt CSAK egyszer szabad futtatni, az első telepítéskor!
-- Ha már fut az éles rendszer, SOHA ne importáld újra — törli az összes adatot!
-- Későbbi módosításokhoz használd a database/migrations/ mappát.

CREATE DATABASE IF NOT EXISTS elite_fashion
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_hungarian_ci;

USE elite_fashion;

-- ============================================
-- Boltok
-- ============================================
CREATE TABLE stores (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- ============================================
-- Bejelentkezhető fiókok (tulajdonos + bolt)
-- ============================================
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    role            ENUM('tulajdonos','bolt') NOT NULL DEFAULT 'bolt',
    store_id        INT UNSIGNED DEFAULT NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    remember_token  VARCHAR(64) DEFAULT NULL,
    avatar          VARCHAR(255) DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- ============================================
-- Dolgozók (NEM jelentkeznek be!)
-- ============================================
CREATE TABLE employees (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- ============================================
-- Dolgozó-Bolt hozzárendelés
-- ============================================
CREATE TABLE employee_store (
    employee_id INT UNSIGNED NOT NULL,
    store_id    INT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (employee_id, store_id),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- ============================================
-- Tab jogosultságok (bolt fiókokra)
-- ============================================
CREATE TABLE tab_permissions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    tab_slug    VARCHAR(50) NOT NULL,
    can_view    TINYINT(1) NOT NULL DEFAULT 0,
    can_edit    TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY unique_user_tab (user_id, tab_slug),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- ============================================
-- Könyvelés / Pénzmozgások (9 típus)
-- ============================================
CREATE TABLE financial_records (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id            INT UNSIGNED NOT NULL,
    recorded_by         INT UNSIGNED NOT NULL,
    record_date         DATE NOT NULL,
    purpose             ENUM(
                            'napi_keszpenz',
                            'napi_bankkartya',
                            'meretre_igazitas',
                            'tankolas',
                            'munkaber',
                            'egyeb_kifizetes',
                            'bank_kifizetes',
                            'befizetes_bankbol',
                            'befizetes_boltbol',
                            'kassza_nyito',
                            'szamla_kifizetes',
                            'selejt_befizetes'
                        ) NOT NULL,
    amount              DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    description         TEXT DEFAULT NULL,
    paid_to_employee_id INT UNSIGNED DEFAULT NULL,
    bank_id             INT UNSIGNED DEFAULT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (paid_to_employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    INDEX idx_store_date (store_id, record_date),
    INDEX idx_purpose (purpose)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- ============================================
-- Saját Fizetés
-- ============================================
CREATE TABLE salary_payments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED NOT NULL,
    issued_by       VARCHAR(100) NOT NULL,
    month           TINYINT UNSIGNED NOT NULL,
    year            SMALLINT UNSIGNED NOT NULL,
    payment_source  ENUM('bank','selmeci','ulloi_ut','egyeb') NOT NULL,
    amount          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    recorded_by     INT UNSIGNED NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_employee_period (employee_id, year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- ============================================
-- Bolti Értékelés Összesítő
-- ============================================
CREATE TABLE evaluations (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id            INT UNSIGNED NOT NULL,
    recorded_by         INT UNSIGNED NOT NULL,
    record_date         DATE NOT NULL,
    customer_count      INT UNSIGNED NOT NULL DEFAULT 0,
    google_review_count INT UNSIGNED NOT NULL DEFAULT 0,
    notes               TEXT DEFAULT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_store_date (store_id, record_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- Ki dolgozott aznap (értékelés jóváíráshoz)
CREATE TABLE evaluation_workers (
    evaluation_id   INT UNSIGNED NOT NULL,
    employee_id     INT UNSIGNED NOT NULL,
    PRIMARY KEY (evaluation_id, employee_id),
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- ============================================
-- Szabadság kérvények
-- ============================================
CREATE TABLE vacation_requests (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id          INT UNSIGNED NOT NULL,
    date_from            DATE NOT NULL,
    date_to              DATE NOT NULL,
    confirmed_no_overlap TINYINT(1) NOT NULL DEFAULT 0,
    status               ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    approved_by          INT UNSIGNED DEFAULT NULL,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_dates (date_from, date_to),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- ============================================
-- Beosztások
-- ============================================
CREATE TABLE schedules (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id    INT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED NOT NULL,
    work_date   DATE NOT NULL,
    shift_start TIME DEFAULT NULL,
    shift_end   TIME DEFAULT NULL,
    created_by  INT UNSIGNED NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_date (employee_id, work_date),
    INDEX idx_store_date (store_id, work_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- ============================================
-- Selejt tételek (vonalkód szkenner)
-- ============================================
CREATE TABLE defect_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id    INT UNSIGNED NOT NULL,
    barcode     VARCHAR(100) NOT NULL,
    scanned_by  INT UNSIGNED NOT NULL,
    scanned_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (scanned_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_store (store_id),
    INDEX idx_barcode (barcode),
    INDEX idx_scanned_at (scanned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- ============================================
-- Chat üzenetek
-- ============================================
CREATE TABLE chat_messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id   INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED DEFAULT NULL,
    message     TEXT NOT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_conversation (sender_id, receiver_id),
    INDEX idx_unread (receiver_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- ============================================
-- Értesítések
-- ============================================
CREATE TABLE notifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    type        VARCHAR(50) NOT NULL,
    title       VARCHAR(255) NOT NULL,
    message     TEXT DEFAULT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    link        VARCHAR(500) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- ============================================
-- Audit log (módosítások naplózása)
-- ============================================
CREATE TABLE audit_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED DEFAULT NULL,
    action      VARCHAR(50) NOT NULL,
    table_name  VARCHAR(100) NOT NULL,
    record_id   INT UNSIGNED DEFAULT NULL,
    old_values  JSON DEFAULT NULL,
    new_values  JSON DEFAULT NULL,
    ip_address  VARCHAR(45) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- ============================================
-- SEED ADATOK
-- ============================================

-- Boltok
INSERT INTO stores (name) VALUES
('Vörösmarty'),
('Selmeci'),
('Üllői út');

-- Tulajdonos fiókok (jelszó: eliteFashion2026)
INSERT INTO users (name, email, password, role, store_id) VALUES
('Ádám', 'adam@elitefashion.hu', '$2y$12$K3JCiK..BWFLenMPecjJm.CEN.Cj8KntuJJhZO5gFfHXbCUxy7pkW', 'tulajdonos', NULL),
('Imi', 'imi@elitefashion.hu', '$2y$12$K3JCiK..BWFLenMPecjJm.CEN.Cj8KntuJJhZO5gFfHXbCUxy7pkW', 'tulajdonos', NULL);

-- Bolt fiókok (jelszó: eliteFashion2026)
INSERT INTO users (name, email, password, role, store_id) VALUES
('Vörösmarty bolt', 'vorosmarty@elitefashion.hu', '$2y$12$K3JCiK..BWFLenMPecjJm.CEN.Cj8KntuJJhZO5gFfHXbCUxy7pkW', 'bolt', 1),
('Selmeci bolt', 'selmeci@elitefashion.hu', '$2y$12$K3JCiK..BWFLenMPecjJm.CEN.Cj8KntuJJhZO5gFfHXbCUxy7pkW', 'bolt', 2),
('Üllői út bolt', 'ulloi@elitefashion.hu', '$2y$12$K3JCiK..BWFLenMPecjJm.CEN.Cj8KntuJJhZO5gFfHXbCUxy7pkW', 'bolt', 3);

-- Dolgozók
INSERT INTO employees (name) VALUES
('Tündi'),
('Gabi'),
('Andi'),
('Liza'),
('Sz. Andi'),
('Bartos Ottóné'),
('Nemzeti Józsefné'),
('Nagy Andrea');

-- Alapértelmezett tab jogosultságok a bolt fiókoknak (mind látható, szerkeszthető)
INSERT INTO tab_permissions (user_id, tab_slug, can_view, can_edit) VALUES
-- Vörösmarty bolt (user_id = 3)
(3, 'dashboard', 1, 0), (3, 'konyveles', 1, 1), (3, 'fizetes', 1, 1),
(3, 'ertekeles', 1, 1), (3, 'szabadsag', 1, 1), (3, 'beosztas', 1, 0),
(3, 'selejt', 1, 1), (3, 'chat', 1, 1), (3, 'kimutat', 1, 0),
-- Selmeci bolt (user_id = 4)
(4, 'dashboard', 1, 0), (4, 'konyveles', 1, 1), (4, 'fizetes', 1, 1),
(4, 'ertekeles', 1, 1), (4, 'szabadsag', 1, 1), (4, 'beosztas', 1, 0),
(4, 'selejt', 1, 1), (4, 'chat', 1, 1), (4, 'kimutat', 1, 0),
-- Üllői út bolt (user_id = 5)
(5, 'dashboard', 1, 0), (5, 'konyveles', 1, 1), (5, 'fizetes', 1, 1),
(5, 'ertekeles', 1, 1), (5, 'szabadsag', 1, 1), (5, 'beosztas', 1, 0),
(5, 'selejt', 1, 1), (5, 'chat', 1, 1), (5, 'kimutat', 1, 0);
