# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden in dieser Datei dokumentiert. Format: [Keep a Changelog](https://keepachangelog.com/de/1.1.0/) / [SemVer](https://semver.org/lang/de/).

---

## [3.6.0] – 2026-10-02

Ein Web-Installer (`install.php`) und ein Update-Skript (`update.php`) ersetzen die bisherigen Setup- und Update-Dateien. Dazu kommen zahlreiche Fehlerbehebungen im öffentlichen Bereich und im Adminbereich: Bewertungen, Kommentaranzeige, Downloadzähler, Mails, Screenshot-Upload und das Bearbeiten von Releases funktionieren wieder, mehrere Sicherheitslücken sind geschlossen. Die Oberfläche spricht durchgehend Deutsch in Sie-Form.

### Installation und Update

- **Web-Installer `install.php`** in fünf Schritten: Systemprüfung, Datenbank (Verbindungstest, Versionsprüfung MySQL 8.0+ bzw. MariaDB 10.6+), Website (Name, Adresse, Absenderadresse, Kurzbeschreibung), Administrator, Abschluss. Die Logik liegt in `pdl-inc/installer/` und ist von außen nicht abrufbar.
- Der Installer überschreibt keine vorhandenen Tabellen. Schlägt eine Anweisung fehl, entfernt er nur die in diesem Lauf angelegten Tabellen.
- **Kein Standardpasswort mehr:** Das Administratorkonto entsteht im Installer mit eigenem Passwort (mindestens 8 Zeichen mit Buchstabe und Ziffer). `admin123`, `passwort1`, `password1` und ein Passwort gleich dem Benutzernamen werden abgelehnt.
- Nach dem Abschluss sperrt sich der Installer (`pdl-inc/install.lock`, ersatzweise `logs/install.lock`) und antwortet mit HTTP 403. Die Abschlussseite nennt die Dateien, die vom Server gelöscht werden sollen.
- **Zugangsdaten in `pdl-inc/pdl_config.local.php`:** Der Installer schreibt die Datei selbst. Ist `pdl-inc/` nicht beschreibbar, bietet die Abschlussseite sie zum Herunterladen an. `pdl_config.inc.php` enthält keine Zugangsdaten mehr und wird bei Updates überschrieben. Rangfolge: Umgebungsvariablen `PDL_DB_HOST`, `PDL_DB_PORT`, `PDL_DB_USER`, `PDL_DB_PASS`, `PDL_DB_NAME` vor `pdl_config.local.php` vor den Vorgaben. Der Datenbankport ist jetzt einstellbar.
- **Vorlage `pdl-inc/pdl_config.local.example.php`** für Updates von 3.5.0 und für die Einrichtung von Hand.
- **`update.php`** gleicht eine bestehende Datenbank mit dem Schema von 3.6.0 ab. Nur für angemeldete Admins mit dem Recht „Einstellungen verwalten“. Zuerst erscheint eine Vorschau, geändert wird erst nach „Jetzt aktualisieren“. Jeder Lauf ergänzt nur, was fehlt, und lässt sich beliebig oft wiederholen:
  - fehlende Tabellen, Spalten, Einstellungen, Vorlagen und Rechte,
  - zu kleine Spaltentypen (neue ENUM-Werte, längere Felder),
  - Vorlagen und Beschriftungen, die noch dem Auslieferungsstand 3.5.0 entsprechen, auf den Stand 3.6.0,
  - die neue Gruppe „Gast“ und die Umbenennung von Gruppe 1 in „Mitglied“ (nur, wenn sie noch dem Stand 3.5.0 entspricht),
  - den Installationszeitpunkt, falls er noch 0 ist.
  Eigene Einstellungswerte und vom Betreiber geänderte Vorlagen bleiben unverändert. Gelöscht oder verkleinert wird nichts.
- **Hinweis statt Fehlerseite:** Fehlen Zugangsdaten, Verbindung oder Tabellen, zeigt `downloads.php` „PowerDownload ist noch nicht eingerichtet“ (HTTP 503) und, solange `install.php` existiert und nicht gesperrt ist, den Knopf „Installation starten“. Die Originalmeldung des Datenbankservers landet nur im Fehlerprotokoll. Bisher verwies diese Seite auf das per `.htaccess` gesperrte `setup.php`.
- **Schema `pdl-inc/pdl3_schema.sql`** als gemeinsame Quelle für Installer, Docker und Tests: ohne `DROP TABLE`, ohne Benutzerkonto, Kollation `utf8mb4_unicode_ci`. Damit läuft PowerDownload tatsächlich auf MariaDB 10.6+ (die bisherige Kollation `utf8mb4_0900_ai_ci` kennt MariaDB erst ab 11.4.5).
- **Schemaänderungen:**
  - `pdl3_iplock.ip` auf 45 Zeichen (IPv6), `pdl3_iplock.art` um `download` erweitert,
  - `pdl3_screens` um `text` (Beschriftung) und `views` (Aufrufe),
  - `pdl3_user.get_letter` mit Vorgabe `N`,
  - neue Einstellungen `site_url` (Adresse der Download-Seite), `sitename` (Name der Download-Seite) und `guest_group_id` (Gruppe für nicht angemeldete Besucher),
  - Mail-Vorlagen `mail_register`, `mail_lost1`, `mail_lost2`,
  - Benutzergruppen 1 „Mitglied“ (darf jetzt bewerten), 2 „Administrator“, 3 „Gast“ (nur Download),
  - Einstellungsgruppen neu benannt: Funktionen, Sortierung, Vorschautext, Textformatierung, Hotlink-Schutz, Allgemein, E-Mail, Screenshots, FTP.
- Der Installationszeitpunkt (`installed`) wird gesetzt. Die Tageswerte der Statistik und der Newsletter rechneten bisher ab 1970.
- `pdl-gfx/screens/` wird mit Schutzdateien ausgeliefert (vorher fehlte das Verzeichnis).
- Die Systemprüfung verlangt die PHP-Erweiterung `mbstring` (Texte mit Umlauten: Längenprüfung, Vorschautexte, Suche, Zensur).
- Die Übersicht im Adminbereich warnt, solange `install.php` oder Altdateien wie `setup.php`, `install_303.php` oder `update_*.php` auf dem Server liegen.
- **Docker:** Schema und Entwicklungs-Admin werden getrennt eingebunden (`pdl-inc/pdl3_schema.sql`, `.docker/initdb/02-dev-admin.sql`), der Web-Container erhält `PDL_DB_*`. Das Konto `admin` / `admin123` gibt es nur noch in der Docker-Entwicklung.

### Sicherheit

- **Adminbereich:** Alle schreibenden Formulare tragen ein CSRF-Token. Zerstörende Aktionen (Löschen, Zähler zurücksetzen, Datenbank optimieren, Sicherung einspielen) laufen nur noch per POST nach einer Bestätigungsseite. Bisher genügte etwa ein Link auf `reset.php?submit=1`, um alle Kommentare zu löschen, und Kommentare wurden ohne Rückfrage gelöscht.
- **SQL-Injection über Spaltennamen geschlossen:** Benutzergruppen- und Rechteformulare übernehmen Spaltennamen nur noch aus `pdl3_rights` (geprüft gegen `^[a-z_]+$` und die vorhandenen Spalten), nie aus dem Formular. Die 18 mitgelieferten Rechte sind geschützt, die Rechteverwaltung verlangt „Einstellungen verwalten“.
- **Einheitliche Rechteprüfung** in Menü und Seiten. „Releases und Dateien hinzufügen“ und „Kommentare moderieren“ wirken jetzt, Löschen hängt an „Releases und Dateien löschen“.
- **Gastgruppe:** Nicht angemeldete Besucher erhalten die Rechte der Gruppe aus `guest_group_id` (Vorgabe 3 „Gast“), nie Admin-Rechte. Fehlt die Gruppe, dürfen sie nur herunterladen.
- **Suche:** Mehrwortsuchen zeigten versteckte Releases (fehlende Klammern um die OR-Bedingungen). Die Sortierung aus dem Besucher-Cookie ging ungeprüft in `ORDER BY`; jetzt gilt eine Whitelist, auch für die Cookie-Werte selbst.
- **Versteckte Releases** erscheinen nicht mehr in Statistik, Newsletter und Download-Weiche.
- **BBCode:** Links und Bilder nur noch mit `http`, `https` bzw. `mailto`. Andere Schemata wie `javascript:` erscheinen als Text, Links erhalten `rel="noopener nofollow ugc"`.
- **Anmelden und Abmelden:** Das Anmeldeformular wird per CSRF geprüft. Abmelden geht per Formular oder Link mit Token; ein Abmelde-Link von einer fremden Seite führt zu einer Rückfrage.
- **Sitzungen:** Eine Anmeldung auf einem zweiten Gerät meldet das erste nicht mehr ab. „Abmelden“ und „Passwort vergessen“ melden alle Geräte ab, ein Passwortwechsel im Profil alle anderen Geräte.
- **Anmeldesperre nicht mehr umgehbar:** Nach fünf Fehlversuchen von einer IP-Adresse innerhalb von 15 Minuten bleibt die Anmeldung gesperrt, gleich für welches Konto (öffentlicher Bereich und Adminbereich). Fehlversuche werden mit dem versuchten Konto gespeichert, eine erfolgreiche Anmeldung löscht nur die Fehlversuche dieses Kontos. Bisher löschte sie alle Fehlversuche der IP-Adresse; wer zwischendurch das eigene Konto anmeldete, konnte fremde Passwörter unbegrenzt durchprobieren.
- **IPv6:** Die IP-Sperren für Anmeldung, Registrierung, „Passwort vergessen“ und Bewertungen griffen bei IPv6-Adressen nicht, weil die Spalte zu kurz war.
- **Sicherung:** enthält nur noch die Tabellen von PowerDownload, `NULL` bleibt `NULL` (bisher brach die Sicherung dabei ab), Anmelde-Tokens und Codes aus „Passwort vergessen“ werden geleert, IP-Sperren nicht gesichert.
- **Datenschutz:** Öffentliche Seiten zeigen Autoren ohne `mailto:`-Link. Nach einer Konto-Löschung steht im Fehlerprotokoll nur noch die Benutzer-ID, nicht mehr Name und E-Mail-Adresse.
- **Mails:** Kopfzeilen ohne Zeilenumbrüche (Schutz vor Header-Injection).
- **`pdl-files/` gesperrt:** Direkte Aufrufe von `pdl-files/…` beantwortet der Webserver mit HTTP 403 (`pdl-files/.htaccess` und zusätzlich die Webroot-`.htaccess`). Downloads laufen nur noch über `downloads.php?load_file=N`; so lassen sich Recht „Downloads erlaubt“, versteckte Releases, Hotlink-Schutz und Zähler nicht mehr umgehen. **Wer bisher direkt auf Dateien unter `pdl-files/` verlinkt hat, muss diese Links umstellen.** Eigene Adressen der Form `https://<diese Domain>/pdl-files/…` in Dateieinträgen liefert PowerDownload selbst aus.
- **Doppelte Dateiendungen:** Uploads wie `paket.php.zip` und Smiley-Bilder wie `bild.php.png` werden als `paket_php.zip` bzw. `bild_php.png` gespeichert; auf Webspaces mit `AddHandler … .php` liefen sie sonst als PHP. Ausführbare Endungen (auch `.pHp`, `.pht`, `.phtml`, `.shtml`) werden abgelehnt. `pdl-files/`, `pdl-gfx/screens/` und das neue `pdl-gfx/smilies/.htaccess` schalten PHP-Handler ab und sperren Skript-Endungen an beliebiger Stelle im Dateinamen.
- **Installer bei vorhandenen Uploads gesperrt:** Liegen in `pdl-files/` oder `pdl-gfx/screens/` schon hochgeladene Dateien, startet `install.php` nicht. Wurde ein Update samt `install.php` hochgeladen, bevor `pdl_config.local.php` existiert, konnte bisher ein Fremder den Installer mit eigener Datenbank durchlaufen. Die Seite „noch nicht eingerichtet“ zeigt „Installation starten“ nur noch, wenn der Installer nicht gesperrt ist, sonst einen Hinweis auf `update.php`.
- **Faktische Admin-Rechte gekennzeichnet:** Auch „Releases und Dateien hinzufügen“ (Uploads, per FTP in beliebige Verzeichnisse) und „Ersetzungen verwalten“ (Glossar-HTML) tragen die Kennzeichnung „Admin-Recht“. Konten aus Gruppen mit Admin-Zugang darf nur löschen, wer zusätzlich „Einstellungen verwalten“ hat.
- Ausgelieferte Dateien kommen immer als Download (`attachment`, `nosniff`, Sandbox-Richtlinie), auch HTML und SVG.
- `display_errors` wird jetzt auch im Code abgeschaltet; auf Servern mit PHP-FPM wirkt `php_flag` aus der `.htaccess` nicht.
- Die Zensur ändert nur noch sichtbaren Text, keine Link-Adressen, Bildpfade oder HTML-Attribute.
- Das FTP-Passwort steht in den Einstellungen in einem Passwortfeld statt im Klartext.
- SQL-Fehler werden ins PHP-Fehlerprotokoll geschrieben (Passwörter und Tokens maskiert), statt still verschluckt zu werden.

### Behobene Fehler

Öffentlicher Bereich:

- **Bewertungen** wurden nie gespeichert. Jetzt mit Rückmeldung („Danke für Ihre Bewertung (8/10).“), eine Bewertung je Release in 24 Stunden: für Angemeldete je Konto, für Gäste je IP-Adresse. So kann im Vereins- oder Firmen-WLAN, wo sich viele Mitglieder eine Adresse teilen, jedes Mitglied selbst bewerten.
- **Kommentare** ließen sich nicht anzeigen, der Knopf „Anzeigen“ blieb wirkungslos. Sie erscheinen jetzt direkt auf der Release-Seite.
- **Kommentar schreiben:** Nach dem Absenden geht es per Weiterleitung zurück zum Release, die Meldung „Ihr Kommentar wurde veröffentlicht.“ steht direkt unter dem neuen Kommentar. Bisher blieb die Antwort auf das Formular stehen, und Neuladen schickte den Kommentar ein zweites Mal ab. Zwischen zwei Kommentaren eines Kontos liegt mindestens eine Minute; der Titel darf höchstens 128, der Text höchstens 5.000 Zeichen lang sein.
- **Downloadzähler:** Die Dateilinks zeigten direkt auf die Datei und umgingen Zähler, das Recht „Downloads erlaubt“ und den Hotlink-Schutz. Alle Links laufen jetzt über `downloads.php?load_file=…`. Ein Download zählt je Datei höchstens einmal pro Stunde, für Angemeldete je Konto, für Gäste je IP-Adresse. Unbekannte oder versteckte Dateien zeigen „Datei nicht gefunden“ statt einer leeren Weiterleitung. Lokale Dateien unter `pdl-files/` liefert PowerDownload selbst aus.
- **Ohne Download-Recht** erscheint eine Hinweisseite mit „Zurück zum Release“, Gäste finden dort einen Anmelde-Link. Nach der Anmeldung über diesen Link oder über die Hinweise bei Bewertung und Kommentaren geht es zurück zum Release, dort steht „Sie sind jetzt angemeldet.“. Bisher stand nur „Sie haben keine Berechtigung eine Datei zu downloaden.“ da, ohne Weg zurück.
- **Archive herunterladbar:** Die `.htaccess` sperrte ZIP, RAR, 7z und TAR.GZ auch in `pdl-files/` (HTTP 403).
- **Mails:** Registrierung, „Passwort vergessen“ und „Passwort geändert“ verschickten leere Mails, weil die Vorlagen fehlten. Links in Mails sind jetzt absolut (Einstellung `site_url`, sonst die aufgerufene Adresse), die Kopfzeilen korrekt kodiert (UTF-8, Betreff nach RFC 2047, MIME-Version). Ohne Vorlage greift ein eingebauter Standardtext.
- Die Meldung zu „Passwort vergessen“ nennt die richtige Gültigkeit des Links (60 Minuten statt „24 Stunden“).
- **Kein Schein-Erfolg mehr:** Registrierung und „Passwort vergessen“ meldeten Erfolg, obwohl bei zu schnellem Absenden, falscher Rechenaufgabe oder erreichtem IP-Limit nichts geschah. Jetzt erscheint ein Hinweis, die Eingaben bleiben erhalten. Nur das versteckte Bot-Feld wird weiterhin still abgewiesen.
- Die Newsletter-Einwilligung bei der Registrierung ist nicht mehr vorab angehakt.
- Gleiche Passwortregeln bei Registrierung, Profil und „Neues Passwort setzen“.
- Nach dem Absenden eines Formulars stehen die Startseiten-Kästen nicht mehr über der Meldung. Unterseiten haben oben keinen leeren Streifen mehr.
- **Suche:** „Suchen in“ wirkt, der Suchbegriff bleibt beim Blättern erhalten, alle Wörter müssen vorkommen, Wörter unter zwei Zeichen werden ignoriert, die Hervorhebung zerstört keine Umlaute mehr.
- **Statistik:** „Gesamtgröße“ mit echtem Umlaut; Tageswerte ab Installation bzw. ältestem Release; Hinweis statt „0 KB“, solange es keine Dateien gibt.
- **Screenshots:** Upload, Anzeige und Aufrufzähler funktionieren (Schema und Verzeichnis passten nicht zum Code). PNG und WebP werden in JPG umgewandelt. Das Großbild zeigt die Beschriftung und einen Link zurück zum Release.
- **Profil:** Nach dem Speichern bleibt das Formular mit einer Meldung sichtbar, bei Fehlern bleiben die Eingaben erhalten.
- **Konto löschen:** Die Navigation zeigt danach sofort den abgemeldeten Zustand.
- Unbekannte Ordner, Releases und Screenshots zeigen „nicht gefunden“ (HTTP 404) statt „Dieser Ordner ist noch leer.“.
- Seitentitel nennen den Release- bzw. Ordnernamen.
- Vorschautexte werden UTF-8-sicher an einer Wortgrenze bzw. an der Trennmarke `{trenn}` gekürzt. Bisher konnte die Kürzung nach Bytes Umlaute und BB-Code zerschneiden.
- Größenangaben einheitlich mit Dezimalkomma.
- Der E-Mail-Schutz ersetzte alle Adressen eines Textes durch die erste.
- `pdl-gfx/favicon.svg` fehlte.

Adminbereich:

- **Release bearbeiten** speicherte Name, Beschreibung, Ordner, Sichtbarkeit und Aufrufe nicht, meldete aber Erfolg.
- **Admin-Optionen auf der Download-Seite** erscheinen je Recht: „Release bearbeiten“ mit „Releases und Dateien bearbeiten“, „Release löschen“ mit „Releases und Dateien löschen“, „Datei hinzufügen“ und „Screenshot hochladen“ mit „… hinzufügen“ oder „… bearbeiten“. Bisher fehlte die Auswahl auf der Release-Seite ganz, solange nicht beide Rechte „bearbeiten“ und „löschen“ vorlagen. Im Ordner steht „Release hinzufügen“ (nur mit „Releases und Dateien hinzufügen“) statt „Datei hinzufügen“, das ohne Release ins Leere führte.
- Ein falsches Passwort bei der Admin-Anmeldung führte auf eine 404-Seite. Jetzt erscheint eine Meldung auf der Anmeldeseite.
- Erfolgsmeldungen erscheinen nur noch, wenn die Datenbank die Änderung bestätigt hat. Sonst zeigt die Seite eine Fehlermeldung.
- „Einstellung“, „Einstellungsgruppe“, „Vorlage“, „Vorlagengruppe“ und „Benutzerrecht hinzufügen“ speicherten nichts. „Einstellungen und Gruppen bearbeiten“ griff auf eine nicht vorhandene Spalte zu und erzeugte je Aufruf 36 Warnungen.
- Ein Ordner ließ sich unter einen eigenen Unterordner hängen und verschwand dann samt Releases. Die Auswahl blendet den eigenen Teilbaum jetzt aus, „Ordner und Releases“ warnt vor Ordnern, die nicht am Index hängen.
- **Übersicht:** zeigte immer „Leider war seit dem letzten Login nichts los.“. Jetzt Kennzahlen (Ordner, Releases, Dateien, Downloads, Benutzer) und die neuen Releases und Kommentare der letzten 14 Tage.
- **Newsletter:** begann „Seit dem 01.01.1970“, enthielt versteckte Releases und relative Links, speicherte das Versanddatum nie und ging ohne Zeichensatz-Kopfzeile hinaus. Jetzt Zeitraum ab dem letzten Versand (sonst 30 Tage), nur sichtbare Releases, absolute Links, Text ohne BB-Code, korrigierte Grammatik, Empfänger nur aus den gewählten Gruppen mit Einwilligung.
- **Benutzergruppe löschen:** Die Mitglieder werden vorher in eine Zielgruppe verschoben. Bisher verschwanden sie aus allen Listen.
- **Benutzer bearbeiten:** Aus `https://…` wurde `http://https://…`. Benutzername und E-Mail-Adresse werden geprüft.
- **Kommentar bearbeiten:** Backslashes bleiben erhalten, der Bearbeitungsvermerk wird ersetzt statt bei jedem Speichern angehängt.
- **Vorlagen:** Strg+S löst keinen „Seite verlassen?“-Dialog mehr aus, Entitäten wie `&amp;` bleiben erhalten, gespeichert werden nur geänderte Vorlagen.
- Beim Löschen von Dateien und Releases verschwinden auch die Dateien unter `pdl-files/` und `pdl-gfx/screens/`. Leer gewordene Release-Verzeichnisse werden entfernt. Eine Datei, die ein anderer Eintrag noch nutzt, bleibt erhalten, auch wenn dessen Adresse anders geschrieben ist (etwa `%20` statt Leerzeichen oder mit Domain).
- **Datei hinzufügen und bearbeiten:** Größe mit Einheit (B, KB, MB, GB) statt in Byte, Prüfung von Name und Adresse, kein Aufruf ohne Datei-ID mehr mit Warnungen. Bekommt eine hochgeladene Datei eine neue Adresse, wird die alte Datei entfernt, sofern sie kein anderer Eintrag nutzt.
- Smileys mit `https://`-Adresse erschienen als kaputtes Bild.
- Der FTP-Browser übergibt Adresse und Größe an „Datei hinzufügen“. `?chdir[]=…` führt nicht mehr zu einem PHP-Fehler, die Blätterlinks sind nicht mehr mehrfach maskiert.
- **Newsletter:** Ein zweites „Senden“ (auch nach Neuladen der Seite) verschickt denselben Newsletter nicht noch einmal. Bricht der Versand ab, setzt ein erneutes „Senden“ beim nächsten Empfänger fort. Versandzeitpunkt und Fortschritt stehen vor der ersten Mail in der Datenbank.
- **Kommentare moderieren:** Neue Seite „Kommentare“ (`pdl-admin/comments.php`, Link „Alle Kommentare anzeigen“ in der Übersicht) mit allen Kommentaren, auch für Moderatoren ohne „Releases und Dateien bearbeiten“. Bisher erreichten sie nur die Kommentare der letzten 14 Tage.
- Ordnerbäume und Navigationspfade brechen bei Ordnern, die im Kreis aufeinander zeigen, nach 32 Ebenen ab, statt die Seite hängen zu lassen.
- **Sicherung einspielen** zählt und zeigt fehlgeschlagene Anweisungen. **Zähler und Kommentare zurücksetzen** läuft in einer Transaktion. **Datenbank optimieren** zeigt Einträge und Größe vorher und nachher, nur für die Tabellen von PowerDownload.
- Einstellungen mit festen Werten (Sortierung, Sortierrichtung, Vorschautext, Screenshot-Bezugsseite) sind Auswahllisten statt Freitext. Ein falscher Wert legte bisher die Release-Listen lahm.

### Neue Funktionen

- Web-Installer `install.php` und Update-Skript `update.php` (siehe oben).
- Einstellungen „Adresse der Download-Seite“ (`site_url`), „Name der Download-Seite“ (`sitename`) und „Gruppe für nicht angemeldete Besucher“ (`guest_group_id`). Damit lassen sich etwa Downloads nur für Mitglieder freigeben.
- Einstellungen als Reiter je Gruppe mit passenden Eingabearten (Schalter, Zahl, Auswahlliste, E-Mail, Adresse, Passwort). Gespeichert werden nur geänderte und geprüfte Werte.
- Newsletter mit Betreff, Vorschau (Anzahl der Empfänger, fertiger Text) und Testmail (vorbelegt mit der eigenen Adresse).
- Übersicht im Adminbereich mit Kennzahlen-Kacheln.
- Ergebnisseiten nach dem Anlegen mit den sinnvollen nächsten Schritten (etwa „Datei hinzufügen“, „Screenshot hochladen“), Bestätigungsseiten mit „Abbrechen“.
- Audit-Log in `pdl3_admin_log` jetzt auch für Löschungen, Benutzer, Einstellungen, Vorlagen, Ersetzungen, Newsletter, Sicherung, Wartung und `update.php` (bisher nur für Releases, Ordner, neue Dateien und die Konto-Löschung). Eine Ansicht im Adminbereich gibt es noch nicht.
- Rückmeldungen nach dem Anmelden („Sie sind jetzt angemeldet.“) und Abmelden („Sie wurden abgemeldet.“).
- Bewertung mit Anzeige „x/10“ auf der Release-Seite und im Kasten „Bestbewertet“.
- Zentraler Mailversand (`pdl-inc/pdl_mail.inc.php`) für alle Mails.

### Oberfläche und Sprache

- Anrede durchgehend „Sie“, echte Umlaute in allen sichtbaren Texten (bisher standen u. a. im Newsletter, in den Einstellungen, beim Ordner-Löschen und in der Statistik Ersatzschreibungen).
- Einheitliche deutsche Begriffe: Benutzer, Benutzergruppe, Einstellungen, Vorlagen, Dateien, Aufrufe, Spiegel-Server, Screenshot, Smiley, Adminbereich, das Release.
- Admin-Menü neu gegliedert: Releases, Ordner, Benutzer, Newsletter, Vorlagen und Ersetzungen, System, Erweitert, Nützliches. „Datenbank-Backup“ heißt jetzt „Sicherung erstellen“, „Backup ausführen“ (spielte eine Sicherung ein) „Sicherung einspielen“, „Download-Datenbank zurücksetzen“ „Zähler und Kommentare zurücksetzen“.
- Der aktuelle Menüpunkt ist hervorgehoben, die Seitenleiste scrollt unabhängig vom Inhalt.
- Keine Browser-Dialoge (`confirm`, `alert`, `beforeunload`) mehr, stattdessen Bestätigungsseiten und Hinweise im Seitenfuß.
- Rechte mit verständlichen Namen und Beschreibungen, die auch sagen, welche Rechte faktisch Admin-Rechte sind.
- Startseiten-Kästen und Kommentarformular in Bootstrap statt alter Tabellen mit `bgcolor`. Leere Kästen zeigen einen Hinweis, „1 Download“ und „2 Downloads“ stehen in Ein- und Mehrzahl.
- Kommentarbereich ohne den wirkungslosen Knopf „Anonym posten“; Gäste sehen einen Hinweis zum Anmelden oder Registrieren.
- Ordnerauswahl mit sichtbarer Einrückung (`└`) statt Strichen.

### Entfernt

- `setup.php`, `install_303.php`, `install_querys.inc`, `update_224to303.php` und `update_301to303.php`. Sie konnten ohne Anmeldung alle Tabellen löschen oder beschädigen. Die Sperre in der `.htaccess` bleibt für Reste auf alten Servern bestehen.
- `.docker/initdb/01-pdl3-init.sql` (ersetzt durch `pdl-inc/pdl3_schema.sql` und `.docker/initdb/02-dev-admin.sql`).
- Der feste Administrator `admin` / `admin123` außerhalb der Docker-Entwicklung.
- Die wirkungslosen Vorlagen `ulogin_form`, `uregister_form`, `ulost_form`, `uprofil_form` und `all_width` aus dem Schema. In bestehenden Installationen bleiben sie liegen, weil `update.php` nichts löscht.
- Der Knopf „Zum Profil“ nach der Registrierung und der Knopf „Login bestätigen“ in der Übersicht.
- `pdl-admin/style.css` (unbenutzt).

### Entwicklung

- `.gitattributes`: Release-Archive (`git archive`, Quellcode-ZIP von GitHub) enthalten nur noch, was auf den Webspace gehört. `.docker/`, `docs/`, `tests/`, `tools/` und die Konfiguration der Prüfwerkzeuge bleiben im Repository. SQL-Dateien immer mit LF.
- `.gitignore`: `pdl-inc/pdl_config.local.php`, `pdl-inc/install.lock`, hochgeladene Dateien in `pdl-files/` und `.build/`.
- Neue Dateien: `pdl-inc/pdl_localconfig.inc.php` (Klasse `PowerDownload\LocalConfig`), `pdl-inc/pdl_mail.inc.php`, `pdl-inc/pdl_setup_required.inc.php`, `pdl-inc/pdl_locks.inc.php` (Sperren je Konto bzw. IP-Adresse, Rücksprung nach der Anmeldung), `pdl-admin/comments.php`, `pdl-admin/system_helpers.inc.php`, `pdl-inc/installer/` (Namensraum `PowerDownload\Installer`, eigener Autoloader, kein Composer auf dem Webspace nötig).
- Datenbankklasse: Port, `sql_error()` und `sql_errno()`, Protokollierung fehlgeschlagener Abfragen.
- Neue Tests: Installer und Update (`tests/Unit/Installer/`: Controller, FormValidator, InstallState, LocalConfig, Schema, Updater, Wizard, Wording), Integrationstest gegen eine eigene Testdatenbank (`tests/Integration/InstallerDatabaseTest.php`), öffentlicher Bereich (`PublicAreaFixesTest`, `ReleaseFlowTest`), Benutzerbereich (`UserCenterTest`), Adminbereich (`AdminInhalteTest`, `SystemHelpersTest`), Sperren (`LocksTest`), Sicherheitskorrekturen (`SecurityFixes36Test`).
- PHPStan und Psalm: Ausschlüsse der entfernten Dateien gestrichen.

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
