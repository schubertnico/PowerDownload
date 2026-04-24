# Userbereich-Bugfixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Die in `docs/2026-04-23-Userbereichs-bugs.md` dokumentierten 30 Bugs (vorrangig blocker/hoch) im Userbereich beheben.

**Architecture:** Root-Cause-Fix im `pdl_header.inc.php` löst 5 Blocker gleichzeitig. Anschließend werden DB-Schema-Inkonsistenzen behoben, Authentifizierung modernisiert (password_hash, Session-Token), Sicherheits-Features (CSRF, Rate-Limit) ergänzt und UX-Fehler (Login-Meldung, Form-Rerender) ausgebessert.

**Tech Stack:** PHP 8.4, MySQL 8.0 (InnoDB), PHPUnit, Apache 2.4.

**Betroffene Dateien** (Hauptfokus):
- `pdl-inc/pdl_header.inc.php` – Variablen-Extraktion, Session, Rate-Limit
- `pdl-inc/pdl_uregister.modul.php`, `pdl_ulogin.modul.php`, `pdl_uprofil.modul.php`, `pdl_ulost.modul.php`, `pdl_ulost2.modul.php`, `pdl_ucomments.modul.php`
- `pdl-inc/pdl_downloads.inc.php`
- `pdl-inc/pdl_release.modul.php`, `pdl_ordner.modul.php`
- `install_querys.inc` und DB-Migration
- `tests/Unit/` (Regressions- und Unit-Tests)

---

## Task 1: Root-Cause-Fix – fehlende POST/GET-Variablen extrahieren (BUG-001 + BUG-007 + BUG-008 + BUG-009 + BUG-010)

**Files:**
- Modify: `pdl-inc/pdl_header.inc.php`
- Test: `tests/Unit/HeaderExtractionTest.php` (neu)

- [ ] **Step 1:** Extraktionsblock in `pdl_header.inc.php` direkt nach Zeile 165 ergänzen, sodass `email`, `pw_old`, `pw_new`, `pw_new2`, `homepage`, `icq`, `get_letter`, `titel`, `text`, `remind_code` aus `$_POST`/`$_GET` mit Typcasts übernommen werden.
- [ ] **Step 2:** Registration-Flow im Browser prüfen (Testnutzer anlegen), E-Mail-Validierung / Passwort-Match.
- [ ] **Step 3:** Profil-Update mit admin testen.
- [ ] **Step 4:** Lost1-Flow mit admin@example.com testen, `remind_code` in DB prüfen.
- [ ] **Step 5:** Commit `fix: extract missing POST/GET variables in header (BUG-001 family)`

## Task 2: DB-Schema-Sync – fehlende Spalten + Engine (BUG-027 + BUG-013)

**Files:**
- Modify: `install_querys.inc`
- Create: `update_to_current.sql` (idempotente DDL)
- Modify: Live-DB via mysql-Client

- [ ] **Step 1:** `install_querys.inc`: `TYPE=MyISAM` → `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4` (alle Stellen).
- [ ] **Step 2:** Fehlende Spalten `get_letter`, `signatur`, `remind_code`, `lastactive` an `pdl3_user` ergänzen; `passwort` auf `VARCHAR(128)` erweitern.
- [ ] **Step 3:** Usergroup-Tabelle: Spalte `addcomments` prüfen/hinzufügen, falls fehlt (wird in Comments-Modul benötigt).
- [ ] **Step 4:** Docker-DB mit `ALTER TABLE` aktualisieren.
- [ ] **Step 5:** Commit `fix: sync database schema with install scripts (BUG-013, BUG-027)`

## Task 3: Login mit password_verify + MD5-Migration (BUG-002 + BUG-004)

**Files:**
- Modify: `pdl-inc/pdl_header.inc.php` (Login-Check)
- Test: `tests/Unit/PasswordAuthTest.php` (neu)

- [ ] **Step 1:** Login prüft zuerst mit `password_verify()`. Falls Hash noch MD5 ist (Länge 32 hex), Vergleich gegen `md5($pw)` und bei Erfolg migrate auf `password_hash()`.
- [ ] **Step 2:** Unit-Test für Migration (MD5-Hash → bcrypt nach Login).
- [ ] **Step 3:** Live-Test mit admin.
- [ ] **Step 4:** Commit `fix: use password_verify with MD5 legacy migration (BUG-002, BUG-004)`

## Task 4: Login-Fehlerhinweis (BUG-005)

**Files:**
- Modify: `pdl-inc/pdl_header.inc.php`
- Modify: `pdl-inc/pdl_ulogin.modul.php`

- [ ] **Step 1:** Nach fehlgeschlagenem Login `header("Location: …usercenter=login&login_error=1")`.
- [ ] **Step 2:** Login-Modul zeigt Fehlertext, wenn `$_GET['login_error']==1`.
- [ ] **Step 3:** Live-Test mit falschem Passwort.
- [ ] **Step 4:** Commit `fix: show login error message on failed authentication (BUG-005)`

## Task 5: Session-Token statt Passwort-Hash-Cookie + Secure-Flag (BUG-003 + BUG-028)

**Files:**
- Modify: `install_querys.inc` (neue Spalte `session_token`)
- Modify: `pdl-inc/pdl_header.inc.php`

- [ ] **Step 1:** Spalte `session_token VARCHAR(64)` zu `pdl3_user` hinzufügen.
- [ ] **Step 2:** Bei Login zufälligen 32-Byte-Token (`bin2hex(random_bytes(32))`) generieren und in DB speichern; Cookie `login_pw` durch diesen Token ersetzen.
- [ ] **Step 3:** Persistenz-Check prüft DB-Spalte `session_token` (nicht mehr `passwort`).
- [ ] **Step 4:** `setcookie` mit `secure => !empty($_SERVER['HTTPS'])`.
- [ ] **Step 5:** Commit `fix: use session token instead of password hash in cookie (BUG-003, BUG-028)`

## Task 6: CSRF-Schutz für alle POST-Forms (BUG-018)

**Files:**
- Create: `pdl-inc/pdl_csrf.inc.php`
- Modify: `pdl-inc/pdl_header.inc.php` (Session-Start + CSRF-Init)
- Modify: Register/Profil/Login/Lost/Comments-Module + Templates

- [ ] **Step 1:** Helper `csrf_token()` und `csrf_verify()`.
- [ ] **Step 2:** Jedes Form erhält Hidden-Input `<input type="hidden" name="csrf_token" value="…">` (über Template oder in Modul eingefügt).
- [ ] **Step 3:** Module verifizieren Token bei `submit==1`; bei Fehlschlag → 403-Äquivalent-Meldung.
- [ ] **Step 4:** Live-Tests für alle Forms.
- [ ] **Step 5:** Commit `feat: add CSRF protection to all POST forms (BUG-018)`

## Task 7: Rate-Limit für Login (BUG-019)

**Files:**
- Modify: `pdl-inc/pdl_header.inc.php` (Login-Check + IP-Lock)

- [ ] **Step 1:** Vor Login-Versuch IP aus `pdl3_iplock` abfragen; wenn ≥5 Fehler in letzten 15 min → Block.
- [ ] **Step 2:** Bei fehlgeschlagenem Login Row in `pdl3_iplock` hinzufügen/updaten.
- [ ] **Step 3:** Bei Erfolg Reset.
- [ ] **Step 4:** Commit `feat: rate-limit login attempts via iplock (BUG-019)`

## Task 8: User-Enumeration verhindern (BUG-006)

**Files:**
- Modify: `pdl-inc/pdl_ulost.modul.php`

- [ ] **Step 1:** Antwort immer „Wenn ein Konto mit dieser E-Mail existiert, wurde eine E-Mail versendet."
- [ ] **Step 2:** Commit `fix: avoid user enumeration on lost password (BUG-006)`

## Task 9: Form-Rerender mit Fehler-Anzeige (BUG-014)

**Files:**
- Modify: `pdl-inc/pdl_uregister.modul.php`, `pdl_uprofil.modul.php`, `pdl_ucomments.modul.php`

- [ ] **Step 1:** Bei Validation-Fehler Form erneut rendern, Werte zurückspielen, Fehlermeldung oben/am Feld anzeigen.
- [ ] **Step 2:** Commit `fix: re-render form on validation error instead of history.back (BUG-014)`

## Task 10: E-Mail-Validierung auf Server (BUG-015)

**Files:**
- Modify: `pdl-inc/pdl_uregister.modul.php`, `pdl_uprofil.modul.php`

- [ ] **Step 1:** `filter_var($email, FILTER_VALIDATE_EMAIL)` prüfen.
- [ ] **Step 2:** Commit `fix: validate email server-side (BUG-015)`

## Task 11: Passwort-Reset-Flow ohne Klartext-Versand (BUG-016 + BUG-017)

**Files:**
- Modify: `pdl-inc/pdl_ulost2.modul.php`
- Modify: `pdl-inc/pdl_uregister.modul.php` (Mail-Template anpassen)

- [ ] **Step 1:** `lost2` zeigt ein Formular „Neues Passwort setzen". Nach Submit (mit Token und CSRF) speichert neues Passwort.
- [ ] **Step 2:** Remind-Code-Ablauf: `remind_expires`-Spalte hinzufügen, 60 min TTL.
- [ ] **Step 3:** Register-Mail ohne Klartext-Passwort.
- [ ] **Step 4:** Commit `fix: secure password reset flow without plaintext mail (BUG-016, BUG-017)`

## Task 12: Admin-Optionen nur bei existierender Entität (BUG-012)

**Files:**
- Modify: `pdl-inc/pdl_release.modul.php`

- [ ] **Step 1:** Admin-Buttons nur rendern wenn `$release` existiert.
- [ ] **Step 2:** Commit `fix: hide admin buttons for non-existent releases (BUG-012)`

## Task 13: orderby/orderseq/perpage-Whitelist + URL-Parameter respektieren (BUG-020 + BUG-021)

**Files:**
- Modify: `pdl-inc/pdl_ordner.modul.php`

- [ ] **Step 1:** Whitelist `$allowed_orderby = ['name','date','size','downloads']`; fallback auf `$settings['orderby']`.
- [ ] **Step 2:** URL-Parameter zählen, falls in Whitelist.
- [ ] **Step 3:** Commit `fix: whitelist orderby params to prevent SQLi (BUG-020, BUG-021)`

## Task 14: Checkbox-Label + HTML-Grundlagen (BUG-022 + BUG-023)

**Files:**
- Modify: Templates (DB-Tabelle `pdl3_template`) oder inline HTML in Formular-Generierung.

- [ ] **Step 1:** Newsletter-Checkbox mit `<label>` und sinnvollem Text.
- [ ] **Step 2:** `<body bgcolor>` und `<center>` durch CSS ersetzen (downloads.php).
- [ ] **Step 3:** Commit `fix: improve html markup and labels (BUG-022, BUG-023)`

## Task 15: @mail() → logging (BUG-024)

**Files:**
- Modify: `pdl_uregister.modul.php`, `pdl_ulost.modul.php`, `pdl_ulost2.modul.php`

- [ ] **Step 1:** `@` entfernen. Bei Fehler error_log().
- [ ] **Step 2:** Commit `fix: log mail send errors instead of suppressing (BUG-024)`

## Task 16: Homepage-URL sanitizen (BUG-026)

**Files:**
- Modify: `pdl_uregister.modul.php`, `pdl_uprofil.modul.php`

- [ ] **Step 1:** `filter_var($homepage, FILTER_VALIDATE_URL)`; nur `http(s)://` akzeptieren.
- [ ] **Step 2:** Commit `fix: validate homepage URL (BUG-026)`

## Task 17: change_list (BUG-029) — GESCHLOSSEN (Fehlanalyse, 2026-04-23)

**Ergebnis:** Kein Dead-Code. `$change_list` ist aktives Feature „individuelles Listen":
Form-Submits aus `pdl_ordner.modul.php` und `pdl_search.modul.php` (Action
`downloads.php?change_list=1`) setzen in `pdl_header.inc.php` den Cookie `pdl_list`
mit den User-Sortier-/Pagination-Preferences. Keine Code-Änderung notwendig.

- [x] **Analyse abgeschlossen** — Details siehe `docs/2026-04-23-Userbereichs-bugs.md` BUG-029.

## Task 18: Startseite Info-Boxen wieder sichtbar (BUG-011)

**Files:**
- Modify: `downloads.php` oder `pdl_stats.inc.php`, `pdl_top.inc.php`, etc.

- [ ] **Step 1:** Identifizieren warum Info-Boxen leer bleiben; ggf. minimale CSS ergänzen (Tabellen-Layout bleibt).
- [ ] **Step 2:** Live prüfen.
- [ ] **Step 3:** Commit `fix: make home info boxes visible (BUG-011)`

## Task 19: Eingeloggt-Register-Logikfix (BUG-030)

**Files:**
- Modify: `pdl_uregister.modul.php`

- [ ] **Step 1:** Auch bei `submit=1` eingeloggte User ablehnen.
- [ ] **Step 2:** Commit `fix: reject register submit when already logged in (BUG-030)`

---

## Ausführungs-Hinweis

Ausführung inline im aktuellen Branch (`master`) mit Zwischencommits.
