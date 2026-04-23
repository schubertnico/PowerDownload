# Userbereichs-Bugs – 2026-04-23

Tester: Senior QA
Basis-URL: http://localhost:8092/
Anwendung: PowerDownload (v3.0.3)
PHP: 8.4 / MySQL: 8.0.45 / Apache/2.4.65 (Debian)

Format je Eintrag:

- **Bereich**
- **URL / Route**
- **Reproduktionsschritte**
- **Erwartet**
- **Tatsächlich**
- **Fehlerart**
- **Schweregrad** (blocker / hoch / mittel / niedrig)
- **Konsole / Stacktrace**
- **Netzwerkhinweise**
- **Status**: Offen
- **Hinweis**: Nicht beheben

---

## BUG-001 – Registrierung: POST-Variablen werden im Header nicht extrahiert (komplette Registrierung kaputt)

- **Bereich**: Registrierung
- **URL / Route**: `POST /downloads.php?usercenter=register&submit=1`
- **Reproduktionsschritte**:
  1. `/downloads.php?usercenter=register` aufrufen
  2. Nickname `qatest_2026_04_23`, E-Mail `qatest_2026_04_23@example.com`, Passwort + Wdh. `TestPW_2026!` ausfüllen
  3. Auf „Registrieren" klicken
- **Erwartet**: Neuer Benutzer wird angelegt, Seite meldet „Anmeldung erfolgreich".
- **Tatsächlich**: Seite zeigt „Bitte geben sie eine Email Adresse an.", obwohl E-Mail-Feld korrekt gesendet wurde.
- **Fehlerart**: Logik-/Architekturfehler (fehlende Variablenübernahme).
- **Schweregrad**: blocker – Registrierung ist vollständig unbrauchbar, alle abhängigen Userbereiche sind dadurch nicht normal erreichbar.
- **Konsole / Stacktrace**: keine JS-Fehler; Server liefert HTTP 200.
- **Netzwerkhinweise**: `POST http://localhost:8092/downloads.php?&usercenter=register&submit=1` → 200.
- **Nachweis im Code**:
  - `pdl-inc/pdl_uregister.modul.php` liest bei `submit==1` die Variablen `$email`, `$pw_new`, `$pw_new2`, `$homepage`, `$icq`, `$get_letter` direkt aus dem lokalen Scope.
  - In `pdl-inc/pdl_header.inc.php` (Zeilen 147–165) werden aber nur `$ordner_id`, `$release_id`, `$screen_id`, `$page`, `$usercenter`, `$show_search`, `$show_stats`, `$wrong_referer`, `$wrong_rights`, `$login`, `$logout`, `$load_file`, `$nick`, `$pw`, `$submit`, `$change_list`, `$orderseq`, `$orderby`, `$perpage` aus `$_GET/$_POST` extrahiert – `email`, `pw_new`, `pw_new2`, `homepage`, `icq`, `get_letter` fehlen vollständig.
  - Da kein `extract($_POST)` mehr aktiv ist (war PHP 5/register_globals-Verhalten), bleiben diese Variablen in PHP 8.4 leer – die Validierung schlägt fehl.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-002 – Passwort-Hash-Mismatch zwischen Registrierung und Login (Login dauerhaft unmöglich für neue User)

- **Bereich**: Login / Registrierung
- **URL / Route**: `POST /downloads.php?usercenter=register&submit=1` (Insert) vs. `POST /downloads.php?login=1` (Check)
- **Reproduktionsschritte** (hypothetisch, da Registrierung bereits kaputt ist):
  1. User wird angelegt mit `password_hash($pw_new, PASSWORD_DEFAULT)` (bcrypt).
  2. Login versucht mit `md5($pw)` gegen Spalte `passwort`.
- **Erwartet**: Identischer Hash-Algorithmus auf beiden Seiten, Login möglich.
- **Tatsächlich**: Registrierung speichert bcrypt-Hash, Login vergleicht mit MD5 → SQL liefert 0 Zeilen, Login schlägt immer fehl.
- **Fehlerart**: Logik-/Sicherheitsfehler; inkompatible Hashing-Strategien.
- **Schweregrad**: blocker – Nutzer könnten sich niemals einloggen, selbst wenn Registrierung funktionierte. (Nur der Seed-Admin mit MD5-Hash aus der Installation funktioniert.)
- **Konsole / Stacktrace**: keine.
- **Netzwerkhinweise**: HTTP 200, Server setzt keine `login_id`/`login_pw` Cookies, Redirect nach Login findet nicht statt.
- **Nachweis im Code**:
  - `pdl_uregister.modul.php` Zeile 32: `$pw_hash = password_hash($pw_new, PASSWORD_DEFAULT);`
  - `pdl_header.inc.php` Zeile 214: `$pw_hash = md5($pw);`
  - `pdl_ulost2.modul.php` Zeile 11: `$pw_hash = password_hash($pw_new, PASSWORD_DEFAULT);` – nach einem Passwort-Reset wäre ein User ebenfalls ausgesperrt.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-003 – Cookie-basiertes Login speichert Passwort-Hash statt Session-Token (Pass-the-Hash-Risiko)

- **Bereich**: Session / Login-Persistenz
- **URL / Route**: Cookies `login_id` + `login_pw`, gesetzt in `pdl_header.inc.php`.
- **Reproduktionsschritte**:
  1. Erfolgreicher Login (z. B. admin).
  2. Cookie `login_pw` enthält unverändert den gespeicherten Passwort-Hash.
  3. Beim nächsten Request wird der Cookie-Wert direkt als Passwort gegen die DB geprüft (`… AND passwort='" . $login_pw_escaped . "'`).
- **Erwartet**: Session-Token bzw. signiertes HMAC-Token, keine Speicherung sensibler Credentials im Cookie.
- **Tatsächlich**: Password-Hash wird unverschlüsselt im Client-Cookie gespeichert. Jeder mit Zugriff auf den Hash kann sich dauerhaft authentifizieren (Pass-the-Hash, bis zur Passwortänderung).
- **Fehlerart**: Sicherheitslücke (Authentifizierung).
- **Schweregrad**: hoch (Sicherheitsrelevant).
- **Konsole / Stacktrace**: keine.
- **Netzwerkhinweise**: Cookies `login_id`, `login_pw` mit `HttpOnly`/`SameSite=Strict`, aber ohne `Secure`-Flag.
- **Nachweis im Code**: `pdl_header.inc.php` Zeilen 178–200 und 219–230.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-004 – Login-Check verwendet MD5 (unsicheres Hashverfahren)

- **Bereich**: Authentifizierung
- **URL / Route**: `POST /downloads.php?login=1`
- **Reproduktionsschritte**: Quellcode-Analyse + Login-Test mit admin/admin123 (MD5 `0192023a7bbd73250516f069df18b500`) → erfolgreich.
- **Erwartet**: Login prüft per `password_verify()` gegen bcrypt/argon2-Hash.
- **Tatsächlich**: Login verwendet ungesalzenes `md5($pw)`.
- **Fehlerart**: Sicherheitslücke (schwacher Hash, Rainbow-Table-Angriff, keine Salts).
- **Schweregrad**: hoch.
- **Konsole / Stacktrace**: keine.
- **Netzwerkhinweise**: nicht anwendbar.
- **Nachweis im Code**: `pdl-inc/pdl_header.inc.php` Zeile 214.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-005 – Falsches Passwort beim Login zeigt keine Fehlermeldung (silent failure)

- **Bereich**: Login
- **URL / Route**: `POST /downloads.php?login=1`
- **Reproduktionsschritte**:
  1. `/downloads.php?usercenter=login` öffnen
  2. Nickname `admin`, Passwort `wrong_password` eingeben
  3. Absenden
- **Erwartet**: Fehlermeldung („Ungültiger Benutzername oder Passwort").
- **Tatsächlich**: Redirect (HTTP 200) auf die Startseite; Navigation zeigt immer noch „Login / Anmelden" → User erkennt, dass Login fehlschlug, erhält aber keinerlei Feedback. Passwortfeld ist auch nicht leer, weil Seite neu geladen wurde.
- **Fehlerart**: UX-/Funktionsfehler.
- **Schweregrad**: hoch.
- **Konsole / Stacktrace**: keine.
- **Netzwerkhinweise**: POST → 200, kein Set-Cookie für login_id/login_pw.
- **Nachweis im Code**: `pdl_header.inc.php` Zeilen 213–240 – im Negativ-Zweig wird kein Fehlermarker gesetzt, kein Redirect mit Fehlerparameter, keine `echo`-Meldung.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-006 – Passwort-vergessen gibt existenzbasierte Fehlermeldungen aus (User-Enumeration)

- **Bereich**: Passwort vergessen
- **URL / Route**: `POST /downloads.php?usercenter=lost&submit=1`
- **Reproduktionsschritte**:
  1. `/downloads.php?usercenter=lost` öffnen
  2. Einmal `admin@example.com` (existiert), einmal `fake@xyz.de` (existiert nicht) eingeben
  3. Antworten vergleichen
- **Erwartet**: Gleiche generische Antwort in beiden Fällen („Wenn die E-Mail existiert, wird eine Mail versendet").
- **Tatsächlich**:
  - Existierende E-Mail: „Bestaetigungsmail versendet." (hier aber wegen BUG-001 auch bei existierender E-Mail „Kein Benutzer …" erscheint, da `$email` leer bleibt).
  - Nicht existierende E-Mail: „Kein Benutzer mit dieser E-Mail Adresse gefunden."
- **Fehlerart**: Sicherheits-/Privacy-Fehler (User-Enumeration).
- **Schweregrad**: mittel.
- **Konsole / Stacktrace**: keine.
- **Netzwerkhinweise**: POST → 200.
- **Nachweis im Code**: `pdl-inc/pdl_ulost.modul.php` Zeilen 11–27.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-007 – Profil-Update funktioniert nicht (gleiche Root-Cause wie BUG-001)

- **Bereich**: Profil
- **URL / Route**: `POST /downloads.php?usercenter=profil&submit=1`
- **Reproduktionsschritte**:
  1. Als `admin/admin123` einloggen
  2. `/downloads.php?usercenter=profil` öffnen
  3. Homepage `https://example.com`, ICQ `12345678`, Altes Passwort `admin123` eingeben
  4. Speichern klicken
- **Erwartet**: Profil wird gespeichert, Meldung „Profil erfolgreich geaendert."
- **Tatsächlich**: Meldung „Altes Passwort ist falsch.", obwohl `admin123` korrekt ist.
- **Fehlerart**: Logik-/Architekturfehler (Variablen `$email`, `$pw_old`, `$pw_new`, `$pw_new2`, `$homepage`, `$icq`, `$get_letter` werden nie aus `$_POST` extrahiert – siehe BUG-001).
- **Schweregrad**: blocker – Profil kann nicht bearbeitet werden.
- **Konsole / Stacktrace**: keine.
- **Netzwerkhinweise**: POST → 200.
- **Nachweis im Code**: `pdl-inc/pdl_uprofil.modul.php` Zeilen 7–16 + `pdl_header.inc.php` (siehe BUG-001).
- **Status**: Offen
- **Nicht beheben**

---

## BUG-008 – Passwort-vergessen: E-Mail-Feld wird nicht gelesen (gleiche Root-Cause wie BUG-001)

- **Bereich**: Passwort vergessen
- **URL / Route**: `POST /downloads.php?usercenter=lost&submit=1`
- **Reproduktionsschritte**:
  1. `/downloads.php?usercenter=lost` öffnen
  2. `admin@example.com` eingeben (dieser User existiert)
  3. Absenden
- **Erwartet**: „Bestaetigungsmail versendet.", remind_code in DB gesetzt.
- **Tatsächlich**: „Kein Benutzer mit dieser E-Mail Adresse gefunden." – obwohl der admin-Account existiert.
- **Fehlerart**: Logik-/Architekturfehler. `$email` wird im Modul erwartet, aber im Header nicht aus `$_POST` extrahiert.
- **Schweregrad**: blocker.
- **Konsole / Stacktrace**: keine.
- **Netzwerkhinweise**: POST → 200.
- **Nachweis im Code**: `pdl-inc/pdl_ulost.modul.php` Zeile 10.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-009 – Passwort-Reset (lost2) erwartet `remind_code` aus URL, wird aber nicht aus GET extrahiert

- **Bereich**: Passwort vergessen – Schritt 2
- **URL / Route**: `GET /downloads.php?usercenter=lost2&remind_code=…`
- **Reproduktionsschritte** (hypothetisch, da lost1 nicht funktioniert):
  1. Gültigen `remind_code` aus DB-Spalte `remind_code` verwenden und Link `/downloads.php?usercenter=lost2&remind_code=<code>` aufrufen.
- **Erwartet**: Neues Passwort wird generiert, per E-Mail versendet.
- **Tatsächlich**: `$remind_code` ist leer, weil im Header nicht aus `$_GET` extrahiert → es wird der leere Code gegen DB geprüft, `"Kein Benutzer mit diesem Bestaetigungscode gefunden."` erscheint.
- **Fehlerart**: Logik-/Architekturfehler (gleiche Root-Cause wie BUG-001).
- **Schweregrad**: blocker.
- **Konsole / Stacktrace**: keine.
- **Netzwerkhinweise**: GET → 200.
- **Nachweis im Code**: `pdl-inc/pdl_ulost2.modul.php` Zeile 7.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-010 – Kommentar-Post funktioniert nicht (gleiche Root-Cause wie BUG-001)

- **Bereich**: Kommentare
- **URL / Route**: `POST /downloads.php?usercenter=comments&submit=1&release_id=<id>`
- **Reproduktionsschritte** (hypothetisch, da keine Releases existieren):
  1. Release aufrufen, Kommentarformular ausfüllen, absenden.
- **Erwartet**: Kommentar wird gespeichert.
- **Tatsächlich**: `$titel` und `$text` werden im Modul erwartet, aber im Header nicht aus `$_POST` extrahiert. Ergebnis: „Bitte Titel und Text eingeben." erscheint unabhängig von Eingabe.
- **Fehlerart**: Logik-/Architekturfehler (gleiche Root-Cause wie BUG-001).
- **Schweregrad**: hoch (Feature komplett unbrauchbar, sobald Releases existieren).
- **Konsole / Stacktrace**: keine.
- **Netzwerkhinweise**: POST → 200.
- **Nachweis im Code**: `pdl-inc/pdl_ucomments.modul.php` Zeilen 11–12.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-011 – Startseite zeigt leere Info-Boxen (Statistik/Top/Flop/Latest/Rated fehlen visuell)

- **Bereich**: Startseite
- **URL / Route**: `GET /downloads.php`
- **Reproduktionsschritte**:
  1. `/` öffnen
- **Erwartet**: Die fünf Info-Boxen (Stats, Top-Downloads, Flop, Latest, Rated) sollten sichtbar sein (Spalten-Layout).
- **Tatsächlich**: Seite rendert nur Navigation + „Dieser Ordner ist leer." + Footer. Die `<td>`-Spalten der Info-Tabelle sind offenbar unsichtbar (weißer Text auf schwarzem Grund, Boxen leer).
- **Fehlerart**: UI-/Template-/CSS-Fehler. Es werden zwar `pdl_stats.inc.php`, `pdl_top.inc.php` usw. eingebunden, aber sie liefern keinen sichtbaren Output auf der Home-Seite, obwohl die Stats-Seite (`?show_stats=1`) Daten zeigt (DB Version, 14 Tabellen, 25 Einträge).
- **Schweregrad**: mittel.
- **Konsole / Stacktrace**: keine.
- **Netzwerkhinweise**: 200.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-012 – "Admin-Optionen" werden auf Release-Detailseite auch bei nicht existierendem Release angezeigt

- **Bereich**: Release-Detail
- **URL / Route**: `GET /downloads.php?release_id=999` (als Admin eingeloggt)
- **Reproduktionsschritte**:
  1. Als admin einloggen
  2. `/downloads.php?release_id=999` aufrufen (Release existiert nicht)
- **Erwartet**: „Release nicht gefunden." ohne Admin-Buttons, oder Buttons führen nur zu existierenden Releases.
- **Tatsächlich**: Neben „Release nicht gefunden." werden „Release Editieren / Datei hinzufügen / Screenshot hochladen / Release Löschen" angezeigt. Die Links führen auf nicht-existierende IDs und können im Admin-Bereich Folgefehler produzieren.
- **Fehlerart**: UI-Logikfehler.
- **Schweregrad**: niedrig (nur Admins, kein Datenverlust direkt sichtbar).
- **Konsole / Stacktrace**: keine.
- **Netzwerkhinweise**: 200.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-013 – Schema `install_querys.inc` verwendet `TYPE=MyISAM` (inkompatibel mit MySQL 5.5+)

- **Bereich**: Installation / Setup
- **URL / Route**: `install_querys.inc` (Setup-Prozess via `install_303.php` / `update_*.php`)
- **Reproduktionsschritte**: Code-Analyse. `TYPE=MyISAM` ist seit MySQL 5.5 entfernt, MySQL 8.0 lehnt das Keyword ab.
- **Erwartet**: `ENGINE=MyISAM` (oder besser `ENGINE=InnoDB`).
- **Tatsächlich**: `install_querys.inc` enthält mehrfach `TYPE=MyISAM` (z. B. Zeile 185). Setup würde auf MySQL 8 scheitern oder Warnungen erzeugen. Im laufenden Container-Schema sind die Tabellen vermutlich mit anderem Mechanismus erzeugt worden, daher läuft es – Fresh-Installs sind aber broken.
- **Fehlerart**: Installations-/Kompatibilitätsfehler.
- **Schweregrad**: hoch bei Neuinstallation, mittel sonst.
- **Konsole / Stacktrace**: nicht getestet (Setup nicht ausgeführt).
- **Netzwerkhinweise**: n/a.
- **Nachweis im Code**: `install_querys.inc` mehrfach.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-014 – Fehlermeldungen verwenden `javascript:history.back()` statt Formular-Reanzeige mit Fehlern

- **Bereich**: Registrierung, Kommentare, Profil (alle mit Validierung)
- **URL / Route**: diverse
- **Reproduktionsschritte**:
  1. Register-Formular fehlerhaft abschicken → „Bitte geben sie einen Nickname an. Zurueck" (Link mit `javascript:history.back()`).
- **Erwartet**: Formular wird mit Fehlerhinweis am betreffenden Feld und befüllten Werten erneut angezeigt.
- **Tatsächlich**: Reiner Text + JS-Back-Link. Eingegebene Werte gehen teilweise verloren. Ohne JS unbrauchbar.
- **Fehlerart**: UX-Fehler / Accessibility.
- **Schweregrad**: mittel.
- **Konsole / Stacktrace**: keine.
- **Netzwerkhinweise**: n/a.
- **Nachweis im Code**: `pdl_uregister.modul.php`, `pdl_ucomments.modul.php`.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-015 – Registrierung validiert E-Mail-Adresse nicht (syntaktisch)

- **Bereich**: Registrierung
- **URL / Route**: `POST /downloads.php?usercenter=register&submit=1`
- **Reproduktionsschritte**: Code-Analyse – es fehlt `filter_var($email, FILTER_VALIDATE_EMAIL)`.
- **Erwartet**: Server lehnt offensichtlich ungültige Adressen ab (z. B. `abc`, `abc@`, `@xyz`).
- **Tatsächlich**: Jeder non-empty String wird akzeptiert, lediglich Duplikat-Check in DB. (Aktuell aber ohnehin durch BUG-001 blockiert.)
- **Fehlerart**: Datenqualitäts-/UX-Fehler.
- **Schweregrad**: niedrig (kann SPAM-Registrierungen fördern).
- **Nachweis im Code**: `pdl_uregister.modul.php` Zeilen 18–27.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-016 – Registrierung-Mail versendet Passwort im Klartext

- **Bereich**: Registrierung
- **URL / Route**: `POST /downloads.php?usercenter=register&submit=1`
- **Reproduktionsschritte**: Code-Analyse.
- **Erwartet**: Mail enthält keine Credentials, höchstens Bestätigungslink.
- **Tatsächlich**: `mail_register`-Template wird mit `{nick}` + `{pw}` ersetzt und per E-Mail versendet → Klartext-Passwort in Mailservern.
- **Fehlerart**: Sicherheitsfehler (Privacy, E-Mail-Logs).
- **Schweregrad**: hoch.
- **Konsole / Stacktrace**: n/a.
- **Netzwerkhinweise**: n/a.
- **Nachweis im Code**: `pdl_uregister.modul.php` Zeile 40.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-017 – Passwort-Reset generiert ausschließlich serverseitig ein Passwort und versendet es per Mail

- **Bereich**: Passwort vergessen – Schritt 2
- **URL / Route**: `GET /downloads.php?usercenter=lost2&remind_code=…`
- **Reproduktionsschritte**: Code-Analyse.
- **Erwartet**: User bekommt Link zu einer Reset-Seite, auf der er selbst ein neues Passwort wählt.
- **Tatsächlich**: Server generiert per `generate_string(16)` ein neues Passwort und schickt es per E-Mail zurück (`mail_lost2`). Klartext-Passwort in Mailservern, User kann es nicht selbst setzen.
- **Fehlerart**: Sicherheits-/UX-Fehler.
- **Schweregrad**: mittel.
- **Konsole / Stacktrace**: n/a.
- **Netzwerkhinweise**: n/a.
- **Nachweis im Code**: `pdl_ulost2.modul.php` Zeilen 10–23.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-018 – Kein CSRF-Schutz auf POST-Endpunkten

- **Bereich**: alle Formulare (Login, Register, Profil, Lost, Comments)
- **URL / Route**: diverse `POST /downloads.php?…`
- **Reproduktionsschritte**: Code-Analyse. Keine Token-Ausgabe in Templates, keine Token-Prüfung in Modulen.
- **Erwartet**: CSRF-Token pro Session/Form, Prüfung bei allen POSTs.
- **Tatsächlich**: Kein Token. Ein bösartiger Third-Party kann via `<form action="http://localhost:8092/downloads.php?login=1" method="POST">` auf externer Seite z. B. Profiländerungen auslösen.
- **Fehlerart**: Sicherheitslücke (OWASP A01).
- **Schweregrad**: hoch.
- **Konsole / Stacktrace**: n/a.
- **Netzwerkhinweise**: n/a.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-019 – Kein Rate-Limit / Brute-Force-Schutz auf Login

- **Bereich**: Login
- **URL / Route**: `POST /downloads.php?login=1`
- **Reproduktionsschritte**: Mehrere falsche Login-Versuche in Folge → keine Sperre, keine Verzögerung, keine Captcha.
- **Erwartet**: Rate-Limit (z. B. 5 Versuche / IP / 15 min) oder Captcha nach n Fehlversuchen. Optional IP-Lock via `pdl3_iplock`.
- **Tatsächlich**: Unbegrenzte Versuche möglich. Tabelle `pdl3_iplock` existiert zwar, wird aber im Login-Pfad nicht verwendet.
- **Fehlerart**: Sicherheitslücke (Brute-Force, Credential-Stuffing).
- **Schweregrad**: hoch.
- **Konsole / Stacktrace**: n/a.
- **Netzwerkhinweise**: n/a.
- **Nachweis im Code**: `pdl_header.inc.php` Zeilen 213–240.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-020 – Ordner-Modul ignoriert URL-Parameter `orderby`/`orderseq`/`perpage`

- **Bereich**: Ordner / Release-Liste
- **URL / Route**: `GET /downloads.php?ordner_id=…&orderby=name&orderseq=DESC&perpage=20`
- **Reproduktionsschritte**:
  1. Ordner aufrufen und versuchen `orderby`/`orderseq`/`perpage` über URL zu steuern.
- **Erwartet**: Sortierung/Paginierung richtet sich nach URL-Parametern.
- **Tatsächlich**: `pdl_ordner.modul.php` überschreibt `$orderby` mit `$settings['orderby']` usw. (Zeilen 48, 57, 58). URL-Parameter sind wirkungslos.
- **Fehlerart**: Funktions-/Logikfehler (Parameter werden in der UI verlinkt, aber serverseitig ignoriert).
- **Schweregrad**: niedrig.
- **Konsole / Stacktrace**: n/a.
- **Netzwerkhinweise**: n/a.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-021 – Ordner-Modul setzt `$orderby` direkt in SQL ein (SQLi-Risiko bei manipulierten Settings)

- **Bereich**: Ordner / Release-Liste
- **URL / Route**: `GET /downloads.php?ordner_id=…`
- **Reproduktionsschritte**: Code-Analyse.
- **Erwartet**: `$orderby` nur gegen Whitelist (`name`, `date`, `size` …) prüfen, nie direkt interpolieren.
- **Tatsächlich**: `pdl_ordner.modul.php` Zeile 59: `… ORDER BY " . $orderby . " " . $orderseq . " LIMIT …"`. `$orderby` stammt aus `$settings['orderby']`, das wiederum DB-seitig gepflegt wird. Sobald ein Admin einen manipulierten Setting-Wert einträgt oder ein zweiter Bug (z. B. Admin-XSS) ihn setzen kann, ist SQL-Injection möglich.
- **Fehlerart**: Sicherheitsrisiko (Second-Order SQLi).
- **Schweregrad**: niedrig–mittel.
- **Konsole / Stacktrace**: n/a.
- **Netzwerkhinweise**: n/a.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-022 – HTML verwendet Pre-HTML5 Attribute (`bgcolor`, `text`, `<center>`)

- **Bereich**: gesamter Userbereich / Templates
- **URL / Route**: alle Seiten.
- **Reproduktionsschritte**: View-Source der Startseite zeigt `<body bgcolor="#000000" text="#FFFFFF">` und `<center>`-Tags.
- **Erwartet**: CSS-basiertes Styling, HTML5-konform.
- **Tatsächlich**: HTML3.2-Stil in HTML5-Dokument (`<!DOCTYPE html>`). Attribute wie `bgcolor`, `text`, Tags wie `<center>` sind veraltet, Browser rendern aber noch.
- **Fehlerart**: Technischer Rückstand (Markup-Qualität, Accessibility, Dark-Mode inkonsistent).
- **Schweregrad**: niedrig.
- **Konsole / Stacktrace**: n/a.
- **Netzwerkhinweise**: n/a.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-023 – Checkbox-Label für Newsletter ist nur "Y" (nicht barrierefrei)

- **Bereich**: Registrierung, Profil
- **URL / Route**: `?usercenter=register`, `?usercenter=profil`
- **Reproduktionsschritte**: Formular öffnen.
- **Erwartet**: Label „Ja, ich möchte den Newsletter abonnieren" oder vergleichbar, ggf. Checkbox-Text.
- **Tatsächlich**: Checkbox hat value `Y` als Label (so zeigt a11y-Tree `checkbox "Y"`). Screenreader lesen nur „Y".
- **Fehlerart**: Accessibility / UX.
- **Schweregrad**: niedrig.
- **Konsole / Stacktrace**: n/a.
- **Netzwerkhinweise**: n/a.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-024 – @-Operator unterdrückt Fehler beim Mailversand (keine Logging / stille Fehler)

- **Bereich**: Registrierung, Passwort vergessen (1 + 2)
- **URL / Route**: diverse
- **Reproduktionsschritte**: Code-Analyse.
- **Erwartet**: Fehler beim Mailversand werden geloggt und ggf. dem Admin angezeigt.
- **Tatsächlich**: `@mail(...)` unterdrückt Fehler, Benutzer erhält „Bestaetigungsmail versendet." auch wenn Versand scheiterte.
- **Fehlerart**: Observability/Operationsfehler.
- **Schweregrad**: mittel.
- **Konsole / Stacktrace**: n/a.
- **Netzwerkhinweise**: n/a.
- **Nachweis im Code**: `pdl_uregister.modul.php` Z.45, `pdl_ulost.modul.php` Z.27, `pdl_ulost2.modul.php` Z.21.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-025 – Form-Action-Attribut wurde durch Chrome-Dev-Proxy als "BLOCKED: Cookie/query string data" erkannt (Hinweis)

- **Bereich**: Registrierung
- **URL / Route**: `/downloads.php?usercenter=register`
- **Reproduktionsschritte**: `document.querySelector('form').action` im DevTools auslesen.
- **Erwartet**: `http://localhost:8092/downloads.php?usercenter=register&submit=1`
- **Tatsächlich**: Der Wert wird vom Sicherheitsfilter des Chrome-Tooling als „BLOCKED: Cookie/query string data" markiert – der Action-URL enthält Query-String-Daten, die als „sensitive query string" interpretiert werden. Zeigt strukturell, dass Formular-URLs zu viel Zustand in Query-Params tragen.
- **Fehlerart**: Design-/UX-/Tooling-Interaktionsfehler.
- **Schweregrad**: niedrig (nur Tooling, funktioniert im Browser).
- **Konsole / Stacktrace**: n/a.
- **Netzwerkhinweise**: n/a.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-026 – Homepage-Eingabe wird bei `http://`-Präfix automatisch umgeschrieben, aber ohne Validierung

- **Bereich**: Registrierung, Profil
- **URL / Route**: diverse
- **Reproduktionsschritte**: Code-Analyse: `if ($homepage && !preg_match("!^https?://!", $homepage)) { $homepage = "http://" . $homepage; }`
- **Erwartet**: Server prüft, ob es sich um eine gültige URL handelt; minimum FILTER_VALIDATE_URL.
- **Tatsächlich**: Jeder Schrott wird mit `http://` präfigiert. Z. B. „javascript:alert(1)" wird nicht zu `http://…`, weil es mit `javascript:` beginnt und nicht mit `http(s)://` – ohne Validierung wird so ein Wert in die Homepage-Spalte gespeichert und später im Profil-Template ausgegeben → potenziell XSS.
- **Fehlerart**: Sicherheits-/Datenqualitätsfehler.
- **Schweregrad**: mittel.
- **Konsole / Stacktrace**: n/a.
- **Netzwerkhinweise**: n/a.
- **Nachweis im Code**: `pdl_uregister.modul.php` Z.29–31, `pdl_uprofil.modul.php` Z.30–35.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-027 – Admin kennt Benutzer-Tabelle nur mit 7 Spalten (fehlen `get_letter`, `remind_code`, `lastactive`, `signatur`)

- **Bereich**: Datenbank-Schema
- **URL / Route**: n/a (DB)
- **Reproduktionsschritte**: `DESCRIBE pdl3_user` im Container → nur 7 Spalten sichtbar (user_id, nick, email, passwort, ugroup_id, icq, homepage).
- **Erwartet**: Alle Spalten des install-Skripts (`install_querys.inc` Zeilen 170–185) vorhanden (u. a. `get_letter`, `remind_code`, `lastactive`, `signatur`).
- **Tatsächlich**: Tabelle scheint ein kleineres Subset zu enthalten. Damit laufen Register-Inserts `… (nick,email,passwort,homepage,icq,get_letter,ugroup_id,lastactive) …` auf SQL-Error (auch wenn BUG-001 vorher blockt).
- **Fehlerart**: Daten-/Schema-Inkonsistenz zwischen Install-Skript und Docker-Seed.
- **Schweregrad**: hoch (auch wenn BUG-001 behoben würde, würde Register dann am SQL scheitern).
- **Konsole / Stacktrace**: potenziell `mysql_query` Fehler (silent im Handler).
- **Netzwerkhinweise**: n/a.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-028 – Cookie `Secure`-Flag fehlt

- **Bereich**: Session-Cookies
- **URL / Route**: `pdl_header.inc.php` `setcookie(…)` für `login_id`, `login_pw`.
- **Reproduktionsschritte**: Cookie-Options anschauen.
- **Erwartet**: Bei HTTPS-Deployment `secure => true` setzen, damit Cookies nicht über HTTP übertragen werden.
- **Tatsächlich**: `secure` fehlt im Options-Array. Unter HTTPS würde der Browser die Cookies trotzdem über HTTP senden, falls versehentlich ein HTTP-Request erfolgt.
- **Fehlerart**: Sicherheitsfehler (Cookie-Hijacking).
- **Schweregrad**: mittel (abhängig vom Deployment).
- **Konsole / Stacktrace**: n/a.
- **Netzwerkhinweise**: n/a.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-029 – „change_list"-Parameter ist Dead-Code (wird extrahiert, aber nirgends genutzt)

- **Bereich**: Routing / Parameter
- **URL / Route**: `?change_list=…`
- **Reproduktionsschritte**: Grep nach `$change_list` im Quellcode.
- **Erwartet**: Parameter wirkt entweder auf UI oder wird entfernt.
- **Tatsächlich**: Wird nur im Header extrahiert; keine Verwendung in Modulen (getestet mit `grep`).
- **Fehlerart**: Dead-Code.
- **Schweregrad**: niedrig.
- **Konsole / Stacktrace**: n/a.
- **Netzwerkhinweise**: n/a.
- **Status**: Offen
- **Nicht beheben**

---

## BUG-030 – Eingeloggter Admin kann `usercenter=register` aufrufen (sollte abgefangen sein, tut es aber nicht konsequent)

- **Bereich**: Registrierung / Navigation
- **URL / Route**: `/downloads.php?usercenter=register` mit gültigem Login
- **Reproduktionsschritte**:
  1. Als admin einloggen
  2. Manuell `?usercenter=register` aufrufen
- **Erwartet**: Keine Registrierungsform bzw. Meldung „Bereits angemeldet".
- **Tatsächlich**: Wenn kein submit, zeigt das Modul „Sie sind bereits angemeldet …"; solange aber `submit=1` auf URL steht, versucht es trotzdem ein Insert. Das ist kein kritischer Bug, aber bei Vorbelegung von Feldern inkonsistent. Die Navigation verlinkt auch noch auf das Anmelden-Formular (eingeloggte User sehen keinen „Anmelden"-Link, aber die URL bleibt erreichbar).
- **Fehlerart**: Logikinkonsistenz.
- **Schweregrad**: niedrig.
- **Konsole / Stacktrace**: n/a.
- **Netzwerkhinweise**: n/a.
- **Status**: Offen
- **Nicht beheben**

---
