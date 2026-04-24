=====================================================================
                    PowerDownload 3.0.3 - Readme
=====================================================================

PowerDownload ist ein PHP-basiertes Download-Management-System mit
Ordnerstruktur, Benutzerverwaltung, Bewertungs- und Kommentarsystem.

Original 2001/2002 von PowerScripts veröffentlicht und 2025/2026 auf
PHP 8.4 + MySQL 8 modernisiert (Sessions, password_hash, CSRF,
Rate-Limit, Cookie-Hardening).

Projektseite : https://www.powerscripts.org
Projekte     : https://www.powerscripts.org/projects-6.html
GitHub       : https://github.com/schubertnico/PowerDownload


---------------------------------------------------------------------
1. SCHNELLSTART (Docker)
---------------------------------------------------------------------

  git clone https://github.com/schubertnico/PowerDownload.git
  cd PowerDownload
  docker compose -f .docker/docker-compose.yml up -d --build

Anwendung   : http://localhost:8092
phpMyAdmin  : http://localhost:8094  (root / root)
Admin-Login : admin / admin123  (wird beim Login auf bcrypt migriert)

Stoppen     : docker compose -f .docker/docker-compose.yml down


---------------------------------------------------------------------
2. SYSTEMVORAUSSETZUNGEN
---------------------------------------------------------------------

  - PHP 8.4 (mysqli, gd, mbstring)
  - MySQL 8.0 oder MariaDB 10.6+ (InnoDB, utf8mb4)
  - Apache 2.4+ mit mod_rewrite oder Nginx
  - Composer 2.x (nur für Entwicklung)


---------------------------------------------------------------------
3. INSTALLATION (manuell, ohne Docker)
---------------------------------------------------------------------

  - Dateien auf den Webserver hochladen.
  - MySQL-Datenbank anlegen (utf8mb4, InnoDB).
  - Verbindungsdaten in pdl-inc/pdl_config.inc.php setzen:
      $config_sql_server, $config_sql_user,
      $config_sql_password, $config_sql_database
  - Im Browser install_303.php aufrufen.
  - Danach install_303.php und update_*.php LÖSCHEN.
  - Schreibrechte: pdl-gfx/screens/ und pdl-gfx/smilies/ -> 0775.
  - Settings im Admin-Panel anpassen (script_file, mail_*, ...).

Update aus Vorgängerversion:
  - 2.2.4 -> 3.0.x : update_224to303.php
  - 3.0.1 -> 3.0.3 : update_301to303.php


---------------------------------------------------------------------
4. ROUTING (Front Controller: downloads.php)
---------------------------------------------------------------------

  /downloads.php                                 Wurzel-Ordner
  /downloads.php?ordner_id=N                     Ordner-Inhalt
  /downloads.php?release_id=N                    Release-Detail
  /downloads.php?screen_id=N                     Screenshot
  /downloads.php?show_search=1                   Suche
  /downloads.php?show_stats=1                    Statistik
  /downloads.php?usercenter=login                Login
  /downloads.php?usercenter=register             Registrierung
  /downloads.php?usercenter=profil               Profil bearbeiten
  /downloads.php?usercenter=lost                 Passwort vergessen
  /downloads.php?usercenter=lost2&remind_code=X  Neues Passwort setzen
  /downloads.php?usercenter=comments&release_id=N Kommentar
  /downloads.php?logout=1                        Logout
  /pdl-admin/                                    Admin-Panel


---------------------------------------------------------------------
5. EINBINDUNG IN EINE EIGENE SEITE
---------------------------------------------------------------------

Wichtig: Header GANZ OBEN einbinden, vor jeglichem Output:

  <?php include("pdl-inc/pdl_header.inc.php"); ?>

Download-Übersicht einbinden:

  <?php include("pdl-inc/pdl_downloads.inc.php"); ?>

Optionale Widgets:

  <?php include("pdl-inc/pdl_top.inc.php"); ?>     Top-X
  <?php include("pdl-inc/pdl_flop.inc.php"); ?>    Flop-X
  <?php include("pdl-inc/pdl_latest.inc.php"); ?>  Neueste-X
  <?php include("pdl-inc/pdl_rated.inc.php"); ?>   Bestbewertete-X
  <?php include("pdl-inc/pdl_stats.inc.php"); ?>   Statistik-Box

Anzahl + Aussehen werden in den Settings/Templates konfiguriert.


---------------------------------------------------------------------
6. ENTWICKLUNG
---------------------------------------------------------------------

  docker exec powerdownload_web composer install
  docker exec powerdownload_web composer phpunit
  docker exec powerdownload_web composer phpstan
  docker exec powerdownload_web composer psalm
  docker exec powerdownload_web composer cs
  docker exec powerdownload_web composer cs:fix
  docker exec powerdownload_web composer rector       (Dry-Run)
  docker exec powerdownload_web composer rector:fix   (Anwenden)
  docker exec powerdownload_web composer quality      (alle Tools)


---------------------------------------------------------------------
7. SICHERHEITS-FEATURES
---------------------------------------------------------------------

  - password_hash / password_verify (bcrypt, PASSWORD_DEFAULT)
  - Transparente MD5 -> bcrypt Migration nach erfolgreichem Login
  - Session-Token im Cookie (kein Passwort-Hash mehr)
  - session_regenerate_id nach Login
  - Cookie-Flags: HttpOnly, SameSite=Lax, Secure (HTTPS)
  - CSRF-Schutz auf allen POST-Endpunkten
  - Rate-Limit Login: 5 Fehlversuche / IP / 15 min
  - User-Enumeration verhindert (generische Antworten)
  - Server-Validierung E-Mail (FILTER_VALIDATE_EMAIL) + URL
  - Path-Traversal-Schutz (Whitelist bei Modul-Includes)
  - utf8mb4 als DB-Charset


---------------------------------------------------------------------
8. PORTS (Docker-Setup)
---------------------------------------------------------------------

  Web         8092    Apache + PHP
  MySQL       3319    Datenbank
  phpMyAdmin  8094    Datenbank-Verwaltung


---------------------------------------------------------------------
9. PROFI-INFOS / API
---------------------------------------------------------------------

Settings : $settings['<name>']         (DB: pdl3_settings)
Templates: $template['<name>']         (DB: pdl3_template)
Rechte   : $user_rights['<name>']      (pro Usergruppe)
User     : $user_details[...]          (leer = nicht eingeloggt)

CSRF-Helper:
  csrf_token()     - Token holen / erzeugen
  csrf_verify($t)  - Token prüfen (true/false)
  csrf_input()     - <input type="hidden" name="csrf_token" ...>

DB-Klasse $db_handler:
  sql_query("query")
  sql_fetch_array($result)
  sql_num_rows($result)
  sql_num_fields($result)
  sql_escape_string("string")
  sql_escape_int($int)
  sql_insert_id()


---------------------------------------------------------------------
10. TROUBLESHOOTING
---------------------------------------------------------------------

  - "Database not initialized" -> install_303.php aufrufen.
  - Bestandsuser kann sich nicht einloggen -> Passwort vergessen
    nutzen, alte MD5-Hashes werden beim Login automatisch migriert.
  - "Zu viele Fehlversuche" -> Rate-Limit; 15 min warten oder in
    MySQL: DELETE FROM pdl3_iplock WHERE ip='X' AND art='login';
  - Mailversand schlägt fehl -> im Container kein sendmail; in
    Produktion MTA bereitstellen, Fehler werden per error_log()
    protokolliert.
  - Leere Info-Boxen -> docs/superpowers/plans/
    2026-04-23-seed-templates.sql einspielen.


---------------------------------------------------------------------
11. LIZENZ
---------------------------------------------------------------------

MIT-Lizenz - siehe LICENSE.

  Copyright (c) 2001-2002 PowerScripts
  Copyright (c) 2025-2026 Nico Schubert


---------------------------------------------------------------------
12. KONTAKT
---------------------------------------------------------------------

  SchubertMedia
  Inhaber: Nico Schubert
  Stauffenbergallee 57
  99085 Erfurt
  Deutschland

  Telefon : +49 (0) 3612 3002247  (Mo.-Fr. 9-12 und 13-18 Uhr)
  Telefax : +49 (0) 3612 3004636
  E-Mail  : info@schubertmedia.de
  Web     : https://www.powerscripts.org

=====================================================================
