-- Banki jutalék típus hozzáadása + commission oszlop kártyás tranzakciókhoz
-- Ha a commission oszlop már létezik, csak a MODIFY-t futtasd
ALTER TABLE bank_transactions
    ADD COLUMN commission DECIMAL(12,2) DEFAULT NULL AFTER amount;

ALTER TABLE bank_transactions
    MODIFY COLUMN type ENUM('kartya_beerkezes','szolgaltato_levon','hitel_torlesztes','szamla_kozti','banki_jutalek') NOT NULL;
