# PowerDownload

[![Version](https://img.shields.io/badge/version-3.5.0-9b0000.svg?style=flat-square)](CHANGELOG.md)
[![PHP](https://img.shields.io/badge/PHP-8.4-8892BF.svg?style=flat-square)](https://www.php.net/)
[![tests](https://img.shields.io/badge/tests-passing-success.svg?style=flat-square)](tests/)
[![license](https://img.shields.io/badge/license-MIT-yellow.svg?style=flat-square)](LICENSE)

**PowerDownload** ist ein PHP-basiertes Download-Management-System mit Ordnerstruktur, Benutzerverwaltung, Bewertungs- und Kommentarsystem. Das Projekt wurde 2001/2002 von **PowerScripts** veröffentlicht und 2025/2026 vollständig auf **PHP 8.4** sowie **MySQL 8** modernisiert. Alle Userbereichs-Funktionen (Registrierung, Login, Profil, Passwort-Reset, Kommentare) wurden überarbeitet, mit `password_hash`/`password_verify`, Session-Tokens, CSRF-Schutz und Rate-Limit ausgestattet. Die gesamte HTML-Ausgabe (öffentlicher Bereich + Administrationsbereich + Setup/Update-Skripte) nutzt **Bootstrap 5.3** mit dunklem Theme, hohem WCAG-AAA-Kontrast und responsivem Layout.

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
- **Bootstrap-5-Frontend** mit dunklem PowerDownload-Theme, responsiver Navbar/Sidebar, Cards, responsive Tabellen, Bootstrap-Alerts und Breadcrumbs
- **Barrierefreiheit**: semantisches HTML5 (`<nav>`, `<main>`, `<header>`, `<section>`), `aria-describedby` für Hilfetexte, `visually-hidden-focusable` Skip-Link, hohe Kontraste (WCAG AA / AAA für Body-Text), Bedeutung wird nicht nur über Farbe vermittelt
- **Mehrsprachfähig** (aktuell Deutsch)

### Neu in 3.5.0

- **Self-Service-Konto-Löschung** (DSGVO Art. 17) inkl. Passwort-Verifikation, Doppel-Bestätigung und Audit-Log-Eintrag.
- **Math-CAPTCHA** (settings-gated, Default OFF) als Defense-in-Depth zusätzlich zu Honeypot/Time-Trap/Rate-Limit.
- **IP-Rate-Limit** für Register (5/IP/h) und Lost-Password (3/IP/h).
- **Honeypot** + Time-Trap (sub-3-Sekunden-Submit) in Register- und Lost-Password-Form.
- **Admin-Kontext-Header** für `?usercenter=profil&from=admin` (schlanke Navbar mit „← Admin-Center"-Link).
- **Dynamische Page-Titles** je Subseite (Login/Registrieren/Profil/Statistik/Suche).
- **Subpage-Bleed gefixt**: Dashboard-Widgets nur auf der Startseite.
- **Self-Service-Registrierung-Bug behoben**: Neue User landen jetzt in Gruppe „Gast" statt „Administrator".
- **Alle Admin-System-Seiten erreichbar**: `settings.php`, `backup.php`, `makeletter.php` etc. nutzen jetzt die existierenden Rechte (`settings`/`backup`/`templates`/`adminaccess`).
- **18 Rechte-Bezeichnungen** in sauberem Deutsch (statt Denglisch „Files Adden" → „Dateien hinzufügen").
- **Sidebar-Scroll-Position** bleibt beim Navigieren erhalten (`sessionStorage`).
- **Schema-Migration**: `pdl3_settings` um `name`/`bez`/`eingabe`/`reihenfolge` erweitert, `pdl3_iplock.art`-ENUM um `register`/`lostpw`.
- **Single Source of Truth** für DB-Initialisierung: `.docker/initdb/01-pdl3-init.sql` wird von Docker, `setup.php` und `install_303.php` gleichermaßen verwendet.

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
4. Setup-Skript im Browser aufrufen: `https://example.org/setup.php` (empfohlen) oder `install_303.php` (klassischer Wizard).
5. Nach erfolgreicher Installation `setup.php`, `install_303.php` und `update_*.php` **löschen**.
6. Schreibrechte für Screenshots/Smilies setzen:
   - `pdl-gfx/screens/` und `pdl-gfx/smilies/` → `chmod 0775`
7. Konfigurationen im Admin-Panel anpassen (Pfade, Mail-Absender, Settings).

> **Single Source of Truth:** Alle drei Installations-Wege (Docker-Init, `setup.php`, `install_303.php`) spielen das gleiche SQL aus `.docker/initdb/01-pdl3-init.sql` ein. Wer das Schema oder die Default-Daten ändern will, ändert nur diese eine Datei.

### Update von älterer Version

- Aus 2.2.4 → 3.0.x: `update_224to303.php`
- Aus 3.0.1 → 3.0.3: `update_301to303.php`
- Aus 3.0.3 → 3.5.0: keine separate Update-Datei – `setup.php` (oder Docker-Volume-Reset) spielt das erweiterte Schema (pdl3_settings mit `name/bez/eingabe/reihenfolge`, pdl3_settingsgroup mit `reihenfolge`, pdl3_iplock-ENUM mit `register`/`lostpw`) und alle aktualisierten Default-Daten ein.

Update-Skripte nach Lauf entfernen.

---

## Verzeichnisstruktur

```
PowerDownload/
├── .docker/                  Docker-Compose, Dockerfile, php.ini
├── docs/                     Audit-Berichte, Bug-/Improvement-Listen, Migrationen
├── pdl-admin/                Admin-Panel (alle admin/*.php-Dateien)
│   ├── header.inc.php        Bootstrap-5 Admin-Navbar + Sidebar
│   ├── footer.inc.php        Admin-Footer + Bootstrap JS
│   ├── functions.inc.php     Helper: menu_topic/_link/_close, makedialog, pdl_admin_breadcrumb, pdl_admin_alert
│   └── admin.css             Bootstrap-5 Override-Theme (rotes Admin-Theme, hohe Kontraste)
├── pdl-gfx/                  Grafiken, Smilies, Screenshots
│   └── pdl-public.css        Bootstrap-5 Override-Theme für öffentlichen Bereich
├── pdl-inc/                  Kern-Module
│   ├── pdl_header.inc.php    Bootstrap, Session, Auth, Rate-Limit
│   ├── pdl_csrf.inc.php      CSRF-Helper
│   ├── pdl_layout.inc.php    Bootstrap-5 Layout-Helper (pdl_layout_start/_end, pdl_alert, pdl_card_start/_end)
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

## Frontend-Theme (Bootstrap 5)

Die gesamte HTML-Ausgabe wurde 2026-05 von der ursprünglichen `<table border="0" bgcolor="#…">`-Struktur auf **Bootstrap 5.3.3** umgestellt. Bootstrap wird via CDN geladen; zwei eigene Theme-Stylesheets überschreiben Bootstrap-Defaults so, dass der dunkle PowerDownload-Look mit hohen Kontrasten erhalten bleibt.

**Layout-Architektur:**

| Bereich            | Layout-Helper                              | Theme-CSS                |
|--------------------|--------------------------------------------|--------------------------|
| Öffentlich         | `pdl-inc/pdl_layout.inc.php`               | `pdl-gfx/pdl-public.css` |
| Administration     | `pdl-admin/header.inc.php` + `footer.inc.php` | `pdl-admin/admin.css`    |
| Setup / Install    | inline mit gemeinsamem `admin.css`         | `pdl-admin/admin.css`    |

**Wichtige Helper-Funktionen:**

- `pdl_layout_start(string $title, array $settings, array $userRights, ?array $userDetails)` – rendert Doctype, Head, Bootstrap-Navbar.
- `pdl_layout_end(array $settings, float $rendertime, int $querycount)` – schließt Layout, fügt Bootstrap-Bundle ein.
- `pdl_alert(string $type, string $message)` – rendert Bootstrap-Alert (`success`/`danger`/`warning`/`info`/`primary`/`secondary`).
- `pdl_card_start(string $title, string $extraClasses = '')` / `pdl_card_end()` – konsistente Card-Wrapper.
- `pdl_admin_breadcrumb(array $items)` – Bootstrap-Breadcrumb für Admin-Seiten.
- `pdl_admin_alert(string $type, string $message)` – Admin-Alert-Variante.
- `makedialog(string $titel, string $text, string $button, string $action)` – rendert Bestätigungs-Card mit `pdl-danger-action`-Markierung für Lösch-/Reset-Aktionen.

**Theme-Eigenschaften:**

- Dunkle Surfaces (`#0f0f10` / `#14070a`), helle Texte (`#f1f1f1` / `#f5f5f5`) – WCAG AAA für Body-Text.
- Akzent-Rot `#9b0000` / `#c62828` für Navbar, Card-Header, primäre Buttons.
- Alle Bootstrap-Variablen `--bs-*` (Body-Background, Link-Color, Border-Color, Code-Color, Heading-Color) werden für das dunkle Theme überschrieben.
- Standardfarben für `text-primary`, `text-muted`, `text-body`, `link-primary`, `<code>`, `<strong>`, `<em>`, `<dt>` etc. werden explizit auf hohe Kontraste gesetzt – kein blauer Text auf schwarzem Hintergrund.
- Alle Alert-Varianten (`info`/`warning`/`success`/`danger`) haben dunkle BG mit sehr hellem Text (statt Bootstrap-Defaults mit hellen Pastelltönen).
- Native HTML-Inputs/Submits (`<input type="text">`, `<input type="submit">` aus DB-Templates) werden zusätzlich gestyled, damit sie im dunklen Theme bleiben.
- `pdl-danger-action`-Klasse: roter Border + roter Header für gefährliche Aktionen, ergänzend zu Klartext-Label (Bedeutung nicht nur über Farbe).
- `:focus-visible`-Outline mit Akzent-Farbe für Tastaturbedienung.
- Skip-Link via `visually-hidden-focusable` für Screenreader.
- Responsive Bootstrap-Grid (`col-12 col-lg-6`) sowie eigenes `pdl-info-grid` (CSS-Grid) für Widget-Boxen auf der Startseite.

**DB-Templates:**

Die in `pdl3_template` gespeicherten Legacy-Templates (Boxen für Stats, Top/Flop, Login-Form, Register-Form etc.) werden in Bootstrap-Cards eingewickelt, damit sie ohne riskante DB-Migration ins neue Theme passen. CSS-Overrides für `table[bgcolor]` / `font[color]` neutralisieren die alten Inline-Attribute.

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

## Changelog (Auswahl)

- **2026-05** – Bootstrap-5-Migration der gesamten HTML-Ausgabe (öffentlich + Admin + Setup/Update). Neue Layout-Helper (`pdl_layout_start/_end`, `pdl_alert`, `pdl_admin_breadcrumb`, `pdl_admin_alert`). Eigene Theme-Stylesheets (`pdl-gfx/pdl-public.css`, `pdl-admin/admin.css`) mit dunklem Theme, WCAG-AAA-Kontrasten und responsivem Layout. Sidebar-Helper (`menu_topic/_link/_close`) und `makedialog` auf Bootstrap-Karten umgestellt. PHPStan Level 8 weiterhin 0 Errors.
- **2026-04** – Userbereichs-Bugfixes (28 dokumentierte Bugs), Schema-Erweiterung (`session_token`, `remind_expires`), Templates-Seed.
- **2026-03** – PHP-8.4-Migration, MySQL-8-Support, Composer-Setup, PHPUnit-Suite.

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
