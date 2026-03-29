# Adatbázis migrációk

## Hogyan működik?

1. Az első telepítéskor a `schema.sql` fájlt kell importálni — ez létrehozza az összes táblát és seed adatokat.
2. Utána **SOHA NE importáld újra** a `schema.sql`-t, mert az törli az adatokat!
3. Ha fejlesztés közben új mező/tábla kell, ide kerül egy új SQL fájl (pl. `002_uj_feature.sql`).
4. A migráció fájlt egyszer kell lefuttatni az éles adatbázison (phpMyAdmin-ban vagy CLI-ben).

## Futtatás sorrendje

Mindig számsorrendben, csak azokat amik még nem futottak:

```
001_initial_deploy.sql  — NEM kell futtatni, csak dokumentáció
002_*.sql               — Ha van, futtatni kell
003_*.sql               — stb.
```

## Fontos szabályok

- Minden migráció `ALTER TABLE` vagy `CREATE TABLE IF NOT EXISTS` legyen
- Soha ne legyen benne `DROP TABLE`
- A fájlnév legyen beszédes: `002_bank_transactions.sql`
