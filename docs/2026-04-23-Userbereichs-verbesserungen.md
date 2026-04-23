# Userbereichs-Verbesserungen – 2026-04-23

Tester: Senior QA
Basis-URL: http://localhost:8092/
Anwendung: PowerDownload (v3.0.3)

Format je Eintrag:

- **Bereich**
- **URL / Route**
- **Beobachtung**
- **Problem im Workflow**
- **Auswirkung**
- **Verbesserungsvorschlag**
- **Priorität** (hoch / mittel / niedrig)

---

## IMP-001 – Zentrale Validierung und Extraktion von Request-Daten

- **Bereich**: gesamter Userbereich
- **URL / Route**: `pdl-inc/pdl_header.inc.php`
- **Beobachtung**: Module erwarten lokale Variablen (`$email`, `$pw_new`, `$titel`, `$text`, `$remind_code`, `$pw_old`, `$homepage`, `$icq`, `$get_letter`), die aber nur zum Teil im Header extrahiert werden. Ergebnis: Alle Userbereichs-Formulare sind de facto funktionslos (siehe BUG-001, 007, 008, 009, 010).
- **Problem im Workflow**: Jede Änderung an Request-Parametern erfordert doppelte Pflege zwischen Header und Modul; neue Felder werden leicht vergessen.
- **Auswirkung**: Broken Features (Register, Profil, Lost1/2, Comments) – blockiert die gesamte User-Funktionalität.
- **Verbesserungsvorschlag**: Ein zentrales `Request`-Value-Object (oder zumindest ein dedizierter Include `pdl_request.inc.php`) pro Modul. Dort alle für das Modul nötigen Variablen aus `$_POST`/`$_GET` typisiert extrahieren (inkl. Whitelist, Typ-Casts, Default-Werte). Module greifen nicht mehr auf lokale Variablen zu, sondern auf `$request->email()` etc.
- **Priorität**: hoch.

---

## IMP-002 – Modernes Authentifizierungs-System (Sessions, password_hash, keine Cookie-Hashes)

- **Bereich**: Login, Session
- **URL / Route**: `pdl_header.inc.php`, `pdl_ulogin.modul.php`
- **Beobachtung**: MD5-Hashing + Passwort-Hash im Cookie (`login_pw`).
- **Problem im Workflow**: Unsichere Authentifizierung, Pass-the-Hash, kein Secure-Flag, kein SameSite=Lax Default.
- **Auswirkung**: Account-Übernahme denkbar, falls Cookies exfiltriert werden; kein zeitlicher Ablauf der Session.
- **Verbesserungsvorschlag**:
  1. `session_start()` mit sicheren Session-Cookie-Optionen (`httponly`, `samesite=Lax`, `secure` je nach HTTPS).
  2. Passwort-Hashes ausschließlich mit `password_hash()` + `password_verify()`.
  3. Rolling Session-Regeneration nach Login.
  4. „Remember me" über eigenständige, rotierte Tokens in der DB – nicht über Cookie-Hash.
  5. Bestehende MD5-Hashes beim ersten erfolgreichen Login mit `password_needs_rehash()` migrieren.
- **Priorität**: hoch.

---

## IMP-003 – CSRF-Schutz auf allen POST-Endpunkten

- **Bereich**: Register, Login, Lost, Profil, Comments
- **URL / Route**: alle POST-Forms
- **Beobachtung**: Keine CSRF-Token.
- **Problem im Workflow**: Jeder eingeloggte User kann durch eine manipulierte Seite zu ungewolltem Profil-Update, Kommentar-Post, etc. bewegt werden.
- **Auswirkung**: OWASP A01 – Broken Access Control.
- **Verbesserungsvorschlag**: Hash/HMAC-basierten Token generieren (pro Session) und in Forms als Hidden-Input ausgeben. Server validiert vor jeder Verarbeitung. Helper `csrf_token()` / `csrf_verify()`.
- **Priorität**: hoch.

---

## IMP-004 – Login-Fehlerkommunikation und Rate-Limiting

- **Bereich**: Login
- **URL / Route**: `POST /downloads.php?login=1`
- **Beobachtung**: Falsches Passwort → stiller Redirect, kein Feedback; unbegrenzt Versuche.
- **Problem im Workflow**: Nutzer weiß nicht, warum Login fehlschlägt; Angreifer kann Brute-Forcen.
- **Auswirkung**: Frustration, Brute-Force, Credential-Stuffing.
- **Verbesserungsvorschlag**:
  1. Bei Fehlschlag: Redirect `?usercenter=login&err=1` mit generischer Meldung („Benutzername oder Passwort falsch").
  2. Rate-Limit per `pdl3_iplock` (bereits vorhanden) + Lockout nach n Fehlern.
  3. Optional: Delay exponential bei Wiederholfehlern.
- **Priorität**: hoch.

---

## IMP-005 – Benutzerfreundliche Formvalidierung statt `history.back()`

- **Bereich**: Registrierung, Profil, Comments
- **URL / Route**: diverse
- **Beobachtung**: Fehlertexte enden mit „<a href='javascript:history.back()'>Zurueck</a>". Daten müssen neu eingegeben werden.
- **Problem im Workflow**: Nutzer verliert Eingaben, muss Formular erneut ausfüllen.
- **Auswirkung**: Hohe Abbruchrate bei Registrierungen / Profil-Änderungen.
- **Verbesserungsvorschlag**: Formular wird serverseitig mit Werten re-rendered und Fehler am entsprechenden Feld markiert. Bei inlined Client-Validation (HTML5 `required`, `pattern`) zusätzliche Hilfen bieten.
- **Priorität**: mittel.

---

## IMP-006 – Passwort-Reset per Link statt per E-Mail mit Klartext-Passwort

- **Bereich**: Passwort vergessen
- **URL / Route**: `?usercenter=lost2`
- **Beobachtung**: Server generiert neues Passwort und sendet es per E-Mail; User hat keine Kontrolle.
- **Problem im Workflow**: Klartext-Passwort in Mailarchiven; Nutzer muss extern Passwortmanager öffnen, einloggen, im Profil ändern.
- **Auswirkung**: Sicherheitsrisiko, UX-Bruch.
- **Verbesserungsvorschlag**: `lost2` zeigt Formular „Neues Passwort eingeben" (mit Bestätigung). Nur bei gültigem `remind_code` (TTL z. B. 60 min). Nach Setzen wird Mail mit Bestätigung (ohne Passwort) versendet.
- **Priorität**: mittel.

---

## IMP-007 – Registrierung: keine E-Mail-Validierung, kein Captcha, kein Double-Opt-In

- **Bereich**: Registrierung
- **URL / Route**: `?usercenter=register`
- **Beobachtung**: Jede Eingabe wird akzeptiert, es gibt keine Bestätigung per E-Mail (Double-Opt-In), kein Captcha.
- **Problem im Workflow**: Bot-Registrierungen, Tippfehler bei E-Mail führen zu „leeren" Accounts.
- **Auswirkung**: Spam, Datenmüll, Registrierungs-Flut möglich.
- **Verbesserungsvorschlag**:
  1. `filter_var($email, FILTER_VALIDATE_EMAIL)` einsetzen.
  2. Double-Opt-In: Nach Registrierung „Pending" Status, Aktivierungslink per Mail.
  3. Optional: hCaptcha / Cloudflare-Turnstile.
  4. Zeitbasierte Registrierung limitieren.
- **Priorität**: hoch.

---

## IMP-008 – Konsistente Modul-Fehlermeldungen & HTTP-Statuscodes

- **Bereich**: alle Module
- **URL / Route**: n/a
- **Beobachtung**: Bei „Unbekanntes Modul", „Release nicht gefunden", „Screenshot nicht gefunden" liefert der Server HTTP 200. Browser/Suchmaschinen können Fehlzustände nicht erkennen.
- **Problem im Workflow**: Debugging / Monitoring erschwert; SEO-Negativ-Folgen.
- **Auswirkung**: Tooling sieht alle Requests als Success.
- **Verbesserungsvorschlag**: 404/410 bei ID-Fehlern, 401 bei fehlendem Login, 403 bei fehlenden Rechten, 400 bei ungültigem POST. Zusätzlich zentrale Error-Komponente.
- **Priorität**: mittel.

---

## IMP-009 – Navigation: dynamischer vs. statischer Login-Zustand

- **Bereich**: Navigation
- **URL / Route**: `pdl_ordner.modul.php`, Template
- **Beobachtung**: Nach erfolgreichem Login wird der Zustand sofort sichtbar (Profil/Logout), nach fehlgeschlagenem Login bleibt die Navigation unverändert und es gibt keine Fehlerhinweise.
- **Problem im Workflow**: Schwacher Feedback-Loop.
- **Auswirkung**: Nutzer bleibt im Unklaren.
- **Verbesserungsvorschlag**: Zentralen Header-Renderer mit sichtbarem Nutzername + Logout-Button; Flash-Messages (Success/Error/Warning/Info) im Header darstellen.
- **Priorität**: niedrig.

---

## IMP-010 – Markup modernisieren (HTML5, CSS, ARIA-Labels)

- **Bereich**: Templates
- **URL / Route**: alle Seiten
- **Beobachtung**: `<body bgcolor>`, `<center>`, Layout-Tabellen, Checkbox-Label „Y".
- **Problem im Workflow**: Veraltetes Markup, schlechtes Mobile-Rendering, mangelhafte Accessibility.
- **Auswirkung**: Nutzung auf Smartphones/ Screenreadern beeinträchtigt.
- **Verbesserungsvorschlag**: Templates auf semantisches HTML5 + CSS-only-Layout refaktorieren, `label[for]`-Zuordnung, sinnvolle Texte, ARIA wo nötig, Darkmode via CSS-Variablen.
- **Priorität**: mittel.

---

## IMP-011 – URL-Scheme sauberer gestalten

- **Bereich**: Routing
- **URL / Route**: `downloads.php?&usercenter=register&submit=1`
- **Beobachtung**: Doppel-`&` („?&" und „&&") in vielen URLs, unterschiedliche Schreibweisen bei Parametern, teilweise unnötige `submit=1` in GET-Links.
- **Problem im Workflow**: Verwirrend, schlechter SEO-Zustand, fragil.
- **Auswirkung**: URLs schwer zu teilen / merken.
- **Verbesserungsvorschlag**: Rewrite-Rules + Router. Saubere URLs `/user/login`, `/user/register`, `/ordner/42` usw.
- **Priorität**: niedrig.

---

## IMP-012 – Kein E-Mail-Logging, keine Beobachtbarkeit

- **Bereich**: Mailversand
- **URL / Route**: `pdl_uregister`, `pdl_ulost*`
- **Beobachtung**: `@mail()` unterdrückt Fehler, Entwickler sehen Probleme nicht.
- **Problem im Workflow**: In Produktion bleiben Kunden ohne Mail, ohne Logfile-Hinweis.
- **Auswirkung**: Schwer zu debuggen, Kunden verärgert.
- **Verbesserungsvorschlag**: Statt `@mail()` z. B. `PHPMailer`/`Symfony Mailer`, Logger-Abstraktion, Retry, Statuscode-Anzeige im Admin.
- **Priorität**: mittel.

---

## IMP-013 – Startseite aussagekräftiger gestalten

- **Bereich**: Startseite
- **URL / Route**: `/downloads.php`
- **Beobachtung**: Nur „Dieser Ordner ist leer." + Navigation. Keine Info-Box, keine Begrüßung, kein Hinweis auf Registrierung.
- **Problem im Workflow**: Erster Eindruck sehr dürftig; Info-Boxen Top/Flop/Latest/Rated scheinen zwar im Code eingebunden, liefern aber leere Ausgaben.
- **Auswirkung**: Nutzer versteht nicht, was die Seite tut.
- **Verbesserungsvorschlag**: Hero-Section, neueste Releases, Top-Downloads, Login/Register-Shortcuts, leere Zustände sinnvoll betexten.
- **Priorität**: mittel.

---

## IMP-014 – Admin-Bereich-Buttons an Zustandsbedingungen koppeln

- **Bereich**: Release-Detail, Ordner-Detail
- **URL / Route**: `?release_id=…`
- **Beobachtung**: Admin-Optionen werden auch bei „Release nicht gefunden" angezeigt.
- **Problem im Workflow**: Admin klickt „Release Editieren", landet auf fehlerhafter Editierseite.
- **Auswirkung**: Verwirrung, potenziell falsche Datenmodifikationen.
- **Verbesserungsvorschlag**: Admin-Buttons nur rendern, wenn Ziel-Entität existiert.
- **Priorität**: niedrig.

---

## IMP-015 – Schema-Synchronisierung

- **Bereich**: Installation
- **URL / Route**: `install_querys.inc`, `install_303.php`, `update_*.php`
- **Beobachtung**: Schema-Statements nutzen `TYPE=MyISAM`; Laufzeit-DB enthält weniger Spalten als das Install-Skript vorsieht. Discrepanz zwischen Setup und Runtime.
- **Problem im Workflow**: Neuinstallationen failen, Migrationen nicht getestet.
- **Auswirkung**: „Works on my machine", unzuverlässiger Rollout.
- **Verbesserungsvorschlag**:
  1. Ein einziges, verbindliches Migration-Tool (z. B. `doctrine/migrations`, `phinx`).
  2. CI-Job, der Install+Register+Login gegen saubere DB testet.
  3. `TYPE=MyISAM` durch `ENGINE=InnoDB` ersetzen, UTF-8-Collation setzen.
- **Priorität**: hoch.

---

## IMP-016 – Testnutzer-Seed für QA

- **Bereich**: Installation
- **URL / Route**: `install_303.php`, Docker-Seed
- **Beobachtung**: Aufgabe fordert „Testnutzer: (selber anlegen über webseite)", aber Registrierung funktioniert nicht (BUG-001) – QA ist blockiert.
- **Problem im Workflow**: Manuelle Tests ohne funktionale Registrierung sind nur mit dem admin-Seed möglich.
- **Auswirkung**: Kaum realistische User-Tests möglich.
- **Verbesserungsvorschlag**: Seed-SQL mit `admin` + `user` (ugroup_id=1) und ggf. Demo-Daten (Ordner, Release, Screenshot) für QA-Umgebung. Dev-Docker optional `DEMO_SEED=true` Env-Flag.
- **Priorität**: hoch (für QA).

---

## IMP-017 – Homepage-Feld sanitisieren und korrekt verlinken

- **Bereich**: Profil
- **URL / Route**: Profilausgabe
- **Beobachtung**: Homepage wird ohne URL-Validation gespeichert; `javascript:`-Schemas werden nicht abgefangen.
- **Problem im Workflow**: Potenzielles XSS, unsaubere Links.
- **Auswirkung**: Angriffsfläche für Stored-XSS.
- **Verbesserungsvorschlag**: `filter_var($homepage, FILTER_VALIDATE_URL)`, Schema-Whitelist `http/https`, Ausgabe immer via `htmlspecialchars` + `rel="noopener noreferrer"`.
- **Priorität**: mittel.

---

## IMP-018 – ICQ-Feld entfernen oder modernisieren

- **Bereich**: Registrierung, Profil
- **URL / Route**: diverse
- **Beobachtung**: „ICQ" als Pflichtfeld (int). ICQ ist seit 2024 eingestellt.
- **Problem im Workflow**: Historische Überbleibsel ohne Nutzen.
- **Auswirkung**: Verwirrt moderne Nutzer.
- **Verbesserungsvorschlag**: Feld entfernen oder durch generisches „Kontakt (Matrix/Discord/…)" ersetzen.
- **Priorität**: niedrig.

---

## IMP-019 – Kommentar-Form: nur für eingeloggte User

- **Bereich**: Kommentare
- **URL / Route**: `?usercenter=comments&release_id=…`
- **Beobachtung**: Form wird auch angezeigt, wenn User Gast ist – Text „Gast – Login – Anmelden". Das Form feuert aber weiter.
- **Problem im Workflow**: Gäste können Comment-Buttons anklicken, bekommen aber Fehler „keine Rechte".
- **Auswirkung**: UX-Rausch.
- **Verbesserungsvorschlag**: Gästen Form mit „Anmelden um zu kommentieren" statt Formular; nur eingeloggten + berechtigten Nutzern das Formular zeigen.
- **Priorität**: niedrig.

---

## IMP-020 – Admin-/User-Bereiche klar trennen

- **Bereich**: Navigation, Layout
- **URL / Route**: `/downloads.php`
- **Beobachtung**: Admin-Center-Link liegt im User-Header; Admin-Rechte werden auf inhaltlichen Seiten direkt sichtbar. Kein sichtbarer Rollenkontext.
- **Problem im Workflow**: Mental Model unklar.
- **Auswirkung**: Erhöhtes Risiko, dass Admin versehentlich Public-Aktionen ausführt.
- **Verbesserungsvorschlag**: Dedizierter Admin-Bereich unter `/pdl-admin/` (bereits vorhanden) mit eigenem Layout, visueller Unterscheidung, getrenntem Session-Kontext.
- **Priorität**: niedrig.

---
