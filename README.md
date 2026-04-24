# PowerDownload

**PowerDownload** ist ein PHP-basiertes Download-Management-System mit Ordnerstruktur, Benutzerverwaltung, Bewertungs- und Kommentarsystem. Das Projekt wurde 2001/2002 von **PowerScripts** veröffentlicht und 2025/2026 vollständig auf **PHP 8.4** sowie **MySQL 8** modernisiert. Alle Userbereichs-Funktionen (Registrierung, Login, Profil, Passwort-Reset, Kommentare) wurden überarbeitet, mit `password_hash`/`password_verify`, Session-Tokens, CSRF-Schutz und Rate-Limit ausgestattet.

- Projektseite: <https://www.powerscripts.org>
- Projektbereich: <https://www.powerscripts.org/projects-6.html>
- GitHub: <https://github.com/schubertnico/PowerDownload>

---

## Schnellstart (Docker)

Voraussetzung: Docker + Docker Compose installiert.

```bash
git clone https://github.com/schubertnico/PowerDownload.git
cd PowerDownload
docker compose -f .docker/docker-compose.yml up -d --build
```

**Anwendung öffnen:** <http://localhost:8092>
**phpMyAdmin:** <http://localhost:8094> (User `root`, Passwort `root`)

**Standard-Admin-Login:** `admin` / `admin123` (wird beim Login automatisch von MD5 auf bcrypt migriert).

Status prüfen, Logs verfolgen, stoppen:

```bash
docker compose -f .docker/docker-compose.yml ps
docker compose -f .docker/docker-compose.yml logs -f web
docker compose -f .docker/docker-compose.yml down
```

---

## Funktionsumfang

- Verwaltung von **Downloads** und **Releases** in einer hierarchischen Ordnerstruktur
- **Benutzerregistrierung** mit E-Mail-Validierung und CSRF-Schutz
- **Login** mit `password_verify` und transparenter MD5→bcrypt-Migration für Bestandsuser
- **Session-Token-basierte Authentifizierung** (kein Passwort-Hash mehr im Cookie)
- **Rate-Limit** für Login-Versuche (5 Fehlschläge / 15 min / IP)
- **Passwort-Vergessen-Flow** mit zeitlich begrenztem Reset-Link (60 min TTL)
- **Bewertungssystem** (1–10) mit IP-Lock gegen Mehrfachvotes
- **Kommentar-System** mit BBCode, Smilies, Glossar, Bad-Words-Filter
- **Screenshot-Upload** und -Anzeige je Release
- **Template-System** mit DB-basierten Vorlagen (Boxen, Forms, Mails)
- **Admin-Panel** mit Rechte-System, Usergruppen, Ordnern, Releases, Templates
- **Statistik-Widget** mit Top/Flop/Latest/Rated-Listen
- **Mehrsprachfähig** (aktuell Deutsch)

---

## Systemanforderungen

| Komponente   | Version                                   |
|--------------|-------------------------------------------|
| PHP          | 8.4 (mit `mysqli`, `gd`, `mbstring`)      |
| MySQL/MariaDB| MySQL 8.0 oder MariaDB 10.6+ (InnoDB)     |
| Webserver    | Apache 2.4+ mit `mod_rewrite` oder Nginx  |
| Browser      | Aktuelle Versionen von Chrome, Firefox, Safari, Edge |
| Composer     | 2.x (nur für Entwicklung)                 |

---

## Installation

### Variante A — Docker (empfohlen)

Siehe [Schnellstart](#schnellstart-docker). Die mitgelieferte Compose-Datei startet:

- `powerdownload_web` (Apache + PHP 8.4) auf Port **8092**
- `powerdownload_db` (MySQL 8.0) auf Port **3319**
- `powerdownload_phpmyadmin` (phpMyAdmin) auf Port **8094**

### Variante B — Manuelles Setup auf eigenem Webserver

1. Repository klonen oder ZIP entpacken und Dateien auf den Webserver hochladen.
2. MySQL-Datenbank anlegen (UTF-8 / `utf8mb4`, InnoDB).
3. Verbindungsdaten eintragen in `pdl-inc/pdl_config.inc.php`:
   ```php
   $config_sql_server   = 'localhost';
   $config_sql_user     = 'pdl_user';
   $config_sql_password = 'pdl_password';
   $config_sql_database = 'pdl3';
   ```
4. Setup-Skript im Browser aufrufen: `https://example.org/install_303.php`
5. Nach erfolgreicher Installation `install_303.php` und `update_*.php` **löschen**.
6. Schreibrechte für Screenshots/Smilies setzen:
   - `pdl-gfx/screens/` und `pdl-gfx/smilies/` → `chmod 0775`
7. Konfigurationen im Admin-Panel anpassen (Pfade, Mail-Absender, Settings).

### Update von älterer Version

- Aus 2.2.4 → 3.0.x: `update_224to303.php`
- Aus 3.0.1 → 3.0.3: `update_301to303.php`

Update-Skripte nach Lauf entfernen.

---

## Verzeichnisstruktur

```
PowerDownload/
├── .docker/                  Docker-Compose, Dockerfile, php.ini
├── docs/                     Audit-Berichte, Bug-/Improvement-Listen, Migrationen
├── pdl-admin/                Admin-Panel (alle admin/*.php-Dateien)
├── pdl-gfx/                  Grafiken, Smilies, Screenshots
├── pdl-inc/                  Kern-Module
│   ├── pdl_header.inc.php    Bootstrap, Session, Auth, Rate-Limit
│   ├── pdl_csrf.inc.php      CSRF-Helper
│   ├── pdl_db_class_*.inc.php Datenbank-Abstraktion
│   ├── pdl_functions.inc.php Hilfsfunktionen
│   ├── pdl_downloads.inc.php Routing der Module
│   ├── pdl_*.modul.php       User-Center-Module (login, register, profil, lost, comments, release, ordner, search, stats)
│   └── pdl_*.inc.php         Widgets (stats, top, flop, latest, rated)
├── tests/                    PHPUnit-Tests + Mocks
├── tools/                    Hilfsskripte
├── vendor/                   Composer-Abhängigkeiten
├── downloads.php             Haupt-Einstieg / Front Controller
├── index.php                 Redirect → downloads.php
├── install_303.php           Initiale Installation
├── update_*.php              Migrationen
├── composer.json             PHP-Abhängigkeiten + Scripts
├── phpunit.xml               Test-Konfiguration
├── phpstan.neon.dist         Static-Analysis-Konfiguration
└── README.md / .html / readme.txt
```

---

## Routing-Übersicht

Alle User-Funktionen werden vom Front-Controller `downloads.php` über GET-/POST-Parameter aufgelöst:

| Route                                              | Modul                       |
|----------------------------------------------------|-----------------------------|
| `/downloads.php`                                   | Wurzel-Ordner-Liste         |
| `/downloads.php?ordner_id=N`                       | Ordner-Inhalt               |
| `/downloads.php?release_id=N`                      | Release-Detail              |
| `/downloads.php?screen_id=N`                       | Screenshot-Anzeige          |
| `/downloads.php?show_search=1`                     | Suche                       |
| `/downloads.php?show_stats=1`                      | Server- & DB-Statistik      |
| `/downloads.php?usercenter=login`                  | Login-Formular              |
| `/downloads.php?usercenter=register`               | Registrierung               |
| `/downloads.php?usercenter=profil`                 | Profil bearbeiten           |
| `/downloads.php?usercenter=lost`                   | Passwort vergessen          |
| `/downloads.php?usercenter=lost2&remind_code=…`    | Passwort neu setzen         |
| `/downloads.php?usercenter=comments&release_id=N`  | Kommentar zu Release        |
| `/downloads.php?logout=1`                          | Logout                      |
| `/pdl-admin/`                                      | Admin-Panel (rechtegeschützt) |

Alle Form-Submits gehen an `downloads.php` mit den Steuerparametern als Hidden-Inputs (kein Query-String im Action-Attribut).

---

## Sicherheit

Implementiert in der aktuellen Version:

- **`password_hash` / `password_verify`** mit `PASSWORD_DEFAULT` (bcrypt).
- **Transparente Migration** alter MD5-Hashes nach erfolgreichem Login.
- **Session-Token im Cookie** (statt Passwort-Hash); Token wird in `pdl3_user.session_token` gespeichert.
- **`session_regenerate_id`** nach Login.
- **Cookie-Flags:** `HttpOnly`, `SameSite=Lax`, `Secure` (automatisch bei HTTPS).
- **CSRF-Schutz** auf allen POST-Endpunkten via `csrf_token()` / `csrf_verify()`.
- **Rate-Limit** für Login: 5 Fehlversuche pro IP / 15 min, persistiert in `pdl3_iplock`.
- **User-Enumeration verhindert:** „Wenn ein Konto existiert, wurde eine E-Mail versendet." (statt „User nicht gefunden").
- **Generische Fehlermeldungen** beim Login.
- **Form-Rerender** mit Fehlerliste statt `javascript:history.back()`.
- **Server-Validierung** für E-Mail (`FILTER_VALIDATE_EMAIL`) und Homepage (`FILTER_VALIDATE_URL`, Whitelist `http`/`https`).
- **Path-Traversal-Schutz** bei Modul-Includes (Whitelist statt direkte File-Inclusion).
- **`utf8mb4`** als Datenbank-Charset.

---

## Entwicklung

### Komponenten installieren

```bash
docker exec powerdownload_web composer install
```

### Test-Suite

```bash
docker exec powerdownload_web composer phpunit
```

### Static Analysis

```bash
docker exec powerdownload_web composer phpstan
docker exec powerdownload_web composer psalm
docker exec powerdownload_web composer phpmd
```

### Code-Style

```bash
docker exec powerdownload_web composer cs        # Prüfen (Dry-Run)
docker exec powerdownload_web composer cs:fix    # Automatisch fixen
```

### Rector (Refactoring zu modernem PHP)

```bash
docker exec powerdownload_web composer rector       # Dry-Run
docker exec powerdownload_web composer rector:fix   # Anwenden
```

### Quality-Bundle (alle Static-Analyse-Tools)

```bash
docker exec powerdownload_web composer quality
```

### Tests + PHPStan zusammen

```bash
docker exec powerdownload_web composer test
```

---

## Konfiguration im Admin-Panel

- **Settings**: über `$settings['<name>']` im Code abrufbar; Verwaltung im Admin-Panel.
- **Templates**: über `$template['<name>']` im Code abrufbar; HTML-Schnipsel mit `{platzhalter}`.
- **Rechte**: über `$user_rights['<name>']` abrufbar; pro Benutzergruppe konfigurierbar.
- **User**: über `$user_details[...]`; ist die Variable leer, ist niemand eingeloggt.

Wichtige Settings:

| Setting              | Bedeutung                                       |
|----------------------|-------------------------------------------------|
| `script_file`        | URL-Präfix der Hauptseite                       |
| `enable_comments`    | Y/N – Kommentare global aktivieren              |
| `bb_code`            | Y/N – BBCode in Kommentaren erlauben            |
| `smilies`            | Y/N – Smilies in Kommentaren                    |
| `mail_fromname`      | Absender-Name für System-Mails                  |
| `mail_fromaddr`      | Absender-Adresse für System-Mails               |
| `dlspeed`            | Geschätzte Download-Geschwindigkeit (kB/s) für Anzeige |
| `perpage`            | Standard-Anzahl Releases pro Seite              |
| `orderby` / `orderseq` | Standard-Sortierung in Ordnerlisten           |

---

## Datenbank-Schema (wichtigste Tabellen)

| Tabelle           | Zweck                                                       |
|-------------------|-------------------------------------------------------------|
| `pdl3_user`       | Benutzer (inkl. `passwort`, `session_token`, `remind_code`, `remind_expires`) |
| `pdl3_usergroup`  | Benutzergruppen + Rechte (`addcomments`, `vote`, `download` …) |
| `pdl3_ordner`     | Ordner-Hierarchie                                           |
| `pdl3_release`    | Releases (Downloads)                                        |
| `pdl3_files`      | Einzelne Dateien je Release                                 |
| `pdl3_screens`    | Screenshots je Release                                      |
| `pdl3_comments`   | Benutzer-Kommentare                                         |
| `pdl3_iplock`     | IP-Lock für Voting + Login-Rate-Limit                       |
| `pdl3_settings`   | Konfigurations-Werte                                        |
| `pdl3_template`   | HTML-Templates                                              |
| `pdl3_rights`     | Rechte-Definitionen                                         |

---

## Ports (Docker)

| Service     | Port  | Beschreibung                  |
|-------------|-------|-------------------------------|
| Web         | 8092  | Apache + PHP                  |
| MySQL       | 3319  | Datenbank-Hostport            |
| phpMyAdmin  | 8094  | Datenbank-Verwaltung          |

---

## Troubleshooting

- **„Database not initialized"** → `install_303.php` aufrufen oder Compose-Stack mit `--force-recreate` neu starten.
- **Login schlägt mit Bestandsuser fehl** → Passwort über „Passwort vergessen" zurücksetzen; alte MD5-Hashes werden beim ersten erfolgreichen Login automatisch migriert.
- **„Zu viele Fehlversuche"** → Rate-Limit erreicht; in MySQL `DELETE FROM pdl3_iplock WHERE ip='<IP>' AND art='login';` oder 15 Minuten warten.
- **Mailversand schlägt fehl** → Im Container ist `sendmail` nicht installiert; in Produktion einen MTA bereitstellen oder `mail()` durch externen Mailer (z. B. PHPMailer/Symfony Mailer) ersetzen. Fehler werden mit `error_log()` protokolliert.
- **Leere Info-Boxen auf Startseite** → DB-Templates wurden 2026-04 ergänzt; bei alten Installationen `docs/superpowers/plans/2026-04-23-seed-templates.sql` einspielen.
- **Schema-Inkonsistenz nach Update** → Spalten `session_token`, `remind_expires`, `signatur (NULL)` in `pdl3_user` und `addcomments` in `pdl3_usergroup` prüfen, ggf. per `ALTER TABLE` ergänzen.

---

## Audit & Dokumentation

Im Verzeichnis `docs/` finden sich:

- `2026-04-23-Userbereichs-bugs.md` – vollständige Bug-Liste des Userbereichs (mit Status pro Eintrag)
- `2026-04-23-Userbereichs-improvements.md` – Verbesserungsvorschläge
- `2026-04-23-Userbereichs-test-coverage.md` – Testabdeckung & Routenmatrix
- `2026-04-23-Userbereichs-abschlussbericht.md` – Management-Zusammenfassung
- `superpowers/plans/2026-04-23-userbereich-bugfixes.md` – Implementierungsplan der Fixes
- `superpowers/plans/2026-04-23-seed-templates.sql` – idempotente Migration für fehlende Templates

---

## Lizenz

Dieses Projekt steht unter der **MIT-Lizenz** – siehe [LICENSE](LICENSE).

```
MIT License

Copyright (c) 2001-2002 PowerScripts
Copyright (c) 2025-2026 Nico Schubert
```

---

## Credits

- **Original-Entwicklung**: PowerScripts (2001–2002)
- **PHP 8.4 Migration & Modernisierung**: Nico Schubert (2025–2026)

---

## Kontakt

**SchubertMedia**
Inhaber: Nico Schubert
Stauffenbergallee 57
99085 Erfurt
Deutschland

- **Telefon:** +49 (0) 3612 3002247 (Mo.–Fr. 9–12 und 13–18 Uhr)
- **Telefax:** +49 (0) 3612 3004636
- **E-Mail:** <info@schubertmedia.de>
- **Web:** <https://www.powerscripts.org>
