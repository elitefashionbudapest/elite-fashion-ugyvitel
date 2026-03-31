-- Adó kifizetés típus hozzáadása
ALTER TABLE bank_transactions
    MODIFY COLUMN type ENUM('kartya_beerkezes','szolgaltato_levon','hitel_torlesztes','szamla_kozti','banki_jutalek','tulajdonosi_fizetes','tagi_kolcson_be','tagi_kolcson_ki','ado_kifizetes') NOT NULL;
