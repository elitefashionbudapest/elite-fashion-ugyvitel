-- Fizetések összekapcsolása banki tranzakciókkal + új tranzakció típusok
ALTER TABLE bank_transactions
    MODIFY COLUMN type ENUM('kartya_beerkezes','szolgaltato_levon','hitel_torlesztes','szamla_kozti','banki_jutalek','tulajdonosi_fizetes','tagi_kolcson') NOT NULL;

ALTER TABLE owner_payments ADD COLUMN bank_transaction_id INT UNSIGNED DEFAULT NULL;
ALTER TABLE salary_payments ADD COLUMN bank_transaction_id INT UNSIGNED DEFAULT NULL;
