# Personas-getriebene Verbesserungsanalyse: Release + Ordner hinzufügen

> Erstellt: 2026-05-10
> Scope: Admin-Bereich, Hinzufügen-/Bearbeiten-/Löschen-Workflow für Ordner, Releases, Dateien und Screenshots
> Quellen: Code-Review (`pdl-admin/addrelease.php`, `adddir.php`, `addfile.php`, `addscreen.php`, `editrelease.php`, `editdir.php`, `delrelease.php`, `deldir.php`) + Live-Begutachtung über Chrome MCP mit `admin/admin123` gegen `http://localhost:8092/pdl-admin/`

---

## 1. Personas

### Persona 1 — Sven, Security-Engineer
- **Hintergrund:** OWASP-orientiert, prüft jede neue Anwendung auf Top-10-Schwachstellen.
- **Erwartet:** CSRF-Schutz auf jedem Schreibendpoint, kein DELETE über GET, kein offener Formular-Resubmit, keine ungeprüften Dateinamen, MIME-Detection via `finfo`.
- **Beobachtung:** `addrelease.php`, `adddir.php`, `addfile.php`, `addscreen.php`, `editrelease.php`, `editdir.php`, `delrelease.php`, `deldir.php` enthalten **kein einziges** `csrf_verify()`. Cross-Site-POST mit fremder Origin wurde erfolgreich abgesetzt. `deldir.php` führt das Löschen **direkt per GET** und ohne Bestätigung aus. `addscreen.php` hat **gar keine Rechte-Prüfung**.

### Persona 2 — Marie, Power-Admin / Content-Manager
- **Hintergrund:** Legt täglich Releases an, organisiert Ordnerhierarchien, will keine Fingerübungen wiederholen.
- **Erwartet:** Sinnvolle Defaults, „Release hinzufügen" mit vorausgewähltem Ziel-Ordner (per `?ordner_id=…`), Auswahlbox sortiert nach Baum, Submit per Enter, klare Erfolgsanzeige inkl. Sprung zur Datei-Anlage.
- **Beobachtung:** `addrelease.php` ignoriert `?ordner_id=` aus Query-String beim Initial-Render. Treeview-Auswahl ist nicht vorselektiert. Pagination fehlt komplett in `editrelease.php` (files-/screens-/comments-Tabellen).

### Persona 3 — Lukas, Junior-Redakteur
- **Hintergrund:** Erst seit zwei Wochen dabei, klickt sich orientierungslos durch.
- **Erwartet:** Server-seitige Pflichtfeld-Hinweise (nicht nur HTML5 `required`), Wiederherstellung der eingegebenen Daten bei Fehlern, deutliche Fehlermeldungen.
- **Beobachtung:** Live-Test zeigt: **Ordner mit leerem Namen wird angelegt**, Release ohne Name wird angelegt, Release mit ungültiger E-Mail/URL wird angelegt. Server-Validierung **fehlt komplett**. Bei Fehlern müsste der Nutzer wieder von vorn beginnen.

### Persona 4 — Aisha, Tester / QA
- **Hintergrund:** Schreibt PHPUnit-/Integration-Tests, achtet auf Coverage.
- **Erwartet:** Jede Validierung als reine Funktion isolierbar, deterministisch testbar; Coverage darf bei Änderungen nicht sinken.
- **Beobachtung:** Validierungs-Logik ist überhaupt nicht vorhanden, daher auch nicht testbar. Es existiert kein `Unit/AdminValidationTest.php`. `tests/Unit/FunctionsTest.php` deckt nur Helper, nicht Admin-Workflow ab.

### Persona 5 — Hannes, Datenbank-Engineer
- **Hintergrund:** Achtet auf referenzielle Integrität, transaktionale Sicherheit, Foreign Keys.
- **Erwartet:** Insert von Release in nicht-existenten Ordner muss scheitern. Ordner mit zirkulärer Parent-Beziehung darf nicht entstehen. Mehrere DB-Schreibzugriffe in einem Vorgang gehören in eine Transaktion.
- **Beobachtung:** `ordner_id=99999` beim Anlegen wird **akzeptiert** → Waisen-Datensatz. `editdir.php` kann theoretisch `sordner_id = ordner_id` setzen → unendliche Rekursion in `treeview_select`. `addrelease.php` schreibt erst Insert, dann Update — falls Update fehlschlägt, bleibt Insert stehen (keine Transaktion).

### Persona 6 — Lena, UX-Designerin
- **Hintergrund:** Bewertet Form-Flow, Mikrointeraktionen, Visual-Feedback.
- **Erwartet:** Bestätigungs-Modal vor jeder destruktiven Aktion, Erfolgs-Bestätigung mit kontextueller Next-Action, Form-Feldgruppen klar gegliedert.
- **Beobachtung:** `deldir.php` löscht **ohne Dialog** (kein `makedialog`-Aufruf). `addrelease`-Erfolg liefert nur „Release wurde angelegt." ohne Hinweis auf die ID/den Namen. Sprungnavigation in `editrelease.php` springt zwar zu Ankern, hat aber kein Active-State.

### Persona 7 — Robin, Barrierefreiheits-Anwalt
- **Hintergrund:** Audit nach WCAG 2.2 AA / AAA, prüft mit Screenreader.
- **Erwartet:** Fehler in einer einzigen Liste mit `role="alert"`, klare `aria-invalid`-Markierung bei Feldern, Fokus auf ersten Fehler.
- **Beobachtung:** Beim Server-Fehler kommt nur ein generisches Banner („wurde angelegt") — Fehlerszenario nicht implementiert; keine `aria-invalid`-Setzung bei Bad-Input, kein Focus-Management.

### Persona 8 — Tomasz, Polnischer Internationalisierungs-Berater
- **Hintergrund:** Plant die Übersetzung der Admin-Oberfläche, beobachtet harte Strings im Code.
- **Erwartet:** Übersetzbare Strings — Labels, Hilfetexte, Erfolgs-/Fehlermeldungen ausschließlich über Settings/Templates.
- **Beobachtung:** Strings wie „Ordner wurde angelegt.", „Release wurde angelegt.", „Sie haben keine Berechtigung…" sind **hart in PHP** verdrahtet. `pdl_admin_alert` wird mit deutschem Klartext aufgerufen; keine `i18n`-Helper.

### Persona 9 — Karin, Operations / Audit-Logging
- **Hintergrund:** Verantwortet Audit-Trail, will nachvollziehen wer wann was gemacht hat.
- **Erwartet:** Erstellung, Änderung, Löschung in einem Audit-Log mit Nutzer-ID, Timestamp, betroffenem Datensatz.
- **Beobachtung:** Audit-Log fehlt vollständig. `pdl3_release.uploader` speichert nur den ursprünglichen User; spätere Edits werden nicht protokolliert. Lösch-Vorgänge hinterlassen keine Spur.

### Persona 10 — Felix, Performance-Engineer
- **Hintergrund:** Optimiert Render-Zeit, Query-Count, Bundle-Größe.
- **Erwartet:** `treeview_select` sollte nicht für jeden Select neu aus DB lesen; Files-Tabelle in `editrelease.php` mit Pagination; keine N+1-Queries.
- **Beobachtung:** `treeview_select(0,"-")` wird in `editrelease.php` mehrfach pro Request ausgeführt (3× im Form, je 1× pro Subordner). Jeder Aufruf macht ein eigenes `SELECT * FROM pdl3_ordner` (siehe Implementierung). Für Mirror-Files in `editrelease` wird pro Mirror ein zusätzlicher `SELECT` abgesetzt (N+1).

---

## 2. Verbesserungs-Backlog (priorisiert)

| # | Persona(s) | Bereich | Verbesserung | Test-Strategie |
|---|------------|---------|--------------|----------------|
| **1** | Sven | Security | CSRF-Schutz für `addrelease`, `adddir`, `addfile`, `addscreen`, `editrelease`, `editdir`, `delrelease`, `deldir` (POST-Token-Verifikation via `csrf_verify`) | Unit-Test: `csrf_verify` mit gültigem/ungültigem Token. Integration: simulierter POST ohne Token muss abgelehnt werden. |
| **2** | Sven, Lena | Security/UX | `deldir.php` auf POST + Bestätigungsdialog (`makedialog`) umstellen | Unit-Test: GET-Request rendert Dialog, POST mit Submit löscht. |
| **3** | Sven | Security | Rechte-Prüfung in `addscreen.php` (`editfiles`-Recht) | Unit-Test: Funktion `admin_can_add_screen($user_rights)` → true/false. |
| **4** | Lukas, Aisha | Validierung | Server-seitige Pflichtfeld-Prüfung (`name` darf nicht leer sein) für Ordner, Release, Datei, Screen | Unit-Test: `pdl_validate_required(array, [Felder])` → liefert Fehler-Array. |
| **5** | Lukas | Validierung | `autor_email` per `FILTER_VALIDATE_EMAIL`, `autor_homepage` und `url` der Datei per `FILTER_VALIDATE_URL` (Whitelist `http`/`https`) | Unit-Test: `pdl_validate_release_input` mit gültigen/ungültigen E-Mail- und URL-Eingaben. |
| **6** | Hannes | DB-Integrität | Vor Insert prüfen, ob `ordner_id` existiert (oder = 0/Index) | Unit-Test: `pdl_ordner_exists($db, $id)` mit Mock-DB. |
| **7** | Hannes | DB-Integrität | In `editdir.php`: verhindern dass `sordner_id === ordner_id` (Self-Parent) | Unit-Test: `pdl_validate_ordner_parent($id, $newParent, $db)`. |
| **8** | Lukas, Robin | UX | Bei Validierungs-Fehler Form mit `$_POST`-Werten neu rendern + Fehler-Banner + `aria-invalid` | Integration: POST mit leeren Pflichtfeldern → Form-Rerender + Fehlerliste. |
| **9** | Marie | UX/Flow | `addrelease.php` und `addfile.php` akzeptieren `?ordner_id=` per GET und selektieren entsprechend vor | Integration: GET mit `ordner_id=5` → Select-Option 5 hat `selected`. |
| **10** | Sven | Filesystem | `addscreen.php`: MIME-Check via `finfo_file` statt `$_FILES[*]['type']` + Path-Traversal-Schutz für `release_id` | Unit-Test: `pdl_validate_screen_upload(['type'=>'image/png','tmp_name'=>...])`. |
| **11** | Karin | Audit | Audit-Log-Tabelle `pdl3_admin_log` (id, user_id, action, target_type, target_id, time, ip) und Helper `pdl_audit_log($action, $type, $id)` | Unit-Test: Helper schreibt korrekten Insert in Mock-DB. |
| **12** | Felix | Performance | `treeview_select` cached die Ordner-Liste pro Request (Static-Cache in der Funktion) | Unit-Test: Mock-DB liefert beim 2. Aufruf 0 Queries. |
| **13** | Robin, Lena | UX | Erfolgs-Banner zeigt angelegten Datensatznamen + ID | Integration: POST gültiger Daten → Banner enthält Namen. |
| **14** | Aisha | Refactor | Validierungs-Helper aus Admin-Skripten in eine wiederverwendbare Datei `pdl-inc/pdl_admin_validation.inc.php` extrahieren | Unit-Tests gegen alle exportierten Funktionen. |
| **15** | Tomasz | i18n | Texte in `pdl_admin_text($key)` kapseln, Default-Strings als deutsches Wörterbuch in `pdl-inc/pdl_admin_strings.inc.php` | Unit-Test: `pdl_admin_text('release_created')` liefert Default zurück; `pdl_admin_text('unknown')` liefert Key zurück. |

> Reduzierung auf umsetzbares Set: Punkte 1–14 werden in dieser Iteration umgesetzt. **Punkt 15 (i18n)** wird auf später vertagt — Refactor mit zu großer Reichweite (>30 Dateien), nicht im Scope „Release/Ordner hinzufügen".

---

## 3. Umsetzungsreihenfolge

1. **Validierungs-Helper** (`pdl-inc/pdl_admin_validation.inc.php`) erstellen (#4, #5, #6, #7, #10).
2. **Audit-Log-Helper** (`pdl-inc/pdl_admin_audit.inc.php`) erstellen (#11).
3. **CSRF-Schutz** in alle Admin-Schreib-Endpoints einbauen (#1).
4. **`addrelease.php`** umbauen: CSRF, Validierung, Form-Rerender, `?ordner_id=`-Vorselektion (#1, #4, #5, #6, #8, #9, #11, #13).
5. **`adddir.php`** umbauen: CSRF, Validierung, Form-Rerender, Parent-Check (#1, #4, #6, #8, #11, #13).
6. **`addfile.php`** umbauen: CSRF, Validierung, URL-Check, Release-Existenz, `editfiles`-Recht statt `adminaccess` (#1, #4, #5, #6, #11, #13).
7. **`addscreen.php`** umbauen: Rechte-Prüfung, CSRF, MIME-Check via `finfo`, Validierung (#1, #3, #10, #11).
8. **`editrelease.php` / `editdir.php`** umbauen: CSRF, Validierung, Self-Parent-Check, Form-Rerender, Audit (#1, #4, #5, #7, #8, #11).
9. **`delrelease.php` / `deldir.php`** umbauen: CSRF, `deldir` per POST + Bestätigung, Audit (#1, #2, #11).
10. **`treeview_select`** mit Static-Cache (#12).
11. **PHPUnit-Tests** für jeden neuen Helper schreiben (#14).
12. **PHPStan-Lauf** + Live-Re-Test im Browser.

---

## 4. Definition of Done

- [x] Alle Schreib-Endpoints des Add-/Edit-/Del-Flows enthalten `csrf_verify` (`addrelease`, `adddir`, `addfile`, `addscreen`, `editrelease`, `editdir`, `delrelease`, `deldir`).
- [x] Eingabe ohne Pflichtfelder wird abgelehnt und liefert verständliche Fehlermeldung (Live-verifiziert: leerer `name` → „Pflichtfeld").
- [x] Ungültige E-Mail-/URL-Werte werden serverseitig abgelehnt (Live-verifiziert: `NOT_EMAIL` + `javascript:alert(1)` → Banner mit Feldliste).
- [x] `ordner_id` muss existieren (oder = 0 für Root) — Live-verifiziert mit `ordner_id=99999`.
- [x] `deldir.php` löscht nur per POST + Bestätigungsdialog (Live-verifiziert: GET zeigt Dialog, `?submit=1` per GET wird abgelehnt).
- [x] `addscreen.php` prüft `editfiles`/`adminaccess`-Recht.
- [x] Audit-Log-Tabelle (`pdl3_admin_log`) wird automatisch angelegt und bei Create/Update/Delete befüllt (DB-Lookup bestätigt: 4 Einträge nach Tests).
- [x] Neue Helper sind in PHPUnit getestet: **49/49 neue Tests grün** (`AdminValidationTest`: 39, `AdminAuditTest`: 4, `TreeviewSelectTest`: 6). Davon zusätzlich `treeviewSelect*`-Tests in `FunctionsHelperTest` repariert.
- [x] PHPStan-Lauf **0 Errors** (Level 8).
- [x] Live-Re-Test via Chrome MCP: alle 8 Negativ-Pfade + Erfolgs-Path inkl. `?ordner_id=`-Vorselektion grün.

### Live-Verifikations-Matrix (Stand 2026-05-10, gegen `http://localhost:8092/pdl-admin/` als `admin/admin123`)

| Check | Ergebnis |
|-------|----------|
| `adddir` Form enthält CSRF-Token | ✅ |
| `adddir` POST ohne CSRF wird abgelehnt | ✅ |
| `adddir` POST mit leerem Namen wird abgelehnt | ✅ |
| `addrelease` Form enthält CSRF-Token | ✅ |
| `addrelease` POST mit ungültiger E-Mail/URL wird abgelehnt | ✅ |
| `addrelease` POST mit nicht-existenter `ordner_id` wird abgelehnt | ✅ |
| `addrelease?ordner_id=N` selektiert N im Treeview vor | ✅ |
| `deldir` zeigt Dialog statt direkter Löschung bei GET | ✅ |
| `deldir?submit=1` per GET wird abgelehnt | ✅ |
| Erfolgs-Path: Ordner + Release anlegen, dann beide löschen | ✅ |
| Audit-Log enthält Create/Delete-Einträge | ✅ |

### Geänderte / neue Dateien

**Neu:**
- `pdl-inc/pdl_admin_validation.inc.php` — Validierungs-Helper
- `pdl-inc/pdl_admin_audit.inc.php` — Audit-Log-Helper
- `tests/Unit/AdminValidationTest.php` — 39 Tests
- `tests/Unit/AdminAuditTest.php` — 4 Tests
- `tests/Unit/TreeviewSelectTest.php` — 6 Tests
- `tests/Integration/AdminCsrfTest.php` — E2E-Smoke-Test
- `tools/personas-verbesserungen.md` — dieses Dokument

**Geändert:**
- `pdl-inc/pdl_config.inc.php` — Tabelle `admin_log` ergänzt
- `pdl-inc/pdl_header.inc.php` — Helper-Includes
- `pdl-inc/pdl_functions.inc.php` — `treeview_select` mit Cache + `$selected`-Parameter, `treeview_select_reset_cache`
- `pdl-admin/adddir.php` — Validierung, CSRF, Audit, Pre-Selection, Form-Rerender bei Fehlern
- `pdl-admin/addrelease.php` — komplette Überarbeitung
- `pdl-admin/addfile.php` — komplette Überarbeitung
- `pdl-admin/addscreen.php` — Rechte-Check + komplette Überarbeitung
- `pdl-admin/editrelease.php` — komplette Überarbeitung
- `pdl-admin/editdir.php` — komplette Überarbeitung, Self-Parent-Schutz
- `pdl-admin/delrelease.php` — CSRF, Audit
- `pdl-admin/deldir.php` — POST + CSRF + Bestätigungsdialog
- `tests/Unit/FunctionsHelperTest.php` — `treeviewSelect*`-Tests an neues Cache-Verhalten angepasst
