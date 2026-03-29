# Elite Fashion — Telepítési és frissítési útmutató

## Első telepítés

### 1. Fájlok feltöltése
Töltsd fel az EGÉSZ projekt mappát a szerverre FTP-vel:
```
public_html/
└── ugyvitel/          ← ide az egész projekt
    ├── app/
    ├── config/
    ├── database/
    ├── public/        ← ez a web gyökér (ide kell mutatnia a domainnek)
    ├── storage/
    ├── vendor/
    ├── .htaccess
    └── composer.json
```

### 2. .env fájl létrehozása
A szerveren a projekt gyökerében hozz létre egy `.env` fájlt (File Manager-ben):
```
APP_URL=https://ugyvitel.elitedivat.hu
APP_DEBUG=false

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=az_adatbazis_neve
DB_USERNAME=az_adatbazis_user
DB_PASSWORD=az_adatbazis_jelszo
```

### 3. Adatbázis importálás
phpMyAdmin → válaszd ki az adatbázist → Importálás → `database/schema.sql`

### 4. Mappák jogosultságai
A File Manager-ben állítsd 755-re (ha kell):
- `storage/` → 755
- `public/uploads/` → 755

---

## Frissítés (ha fejlesztés után új verziót töltesz fel)

### Fájlok frissítése
1. GitHub-ról töltsd le a legújabb ZIP-et (Code → Download ZIP)
2. FTP-vel töltsd fel a módosult fájlokat — felülírhatod az összeset
3. **NE töröld/írd felül**: `.env`, `public/uploads/`, `storage/backups/`

### Adatbázis frissítés (ha kell)
Nézd meg a `database/migrations/` mappát — ha van új SQL fájl:
1. phpMyAdmin → válaszd ki az adatbázist
2. SQL fül → másold be a migráció tartalmát → Indítás
3. A migrációk NEM törölnek adatot, csak hozzáadnak (ALTER TABLE, CREATE TABLE IF NOT EXISTS)

### Mi NEM változik frissítéskor?
- `.env` — a te éles beállításaid, sosem írjuk felül
- `public/uploads/` — feltöltött képek, nem része a kódnak
- `storage/backups/` — mentések
- **Az adatbázis adatai** — a kód frissítés nem érinti
