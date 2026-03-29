-- Elite Fashion adatbázis - teljes export adatokkal
-- Dátum: 2026-03-29 09:03:56

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE `audit_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` int(10) unsigned DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_table_record` (`table_name`,`record_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `created_at`) VALUES
('1', '1', 'update', 'tab_permissions', NULL, NULL, '{\"updated\":[4,5,3]}', '::1', '2026-03-28 16:02:15'),
('2', '1', 'update', 'tab_permissions', NULL, NULL, '{\"updated\":[4,5,3]}', '::1', '2026-03-28 17:04:22'),
('3', '1', 'update', 'tab_permissions', NULL, NULL, '{\"updated\":[4,5,3,6]}', '::1', '2026-03-28 17:27:47'),
('4', '1', 'update', 'banks', '2', '{\"id\":2,\"name\":\"CIB Bank\",\"account_number\":null,\"notes\":null,\"opening_balance\":\"0.00\",\"min_balance\":null,\"is_active\":1,\"is_loan\":0,\"created_at\":\"2026-03-28 15:33:59\"}', '{\"_csrf\":\"03b3b9a10133c9276406e3083df0cb73445dd1d90f2f160d59f4eabfdb1eee2d\",\"is_loan\":\"0\",\"name\":\"CIB Bank\",\"account_number\":\"10700347-73718955-11000005\",\"opening_balance\":\"3305326\",\"min_balance\":\"\",\"notes\":\"\",\"is_active\":\"1\"}', '::1', '2026-03-28 20:12:28'),
('5', '1', 'update', 'banks', '1', '{\"id\":1,\"name\":\"OTP\",\"account_number\":null,\"notes\":null,\"opening_balance\":\"0.00\",\"min_balance\":null,\"is_active\":1,\"is_loan\":0,\"created_at\":\"2026-03-28 15:33:59\"}', '{\"_csrf\":\"03b3b9a10133c9276406e3083df0cb73445dd1d90f2f160d59f4eabfdb1eee2d\",\"is_loan\":\"0\",\"name\":\"OTP\",\"account_number\":\"11742551-21385663\",\"opening_balance\":\"1389294\",\"min_balance\":\"\",\"notes\":\"\",\"is_active\":\"1\"}', '::1', '2026-03-28 20:13:49'),
('6', '1', 'update', 'banks', '7', '{\"id\":7,\"name\":\"Széchenyi Hitelkártya\",\"account_number\":null,\"notes\":\"Széchenyi hitelkeret kártya, -7M limit\",\"opening_balance\":\"0.00\",\"min_balance\":\"-7000000.00\",\"is_active\":1,\"is_loan\":0,\"created_at\":\"2026-03-28 20:03:20\"}', '{\"_csrf\":\"03b3b9a10133c9276406e3083df0cb73445dd1d90f2f160d59f4eabfdb1eee2d\",\"is_loan\":\"0\",\"name\":\"Széchenyi Hitelkártya\",\"account_number\":\"11718000-22415392\",\"opening_balance\":\"-6453992\",\"min_balance\":\"-7000000\",\"notes\":\"Széchenyi hitelkeret kártya, -7M limit\",\"is_active\":\"1\"}', '::1', '2026-03-28 20:14:23'),
('7', '1', 'update', 'banks', '8', '{\"id\":8,\"name\":\"WISE (USD)\",\"currency\":\"USD\",\"account_number\":null,\"notes\":\"WISE USD számla\",\"opening_balance\":\"0.00\",\"min_balance\":null,\"is_active\":1,\"is_loan\":0,\"created_at\":\"2026-03-28 20:20:26\"}', '{\"_csrf\":\"03b3b9a10133c9276406e3083df0cb73445dd1d90f2f160d59f4eabfdb1eee2d\",\"is_loan\":\"0\",\"name\":\"WISE (USD)\",\"currency\":\"USD\",\"account_number\":\"8312438935\",\"opening_balance\":\"736.23\",\"min_balance\":\"\",\"notes\":\"WISE USD számla\",\"is_active\":\"1\"}', '::1', '2026-03-28 20:38:44');

DROP TABLE IF EXISTS `bank_statements`;
CREATE TABLE `bank_statements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bank_id` int(10) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `uploaded_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_bank_month` (`bank_id`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

DROP TABLE IF EXISTS `bank_transaction_stores`;
CREATE TABLE `bank_transaction_stores` (
  `bank_transaction_id` int(10) unsigned NOT NULL,
  `store_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`bank_transaction_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

DROP TABLE IF EXISTS `bank_transactions`;
CREATE TABLE `bank_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bank_id` int(10) unsigned NOT NULL,
  `type` enum('kartya_beerkezes','szolgaltato_levon','hitel_torlesztes','szamla_kozti') NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `source_amount` decimal(12,2) DEFAULT NULL,
  `target_currency` varchar(3) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `provider_name` varchar(255) DEFAULT NULL,
  `invoice_id` int(10) unsigned DEFAULT NULL,
  `loan_bank_id` int(10) unsigned DEFAULT NULL,
  `target_bank_id` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_bank_date` (`bank_id`,`transaction_date`),
  KEY `idx_type` (`type`),
  KEY `idx_invoice` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

DROP TABLE IF EXISTS `banks`;
CREATE TABLE `banks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'HUF',
  `account_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `opening_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `min_balance` decimal(12,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_loan` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

INSERT INTO `banks` (`id`, `name`, `currency`, `account_number`, `notes`, `opening_balance`, `min_balance`, `is_active`, `is_loan`, `created_at`) VALUES
('1', 'OTP', 'HUF', '11742551-21385663', NULL, '1389294.00', NULL, '1', '0', '2026-03-28 15:33:59'),
('2', 'CIB Bank', 'HUF', '10700347-73718955-11000005', NULL, '3305326.00', NULL, '1', '0', '2026-03-28 15:33:59'),
('3', 'WISE (HUF)', 'HUF', NULL, NULL, '0.00', NULL, '1', '0', '2026-03-28 15:33:59'),
('4', 'Széchenyi Hitel 1', 'HUF', NULL, 'Széchenyi hitelkeret', '0.00', '-7000000.00', '1', '1', '2026-03-28 19:11:56'),
('5', 'Széchenyi Hitel 2', 'HUF', NULL, 'Széchenyi hitelkeret 2', '0.00', NULL, '1', '1', '2026-03-28 19:13:47'),
('6', 'MFB Hitel', 'HUF', NULL, 'MFB hitelkeret', '0.00', NULL, '1', '1', '2026-03-28 19:13:47'),
('7', 'Széchenyi Hitelkártya', 'HUF', '11718000-22415392', 'Széchenyi hitelkeret kártya, -7M limit', '-6453992.00', '-7000000.00', '1', '0', '2026-03-28 20:03:20'),
('8', 'WISE (USD)', 'USD', '8312438935', 'WISE USD számla', '736.23', NULL, '1', '0', '2026-03-28 20:20:26'),
('9', 'WISE (EUR)', 'EUR', NULL, 'WISE EUR számla', '0.00', NULL, '1', '0', '2026-03-28 20:20:26');

DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE `chat_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int(10) unsigned NOT NULL,
  `receiver_id` int(10) unsigned DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_conversation` (`sender_id`,`receiver_id`),
  KEY `idx_unread` (`receiver_id`,`is_read`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

INSERT INTO `chat_messages` (`id`, `sender_id`, `receiver_id`, `message`, `is_read`, `created_at`) VALUES
('1', '1', NULL, 'Hali!', '0', '2026-03-28 11:46:24'),
('2', '1', NULL, 'Hali!', '0', '2026-03-28 15:45:33'),
('3', '1', NULL, 'Sziasztok!', '0', '2026-03-28 15:48:29'),
('4', '1', NULL, 'Hali', '0', '2026-03-28 15:48:53'),
('5', '1', NULL, 'Na most már megy!', '0', '2026-03-28 15:50:34');

DROP TABLE IF EXISTS `defect_daily_values`;
CREATE TABLE `defect_daily_values` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL,
  `value_date` date NOT NULL,
  `total_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `recorded_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_store_date` (`store_id`,`value_date`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `defect_daily_values_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `defect_daily_values_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

DROP TABLE IF EXISTS `defect_items`;
CREATE TABLE `defect_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL,
  `barcode` varchar(100) NOT NULL,
  `scanned_by` int(10) unsigned NOT NULL,
  `scanned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `scanned_by` (`scanned_by`),
  KEY `idx_store` (`store_id`),
  KEY `idx_barcode` (`barcode`),
  KEY `idx_scanned_at` (`scanned_at`),
  CONSTRAINT `defect_items_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `defect_items_ibfk_2` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

DROP TABLE IF EXISTS `employee_store`;
CREATE TABLE `employee_store` (
  `employee_id` int(10) unsigned NOT NULL,
  `store_id` int(10) unsigned NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`employee_id`,`store_id`),
  KEY `store_id` (`store_id`),
  CONSTRAINT `employee_store_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_store_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `vacation_days_total` int(10) unsigned NOT NULL DEFAULT 20,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

INSERT INTO `employees` (`id`, `name`, `is_active`, `vacation_days_total`, `created_at`, `updated_at`) VALUES
('1', 'V.né Nagy Andrea', '1', '30', '2026-03-28 11:24:03', '2026-03-28 17:22:35'),
('2', 'Molnár Attiláné', '1', '30', '2026-03-28 11:24:03', '2026-03-28 17:22:35'),
('3', 'B.né Hrotkó Tünde', '1', '30', '2026-03-28 11:24:03', '2026-03-28 17:22:35'),
('4', 'Szilágyi Andrea', '1', '30', '2026-03-28 11:24:03', '2026-03-28 17:22:35'),
('5', 'Németh Józsefné', '1', '30', '2026-03-28 11:24:03', '2026-03-28 17:22:35');

DROP TABLE IF EXISTS `evaluation_workers`;
CREATE TABLE `evaluation_workers` (
  `evaluation_id` int(10) unsigned NOT NULL,
  `employee_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`evaluation_id`,`employee_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `evaluation_workers_ibfk_1` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluation_workers_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

DROP TABLE IF EXISTS `evaluations`;
CREATE TABLE `evaluations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL,
  `recorded_by` int(10) unsigned NOT NULL,
  `record_date` date NOT NULL,
  `customer_count` int(10) unsigned NOT NULL DEFAULT 0,
  `google_review_count` int(10) unsigned NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `recorded_by` (`recorded_by`),
  KEY `idx_store_date` (`store_id`,`record_date`),
  CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluations_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

DROP TABLE IF EXISTS `financial_records`;
CREATE TABLE `financial_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL,
  `recorded_by` int(10) unsigned NOT NULL,
  `record_date` date NOT NULL,
  `purpose` enum('napi_keszpenz','napi_bankkartya','meretre_igazitas','tankolas','munkaber','egyeb_kifizetes','bank_kifizetes','befizetes_bankbol','befizetes_boltbol','kassza_nyito','szamla_kifizetes','selejt_befizetes') NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `paid_to_employee_id` int(10) unsigned DEFAULT NULL,
  `bank_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `recorded_by` (`recorded_by`),
  KEY `paid_to_employee_id` (`paid_to_employee_id`),
  KEY `idx_store_date` (`store_id`,`record_date`),
  KEY `idx_purpose` (`purpose`),
  CONSTRAINT `financial_records_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `financial_records_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `financial_records_ibfk_3` FOREIGN KEY (`paid_to_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

INSERT INTO `financial_records` (`id`, `store_id`, `recorded_by`, `record_date`, `purpose`, `amount`, `description`, `paid_to_employee_id`, `bank_id`, `created_at`, `updated_at`) VALUES
('1', '3', '1', '2026-03-28', 'kassza_nyito', '240713.00', NULL, NULL, NULL, '2026-03-28 16:44:45', '2026-03-28 16:44:45'),
('2', '1', '1', '2026-03-28', 'kassza_nyito', '327400.00', NULL, NULL, NULL, '2026-03-28 16:44:45', '2026-03-28 16:44:45'),
('3', '2', '1', '2026-03-28', 'kassza_nyito', '300170.00', NULL, NULL, NULL, '2026-03-28 16:44:45', '2026-03-28 16:44:45');

DROP TABLE IF EXISTS `holidays`;
CREATE TABLE `holidays` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`date`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

INSERT INTO `holidays` (`id`, `date`, `name`, `created_at`) VALUES
('1', '2026-01-01', 'Újév', '2026-03-28 17:00:39'),
('2', '2026-03-15', 'Nemzeti ünnep', '2026-03-28 17:00:39'),
('3', '2026-04-03', 'Nagypéntek', '2026-03-28 17:00:39'),
('4', '2026-04-06', 'Húsvéthétfő', '2026-03-28 17:00:39'),
('5', '2026-05-01', 'A munka ünnepe', '2026-03-28 17:00:39'),
('6', '2026-05-25', 'Pünkösdhétfő', '2026-03-28 17:00:39'),
('7', '2026-08-20', 'Az államalapítás ünnepe', '2026-03-28 17:00:39'),
('8', '2026-10-23', 'Az 1956-os forradalom ünnepe', '2026-03-28 17:00:39'),
('9', '2026-11-01', 'Mindenszentek', '2026-03-28 17:00:39'),
('10', '2026-12-24', 'Szenteste', '2026-03-28 17:00:39'),
('11', '2026-12-25', 'Karácsony', '2026-03-28 17:00:39'),
('12', '2026-12-26', 'Karácsony 2. napja', '2026-03-28 17:00:39'),
('13', '2027-01-01', 'Újév', '2026-03-28 17:00:39');

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned DEFAULT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `net_amount` decimal(12,2) DEFAULT NULL,
  `currency` enum('HUF','EUR') NOT NULL DEFAULT 'HUF',
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `payment_method` enum('keszpenz','atutalas','kartya','utanvet') NOT NULL DEFAULT 'atutalas',
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `paid_at` date DEFAULT NULL,
  `paid_from_bank_id` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `recorded_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `recorded_by` (`recorded_by`),
  KEY `idx_store` (`store_id`),
  KEY `idx_supplier` (`supplier_id`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_paid` (`is_paid`),
  KEY `paid_from_bank_id` (`paid_from_bank_id`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_ibfk_4` FOREIGN KEY (`paid_from_bank_id`) REFERENCES `banks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

INSERT INTO `invoices` (`id`, `store_id`, `supplier_id`, `invoice_number`, `amount`, `net_amount`, `currency`, `invoice_date`, `due_date`, `payment_method`, `is_paid`, `paid_at`, `paid_from_bank_id`, `notes`, `image_path`, `recorded_by`, `created_at`, `updated_at`) VALUES
('1', '1', '1', 'TRTX-2026-197', '17200.00', '17200.00', 'HUF', '2026-03-18', '2026-03-18', 'keszpenz', '1', '2026-03-18', NULL, NULL, NULL, '1', '2026-03-28 16:36:51', '2026-03-28 19:35:50'),
('2', '1', '2', 'NJ6LA052038', '240000.00', '240000.00', 'HUF', '2026-03-17', '2026-03-17', 'keszpenz', '1', '2026-03-17', NULL, NULL, NULL, '1', '2026-03-28 16:36:51', '2026-03-28 19:35:50'),
('3', '1', '2', 'Nj6LA0525037', '27381.20', '27381.20', 'HUF', '2026-03-17', '2026-03-17', 'keszpenz', '1', '2026-03-17', NULL, NULL, NULL, '1', '2026-03-28 16:36:51', '2026-03-28 19:35:50'),
('4', '3', '3', 'A10300877/1569/00001', '9040.00', '9040.00', 'HUF', '2026-02-22', '2026-02-22', 'keszpenz', '1', '2026-02-22', NULL, NULL, NULL, '1', '2026-03-28 16:36:51', '2026-03-28 19:35:50'),
('5', '1', '2', 'NJ6LA0525035', '27381.20', '27381.20', 'HUF', '2026-02-17', '2026-02-17', 'keszpenz', '1', '2026-02-17', NULL, NULL, NULL, '1', '2026-03-28 16:36:51', '2026-03-28 19:35:50'),
('6', '1', '2', 'NJ6LA0525036', '240000.00', '240000.00', 'HUF', '2026-02-17', '2026-02-17', 'keszpenz', '1', '2026-02-17', NULL, NULL, NULL, '1', '2026-03-28 16:36:51', '2026-03-28 19:35:50'),
('7', '1', '2', 'NJ6LA0525034', '27381.20', '27381.20', 'HUF', '2026-01-21', '2026-01-21', 'keszpenz', '1', '2026-01-21', NULL, NULL, NULL, '1', '2026-03-28 16:36:51', '2026-03-28 19:35:50'),
('8', '1', '2', 'NJ6LA0525033', '240000.00', '240000.00', 'HUF', '2026-01-21', '2026-01-21', 'keszpenz', '1', '2026-01-21', NULL, NULL, NULL, '1', '2026-03-28 16:36:51', '2026-03-28 19:35:50');

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `link` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_unread` (`user_id`,`is_read`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

DROP TABLE IF EXISTS `owner_payments`;
CREATE TABLE `owner_payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `owner_name` varchar(100) NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `payment_source` enum('bank','selmeci','ulloi_ut','egyeb') NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `recorded_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `recorded_by` (`recorded_by`),
  KEY `idx_owner_period` (`owner_name`,`year`,`month`),
  CONSTRAINT `owner_payments_ibfk_1` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

DROP TABLE IF EXISTS `payslips`;
CREATE TABLE `payslips` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `uploaded_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_emp_month` (`employee_id`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

DROP TABLE IF EXISTS `salary_payments`;
CREATE TABLE `salary_payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `issued_by` varchar(100) NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `payment_source` enum('bank','selmeci','ulloi_ut','egyeb') NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `recorded_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `recorded_by` (`recorded_by`),
  KEY `idx_employee_period` (`employee_id`,`year`,`month`),
  CONSTRAINT `salary_payments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `salary_payments_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

DROP TABLE IF EXISTS `schedule_approvals`;
CREATE TABLE `schedule_approvals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `status` enum('draft','approved','modified') NOT NULL DEFAULT 'draft',
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `modified_by` int(10) unsigned DEFAULT NULL,
  `modified_at` timestamp NULL DEFAULT NULL,
  `modify_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_store_month` (`store_id`,`year`,`month`),
  KEY `approved_by` (`approved_by`),
  KEY `modified_by` (`modified_by`),
  CONSTRAINT `schedule_approvals_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `schedule_approvals_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `schedule_approvals_ibfk_3` FOREIGN KEY (`modified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

DROP TABLE IF EXISTS `schedules`;
CREATE TABLE `schedules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL,
  `employee_id` int(10) unsigned NOT NULL,
  `work_date` date NOT NULL,
  `shift_start` time DEFAULT NULL,
  `shift_end` time DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_emp_store_date` (`employee_id`,`store_id`,`work_date`),
  KEY `created_by` (`created_by`),
  KEY `idx_store_date` (`store_id`,`work_date`),
  CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `schedules_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=200 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

INSERT INTO `schedules` (`id`, `store_id`, `employee_id`, `work_date`, `shift_start`, `shift_end`, `created_by`, `created_at`, `updated_at`) VALUES
('107', '1', '1', '2026-04-01', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('108', '1', '2', '2026-04-01', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('109', '2', '4', '2026-04-01', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('110', '3', '5', '2026-04-01', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('111', '1', '1', '2026-04-02', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('112', '2', '2', '2026-04-02', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('113', '3', '4', '2026-04-02', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('114', '1', '2', '2026-04-04', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('115', '2', '4', '2026-04-04', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('116', '3', '5', '2026-04-04', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('117', '1', '1', '2026-04-07', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('118', '1', '2', '2026-04-07', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('119', '2', '4', '2026-04-07', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('120', '3', '5', '2026-04-07', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('121', '1', '1', '2026-04-08', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('122', '1', '2', '2026-04-08', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('123', '2', '3', '2026-04-08', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('124', '3', '5', '2026-04-08', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('125', '1', '2', '2026-04-09', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('126', '1', '4', '2026-04-09', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('127', '2', '3', '2026-04-09', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('128', '3', '5', '2026-04-09', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('129', '1', '1', '2026-04-10', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('130', '1', '2', '2026-04-10', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('131', '2', '3', '2026-04-10', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('132', '3', '4', '2026-04-10', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('133', '1', '1', '2026-04-11', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('134', '1', '2', '2026-04-11', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('135', '2', '3', '2026-04-11', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('136', '2', '4', '2026-04-11', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('137', '3', '5', '2026-04-11', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('138', '1', '1', '2026-04-12', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('139', '1', '1', '2026-04-13', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('140', '2', '3', '2026-04-13', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('141', '3', '5', '2026-04-13', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('142', '1', '2', '2026-04-14', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('143', '2', '3', '2026-04-14', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('144', '2', '4', '2026-04-14', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('145', '3', '5', '2026-04-14', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('146', '1', '1', '2026-04-15', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('147', '1', '2', '2026-04-15', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('148', '2', '4', '2026-04-15', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('149', '3', '5', '2026-04-15', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('150', '1', '2', '2026-04-16', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('151', '1', '4', '2026-04-16', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('152', '2', '3', '2026-04-16', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('153', '3', '5', '2026-04-16', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('154', '1', '2', '2026-04-17', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('155', '2', '3', '2026-04-17', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('156', '3', '4', '2026-04-17', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('157', '1', '2', '2026-04-18', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('158', '2', '3', '2026-04-18', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('159', '3', '5', '2026-04-18', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('160', '1', '3', '2026-04-19', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('161', '1', '1', '2026-04-20', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('162', '2', '3', '2026-04-20', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('163', '2', '4', '2026-04-20', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('164', '3', '5', '2026-04-20', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('165', '1', '1', '2026-04-21', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('166', '1', '2', '2026-04-21', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('167', '2', '3', '2026-04-21', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('168', '3', '5', '2026-04-21', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('169', '1', '1', '2026-04-22', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('170', '1', '2', '2026-04-22', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('171', '2', '4', '2026-04-22', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('172', '3', '5', '2026-04-22', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('173', '1', '2', '2026-04-23', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('174', '1', '4', '2026-04-23', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('175', '2', '3', '2026-04-23', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('176', '3', '5', '2026-04-23', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('177', '1', '1', '2026-04-24', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('178', '1', '2', '2026-04-24', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('179', '2', '3', '2026-04-24', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('180', '3', '4', '2026-04-24', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('181', '1', '1', '2026-04-25', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('182', '1', '2', '2026-04-25', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('183', '2', '3', '2026-04-25', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('184', '2', '4', '2026-04-25', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('185', '3', '5', '2026-04-25', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('186', '1', '2', '2026-04-26', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('187', '1', '1', '2026-04-27', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('188', '2', '3', '2026-04-27', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('189', '3', '5', '2026-04-27', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('190', '1', '1', '2026-04-28', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('191', '1', '2', '2026-04-28', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('192', '2', '3', '2026-04-28', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('193', '3', '5', '2026-04-28', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('194', '1', '1', '2026-04-29', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('195', '2', '2', '2026-04-29', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('196', '3', '5', '2026-04-29', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('197', '1', '2', '2026-04-30', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('198', '2', '3', '2026-04-30', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31'),
('199', '3', '5', '2026-04-30', NULL, NULL, '1', '2026-03-28 16:31:31', '2026-03-28 16:31:31');

DROP TABLE IF EXISTS `stores`;
CREATE TABLE `stores` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

INSERT INTO `stores` (`id`, `name`, `created_at`, `updated_at`) VALUES
('1', 'Vörösmarty', '2026-03-28 11:10:25', '2026-03-28 11:10:25'),
('2', 'Selmeci', '2026-03-28 11:10:25', '2026-03-28 11:10:25'),
('3', 'Üllői út', '2026-03-28 11:10:25', '2026-03-28 11:10:25');

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

INSERT INTO `suppliers` (`id`, `name`, `created_at`) VALUES
('1', 'TRITEX Kft.', '2026-03-28 16:36:51'),
('2', 'Hőkomplex Bt.', '2026-03-28 16:36:51'),
('3', 'DM', '2026-03-28 16:36:51');

DROP TABLE IF EXISTS `tab_permissions`;
CREATE TABLE `tab_permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `tab_slug` varchar(50) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_tab` (`user_id`,`tab_slug`),
  CONSTRAINT `tab_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

INSERT INTO `tab_permissions` (`id`, `user_id`, `tab_slug`, `can_view`, `can_edit`) VALUES
('1', '3', 'dashboard', '1', '1'),
('2', '3', 'konyveles', '1', '1'),
('3', '3', 'fizetes', '1', '1'),
('4', '3', 'ertekeles', '1', '1'),
('5', '3', 'szabadsag', '1', '1'),
('6', '3', 'beosztas', '1', '1'),
('7', '3', 'selejt', '1', '1'),
('8', '3', 'chat', '1', '1'),
('9', '3', 'kimutat', '1', '1'),
('10', '4', 'dashboard', '1', '1'),
('11', '4', 'konyveles', '1', '1'),
('12', '4', 'fizetes', '1', '1'),
('13', '4', 'ertekeles', '1', '1'),
('14', '4', 'szabadsag', '1', '1'),
('15', '4', 'beosztas', '1', '1'),
('16', '4', 'selejt', '1', '1'),
('17', '4', 'chat', '1', '1'),
('18', '4', 'kimutat', '1', '1'),
('19', '5', 'dashboard', '1', '1'),
('20', '5', 'konyveles', '1', '1'),
('21', '5', 'fizetes', '1', '1'),
('22', '5', 'ertekeles', '1', '1'),
('23', '5', 'szabadsag', '1', '1'),
('24', '5', 'beosztas', '1', '1'),
('25', '5', 'selejt', '1', '1'),
('26', '5', 'chat', '1', '1'),
('27', '5', 'kimutat', '1', '1'),
('34', '4', 'szamlak', '1', '1'),
('44', '5', 'szamlak', '1', '1'),
('54', '3', 'szamlak', '1', '1'),
('88', '6', 'dashboard', '1', '0'),
('89', '6', 'konyveles', '1', '0'),
('90', '6', 'fizetes', '1', '0'),
('91', '6', 'szamlak', '1', '0'),
('92', '6', 'kimutat', '1', '0'),
('93', '6', 'ertekeles', '1', '0'),
('94', '6', 'szabadsag', '1', '0'),
('95', '6', 'beosztas', '1', '0'),
('96', '6', 'selejt', '0', '0'),
('97', '6', 'chat', '0', '0'),
('131', '6', 'konyvelo_docs', '1', '1');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('tulajdonos','bolt','konyvelo') NOT NULL DEFAULT 'bolt',
  `store_id` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(64) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `store_id` (`store_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `store_id`, `is_active`, `remember_token`, `avatar`, `created_at`, `updated_at`) VALUES
('1', 'Ádám', 'adam@elitefashion.hu', '$2y$12$K3JCiK..BWFLenMPecjJm.CEN.Cj8KntuJJhZO5gFfHXbCUxy7pkW', 'tulajdonos', NULL, '1', '52dc677c2520dde71ab943752703254f800535696581b5c2f58ace45ee17007a', NULL, '2026-03-28 11:10:25', '2026-03-29 10:59:42'),
('2', 'Imi', 'imi@elitefashion.hu', '$2y$12$K3JCiK..BWFLenMPecjJm.CEN.Cj8KntuJJhZO5gFfHXbCUxy7pkW', 'tulajdonos', NULL, '1', NULL, NULL, '2026-03-28 11:10:25', '2026-03-28 11:10:25'),
('3', 'Vörösmarty bolt', 'vorosmarty@elitefashion.hu', '$2y$12$K3JCiK..BWFLenMPecjJm.CEN.Cj8KntuJJhZO5gFfHXbCUxy7pkW', 'bolt', '1', '1', NULL, NULL, '2026-03-28 11:10:25', '2026-03-28 11:10:25'),
('4', 'Selmeci bolt', 'selmeci@elitefashion.hu', '$2y$12$K3JCiK..BWFLenMPecjJm.CEN.Cj8KntuJJhZO5gFfHXbCUxy7pkW', 'bolt', '2', '1', NULL, NULL, '2026-03-28 11:10:25', '2026-03-28 11:10:25'),
('5', 'Üllői út bolt', 'ulloi@elitefashion.hu', '$2y$12$K3JCiK..BWFLenMPecjJm.CEN.Cj8KntuJJhZO5gFfHXbCUxy7pkW', 'bolt', '3', '1', NULL, NULL, '2026-03-28 11:10:25', '2026-03-28 11:10:25'),
('6', 'Könyvelő', 'konyvelo@elitefashion.hu', '$2y$12$qCVheGfkv1cMaLylnZGdEu0k.m/BADts.MzqtghTjv5TsImZSYWqW', 'konyvelo', NULL, '1', NULL, NULL, '2026-03-28 17:20:05', '2026-03-28 17:20:05');

DROP TABLE IF EXISTS `vacation_requests`;
CREATE TABLE `vacation_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `confirmed_no_overlap` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_dates` (`date_from`,`date_to`),
  KEY `idx_status` (`status`),
  CONSTRAINT `vacation_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vacation_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

INSERT INTO `vacation_requests` (`id`, `employee_id`, `date_from`, `date_to`, `confirmed_no_overlap`, `status`, `approved_by`, `created_at`, `updated_at`) VALUES
('1', '3', '2026-12-29', '2027-01-06', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('2', '4', '2026-12-30', '2026-12-30', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('3', '4', '2026-12-28', '2026-12-28', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('4', '1', '2026-12-23', '2026-12-23', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('5', '2', '2026-12-22', '2026-12-22', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('6', '5', '2026-12-19', '2026-12-19', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('7', '3', '2026-12-17', '2026-12-17', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('8', '5', '2026-12-12', '2026-12-12', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('9', '3', '2026-12-10', '2026-12-10', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('10', '3', '2026-12-03', '2026-12-03', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('11', '5', '2026-11-21', '2026-11-21', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('12', '5', '2026-11-14', '2026-11-14', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('13', '3', '2026-10-19', '2026-10-25', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('14', '5', '2026-09-15', '2026-09-27', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('15', '4', '2026-09-06', '2026-09-14', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('16', '1', '2026-08-24', '2026-09-05', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('17', '2', '2026-08-21', '2026-08-22', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('18', '5', '2026-08-22', '2026-08-22', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('19', '2', '2026-08-18', '2026-08-19', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('20', '4', '2026-08-08', '2026-08-17', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('21', '2', '2026-08-04', '2026-08-07', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('22', '3', '2026-07-19', '2026-08-02', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('23', '1', '2026-07-13', '2026-07-18', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('24', '2', '2026-07-07', '2026-07-11', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('25', '5', '2026-06-22', '2026-07-05', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('26', '1', '2026-06-15', '2026-06-20', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('27', '4', '2026-06-08', '2026-06-14', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('28', '2', '2026-06-02', '2026-06-06', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('29', '5', '2026-05-26', '2026-05-31', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('30', '1', '2026-05-22', '2026-05-23', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('31', '4', '2026-05-15', '2026-05-20', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('32', '2', '2026-05-05', '2026-05-14', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('33', '3', '2026-05-02', '2026-05-02', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('34', '5', '2026-05-02', '2026-05-02', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('35', '4', '2026-04-27', '2026-04-30', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('36', '1', '2026-04-17', '2026-04-18', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('37', '1', '2026-04-14', '2026-04-14', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('38', '3', '2026-04-04', '2026-04-04', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('39', '3', '2026-04-02', '2026-04-02', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('40', '5', '2026-04-02', '2026-04-02', '1', 'approved', '1', '2026-03-28 16:58:05', '2026-03-28 16:58:05'),
('41', '3', '2026-03-30', '2026-03-31', '1', 'approved', '1', '2026-03-28 17:23:51', '2026-03-28 17:23:51'),
('42', '5', '2026-03-19', '2026-03-19', '1', 'approved', '1', '2026-03-28 17:23:51', '2026-03-28 17:23:51'),
('43', '2', '2026-03-18', '2026-03-18', '1', 'approved', '1', '2026-03-28 17:23:51', '2026-03-28 17:23:51'),
('44', '5', '2026-03-14', '2026-03-14', '1', 'approved', '1', '2026-03-28 17:23:51', '2026-03-28 17:23:51'),
('45', '3', '2026-03-09', '2026-03-09', '1', 'approved', '1', '2026-03-28 17:23:51', '2026-03-28 17:23:51'),
('46', '5', '2026-03-05', '2026-03-05', '1', 'approved', '1', '2026-03-28 17:23:51', '2026-03-28 17:23:51'),
('47', '1', '2026-01-30', '2026-01-30', '1', 'approved', '1', '2026-03-28 17:23:51', '2026-03-28 17:23:51'),
('48', '5', '2026-01-29', '2026-01-29', '1', 'approved', '1', '2026-03-28 17:23:51', '2026-03-28 17:23:51'),
('49', '1', '2026-01-27', '2026-01-27', '1', 'approved', '1', '2026-03-28 17:23:51', '2026-03-28 17:23:51'),
('50', '1', '2026-01-23', '2026-01-23', '1', 'approved', '1', '2026-03-28 17:23:51', '2026-03-28 17:23:51'),
('51', '3', '2026-01-04', '2026-01-07', '1', 'approved', '1', '2026-03-28 17:23:51', '2026-03-28 17:23:51'),
('52', '3', '2026-01-03', '2026-01-03', '1', 'approved', '1', '2026-03-28 17:23:51', '2026-03-28 17:23:51');

SET FOREIGN_KEY_CHECKS = 1;
