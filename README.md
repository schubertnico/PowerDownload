# PowerDownload

**PowerDownload** ist ein PHP-basiertes Download-Management-System, ursprünglich entwickelt von PowerScripts (2001-2002). Diese Version wurde vollständig auf **PHP 8.4** migriert und enthält wichtige Sicherheitsverbesserungen.

## Repository

- **Git-Repository**: [https://github.com/schubertnico/PowerDownload](https://github.com/schubertnico/PowerDownload)

## Funktionen

- Verwaltung von Downloads mit Ordnerstruktur
- Benutzerregistrierung und Login-System
- Bewertungssystem für Downloads
- Screenshot-Upload und -Anzeige
- BBCode-Unterstützung in Kommentaren
- Template-System für individuelle Anpassungen
- Admin-Panel zur Verwaltung

## Systemanforderungen

- PHP 8.4 oder höher
- MySQL 8.0 oder höher
- Apache mit mod_rewrite (oder Nginx)
- GD-Library für Screenshot-Verarbeitung

## Setup

### Mit Docker (empfohlen)

1. **Repository klonen**:
   ```bash
   git clone https://github.com/schubertnico/PowerDownload.git
   cd PowerDownload
   ```

2. **Docker-Container bauen und starten**:
   ```bash
   docker compose -f .docker/docker-compose.yml build
   docker compose -f .docker/docker-compose.yml up -d
   ```

3. **Status prüfen**:
   ```bash
   docker compose -f .docker/docker-compose.yml ps
   ```

4. **Anwendung aufrufen**:
   - Webseite: [http://localhost:8092](http://localhost:8092)
   - phpMyAdmin: [http://localhost:8094](http://localhost:8094)

### Manuelles Setup

1. Dateien auf den Webserver kopieren
2. Datenbank erstellen und `install_303.php` ausführen
3. Konfiguration in `pdl-inc/pdl_config.inc.php` anpassen

## Start

Nach dem Docker-Start:

```bash
# Container-Status prüfen
docker compose -f .docker/docker-compose.yml ps

# Logs anzeigen
docker compose -f .docker/docker-compose.yml logs -f web

# PHP-Fehlerlog prüfen
cat logs/php-error.log
```

## Nutzung

1. **Admin-Panel**: Navigiere zu `/pdl-admin/` und melde dich an
2. **Downloads verwalten**: Ordner und Releases im Admin-Panel erstellen
3. **Benutzer verwalten**: Benutzergruppen und Rechte konfigurieren

## Ports

| Service     | Port  | Beschreibung          |
|-------------|-------|----------------------|
| Web         | 8092  | Apache Webserver     |
| MySQL       | 3318  | Datenbank            |
| phpMyAdmin  | 8094  | Datenbank-Verwaltung |

## Prüfen, ob es läuft

```bash
# Container-Status
docker compose -f .docker/docker-compose.yml ps

# Sollte "Up" für alle Services zeigen:
# - powerdownload_web
# - powerdownload_db
# - powerdownload_phpmyadmin

# Health-Check
curl -I http://localhost:8092

# PHP-Version prüfen
docker exec powerdownload_web php -v

# PHP-Fehlerlog
docker exec powerdownload_web cat /var/www/html/logs/php-error.log
```

## Entwicklung

### Composer-Abhängigkeiten installieren

```bash
docker exec powerdownload_web composer install
```

### PHPStan ausführen

```bash
docker exec powerdownload_web composer run phpstan
```

### Rector ausführen (Dry-Run)

```bash
docker exec powerdownload_web composer run rector
```

### Rector ausführen (Änderungen anwenden)

```bash
docker exec powerdownload_web composer run rector:fix
```

## Änderungen / Migration auf PHP 8.4

### PHP 8.4 Kompatibilitätsänderungen

- **Short Open Tags**: `<?` durch `<?php` ersetzt
- **`var` Keyword**: Durch `public` mit Typ-Deklarationen ersetzt
- **mysqli-Funktionen**: Parameter-Reihenfolge korrigiert
  - `mysqli_pconnect()` entfernt (existiert nicht in PHP 8+)
  - `mysqli_select_db($db, $handler)` → `mysqli_select_db($handler, $db)`
  - `mysqli_query($query, $handler)` → `mysqli_query($handler, $query)`
  - `mysqli_escape_string($str)` → `mysqli_real_escape_string($handler, $str)`
- **`$HTTP_*_VARS`**: Entfernt (existieren nicht mehr)
- **`register_globals`**: Workaround entfernt
- **`srand()`**: Entfernt (veraltet)
- **`rand()`**: Durch `random_int()` ersetzt
- **`strstr()`**: Teilweise durch `str_contains()` ersetzt
- **Variablen-Initialisierung**: Alle Variablen werden vor Verwendung initialisiert
- **Typ-Deklarationen**: `declare(strict_types=1)` und Typ-Hints hinzugefügt

### Sicherheitsverbesserungen

- **SQL Injection**: Input-Escaping mit `sql_escape_string()` und `sql_escape_int()`
- **XSS-Schutz**: `htmlspecialchars()` für alle Ausgaben
- **Path Traversal**: Whitelist für Include-Module implementiert
- **Sichere Cookies**: `HttpOnly`, `Secure` und `SameSite=Strict` Flags
- **`extract()` entfernt**: Gefährliche Superglobal-Extraktion entfernt
- **UTF-8**: Zeichensatz auf `utf8mb4` gesetzt

### Strukturelle Änderungen

- MIT-Lizenz hinzugefügt
- Lizenz-Header in allen PHP-Dateien
- Docker-Konfiguration für PHP 8.4
- Composer-Setup mit PHPStan und Rector
- Moderne Coding-Standards

### Geänderte Dateien

- `downloads.php` - Short Open Tags korrigiert, HTML modernisiert
- `pdl-inc/pdl_config.inc.php` - Typ-Deklarationen, Array-Syntax
- `pdl-inc/pdl_db_class_mysql.inc.php` - Komplett überarbeitet
- `pdl-inc/pdl_header.inc.php` - Komplett überarbeitet
- `pdl-inc/pdl_functions.inc.php` - Komplett überarbeitet
- `pdl-inc/pdl_downloads.inc.php` - Sicherheitsfixes, Path Traversal
- `pdl-admin/header.inc.php` - XSS-Schutz, HTML5
- `pdl-admin/functions.inc.php` - Typ-Deklarationen, str_contains()

### Neue Dateien

- `.docker/Dockerfile` - PHP 8.4 Docker-Image
- `.docker/docker-compose.yml` - Docker-Compose Konfiguration
- `.docker/php.ini` - PHP-Konfiguration
- `composer.json` - Composer-Setup
- `phpstan.neon.dist` - PHPStan-Konfiguration
- `rector.php` - Rector-Konfiguration
- `LICENSE` - MIT-Lizenz
- `README.md` - Diese Dokumentation
- `README.html` - HTML-Version der Dokumentation
- `logs/.gitkeep` - Log-Verzeichnis

## Lizenz

Dieses Projekt steht unter der **MIT-Lizenz**. Siehe [LICENSE](LICENSE) für Details.

```
MIT License

Copyright (c) 2001-2002 PowerScripts
Copyright (c) 2025 Nico Schubert

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
```

## Credits

- **Original**: PowerScripts (2001-2002)
- **PHP 8.4 Migration**: Nico Schubert (2025)
