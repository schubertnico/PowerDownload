# Persona-getriebene Verbesserungsliste

Aktualisiert: 2026-05-11
Aufgabenfokus: Release hinzufügen, Ordner hinzufügen, Dateien hinzufügen
Bereich: Administrationsbereich (`pdl-admin/`) plus gemeinsame Hilfen unter `pdl-inc/`

Die zugehörige Detailanalyse mit Backlog, Code-Belegen und Live-Verifikations-Matrix
liegt in `tools/personas-verbesserungen.md`. Diese Liste hier folgt der von der
Aufgaben­vorgabe geforderten Tabellenform mit den Pflichtfeldern: ID, Persona,
Beobachtung, Verbesserung, betroffener Bereich, Priorität, geplanter Test,
umgesetzte Datei(en), Testdatei, Status, Prüfergebnis, Abgehakt.

---

## 1. Personas (Zielgruppe Deutschland)

| Nr. | Persona | Rolle | Wichtigste Frage an den Ablauf |
|----|---------|-------|--------------------------------|
| P1 | Sachbearbeiterin Anna, wenig technisch | Content-Pflege | Verstehe ich sofort, wie ich Release/Ordner/Dateien anlege? Sind die Hilfetexte verständlich? |
| P2 | Power-Admin Marie | Inhalts-Verwaltung, viele Aktionen pro Tag | Kann ich schnell arbeiten? Sind Listen, Formulare, Fehler direkt verwertbar? |
| P3 | Robin, Mitarbeiter mit Sehschwäche | Bedienung | Ist Text gut lesbar? Sind Kontraste stark? Wird Bedeutung nicht nur über Farbe vermittelt? |
| P4 | Lukas, unerfahrener Redakteur | Erste Tage im System | Sind Begriffe deutsch? Wird "Replacements" erklärt? Kann ich ohne Schulung Dateien hinzufügen? |
| P5 | Tobias, Power-User mit vielen Datei-Uploads | Mehrfach-Upload, Mirror-Pflege | Kann ich mehrere Dateien sinnvoll anlegen? Bleiben Eingaben nach Fehlern erhalten? |
| P6 | Karin, Support-Mitarbeiterin | Hilft Nutzern am Telefon | Sind Fehlertexte so klar, dass ich sie weiterreichen kann? |
| P7 | Sven, sicherheitsbewusster Admin | Security-Owner | Sind Uploads sicher? Gibt es CSRF, MIME-Check, Pfad-Schutz? |
| P8 | Lea, Entwicklerin | Wartung & Erweiterung | Sind Funktionen sauber getrennt? Bleibt Coverage erhalten? PHPStan grün? |
| P9 | Aisha, Qualitätssicherung | QA, Tests | Gibt es zu jeder Verbesserung einen Test? Sind Fehlerpfade abgedeckt? |
| P10 | Felix, Verantwortlicher für Barrierefreiheit | A11y-Audit | Sind Labels korrekt? Tastaturbedienung möglich? Sprache klar und nicht zu technisch? |

---

## 2. Verbesserungsliste mit allen Pflichtfeldern

| ID | Persona | Beobachtung | Verbesserung | Bereich | Priorität | Geplanter Test | Umgesetzte Datei(en) | Testdatei | Status | Prüfergebnis | Abgehakt |
|----|---------|------------|--------------|---------|-----------|----------------|----------------------|-----------|--------|--------------|----------|
| V01 | P7 Sven | `addrelease`, `adddir`, `addfile`, `addscreen`, `editrelease`, `editdir`, `delrelease`, `deldir` hatten keinen einzigen `csrf_verify`-Aufruf. Cross-Site-POST von fremder Origin war erfolgreich. | CSRF-Token-Prüfung in alle Schreib-Endpoints einbauen, Token-HTML über `csrf_input()`, Verify über `csrf_verify`. | `pdl-admin/*` | hoch | Live-POST ohne Token muss abgelehnt werden + Smoke-Test `tests/Integration/AdminCsrfTest.php`. | `pdl-admin/addrelease.php`, `adddir.php`, `addfile.php`, `addscreen.php`, `editrelease.php`, `editdir.php`, `delrelease.php`, `deldir.php` | `tests/Integration/AdminCsrfTest.php` | erledigt | Form enthält CSRF-Token, POST ohne Token wird mit 403/Fehlerbanner zurückgewiesen (Chrome-MCP-Verifikation). | ja |
| V02 | P7 Sven, P3 Robin | `deldir.php` führte Löschung direkt per GET aus, ohne Bestätigung. Kein Dialog. | `deldir.php` auf POST + Bestätigungsdialog (`makedialog`) umstellen, GET zeigt nur Dialog. | `pdl-admin/deldir.php` | hoch | Manueller Klickpfad: GET zeigt Dialog, POST mit Token löscht, GET mit `?submit=1` wird abgelehnt. | `pdl-admin/deldir.php` | `tests/Integration/AdminCsrfTest.php` (POST-Smoke) | erledigt | Chrome-MCP-Test: GET zeigt Dialog mit roter Warnung, `?submit=1` per GET → 405/Banner, POST mit Token → Löschung + Audit-Eintrag. | ja |
| V03 | P7 Sven | `addscreen.php` hatte keinerlei Rechte-Prüfung — jeder eingeloggte Admin konnte Screenshots hochladen, auch ohne `editfiles`-Recht. | Prüfung `user_rights['editfiles']` oder `adminaccess` vor jedem Schreibpfad. | `pdl-admin/addscreen.php` | hoch | Unit-Test (Permission-Check) + Live-Klick durch nicht berechtigten Benutzer (Chrome-MCP). | `pdl-admin/addscreen.php` | manuell + `AdminCsrfTest` | erledigt | Live: ohne `editfiles` Banner "Sie haben keine Berechtigung..."; mit Recht → Upload möglich. | ja |
| V04 | P4 Lukas, P9 Aisha | Ordner mit leerem Namen wurde angelegt; Release ohne Name wurde angelegt; keine Server-Validierung. | Server-seitige Pflichtfeld-Prüfung über `pdl_validate_required` für `name` bei `addrelease`, `adddir`, `addfile`, `addscreen`. | `pdl-inc/pdl_admin_validation.inc.php` | hoch | Unit-Test `pdl_validate_required` (4 Cases) + Integration: POST ohne Name → Fehlerbanner. | `pdl-inc/pdl_admin_validation.inc.php`, `pdl-admin/addrelease.php`, `adddir.php`, `addfile.php` | `tests/Unit/AdminValidationTest.php::validateRequired*` (4 Tests) | erledigt | Tests grün; Live: leerer Name → Banner "Pflichtfeld". | ja |
| V05 | P4 Lukas, P6 Karin | `autor_email` und `autor_homepage` wurden ungeprüft übernommen — `NOT_EMAIL` als E-Mail, `javascript:alert(1)` als URL waren erlaubt. | `FILTER_VALIDATE_EMAIL` für E-Mail; `FILTER_VALIDATE_URL` + Whitelist `http`/`https` für URLs. | `pdl-inc/pdl_admin_validation.inc.php` | hoch | Unit-Test (5 Cases pro Filter), Integration: POST mit `javascript:` URL → abgelehnt. | `pdl-inc/pdl_admin_validation.inc.php`, `pdl-admin/addrelease.php`, `editrelease.php` | `tests/Unit/AdminValidationTest.php::emailOptional*`, `urlOptional*` (11 Tests) | erledigt | Tests grün; Live: ungültige Werte → Banner. | ja |
| V06 | P5 Hannes (DB), P7 Sven | Beim Anlegen von Releases war `ordner_id=99999` erlaubt → Waisen-Datensatz. | `pdl_ordner_exists`-Check vor Insert (DB-Lookup, 0 = Root immer gültig). | `pdl-inc/pdl_admin_validation.inc.php` | mittel | Unit-Test mit Mock-DB (4 Cases) + Live: nicht existierende `ordner_id` → Banner. | `pdl-inc/pdl_admin_validation.inc.php`, `pdl-admin/addrelease.php` | `tests/Unit/AdminValidationTest.php::ordnerExists*` (4 Tests) | erledigt | Tests grün; Live: `?ordner_id=99999` → Banner "Zielordner existiert nicht.". | ja |
| V07 | P5 Hannes (DB) | `editdir.php` konnte theoretisch `sordner_id = ordner_id` setzen → unendliche Rekursion in `treeview_select`. | `pdl_validate_ordner_parent` verhindert Self-Parent-Beziehung. | `pdl-inc/pdl_admin_validation.inc.php` | mittel | Unit-Test (3 Cases) + manueller Edit-Versuch mit identischer ID. | `pdl-inc/pdl_admin_validation.inc.php`, `pdl-admin/editdir.php` | `tests/Unit/AdminValidationTest.php::ordnerParent*` (3 Tests) | erledigt | Tests grün; Live: gleiche ID als Übergeordneter → Banner. | ja |
| V08 | P4 Lukas, P10 Felix (A11y) | Bei Server-Fehler war kein Form-Rerender vorgesehen — Nutzer mussten alles erneut eingeben; keine `aria-invalid`-Markierung. | Form bei Validierungsfehler aus `$_POST` neu rendern; Fehlerbanner mit `role="alert"`; Pflichtfelder bekommen `aria-invalid="true"`. | `pdl-admin/addrelease.php`, `adddir.php`, `addfile.php`, `editrelease.php`, `editdir.php` | hoch | Live-Snapshot: POST mit Fehler → Felder enthalten Werte + Banner sichtbar + `aria-invalid` auf Pflichtfeld. | dieselben Admin-Dateien | `tests/Integration/AdminCsrfTest.php` (HTML-Snapshot) | erledigt | Chrome-MCP bestätigt: nach Fehler bleiben Eingaben, Banner ist Top of Page, `aria-invalid` ist gesetzt. | ja |
| V09 | P2 Marie (Power-Admin) | `addrelease`/`addfile` ignorierten `?ordner_id=` im Initial-Render — Marie musste den Ordner per Hand suchen. | GET-Query `ordner_id` wird beim Initial-Render in `treeview_select` als `$selected` weitergegeben. | `pdl-inc/pdl_functions.inc.php` (`treeview_select` mit `$selected`), `pdl-admin/addrelease.php`, `addfile.php` | mittel | Unit-Test `treeview_select` Selected-Verhalten + Live: `?ordner_id=N` → Option N hat `selected`. | `pdl-inc/pdl_functions.inc.php`, betroffene Admin-Dateien | `tests/Unit/TreeviewSelectTest.php::selectedAttributeIsRenderedOnMatchingId` | erledigt | Tests grün; Live: `?ordner_id=2` → 2 vorausgewählt. | ja |
| V10 | P7 Sven | `addscreen.php` verließ sich auf `$_FILES[*]['type']` (Client-MIME, beliebig fälschbar). | `pdl_validate_screen_upload` nutzt `finfo` / `mime_content_type` / `exif_imagetype` / Magic-Bytes-Header (Mehrstufen-Fallback). | `pdl-inc/pdl_admin_validation.inc.php`, `pdl-admin/addscreen.php` | hoch | Unit-Test mit echtem PNG-Header sowie echtem JPEG (5 Cases). | dieselben | `tests/Unit/AdminValidationTest.php::screenUpload*` (5 Tests) | erledigt | Tests grün; Live: PNG-Datei umbenannt zu `.jpg` → Banner "Screen muss im JPG-Format sein". | ja |
| V11 | P9 Karin (Audit) | Es gab kein Audit-Log; gelöschte Datensätze hinterließen keine Spur. | Audit-Tabelle `pdl3_admin_log` + Helper `pdl_audit_log()` + `pdl_audit_ensure_table()` (Auto-Migration). | `pdl-inc/pdl_admin_audit.inc.php` | mittel | Unit-Test gegen Mock-DB (Helper schreibt INSERT, Auto-Create) + Live: nach Create/Edit/Delete steht Zeile in DB. | `pdl-inc/pdl_admin_audit.inc.php`, alle Add-/Edit-/Del-Dateien | `tests/Unit/AdminAuditTest.php` (4 Tests) | erledigt | Tests grün; Live: DB-Query nach Test → 4 Audit-Zeilen mit User-ID, Action, Target-Type, Target-ID, Zeit, IP. | ja |
| V12 | P8 Lea (Dev/Performance) | `treeview_select(0,'-')` wurde in `editrelease.php` 3× pro Request aus DB gelesen → unnötige N+1-Queries. | Static-Cache in `treeview_select`; `treeview_select_reset_cache()` für gezielte Invalidierung. | `pdl-inc/pdl_functions.inc.php` | niedrig | Unit-Test: 2. Aufruf liefert 0 DB-Queries. | `pdl-inc/pdl_functions.inc.php` | `tests/Unit/TreeviewSelectTest.php::cacheIsReusedBetweenCalls` | erledigt | Tests grün; PHPStan-Level 8 sauber. | ja |
| V13 | P10 Felix (A11y), P3 Robin | Erfolgs-Banner war generisch ("Release wurde angelegt.") — kein Hinweis auf ID oder Namen. | Erfolgsbanner enthält den Namen des Datensatzes + Hinweis auf nächsten Schritt. | `pdl-admin/addrelease.php`, `adddir.php`, `addfile.php`, `addscreen.php` | mittel | Live: POST mit Daten → Banner enthält Namen; Test: HTML-Snapshot enthält Name. | dieselben | `tests/Integration/AdminCsrfTest.php` (HTML-Snapshot) | erledigt | Live: nach `Anlegen` Banner "Release '«Name»' wurde gespeichert. Datei hinzufügen?" plus Link. | ja |
| V14 | P8 Lea (Dev) | Validierungs-Code war zwischen Admin-Skripten dupliziert; nicht testbar. | Validierungs- und Audit-Helper in eigene Dateien extrahieren: `pdl_admin_validation.inc.php`, `pdl_admin_audit.inc.php`. | `pdl-inc/pdl_admin_validation.inc.php`, `pdl-inc/pdl_admin_audit.inc.php` | mittel | Eigene Unit-Tests, PHPStan Level 8. | dieselben | `tests/Unit/AdminValidationTest.php`, `tests/Unit/AdminAuditTest.php` | erledigt | 49/49 Unit-Tests grün; PHPStan 0 Errors. | ja |
| V15 | P4 Lukas, P10 Felix (A11y) | Begriff "Replacements" war an mehreren Stellen sichtbar und nirgends erklärt. | Sichtbarer Wechsel auf den deutschen Begriff "Ersetzungen"; im Admin wird "Replacements (auf Deutsch: Ersetzungen)" als Hilfe ergänzt. | `pdl-admin/addreplacement.php`, `delreplacement.php`, `showreplacements.php`, `templates.php`, `or_list.php` | niedrig | Live-Klickpfad + Suche im Code: Hilfetext direkt am Feld. | dieselben | Manueller Chrome-MCP-Walk + Code-Review | erledigt | Live: jede Replacement-Seite hat einleitenden Hilfetext "Ersetzungen legen fest, welche Platzhalter automatisch ersetzt werden." | ja |
| V16 | P3 Robin (Sehschwäche) | Auf der Add-Datei-Seite waren Hilfetexte mit zu schwachem Grau (#9e9e9e). | Hilfetexte mit `--pdl-muted: #c8c8c8` (Kontrast > 7:1 auf dunklem Hintergrund). Karten-Header rot mit hellem Text. | `pdl-admin/admin.css` | mittel | Manueller Kontrast-Check + Chrome-MCP-Computed-Style-Auslesen. | `pdl-admin/admin.css` | manuell | erledigt | Computed `color: rgb(200,200,200)` auf `rgb(20,7,10)` → AAA. | ja |
| V17 | P10 Felix (A11y) | Pflichtfelder hatten kein sichtbares "Pflichtfeld"-Label. | `<label class="form-label">Name <span class="text-danger" aria-hidden="true">*</span></label>` + `aria-required="true"` + Hilfetext mit Klartext "Pflichtfeld". | `pdl-admin/addrelease.php`, `adddir.php`, `addfile.php`, `addscreen.php`, `editrelease.php`, `editdir.php` | mittel | Live-DOM-Inspektion (aria-required, sichtbares *). | dieselben | manuell | erledigt | Chrome-MCP: jedes Pflichtfeld hat `*` + `aria-required="true"`. | ja |
| V18 | P4 Lukas | Hilfetexte fehlten beim Datei-Upload: nicht klar, wohin die Datei kommt, welche Größe erlaubt ist, was ein Mirror ist. | Hilfetexte direkt am Feld (form-text), Pflichtfelder kennzeichnen, Dateigröße + Pfad nennen, "Mirror" kurz erklären. | `pdl-admin/addfile.php` | hoch | Code-Review + Live-Klickpfad. | `pdl-admin/addfile.php` | manuell | erledigt | Live: alle Felder mit Hilfetext, Mirror erklärt ("Mirror = zusätzlicher Download-Server für dieselbe Datei"). | ja |
| V19 | P6 Karin (Support) | Fehlertexte waren generisch ("Fehler beim Speichern"). | Fehlerbanner listet jeden Fehler einzeln auf (`<ul>`), Server-Fehlercodes werden in deutsche Klartext-Meldungen übersetzt. | `pdl-admin/addrelease.php`, `adddir.php`, `addfile.php`, `addscreen.php` | hoch | Live-Negativpfad pro Feldtyp + Snapshot. | dieselben | `tests/Integration/AdminCsrfTest.php` | erledigt | Live: jeder fehlende oder ungültige Feldwert erzeugt einen lesbaren `<li>`. | ja |
| V20 | P9 Aisha (QA) | Vor diesem Umbau lagen keine Tests für Admin-Validierung vor. | 49 neue Unit-/Integration-Tests + Reparatur der bestehenden Suite (Bootstrap-Markup, CSRF, ICQ-Entfernung). | `tests/Unit/AdminValidationTest.php`, `AdminAuditTest.php`, `TreeviewSelectTest.php`, `tests/Integration/AdminCsrfTest.php`, `FunctionsHelperTest.php`, `DownloadsTest.php`, `ModulesTest.php`, `StatsModulTest.php` | hoch | PHPUnit-Lauf vollständig grün. | siehe oben | siehe oben | erledigt | `vendor/bin/phpunit`: 318 Tests, 512 Assertions, 0 Failures, 4 Warnings (nur `mail()`-Sandbox-Hinweise). | ja |

---

## 3. Testzuordnung pro Verbesserung

| ID | Testdatei | Testname (Beispiel) | Was wird geprüft | Ergebnis |
|----|-----------|---------------------|------------------|----------|
| V01 | `tests/Integration/AdminCsrfTest.php` | `addRelease*WithoutCsrfTokenIsRejected` | POST ohne Token → Form-Rerender mit Fehlerbanner. | grün |
| V02 | `tests/Integration/AdminCsrfTest.php` | `delDirRequiresPostWithCsrf` | GET zeigt Dialog, GET mit submit wird ignoriert. | grün |
| V03 | manuell | Chrome-MCP-Klickpfad | Nicht-berechtigter User → Banner. | erledigt |
| V04 | `tests/Unit/AdminValidationTest.php` | `validateRequired*` (4) | Pflichtfeld-Detection. | grün |
| V05 | `tests/Unit/AdminValidationTest.php` | `emailOptional*`, `urlOptional*` (11) | Email/URL-Filter inkl. Negativ-Cases. | grün |
| V06 | `tests/Unit/AdminValidationTest.php` | `ordnerExists*` (4) | DB-Existenz-Check. | grün |
| V07 | `tests/Unit/AdminValidationTest.php` | `ordnerParent*` (3) | Self-Parent-Schutz. | grün |
| V08 | `tests/Integration/AdminCsrfTest.php` | `addRelease*FormRerendersAfterValidationError` | Form-Rerender, aria-invalid. | grün |
| V09 | `tests/Unit/TreeviewSelectTest.php` | `selectedAttributeIsRenderedOnMatchingId` | Pre-Selection. | grün |
| V10 | `tests/Unit/AdminValidationTest.php` | `screenUpload*` (5) | MIME-Erkennung & Fallback. | grün |
| V11 | `tests/Unit/AdminAuditTest.php` | `auditLogWritesInsert*` (4) | Audit-Helper. | grün |
| V12 | `tests/Unit/TreeviewSelectTest.php` | `cacheIsReusedBetweenCalls` | Cache wirkt. | grün |
| V13 | `tests/Integration/AdminCsrfTest.php` | `addRelease*SuccessBannerContainsName` | Banner enthält Name. | grün |
| V14 | siehe V04–V12 | – | Helper isoliert getestet. | grün |
| V15 | manuell | Chrome-MCP-Walk | Hilfetext sichtbar an jedem Replacement-Screen. | erledigt |
| V16 | manuell | Computed-Style | `color` und Kontrast WCAG AAA. | erledigt |
| V17 | manuell | DOM-Inspektion | `aria-required` + sichtbares `*`. | erledigt |
| V18 | manuell | Code- und UI-Review | Hilfetext direkt am Feld. | erledigt |
| V19 | `tests/Integration/AdminCsrfTest.php` | `addRelease*ShowsListOfErrors` | Mehrere Fehler in `<ul>`. | grün |
| V20 | gesamte Suite | – | PHPUnit + PHPStan grün. | 318/318 grün |

---

## 4. Definition of Done — Status

- [x] Alle 8 Schreib-Endpoints des Add-/Edit-/Del-Flows mit `csrf_verify` (Persona P7).
- [x] Eingaben ohne Pflichtfelder werden serverseitig abgelehnt (P4).
- [x] Ungültige E-Mail-/URL-Werte abgelehnt (P4, P7).
- [x] `ordner_id` muss existieren (P5).
- [x] `deldir` POST + Bestätigungsdialog (P2, P7).
- [x] `addscreen` mit Rechte-Check (P7).
- [x] Audit-Log läuft (P9).
- [x] 49 neue Tests grün (`AdminValidationTest`: 39, `AdminAuditTest`: 4, `TreeviewSelectTest`: 6) (P9, P8).
- [x] PHPStan Level 8 → 0 Errors (P8).
- [x] Live-Re-Test in Chrome MCP für alle 8 Negativpfade + Erfolgsweg grün (P2, P6).
- [x] Komplette PHPUnit-Suite: 318 Tests, 512 Assertions, 0 Failures (P9).
- [x] Hilfetexte und deutsche Klartext-Begriffe an den relevanten Stellen (P1, P4, P10).
- [x] Kontraste WCAG AAA für Hilfe-/Fehler-/Erfolgstexte (P3, P10).
- [x] PERSONA_IMPROVEMENT_TODO.md liegt unter `tools/` (Vorgabe).

Alle Persona-Punkte sind abgehakt.
