=====================================================================
                    PowerDownload 3.6.0 - Readme
=====================================================================

PowerDownload ist ein PHP-basiertes Download-Management-System mit
Ordnerstruktur, Benutzerverwaltung, Bewertungs- und Kommentarsystem.

Original 2001/2002 von PowerScripts veröffentlicht und 2025/2026 auf
PHP 8.4 und MySQL 8 / MariaDB 10.6+ modernisiert. Seit 3.6.0 richtet
ein Web-Installer (install.php) PowerDownload im Browser ein,
bestehende Installationen aktualisiert update.php. Öffentlicher
Bereich und Adminbereich nutzen Bootstrap 5.3 mit dunklem Theme,
hohen Kontrasten und responsivem Layout.

Projektseite : https://www.powerscripts.org
Projekte     : https://www.powerscripts.org/projects-6.html
               (Downloads und Video-Anleitungen)
GitHub       : https://github.com/schubertnico/PowerDownload


---------------------------------------------------------------------
1. INSTALLATION MIT DEM WEB-INSTALLER (Standard)
---------------------------------------------------------------------

  1. Dateien per FTP hochladen (inklusive .htaccess).
  2. Leere MySQL-8- bzw. MariaDB-10.6-Datenbank beim Hoster anlegen.
  3. https://ihre-domain/.../install.php aufrufen und den fünf
     Schritten folgen: Systemprüfung, Datenbank, Website,
     Administrator, Abschluss.
  4. install.php löschen.

Zugangsdaten stehen in pdl-inc/pdl_config.local.php;
Umgebungsvariablen PDL_DB_* haben Vorrang.

Hinweise:

  - Den Administrator legen Sie im Installer mit eigenem Passwort an
    (mindestens 8 Zeichen mit Buchstabe und Ziffer). Ein
    Standardpasswort gibt es nicht.
  - Ist pdl-inc/ nicht beschreibbar, bietet die Abschlussseite
    pdl_config.local.php zum Herunterladen an. Die Datei dann selbst
    nach pdl-inc/ hochladen.
  - Der Installer überschreibt keine vorhandenen Tabellen und sperrt
    sich nach dem Abschluss (pdl-inc/install.lock bzw.
    logs/install.lock).
  - install.php direkt nach dem Hochladen aufrufen: Bis zum Abschluss
    könnte jeder Besucher den Installer bedienen.
  - Pflicht: logs/ beschreibbar. Für Uploads außerdem pdl-files/,
    pdl-gfx/screens/ und pdl-gfx/smilies/ beschreibbar (z. B. 0775).
  - Danach im Adminbereich unter System -> Einstellungen Adresse und
    Name der Download-Seite, Absenderadresse und Sortierung prüfen.

Reihenfolge der Zugangsdaten (höchste Priorität zuerst):

  1. Umgebungsvariablen PDL_DB_HOST, PDL_DB_PORT, PDL_DB_USER,
     PDL_DB_PASS, PDL_DB_NAME
  2. pdl-inc/pdl_config.local.php (Vorlage: pdl_config.local.example.php)
  3. Vorgaben (PowerDownload gilt dann als nicht eingerichtet)

pdl-inc/pdl_config.inc.php enthält keine Zugangsdaten mehr und wird
bei jedem Update überschrieben.


---------------------------------------------------------------------
2. UPDATE VON 3.5.0 AUF 3.6.0
---------------------------------------------------------------------

  1. Datensicherung: Datenbank über das Kundenmenü des Hosters oder
     phpMyAdmin sichern, dazu pdl-files/ und pdl-gfx/screens/. (Die
     Sicherungsfunktion von 3.5.0 bricht bei leeren Feldern ab.)
  2. pdl-inc/pdl_config.local.php nach der Vorlage
     pdl-inc/pdl_config.local.example.php anlegen und die
     Zugangsdaten eintragen, die bisher in pdl_config.inc.php standen.
  3. Dateien ohne install.php hochladen.
  4. Als Admin update.php aufrufen (Recht "Einstellungen verwalten"),
     Vorschau prüfen, "Jetzt aktualisieren".
  5. Altdateien löschen: setup.php, install_*.php (install_303.php),
     install_querys.inc und update_*.php (update_224to303.php,
     update_301to303.php). update.php darf bleiben.

update.php ergänzt nur, was fehlt (Tabellen, Spalten, Einstellungen,
Vorlagen, Rechte, Gruppe "Gast", Gruppe 1 wird "Mitglied"), hebt
unveränderte Vorlagen auf den neuen Stand und lässt sich beliebig oft
wiederholen. Eigene Einstellungswerte und geänderte Vorlagen bleiben
unverändert, gelöscht wird nichts. update.php ist für Datenbanken ab
3.5.0 ausgelegt; die Update-Skripte für 2.2.4 und 3.0.x sind entfallen.


---------------------------------------------------------------------
3. DOCKER (nur Entwicklung)
---------------------------------------------------------------------

  git clone https://github.com/schubertnico/PowerDownload.git
  cd PowerDownload
  docker compose -f .docker/docker-compose.yml up -d --build

Anwendung   : http://localhost:8092
phpMyAdmin  : http://localhost:8094  (root / root)
Admin-Login : admin / admin123  (NUR Docker-Entwicklung, angelegt von
              .docker/initdb/02-dev-admin.sql - nie auf einem
              Webserver einspielen)

Stoppen     : docker compose -f .docker/docker-compose.yml down
Neu aufsetzen: docker compose -f .docker/docker-compose.yml down -v

Schema und Grunddaten kommen aus pdl-inc/pdl3_schema.sql (dieselbe
Datei, die auch der Web-Installer einspielt).


---------------------------------------------------------------------
4. SYSTEMVORAUSSETZUNGEN
---------------------------------------------------------------------

  - PHP 8.4 oder neuer mit mysqli und mbstring
    (optional: gd für Vorschaubilder, ftp für den FTP-Browser)
  - MySQL 8.0+ oder MariaDB 10.6+ (InnoDB, utf8mb4)
  - Apache 2.4+ mit mod_rewrite und .htaccess; unter Nginx die
    Sperren aus der .htaccess selbst nachbilden (mindestens pdl-inc/,
    logs/ und *.sql)
  - Composer 2.x (nur für Entwicklung)


---------------------------------------------------------------------
5. ROUTING (Front Controller: downloads.php)
---------------------------------------------------------------------

  /downloads.php                                 Startseite
  /downloads.php?ordner_id=N                     Ordner-Inhalt
  /downloads.php?release_id=N                    Release-Detail
  /downloads.php?load_file=N                     Download (Zähler)
  /downloads.php?screen_id=N                     Screenshot
  /downloads.php?show_search=1                   Suche
  /downloads.php?show_stats=1                    Statistik
  /downloads.php?usercenter=login                Anmelden
  /downloads.php?usercenter=register             Registrierung
  /downloads.php?usercenter=profil               Profil bearbeiten
  /downloads.php?usercenter=lost                 Passwort vergessen
  /downloads.php?usercenter=lost2&remind_code=X  Neues Passwort setzen
  /downloads.php?usercenter=comments&release_id=N Kommentar
  /downloads.php?logout=1&csrf_token=X           Abmelden
  /pdl-admin/                                    Adminbereich
  /install.php                                   Web-Installer
  /update.php                                    Datenbank-Update


---------------------------------------------------------------------
6. EINBINDUNG IN EINE EIGENE SEITE
---------------------------------------------------------------------

Wichtig: Header GANZ OBEN einbinden, vor jeglichem Output:

  <?php include("pdl-inc/pdl_header.inc.php"); ?>

Download-Übersicht einbinden:

  <?php include("pdl-inc/pdl_downloads.inc.php"); ?>

Optionale Kästen (erscheinen nur auf der Startseite):

  <?php include("pdl-inc/pdl_top.inc.php"); ?>     Top-X
  <?php include("pdl-inc/pdl_flop.inc.php"); ?>    Flop-X
  <?php include("pdl-inc/pdl_latest.inc.php"); ?>  Neueste-X
  <?php include("pdl-inc/pdl_rated.inc.php"); ?>   Bestbewertete-X
  <?php include("pdl-inc/pdl_stats.inc.php"); ?>   Statistik-Box

Anzahl und Aussehen werden in den Einstellungen und Vorlagen
konfiguriert. Ist PowerDownload in eine andere Seite eingebunden, die
Einstellung script_file anpassen.


---------------------------------------------------------------------
7. ENTWICKLUNG
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

Release-Archive (git archive, Quellcode-ZIP von GitHub) enthalten laut
.gitattributes nur die Dateien für den Webspace, ohne .docker/,
docs/, tests/ und tools/.


---------------------------------------------------------------------
8. SICHERHEITS-FEATURES
---------------------------------------------------------------------

  - password_hash / password_verify (bcrypt, PASSWORD_DEFAULT)
  - Transparente MD5 -> bcrypt Migration nach erfolgreichem Login
  - Session-Token im Cookie (kein Passwort-Hash)
  - Mehrere Geräte: Eine Anmeldung auf einem zweiten Gerät meldet das
    erste nicht ab. "Abmelden" meldet alle Geräte ab.
  - Cookie-Flags: HttpOnly, SameSite=Lax, Secure (HTTPS)
  - CSRF-Schutz auf allen POST-Formularen, auch Anmelden, Abmelden
    und gesamter Adminbereich; Löschen nur nach Bestätigungsseite
  - Rate-Limits: Anmeldung 5 Fehlversuche / IP / 15 min,
    Registrierung 5 und Passwort vergessen 3 je IP und Stunde
    (IPv4 und IPv6)
  - User-Enumeration verhindert (gleiche Antwort bei Passwort vergessen)
  - Gäste erhalten die Rechte der Gruppe "Gast", nie Admin-Rechte
  - Versteckte Releases bleiben in Suche, Statistik und Newsletter
    verborgen
  - BBCode-Links nur mit http, https, mailto
  - Keine PHP-Ausführung in pdl-files/, pdl-gfx/screens/ und
    pdl-gfx/smilies/; doppelte Endungen (datei.php.zip) werden beim
    Speichern entschärft (datei_php.zip)
  - pdl-files/ ist nicht direkt abrufbar (HTTP 403); Downloads nur über
    downloads.php?load_file=N. Direktlinks auf pdl-files/ funktionieren
    seit 3.6.0 nicht mehr.
  - Sicherung ohne Anmelde-Tokens und Reset-Codes
  - Installer sperrt sich nach dem Abschluss, kein Standardkonto
  - utf8mb4 als DB-Charset


---------------------------------------------------------------------
8a. FRONTEND (Bootstrap 5)
---------------------------------------------------------------------

Bootstrap 5.3.3 wird via CDN geladen, eigene Theme-Stylesheets:

  - pdl-gfx/pdl-public.css   Theme des öffentlichen Bereichs (dunkler
                             Hintergrund, helle Texte, rote Akzente)
  - pdl-admin/admin.css      Theme des Adminbereichs (Seitenleiste mit
                             eigenem Scrollbalken, aktueller Menüpunkt
                             hervorgehoben)

Layout-Helfer (pdl-inc/pdl_layout.inc.php):

  pdl_layout_start($title, $settings, $userRights, $userDetails)
  pdl_layout_end($settings, $rendertime, $querycount)
  pdl_alert($type, $msg)            success|danger|warning|info|...
  pdl_card_start($title, $extra)    einheitliche Card-Rahmen
  pdl_card_end()

Admin-Helfer (pdl-admin/functions.inc.php):

  pdl_admin_breadcrumb($items)      Bootstrap-Breadcrumbs
  pdl_admin_alert($type, $msg)      Admin-Alerts
  makedialog(...)                   Bestätigungsseite (POST mit CSRF,
                                    Knopf "Abbrechen")

Bedeutung wird nicht nur über Farbe vermittelt: gefährliche Aktionen
haben zusätzlich Klartext und einen roten Rahmen. Browser-Dialoge
(confirm/alert) gibt es nicht mehr.


---------------------------------------------------------------------
9. PORTS (Docker-Setup)
---------------------------------------------------------------------

  Web         8092    Apache + PHP
  MySQL       3319    Datenbank
  phpMyAdmin  8094    Datenbank-Verwaltung


---------------------------------------------------------------------
10. PROFI-INFOS / API
---------------------------------------------------------------------

Einstellungen : $settings['<name>']     (DB: pdl3_settings)
Vorlagen      : $template['<name>']     (DB: pdl3_template)
Rechte        : $user_rights['<name>']  (pro Benutzergruppe)
Benutzer      : $user_details[...]      (leer = nicht angemeldet)

Wichtige Einstellungen: site_url (Adresse für Links in Mails),
sitename, mail_fromname, mail_fromaddr, guest_group_id (Gruppe für
nicht angemeldete Besucher, Vorgabe 3), script_file (relativ lassen).

CSRF-Helfer:
  csrf_token()     - Token holen / erzeugen
  csrf_verify($t)  - Token prüfen (true/false)
  csrf_input()     - <input type="hidden" name="csrf_token" ...>

Mailversand:
  pdl_send_mail($to, $subject, $body)  - UTF-8, korrekte Kopfzeilen

DB-Klasse $db_handler:
  sql_query("query")
  sql_fetch_array($result)
  sql_num_rows($result)
  sql_num_fields($result)
  sql_escape_string("string")
  sql_escape_int($int)
  sql_insert_id()
  sql_error() / sql_errno()


---------------------------------------------------------------------
11. TROUBLESHOOTING
---------------------------------------------------------------------

  - "PowerDownload ist noch nicht eingerichtet" -> Neuinstallation:
    install.php aufrufen. Nach einem Update: pdl-inc/
    pdl_config.local.php fehlt oder enthält falsche Zugangsdaten.
  - "Installer gesperrt" -> Installation ist abgeschlossen oder in
    pdl-files/ bzw. pdl-gfx/screens/ liegen schon hochgeladene Dateien;
    install.php vom Server löschen, ein Update läuft über update.php.
  - Direktlink auf pdl-files/ liefert 403 -> so gewollt; verlinken Sie
    downloads.php?load_file=N.
  - Neue Funktionen fehlen nach einem Update -> update.php als Admin
    aufrufen.
  - Links in Mails falsch -> Einstellungen -> Allgemein ->
    "Adresse der Download-Seite" mit https:// eintragen.
  - Bestandsuser kann sich nicht anmelden -> Passwort vergessen
    nutzen, alte MD5-Hashes werden beim Login automatisch umgestellt.
  - "Zu viele Fehlversuche" -> Rate-Limit; 15 min warten oder in
    MySQL: DELETE FROM pdl3_iplock WHERE ip='X' AND art='login';
  - Mailversand schlägt fehl -> PowerDownload nutzt PHP-mail(); im
    Container kein sendmail. Fehler stehen im Fehlerprotokoll.
  - Upload oder Screenshot scheitert -> Schreibrechte für pdl-files/
    bzw. pdl-gfx/screens/ prüfen.


---------------------------------------------------------------------
12. LIZENZ
---------------------------------------------------------------------

MIT-Lizenz - siehe LICENSE.

  Copyright (c) 2001-2002 PowerScripts
  Copyright (c) 2025-2026 Nico Schubert


---------------------------------------------------------------------
13. KONTAKT
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
