# PowerDownload

[![Version](https://img.shields.io/badge/version-3.6.0-9b0000.svg?style=flat-square)](CHANGELOG.md)
[![PHP](https://img.shields.io/badge/PHP-8.4-8892BF.svg?style=flat-square)](https://www.php.net/)
[![tests](https://img.shields.io/badge/tests-passing-success.svg?style=flat-square)](tests/)
[![license](https://img.shields.io/badge/license-MIT-yellow.svg?style=flat-square)](LICENSE)

**PowerDownload** ist ein PHP-basiertes Download-Management-System mit Ordnerstruktur, Benutzerverwaltung, Bewertungs- und Kommentarsystem. Das Projekt wurde 2001/2002 von **PowerScripts** veröffentlicht und 2025/2026 vollständig auf **PHP 8.4** sowie **MySQL 8 / MariaDB 10.6+** modernisiert. Seit Version 3.6.0 richtet ein **Web-Installer** PowerDownload im Browser ein, bestehende Installationen aktualisiert `update.php`. Die HTML-Ausgabe nutzt **Bootstrap 5.3**; öffentlicher Bereich und Adminbereich haben ein dunkles Theme mit hohem Kontrast und responsivem Layout.

- Projektseite: <https://www.powerscripts.org>
- Projektbereich mit Downloads und Video-Anleitungen: <https://www.powerscripts.org/projects-6.html>
- GitHub: <https://github.com/schubertnico/PowerDownload>

---

## Installation

### Web-Installer (Standard)

So richten Sie PowerDownload auf einem gewöhnlichen Webspace ein, ohne Kommandozeile:

1. Dateien per FTP hochladen (inklusive `.htaccess`).
2. Leere MySQL-8- bzw. MariaDB-10.6-Datenbank beim Hoster anlegen.
3. `https://ihre-domain/…/install.php` aufrufen und den fünf Schritten folgen: Systemprüfung, Datenbank, Website, Administrator, Abschluss.
4. `install.php` löschen.

Zugangsdaten stehen in `pdl-inc/pdl_config.local.php`; Umgebungsvariablen `PDL_DB_*` haben Vorrang.

Hinweise:

- Den Administrator legen Sie im Installer mit eigenem Passwort an (mindestens 8 Zeichen mit Buchstabe und Ziffer). Ein Standardpasswort gibt es nicht.
- Ist `pdl-inc/` nicht beschreibbar, bietet die Abschlussseite `pdl_config.local.php` zum Herunterladen an. Laden Sie die Datei dann selbst nach `pdl-inc/` hoch.
- Der Installer überschreibt keine vorhandenen Tabellen und sperrt sich nach dem Abschluss (`pdl-inc/install.lock` bzw. `logs/install.lock`).
- Rufen Sie `install.php` direkt nach dem Hochladen auf. Bis zum Abschluss könnte jeder Besucher den Installer bedienen.
- Pflicht ist ein beschreibbares Verzeichnis `logs/`. Für Uploads sollten außerdem `pdl-files/`, `pdl-gfx/screens/` und `pdl-gfx/smilies/` beschreibbar sein (z. B. `chmod 0775`).
- Nach der Installation im Adminbereich unter **System → Einstellungen** prüfen: Adresse und Name der Download-Seite, Absenderadresse, Sortierung.

Ruft jemand `downloads.php` auf, bevor PowerDownload eingerichtet ist, erscheint die Seite „PowerDownload ist noch nicht eingerichtet“ mit dem Knopf „Installation starten“ (solange `install.php` existiert und nicht gesperrt ist).

Der Installer startet nicht, wenn in `pdl-files/` oder `pdl-gfx/screens/` schon hochgeladene Dateien liegen: Dort besteht offenbar bereits eine Installation, die über `update.php` aktualisiert wird (siehe unten).

### Docker (Entwicklung)

Voraussetzung: Docker und Docker Compose.

```bash
git clone https://github.com/schubertnico/PowerDownload.git
cd PowerDownload
docker compose -f .docker/docker-compose.yml up -d --build
```

**Anwendung:** <http://localhost:8092>
**phpMyAdmin:** <http://localhost:8094> (Benutzer `root`, Passwort `root`)

Beim ersten Start spielt MySQL `pdl-inc/pdl3_schema.sql` und `.docker/initdb/02-dev-admin.sql` ein. Letztere legt den **Entwicklungs-Admin `admin` / `admin123`** an. Dieses Konto gibt es nur in der Docker-Umgebung; spielen Sie `02-dev-admin.sql` nie auf einem Webserver ein.

Die Zugangsdaten bekommt der Web-Container über `PDL_DB_*` aus der Compose-Datei, `install.php` ist dort deshalb gesperrt.

```bash
docker compose -f .docker/docker-compose.yml ps
docker compose -f .docker/docker-compose.yml logs -f web
docker compose -f .docker/docker-compose.yml down       # stoppen
docker compose -f .docker/docker-compose.yml down -v    # stoppen und Datenbank verwerfen
```

Die Compose-Datei startet:

- `powerdownload_web` (Apache + PHP 8.4) auf Port **8092**
- `powerdownload_db` (MySQL 8.0) auf Port **3319**
- `powerdownload_phpmyadmin` (phpMyAdmin) auf Port **8094**

### Konfiguration der Zugangsdaten

| Quelle (höchste Priorität zuerst) | Inhalt |
|---|---|
| Umgebungsvariablen | `PDL_DB_HOST`, `PDL_DB_PORT`, `PDL_DB_USER`, `PDL_DB_PASS`, `PDL_DB_NAME` (Docker, `SetEnv` beim Hoster) |
| `pdl-inc/pdl_config.local.php` | schreibt der Installer; Vorlage zum Anlegen von Hand: `pdl-inc/pdl_config.local.example.php` |
| Vorgaben | ohne Zugangsdaten gilt PowerDownload als nicht eingerichtet |

`pdl-inc/pdl_config.inc.php` enthält keine Zugangsdaten mehr und wird bei jedem Update überschrieben. `pdl_config.local.php` bleibt bei Updates erhalten und ist per `.htaccess` gesperrt.

---

## Update von 3.5.0 auf 3.6.0

1. **Datensicherung:** Datenbank über das Kundenmenü des Hosters oder phpMyAdmin sichern, dazu `pdl-files/` und `pdl-gfx/screens/`. (Die Sicherungsfunktion von 3.5.0 bricht bei leeren Feldern ab und eignet sich dafür nicht.)
2. **`pdl-inc/pdl_config.local.php` anlegen:** Vorlage `pdl-inc/pdl_config.local.example.php` kopieren, als `pdl_config.local.php` speichern und die Zugangsdaten eintragen, die bisher in `pdl-inc/pdl_config.inc.php` standen.
3. **Dateien hochladen, ohne `install.php`.** Dabei wird `pdl_config.inc.php` überschrieben; die Zugangsdaten kommen ab jetzt aus `pdl_config.local.php`.
4. **Als Admin `update.php` aufrufen** (Recht „Einstellungen verwalten“), die Vorschau prüfen und „Jetzt aktualisieren“ klicken.
5. **Altdateien löschen:** `setup.php`, `install_*.php` (z. B. `install_303.php`), `install_querys.inc` und `update_*.php` (z. B. `update_224to303.php`, `update_301to303.php`). `update.php` selbst darf bleiben, es ist nur für Admins erreichbar.

Was `update.php` ändert:

- ergänzt fehlende Tabellen, Spalten, Einstellungen (`site_url`, `sitename`, `guest_group_id`), Vorlagen (Mail-Vorlagen) und Rechte,
- erweitert zu kleine Spalten (`pdl3_iplock.ip` für IPv6, neue ENUM-Werte),
- hebt Vorlagen und Beschriftungen auf den Stand 3.6.0, solange sie noch dem Auslieferungsstand 3.5.0 entsprechen,
- legt die Gruppe „Gast“ für nicht angemeldete Besucher an und benennt Gruppe 1 in „Mitglied“ um (mit Recht zum Bewerten), sofern sie unverändert ist,
- setzt den Installationszeitpunkt, falls er noch fehlt.

Eigene Einstellungswerte und selbst geänderte Vorlagen bleiben unverändert, gelöscht wird nichts. Die Vorschau nennt, was erhalten bleibt. Ein zweiter Aufruf meldet, dass nichts mehr zu tun ist. Auch nach künftigen Updates genügt es, `update.php` aufzurufen.

Fehlt `pdl_config.local.php` nach dem Hochladen, zeigt die Download-Seite „noch nicht eingerichtet“. Es gehen keine Daten verloren: Datei anlegen, hochladen, fertig. `install.php` würde eine Datenbank mit vorhandenen Tabellen ohnehin nicht anrühren.

`update.php` ist für Datenbanken ab Version 3.5.0 ausgelegt. Die früheren Update-Skripte für 2.2.4 und 3.0.x sind entfallen.

---

## Video-Anleitungen

Vertonte Video-Anleitungen mit Untertiteln zeigen Schritt für Schritt Installation, Grundeinstellungen, Ordner, Releases mit Dateien und Screenshots, Texte und Glossar, Registrierung, Herunterladen und Bewerten, Benutzergruppen und Rechte sowie Newsletter und Sicherung: <https://www.powerscripts.org/projects-6.html>

---

## Funktionsumfang

- Verwaltung von **Releases** mit einer oder mehreren Dateien in einer hierarchischen **Ordnerstruktur**
- **Dateien** als Upload nach `pdl-files/` (PowerDownload ermittelt die Größe selbst) oder als externer Link, dazu **Spiegel-Server**
- **Downloadzähler** über `downloads.php?load_file=…` mit Rechteprüfung, Hotlink-Schutz und Zählersperre (je IP und Datei einmal pro Stunde)
- **Screenshots** je Release (JPG, PNG und WebP, automatische Vorschaubilder mit GD)
- **Bewertungssystem** (1–10 Punkte) mit IP-Sperre gegen Mehrfachbewertungen
- **Kommentar-System** mit BBCode, Smileys, Glossar und Zensur
- **Suche** in Titel und/oder Beschreibung, alle Wörter müssen vorkommen
- **Statistik**: Startseiten-Kästen (Statistik, Top, Flop, Neueste, Bestbewertet) und eine Statistikseite
- **Benutzerregistrierung** mit Spamschutz (verstecktes Feld, Mindestzeit, optionale Rechenaufgabe, IP-Limit)
- **Passwort vergessen** mit Link, der 60 Minuten gültig ist
- **Konto-Löschung** durch das Mitglied selbst (DSGVO Art. 17)
- **Benutzergruppen und Rechte**: Gast (nicht angemeldet), Mitglied, Administrator und eigene Gruppen mit 18 Rechten
- **Newsletter** an Mitglieder mit Einwilligung, mit Vorschau und Testmail
- **Vorlagen-System** mit Editor (CodeMirror) im Adminbereich
- **Adminbereich** mit Übersicht, Einstellungen in Reitern, Sicherung, Wartung und Audit-Log
- **Web-Installer** und **Update-Skript**
- **Bootstrap-5-Frontend** mit dunklem Theme, responsiver Navigation, Cards und Breadcrumbs
- **Barrierefreiheit**: semantisches HTML5, `aria-describedby` für Hilfetexte, Skip-Link, hohe Kontraste, Bedeutung nicht nur über Farbe
- **Mehrsprachfähig** (aktuell Deutsch, Anrede „Sie“)

### Neu in 3.6.0

- **Web-Installer `install.php`** in fünf Schritten, ohne Standardpasswort, mit Sperre nach dem Abschluss.
- **`update.php`** mit Vorschau für bestehende Installationen; wiederholbar, löscht nichts.
- **Zugangsdaten in `pdl-inc/pdl_config.local.php`**, Umgebungsvariablen `PDL_DB_*` mit Vorrang, Port einstellbar.
- **MariaDB 10.6+** wird tatsächlich unterstützt (Kollation `utf8mb4_unicode_ci`).
- **Bewertungen, Kommentaranzeige und Downloadzähler funktionieren**; Archive in `pdl-files/` sind herunterladbar.
- **Mails mit Inhalt und absoluten Links** (Einstellung „Adresse der Download-Seite“), korrekt kodiert.
- **Gruppe „Gast“** für nicht angemeldete Besucher, Gruppe „Mitglied“ für Registrierte (darf bewerten).
- **Screenshot-Upload** repariert, auch für PNG und WebP.
- **Sicherheit:** CSRF-Schutz und Bestätigungsseiten für alle schreibenden Admin-Aktionen, keine SQL-Injection über Spaltennamen, versteckte Releases bleiben versteckt, sichere BBCode-Links, IP-Sperren auch für IPv6.
- **Adminbereich:** Übersicht mit Kennzahlen, Einstellungen in Reitern mit passenden Feldern, Newsletter mit Vorschau und Testmail, Release bearbeiten speichert wieder.
- **Sprache:** durchgehend Sie-Form, echte Umlaute, deutsche Begriffe (Benutzer, Einstellungen, Vorlagen, Sicherung).

Alle Änderungen: [CHANGELOG.md](CHANGELOG.md).

---

## Systemanforderungen

| Komponente    | Version |
|---------------|---------|
| PHP           | 8.4 oder neuer mit `mysqli` und `mbstring`; optional `gd` (Vorschaubilder), `ftp` (FTP-Browser) |
| Datenbank     | MySQL 8.0+ oder MariaDB 10.6+ (InnoDB, `utf8mb4`) |
| Webserver     | Apache 2.4+ mit `mod_rewrite` und `.htaccess` (`AllowOverride`). Unter Nginx die Sperren aus der `.htaccess` selbst nachbilden, mindestens für `pdl-inc/`, `logs/` und `*.sql` |
| Browser       | Aktuelle Versionen von Chrome, Firefox, Safari, Edge |
| Composer      | 2.x, nur für die Entwicklung |

Bootstrap und der Vorlagen-Editor werden vom CDN `cdn.jsdelivr.net` geladen.

---

## Verzeichnisstruktur

```
PowerDownload/
├── .docker/                  Docker-Compose, Dockerfile, php.ini (nur Entwicklung)
│   └── initdb/02-dev-admin.sql  Entwicklungs-Admin admin / admin123 (nur Docker)
├── docs/                     Audit-Berichte und Pläne (nur im Repository)
├── logs/                     Fehlerprotokoll, ggf. install.lock (muss beschreibbar sein)
├── pdl-admin/                Adminbereich
│   ├── header.inc.php        Navbar und Seitenleiste
│   ├── footer.inc.php        Fuß und Bootstrap-JS
│   ├── functions.inc.php     Helfer: Bestätigungsseiten, Rechteprüfung, Ordnerbaum, Löschen
│   ├── system_helpers.inc.php  Helfer für Benutzer, Rechte, Einstellungen, Newsletter, Sicherung
│   └── admin.css             Theme des Adminbereichs
├── pdl-files/                hochgeladene Download-Dateien (per .htaccess gesperrt, Auslieferung über load_file)
├── pdl-gfx/                  Grafiken, Smileys, favicon.svg
│   ├── screens/              Screenshots (eigene .htaccess, nur Bilder, kein PHP)
│   ├── smilies/              Smiley-Bilder (eigene .htaccess, nur Bilder, kein PHP)
│   └── pdl-public.css        Theme des öffentlichen Bereichs
├── pdl-inc/                  Kern (per .htaccess gesperrt)
│   ├── installer/            Web-Installer und Updater (Namensraum PowerDownload\Installer)
│   ├── pdl3_schema.sql       Datenbankschema mit Grunddaten (Installer, Docker, Tests)
│   ├── pdl_config.inc.php    Tabellennamen, lädt die Zugangsdaten
│   ├── pdl_config.local.example.php  Vorlage für pdl_config.local.php
│   ├── pdl_localconfig.inc.php  Rangfolge Umgebung / Datei / Vorgaben
│   ├── pdl_header.inc.php    Sitzung, Anmeldung, Rechte, Download, Bewertung
│   ├── pdl_mail.inc.php      Mailversand und absolute Links
│   ├── pdl_setup_required.inc.php  Seite „noch nicht eingerichtet“
│   ├── pdl_csrf.inc.php      CSRF- und Passwort-Helfer
│   ├── pdl_layout.inc.php    Layout-Helfer (pdl_layout_start/_end, pdl_alert, pdl_card_start/_end)
│   ├── pdl_db_class_*.inc.php  Datenbankklasse
│   ├── pdl_functions.inc.php Hilfsfunktionen (BBCode, Kürzen, Suche, Auslieferung)
│   ├── pdl_downloads.inc.php Routing der Module
│   ├── pdl_*.modul.php       Module (login, register, profil, lost, comments, release, ordner, search, stats)
│   └── pdl_*.inc.php         Startseiten-Kästen (stats, top, flop, latest, rated)
├── tests/                    PHPUnit-Tests (nur im Repository)
├── tools/                    Hilfsskripte (nur im Repository)
├── downloads.php             Front-Controller
├── index.php                 Weiterleitung auf downloads.php
├── install.php               Web-Installer (nach der Installation löschen)
├── update.php                Datenbank-Update für Admins
├── composer.json             Abhängigkeiten der Entwicklung
└── README.md / README.html / readme.txt / CHANGELOG.md
```

Release-Archive (`git archive`, Quellcode-ZIP von GitHub) enthalten laut `.gitattributes` nur, was auf den Webspace gehört; `.docker/`, `docs/`, `tests/` und `tools/` fehlen dort.

---

## Routing-Übersicht

Alle Funktionen löst der Front-Controller `downloads.php` über GET- und POST-Parameter auf:

| Route                                              | Modul                       |
|----------------------------------------------------|-----------------------------|
| `/downloads.php`                                   | Startseite mit Ordnerliste  |
| `/downloads.php?ordner_id=N`                       | Ordner-Inhalt               |
| `/downloads.php?release_id=N`                      | Release-Detail              |
| `/downloads.php?load_file=N`                       | Download (Zähler, Rechte, Hotlink-Schutz) |
| `/downloads.php?screen_id=N`                       | Screenshot-Anzeige          |
| `/downloads.php?show_search=1`                     | Suche                       |
| `/downloads.php?show_stats=1`                      | Statistik (Server-Angaben nur für Admins) |
| `/downloads.php?usercenter=login`                  | Anmelden                    |
| `/downloads.php?usercenter=register`               | Registrierung               |
| `/downloads.php?usercenter=profil`                 | Profil und Konto löschen    |
| `/downloads.php?usercenter=lost`                   | Passwort vergessen          |
| `/downloads.php?usercenter=lost2&remind_code=…`    | Neues Passwort setzen       |
| `/downloads.php?usercenter=comments&release_id=N`  | Kommentar zu einem Release  |
| `/downloads.php?logout=1&csrf_token=…`             | Abmelden (auch per Formular) |
| `/pdl-admin/`                                      | Adminbereich (rechtegeschützt) |
| `/install.php`                                     | Web-Installer (gesperrt nach der Installation) |
| `/update.php`                                      | Datenbank-Update (nur Admins) |

Formulare senden an `downloads.php`, die Steuerparameter stehen in versteckten Feldern.

---

## Frontend-Theme (Bootstrap 5)

Die HTML-Ausgabe nutzt **Bootstrap 5.3.3** (CDN). Zwei eigene Stylesheets passen Bootstrap an den dunklen PowerDownload-Look mit hohen Kontrasten an.

| Bereich            | Layout-Helfer                              | Theme-CSS                |
|--------------------|--------------------------------------------|--------------------------|
| Öffentlich         | `pdl-inc/pdl_layout.inc.php`               | `pdl-gfx/pdl-public.css` |
| Administration     | `pdl-admin/header.inc.php` + `footer.inc.php` | `pdl-admin/admin.css`    |
| Installer, Update  | `pdl-inc/installer/templates/layout.php`   | Bootstrap ohne eigenes Theme |

**Wichtige Helfer:**

- `pdl_layout_start(string $title, array $settings, array $userRights, ?array $userDetails)` – Doctype, Head, Navbar; der Seitentitel nennt Release- bzw. Ordnernamen.
- `pdl_layout_end(array $settings, float $rendertime, int $querycount)` – schließt das Layout.
- `pdl_alert(string $type, string $message)` – Bootstrap-Alert.
- `pdl_card_start(string $title, string $extraClasses = '')` / `pdl_card_end()` – Card-Rahmen.
- `pdl_admin_breadcrumb(array $items)`, `pdl_admin_alert(string $type, string $message)` – Adminbereich.
- `makedialog(…)` – Bestätigungsseite für Lösch- und Wartungsaktionen (POST mit CSRF-Token, Knopf „Abbrechen“).

**Theme-Eigenschaften:**

- Dunkle Flächen, helle Texte, Akzent-Rot `#9b0000` / `#c62828`.
- Bootstrap-Variablen und Utility-Klassen (`text-primary`, `text-muted`, Alerts) für das dunkle Theme überschrieben.
- `pdl-danger-action`: roter Rahmen und Klartext für gefährliche Aktionen (Bedeutung nicht nur über Farbe).
- Aktueller Menüpunkt im Adminbereich mit Hintergrund und Balken markiert, Seitenleiste mit eigenem Scrollbalken.
- `:focus-visible`-Rahmen für die Tastaturbedienung, Skip-Link für Screenreader.

**Vorlagen:** Die HTML-Vorlagen in `pdl3_template` (Startseiten-Kästen, Kommentarformular) bestehen seit 3.6.0 aus Bootstrap-Markup statt alter Tabellen. Fehlt eine Vorlage oder enthält sie noch altes Tabellen-HTML, verwendet PowerDownload einen eingebauten Standard. Das gilt auch für die Mail-Vorlagen.

---

## Sicherheit

- **`password_hash` / `password_verify`** mit `PASSWORD_DEFAULT` (bcrypt), alte MD5-Hashes werden nach erfolgreicher Anmeldung umgestellt.
- **Sitzungs-Token im Cookie** (kein Passwort-Hash), `session_regenerate_id` nach der Anmeldung.
- **Mehrere Geräte:** Eine Anmeldung auf einem zweiten Gerät meldet das erste nicht ab. **„Abmelden“ meldet alle Geräte ab**, ebenso „Passwort vergessen“; ein Passwortwechsel im Profil meldet alle anderen Geräte ab.
- **Cookie-Flags:** `HttpOnly`, `SameSite=Lax`, `Secure` bei HTTPS.
- **CSRF-Schutz** auf allen POST-Formularen, auch bei Anmeldung, Abmeldung und im gesamten Adminbereich. Zerstörende Aktionen nur per POST nach Bestätigungsseite.
- **Rate-Limits:** Anmeldung 5 Fehlversuche je IP in 15 Minuten, Registrierung 5 und „Passwort vergessen“ 3 je IP und Stunde; IPv4 und IPv6.
- **Kein Ausforschen von Adressen:** „Passwort vergessen“ antwortet immer gleich.
- **Rechte:** einheitliche Prüfung in Menü und Seiten; Gäste erhalten nie Admin-Rechte; Spaltennamen der Rechte nur aus `pdl3_rights`.
- **Versteckte Releases** erscheinen weder in Listen, Suche, Statistik und Newsletter noch über `load_file`.
- **BBCode-Links** nur mit `http`, `https`, `mailto`.
- **Uploads:** ausführbare Endungen gesperrt, doppelte Endungen wie `datei.php.zip` beim Speichern entschärft (`datei_php.zip`); `pdl-files/`, `pdl-gfx/screens/` und `pdl-gfx/smilies/` ohne PHP-Ausführung.
- **`pdl-files/` nicht direkt abrufbar** (HTTP 403): Downloads nur über `downloads.php?load_file=N` mit Rechteprüfung, Hotlink-Schutz und Zähler. Direktlinks auf `pdl-files/…` funktionieren seit 3.6.0 nicht mehr.
- **Sicherung** ohne Anmelde-Tokens und Reset-Codes.
- **Installer:** gesperrt nach dem Abschluss, schreibt die Konfiguration ohne Überschreiben, legt keine Standardkonten an.
- **Fehlermeldungen:** Datenbankfehler mit Serverdetails nur im Fehlerprotokoll, Passwörter und Tokens dort maskiert.
- **`utf8mb4`** als Zeichensatz.

---

## Entwicklung

Alle Befehle im laufenden Docker-Stack.

```bash
docker exec powerdownload_web composer install
docker exec powerdownload_web composer phpunit
docker exec powerdownload_web composer phpstan
docker exec powerdownload_web composer psalm
docker exec powerdownload_web composer phpmd
docker exec powerdownload_web composer cs        # Code-Style prüfen
docker exec powerdownload_web composer cs:fix    # Code-Style korrigieren
docker exec powerdownload_web composer rector       # Dry-Run
docker exec powerdownload_web composer rector:fix   # Anwenden
docker exec powerdownload_web composer quality   # alle Static-Analyse-Werkzeuge
docker exec powerdownload_web composer test      # Tests und PHPStan
```

Der Integrationstest des Installers (`tests/Integration/InstallerDatabaseTest.php`) legt eine eigene Datenbank `<PDL_DB_NAME>_installer_test` an und entfernt sie wieder. Ohne Recht `CREATE DATABASE` überspringt er sich.

---

## Konfiguration im Adminbereich

- **Einstellungen**: im Code über `$settings['<name>']`, Verwaltung unter **System → Einstellungen** (Reiter je Gruppe).
- **Vorlagen**: über `$template['<name>']`, HTML-Schnipsel mit `{platzhalter}`.
- **Rechte**: über `$user_rights['<name>']`, je Benutzergruppe.
- **Benutzer**: über `$user_details[...]`; leer heißt nicht angemeldet.

Wichtige Einstellungen:

| Einstellung          | Bedeutung                                       |
|----------------------|-------------------------------------------------|
| `site_url`           | Adresse der Download-Seite mit `https://`, Grundlage für Links in Mails und Newsletter |
| `sitename`           | Name der Download-Seite (erscheint in Mails)    |
| `mail_fromname`, `mail_fromaddr` | Absendername und -adresse aller Mails |
| `guest_group_id`     | Benutzergruppe für nicht angemeldete Besucher (Vorgabe 3 „Gast“) |
| `script_file`        | Aufruf der Download-Seite, ab Werk `downloads.php?` (relativ lassen) |
| `enable_comments`, `enable_search` | Kommentare bzw. Suche ein/aus     |
| `bb_code`, `smilies` | BBCode bzw. Smileys in Texten und Kommentaren   |
| `captcha_enabled`    | Rechenaufgabe bei Registrierung und „Passwort vergessen“ |
| `orderby`, `orderseq`, `perpage` | Sortierung und Releases pro Seite   |
| `dlspeed`            | angenommene Geschwindigkeit (KB/s) für die geschätzte Downloadzeit |

---

## Datenbank-Schema (wichtigste Tabellen)

| Tabelle           | Zweck                                                       |
|-------------------|-------------------------------------------------------------|
| `pdl3_user`       | Benutzer (u. a. `passwort`, `session_token`, `remind_code`, `remind_expires`, `get_letter`) |
| `pdl3_usergroup`  | Benutzergruppen und Rechte: 1 Mitglied, 2 Administrator, 3 Gast |
| `pdl3_rights`     | Rechte-Definitionen (Name, Beschreibung, Spalte)            |
| `pdl3_ordner`     | Ordner-Hierarchie                                           |
| `pdl3_release`    | Releases                                                    |
| `pdl3_files`      | Dateien und Spiegel-Server je Release                       |
| `pdl3_screens`    | Screenshots je Release (mit `text`, `views`)                |
| `pdl3_comments`   | Kommentare                                                  |
| `pdl3_iplock`     | IP-Sperren für Bewertung, Kommentar, Anmeldung, Registrierung, „Passwort vergessen“ und Downloadzähler |
| `pdl3_replacements` | Zensur, Glossar, Smileys                                  |
| `pdl3_settings`, `pdl3_settingsgroup` | Einstellungen und ihre Gruppen          |
| `pdl3_template`, `pdl3_templategroup` | Vorlagen und ihre Gruppen               |
| `pdl3_admin_log`  | Audit-Log der Admin-Aktionen                                |

Schema und Grunddaten: `pdl-inc/pdl3_schema.sql`.

---

## Ports (Docker)

| Dienst      | Port  | Beschreibung                  |
|-------------|-------|-------------------------------|
| Web         | 8092  | Apache + PHP                  |
| MySQL       | 3319  | Datenbank-Hostport            |
| phpMyAdmin  | 8094  | Datenbank-Verwaltung          |

---

## Troubleshooting

- **„PowerDownload ist noch nicht eingerichtet“** → Neuinstallation: `install.php` aufrufen. Nach einem Update: `pdl-inc/pdl_config.local.php` fehlt oder enthält falsche Zugangsdaten (Vorlage `pdl_config.local.example.php`). Die genaue Meldung des Datenbankservers steht im Fehlerprotokoll.
- **„Installer gesperrt“** → Die Installation ist abgeschlossen (`install.lock` oder `pdl_config.local.php` vorhanden) oder in `pdl-files/` bzw. `pdl-gfx/screens/` liegen schon hochgeladene Dateien. `install.php` vom Server löschen; ein Update läuft über `update.php`.
- **Direktlink auf `pdl-files/…` liefert 403** → So gewollt. Verlinken Sie `downloads.php?load_file=N` (die Download-Knöpfe der Release-Seite tun das bereits).
- **Neue Funktionen fehlen nach einem Update** (z. B. Gruppe „Gast“, Mail-Vorlagen, „Adresse der Download-Seite“) → `update.php` als Admin aufrufen.
- **Links in Mails falsch** → Unter Einstellungen → Allgemein die „Adresse der Download-Seite“ (`site_url`) mit `https://` eintragen.
- **Bestandsuser kann sich nicht anmelden** → „Passwort vergessen“ nutzen; alte MD5-Hashes werden bei der nächsten Anmeldung umgestellt.
- **„Zu viele Fehlversuche“** → 15 Minuten warten oder in MySQL `DELETE FROM pdl3_iplock WHERE ip='<IP>' AND art='login';`.
- **Mailversand schlägt fehl** → PowerDownload verschickt über PHP-`mail()`. Im Docker-Container ist kein `sendmail` installiert; in Produktion braucht der Server einen funktionierenden Mailversand. Fehler stehen im Fehlerprotokoll.
- **Upload oder Screenshot scheitert** → Schreibrechte für `pdl-files/` bzw. `pdl-gfx/screens/` prüfen; die Systemprüfung des Installers zeigt sie ebenfalls.

---

## Audit und Dokumentation

Im Verzeichnis `docs/` (nur im Repository):

- `2026-04-23-Userbereichs-bugs.md` – Bug-Liste des Benutzerbereichs
- `2026-04-23-Userbereichs-improvements.md` – Verbesserungsvorschläge
- `2026-04-23-Userbereichs-test-coverage.md` – Testabdeckung und Routenmatrix
- `2026-04-23-Userbereichs-abschlussbericht.md` – Zusammenfassung
- `superpowers/plans/` – Implementierungspläne und historische Migrationen

## Changelog (Auswahl)

- **2026-10 (3.6.0)** – Web-Installer und `update.php`, Zugangsdaten in `pdl_config.local.php`, MariaDB-10.6-Unterstützung, Gast- und Mitgliedergruppe, zahlreiche Fehlerbehebungen (Bewertungen, Kommentare, Downloadzähler, Mails, Screenshots, Release bearbeiten) und Sicherheitskorrekturen, Oberfläche durchgehend in Sie-Form.
- **2026-05 (3.5.0)** – Sicherheits- und UX-Überarbeitung, Konto-Löschung, Spamschutz, Bootstrap-5-Ausgabe für alle Bereiche.
- **2026-04 (3.0.3)** – Benutzerbereich überarbeitet (Sitzungen, `password_hash`, CSRF, Rate-Limit).
- **2026-03** – PHP-8.4-Migration, MySQL-8-Unterstützung, Composer, PHPUnit.

Vollständig: [CHANGELOG.md](CHANGELOG.md).

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
