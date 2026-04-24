# Userbereichs-Audit – Abschlussbericht 2026-04-23

**Anwendung**: PowerDownload v3.0.3
**Basis-URL**: http://localhost:8092/
**Stack**: PHP 8.4 · MySQL 8.0.45 · Apache 2.4.65 (Debian)
**Tester**: Senior QA (automatisiert via Chrome-MCP)
**Dauer**: Sessions vom 2026-04-23

## TL;DR

Der Userbereich von PowerDownload ist in seinem aktuellen Zustand **weitgehend funktional defekt**. Vier von sechs User-Center-Modulen (Registrierung, Profil-Update, Passwort-vergessen Schritt 1, Passwort-vergessen Schritt 2, Kommentare) können ihren Hauptzweck nicht erfüllen. Die gemeinsame Ursache ist ein **einzelner Architektur-Bug im zentralen Header**, der seit der Migration auf PHP 8 die automatische Variablen-Extraktion verloren hat (ehemals `register_globals`). Ein Single-Fix im Header würde 5 Blocker auf einmal schließen.

Zusätzlich wurden **mehrere Sicherheitsmängel** dokumentiert: schwache Passwort-Hashes (MD5), Passwort-Hash im Cookie (Pass-the-Hash), kein CSRF-Schutz, kein Rate-Limit, Klartext-Passwörter in E-Mails und eine Schema-Inkonsistenz zwischen Install-Skript und Laufzeit-DB.

## Testabdeckung im Überblick

- **30 Testpunkte** systematisch durchlaufen (UI, Konsole, Netzwerk, Persistenz, Negativfälle, Randfälle, SQLi- und XSS-Versuche).
- **27 GETESTET** (voll).
- **3 BLOCKIERT** durch fehlende Seed-Daten (Release-Detail mit echter ID, Kommentar-Post zu realem Release, Datei-Download).
- Alle erreichbaren Bereiche sind damit geprüft und dokumentiert.

## Gefundene Bugs (30)

- **blocker** (5): BUG-001, BUG-002, BUG-007, BUG-008, BUG-009
- **hoch** (9): BUG-003, BUG-004, BUG-005, BUG-010, BUG-013, BUG-016, BUG-018, BUG-019, BUG-027
- **mittel** (7): BUG-006, BUG-014, BUG-017, BUG-021, BUG-024, BUG-026, BUG-028
- **niedrig** (9): BUG-011, BUG-012, BUG-015, BUG-020, BUG-022, BUG-023, BUG-025, BUG-029, BUG-030

Details siehe `2026-04-23-Userbereichs-bugs.md`.

## Verbesserungs­vorschläge (20)

Fokus: Architektur (zentraler Request-Layer), Sicherheit (Sessions, CSRF, Rate-Limit, Passwort-Reset-Flow), UX (Fehler-Feedback, Form-Rerender), Barrierefreiheit/Markup, Installation/Migration, QA-Seed.

Details siehe `2026-04-23-Userbereichs-verbesserungen.md`.

## Phasen-Dokumentation

| Phase | Ziel | Status |
|-------|------|--------|
| 1 | Struktur erfassen, Testmatrix aufbauen | abgeschlossen |
| 2 | Alle Bereiche systematisch testen | abgeschlossen |
| 3 | Bugs & Improvements fortlaufend dokumentieren | abgeschlossen |
| 4 | Testabdeckung prüfen, Lücken schließen | abgeschlossen |
| 5 | Abschlussbericht | dieses Dokument |

## Risiko-Bewertung

- **Produktionsreife**: aktuell **nicht gegeben**. Registrierung, Profil, Passwortvergessen, Kommentare funktionieren nicht.
- **Sicherheitslage**: mehrere hohe Findings (MD5, Cookie-Hash, fehlender CSRF, kein Rate-Limit).
- **Datenintegrität**: Schema-Inkonsistenz zwischen Install-Skript und Docker-DB (BUG-027) führt dazu, dass Register-INSERT auch nach Behebung von BUG-001 weiterhin scheitern würde.
- **Quick-Wins**:
  - Root-Cause-Fix für BUG-001 (zentraler Header) löst 5 Blocker gleichzeitig.
  - Schema-Sync zwischen `install_querys.inc` und Docker-Seed (BUG-027).
  - `md5()` → `password_verify()` + Migration (BUG-004, BUG-002).
  - Login-Fehlermeldung + Rate-Limit (BUG-005, BUG-019).

## Dateien dieses Audits

- `docs/2026-04-23-Userbereichs-bugs.md`
- `docs/2026-04-23-Userbereichs-improvements.md`
- `docs/2026-04-23-Userbereichs-verbesserungen.md` (identischer Inhalt wie improvements; gem. Abschlusskriterien auch mit deutschem Namen)
- `docs/2026-04-23-Userbereichs-test-coverage.md`
- `docs/2026-04-23-Userbereichs-testabdeckung.md` (identischer Inhalt wie test-coverage; gem. Abschlusskriterien auch mit deutschem Namen)
- `docs/2026-04-23-Userbereichs-abschlussbericht.md` (dieses Dokument)

## Empfohlene Nächste Schritte (für Entwickler-Team, nicht Teil dieses Audits)

1. BUG-001 fixen (zentraler Request-Layer) → 5 Blocker zugleich lösen.
2. BUG-027 prüfen und Schema harmonisieren.
3. MD5-Login auf `password_verify()` umstellen, Migrationspfad `password_needs_rehash()`.
4. Session-basierte Authentifizierung, Pass-the-Hash-Cookie entfernen.
5. CSRF-Token-Helper einführen.
6. Rate-Limit und Login-Fehlermeldung implementieren.
7. Saubere URL-Routen und semantische HTTP-Statuscodes.
8. Regressionstests für Register/Login/Profil/Lost/Comments auf der CI.
9. QA-Seed mit Beispiel-Releases und Testnutzer.
