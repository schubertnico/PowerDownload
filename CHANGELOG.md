# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden in dieser Datei dokumentiert. Format: [Keep a Changelog](https://keepachangelog.com/de/1.1.0/) / [SemVer](https://semver.org/lang/de/).

---

## [3.5.0] – 2026-05-11

Großes UX-, Sicherheits- und Installations-Refactoring. Vier Welle paralleler Fix-Agenten + Re-Audit-Runden gegen den laufenden Container; >40 dokumentierte Befunde aus `todos/2026-05-11-ux-schwachstellen-report.md` behoben.

### Sicherheit

- **[BREAKING-SECURITY-FIX] Self-Service-Registrierung promoviert nicht mehr zum Admin.** `pdl_uregister.modul.php:82` schrieb hart `ugroup_id='2'` (Administrator-Gruppe) in die DB. Korrigiert auf `'1'` (Gast). Bestehende, fälschlich zu Admin promovierte Self-Service-Accounts manuell zu „Gast" verschieben.
- **`user_rights['god']`-Lücke behoben.** 13 Admin-Seiten (Settings, Backup, Reset, Optimize, Newsletter, Template-Editor, Rechte-Editor u. a.) prüften ein nicht im Schema definiertes Recht und waren dadurch für niemanden erreichbar. Mappings auf die existierenden Rechte (`settings`, `backup`, `templates`, `adminaccess`).
- **IP-Rate-Limit** für Register (5/IP/h) und Lost-Password (3/IP/h) auf Basis von `pdl3_iplock`. ENUM `art` um `register`/`lostpw` erweitert. Bei Überschreitung: identische Erfolgsmeldung wie regulärer Pfad + `error_log()`, kein DB-Insert/Mail.
- **Honeypot + Time-Trap** in Register- und Lost-Password-Form (Feld `pdl_website` als `visually-hidden`-Falle; `pdl_ts`-Timestamp für Sub-3-Sekunden-Submits).
- **Math-CAPTCHA** als optionaler Defense-in-Depth-Layer (Setting `captcha_enabled`, Default `N`). Render in `pdl-inc/pdl_captcha.inc.php` mit One-Shot-Session-Lösung.
- **Audit-Log-Eintrag** in `pdl3_admin_log` vor jeder Self-Service-Konto-Löschung (action=`self_delete`, target_type=`user`, IP, Timestamp).
- **Empty-Login-Redirect entfernt.** Leere Login-Felder zeigten keine Fehlermeldung, sondern leiteten zur Startseite. Jetzt: explizite Warnung „Bitte Benutzername und Passwort eingeben."
- **Server-Info nur für Admins.** `?show_stats=1` zeigte anonymen Nutzern DB-Version, MySQL-Version, Apache-Version. Jetzt: Block nur für `adminaccess=Y`, sonst generischer Hinweis.

### Neue Features

- **Self-Service-Konto-Löschung** (DSGVO Art. 17) in `?usercenter=profil`: separate Form mit Passwort-Bestätigung + Bestätigungs-Checkbox; Hauptadmin (user_id=1) ist geschützt; nach Erfolg Session-Cleanup + Flash-Meldung `?account_deleted=1`.
- **Admin-Kontext-Header** für `?usercenter=profil&from=admin`: schlanke `navbar-dark bg-dark` mit „← Admin-Center"-Link und „Logout"; Public-Navbar wird in diesem Modus weggelassen. Sichere Fallback-Logik: ohne `adminaccess` oder ohne `from=admin` → Public-Navbar.
- **Dynamische Page-Titles** je Subseite (Login / Registrieren / Passwort vergessen / Profil / Statistik / Suche / Download Center auf der Index).
- **Kommentar-Empty-State** auf Release-Detailseite: kontext-sensitiv für anonyme, eingeloggte und admin-berechtigte Nutzer („Schreibe den ersten Kommentar!" + Inline-Form bzw. „Anmelden"-Button).
- **`Schnellzugriff`-Karte** im Admin-Dashboard mit conditionalen Buttons (Neues Release, Neuer Ordner, Benutzer verwalten, Templates).
- **Sidebar-Scroll-Position** bleibt beim Navigieren erhalten (`sessionStorage`); aktueller Menüeintrag wird beim Page-Load ins Sichtfeld gescrollt.
- **`from=admin`-Breadcrumb** im Profil-Modul, wenn der User aus dem Admin-Center kommt.

### UX / Frontend

- **Subpage-Bleed gefixt**: Dashboard-Widgets (Statistik / Top / Flop / Latest / Rated) werden jetzt **nur auf der Startseite** gerendert. Subseiten haben einen leeren Hintergrund. Helper-Funktion `pdl_show_dashboard_widgets(): bool` in `pdl_layout.inc.php` + Guard in jedem Widget-Include.
- **Doppelte Card-Titel entfernt** (Widgets, Login/Register/Lost/Profil/Admin-Pages wie `editdelugroup`, `editfile`, `edituser`, `deluser`, `addscreen`).
- **Wortwahl vereinheitlicht**: „Neueste Downloads" → „Neueste Releases"; „Best bewertet" → „Bestbewertet"; „den Download bewerten" → „dieses Release bewerten"; „Files Adden / editieren / löschen" → „Dateien hinzufügen / bearbeiten / löschen"; „Vote!" → „Bewerten"; „Screen uploaden" → „Screenshot hochladen" (inkl. Akzeptanz von JPEG/PNG/WebP).
- **HTML5-Validierung in Public-Forms**: `type="email"`, `type="url"`, `required`, `minlength="8"`, `autocomplete` (`username`, `current-password`, `new-password`, `email`, `url`).
- **`<label for="…">`-Verknüpfungen** in Login, Register, Lost, Profil (vorher nur Tabellenzellen-Beschriftung).
- **Form-Re-Populate nach Fehler** (Register: `nick`, `email`, `homepage`, `get_letter`).
- **Helper-Texte für Newsletter-Checkbox** + Passwort-Mindestlänge.
- **Lost-Password-Erfolg** mit Hinweis (Posteingang inkl. Spam, 24 h gültig) + „Zurück zum Login"-CTA.
- **Register-Erfolg** mit zwei prominenten Buttons („Jetzt einloggen" + „Zum Profil").
- **Admin-Sidebar-Hintergrundfarbe** (Bootstrap-`offcanvas-lg`-Transparenz-Reset überschrieben).
- **Doppelter Menü-Eintrag** „Ersetzungen anzeigen" entfernt.
- **Verbale Menü-Aktionen** mit Subjekt versehen („Release hinzufügen", „Ordner ändern/löschen").
- **Admin-Schutz** für user_id=1 (Super-Admin-Badge in Userliste, Edit/Delete-Sperre); Schutz für ugroup_id ∈ {1,2} (Gast + Administrator gegen Löschung).
- **„Godadmin"-Wording entfernt** (in `edituser.php`, `deluser.php`, `editdelugroup.php`, `adduright.php`). Falsche `ugroup_id==1`-Checks (die ungewollt die Gast-Gruppe schützten) auf `user_id==1` korrigiert.
- **Suche-Link** in Navbar bedingt: nur sichtbar, wenn `$settings['enable_search'] === 'Y'`.

### Frontend (Public-Layout)

- **Meta-Description**, **Favicon-Link** (`pdl-gfx/favicon.svg`) und **visually-hidden H1** auf der Startseite.
- **`script_file`-URL ohne `?&`-Suffix** (Normalisierung in `pdl_header.inc.php`).
- **CSRF-Token + `usercenter`-Hidden-Field** in allen Public-Forms.

### Datenbank / Installation

- **Single Source of Truth** für die DB-Initialisierung: `.docker/initdb/01-pdl3-init.sql` wird von drei Pfaden verwendet:
  1. **Docker** (primär): MySQL führt die Datei via `/docker-entrypoint-initdb.d/` automatisch beim ersten Start aus.
  2. **`setup.php`**: lädt die Datei und führt sie per `mysqli::multi_query` aus. Komplett umgeschrieben (war 285 Zeilen manueller `CREATE TABLE`-Code).
  3. **`install_303.php`** (Legacy): `install_querys.inc` lädt die Datei und feedet sie an die bestehende `split_query()`-Logik.
- **Schema-Erweiterungen** (additiv, kompatibel mit 3.0.3-Installationen):
  - `pdl3_settings`: neue Spalten `name varchar(128)`, `bez varchar(255)`, `eingabe varchar(64)`, `reihenfolge smallint` (damit `pdl-admin/settings.php` funktioniert).
  - `pdl3_settingsgroup`: neue Spalte `reihenfolge tinyint`.
  - `pdl3_iplock.art`: ENUM um `register`/`lostpw` erweitert.
- **Default-Daten geseedet** (38 Settings, 9 Settings-Gruppen, 20 Templates, 18 Rechte mit deutschen Namen+Beschreibung, 2 Usergruppen Gast+Administrator, 1 Default-Admin mit Bcrypt-Hash für `admin123`).
- **Test-Daten-Cleanup**: 17 Test-Ordner (`pdl_int_*`), 3 Test-User, 2 Junk-Gruppen, dazugehörige Releases/Files/Screens/Comments gelöscht.
- **Doppel-UTF-8-Encoding repariert** in `pdl3_rights.name`/`bez` und 5 `pdl3_template.bez`-Einträgen (Folge des PowerShell→docker-exec-Konvertierungsfehlers).

### Aufgeräumt / entfernt

- 13 Admin-Pages mit `user_rights['god']` und 1 mit `writeletter` repariert (waren komplett unerreichbar).
- Test-Daten aus DB entfernt.
- `Changelog.txt` entfernt (durch dieses `CHANGELOG.md` ersetzt).

### Geänderte Dateien

`pdl-inc/pdl_layout.inc.php`, `pdl_header.inc.php`, `pdl_ulogin.modul.php`, `pdl_uregister.modul.php`, `pdl_ulost.modul.php`, `pdl_uprofil.modul.php`, `pdl_release.modul.php`, `pdl_stats.inc.php`, `pdl_top.inc.php`, `pdl_flop.inc.php`, `pdl_latest.inc.php`, `pdl_rated.inc.php`, `pdl_stats.modul.php`, `pdl_captcha.inc.php` (neu), `pdl_downloads.inc.php` · `pdl-admin/header.inc.php`, `footer.inc.php`, `index.php`, `users.php`, `edituser.php`, `deluser.php`, `editdelugroup.php`, `addugroup.php`, `or_list.php`, `addscreen.php`, `addrelease.php`, `addreplacement.php`, `addtemplate.php`, `addtgroup.php`, `addsettings.php`, `addsgroup.php`, `adduright.php`, `editdelsettingssgroup.php`, `editdeltemplatestgroup.php`, `editdeluright.php`, `editfile.php`, `ftp_browser.php`, `makeletter.php`, `settings.php`, `backup.php`, `dobackup.php`, `optimize.php`, `reset.php`, `admin.css` · `setup.php`, `install_303.php` (indirekt), `install_querys.inc`, `.docker/docker-compose.yml`, `.docker/initdb/01-pdl3-init.sql` (neu).

---

## [3.0.3] – 2026-04-23

- Userbereichs-Modernisierung (Sessions, password_hash, CSRF, Rate-Limit, Cookie-Hardening).
- Bootstrap-5-Migration der gesamten HTML-Ausgabe (öffentlicher Bereich, Admin, Setup/Update).
- Audit-Berichte unter `docs/2026-04-23-*.md`.

## [3.0.1] – 2026 (initiale Modernisierung auf PHP 8.4)

## [2.2.4] – 2002

- Letzte Original-Version durch PowerScripts.
