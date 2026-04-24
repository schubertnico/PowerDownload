# Userbereichs-Testabdeckung – 2026-04-23

Tester: Senior QA (Chrome-gestützt)
Basis-URL: http://localhost:8092/
Anwendung: PowerDownload v3.0.3 auf PHP 8.4 / MySQL 8.0.45 / Apache 2.4.65
Einstieg: http://localhost:8092/ → 302 Redirect → `/downloads.php`

## Test-Umfeld

- Container: `powerdownload_web` (Apache), `powerdownload_db` (MySQL 8), `powerdownload_phpmyadmin` (Port 8094)
- Seed-Daten: nur 1 Admin-User (`admin`/MD5 "admin123", ugroup_id=2), keine Ordner, keine Releases, keine Dateien.
- Test-Browser: Chrome via MCP-Anbindung, Viewport 1920×911.

## Routen-/Modul-Matrix

Das Routing des Userbereichs erfolgt über den GET/POST-Parameter `usercenter` in `/downloads.php`.
Quelle: `pdl-inc/pdl_downloads.inc.php` (`$allowed_modules`).

| # | Bereich                         | Route / Modul                                        | UI | Konsole | Netzwerk | Persistenz | Negativ | Status         | Findings                       |
|---|---------------------------------|------------------------------------------------------|----|---------|----------|------------|---------|----------------|--------------------------------|
| 1 | Startseite                      | `downloads.php`                                      | OK | OK      | 200      | n/a        | OK      | GETESTET       | BUG-011, BUG-022               |
| 2 | Registrierung – Form            | `?usercenter=register` (GET)                         | OK | OK      | 200      | n/a        | OK      | GETESTET       | BUG-014, BUG-023, IMP-007       |
| 3 | Registrierung – Submit          | `?usercenter=register&submit=1` (POST)               | OK | OK      | 200      | FEHLER     | OK      | GETESTET       | BUG-001, BUG-015, BUG-016       |
| 4 | Login – Form                    | `?usercenter=login` (GET)                            | OK | OK      | 200      | n/a        | OK      | GETESTET       | BUG-019, IMP-003                |
| 5 | Login – Submit positiv          | `?login=1` (POST admin)                              | OK | OK      | 200      | OK Cookie  | OK      | GETESTET       | BUG-003, BUG-004                |
| 6 | Login – Submit negativ          | `?login=1` (POST falsches PW)                        | OK | OK      | 200      | keine      | OK      | GETESTET       | BUG-005 (silent failure)        |
| 7 | Logout                          | `?logout=1` (GET)                                    | OK | OK      | 200→redir| Cookie leer| OK      | GETESTET       | OK                              |
| 8 | Passwort vergessen – Form       | `?usercenter=lost`                                   | OK | OK      | 200      | n/a        | OK      | GETESTET       | BUG-006                          |
| 9 | Passwort vergessen – Submit     | `?usercenter=lost&submit=1` (POST, existiert)        | OK | OK      | 200      | FEHLER     | OK      | GETESTET       | BUG-008, BUG-006                |
|10 | Passwort vergessen – Submit neg.| `?usercenter=lost&submit=1` (POST, fake mail)        | OK | OK      | 200      | n/a        | OK      | GETESTET       | BUG-006 (Enum)                   |
|11 | Passwort reset – Schritt 2      | `?usercenter=lost2&remind_code=…`                    | OK | OK      | 200      | FEHLER     | OK      | GETESTET       | BUG-009, BUG-017                |
|12 | Profil – Anzeige                | `?usercenter=profil` (eingeloggt)                    | OK | OK      | 200      | Prefill OK | OK      | GETESTET       | BUG-018 (ICQ)                    |
|13 | Profil – Anzeige (Gast)         | `?usercenter=profil` (ausgeloggt)                    | OK | OK      | 200      | n/a        | OK      | GETESTET       | OK (Hinweistext)                 |
|14 | Profil – Submit                 | `?usercenter=profil&submit=1`                        | OK | OK      | 200      | FEHLER     | OK      | GETESTET       | BUG-007 (Altes Passwort falsch)  |
|15 | Kommentare – Form (Gast)        | `?usercenter=comments&release_id=…`                  | OK | n/a     | 200      | n/a        | OK      | GETESTET*      | IMP-019 (kein Release vorhanden) |
|16 | Kommentare – Submit             | `?usercenter=comments&submit=1`                      | n/a| n/a     | n/a      | n/a        | n/a     | BLOCKIERT      | BUG-010 (kein Release vorhanden) |
|17 | Downloads-Liste (Wurzel)        | `?ordner_id=0`                                       | OK | OK      | 200      | leer       | OK      | GETESTET       | BUG-011, BUG-013, BUG-020       |
|18 | Downloads-Liste (nicht exist.)  | `?ordner_id=999`                                     | OK | OK      | 200      | leer       | OK      | GETESTET       | Keine 404 (IMP-008)              |
|19 | Release-Detail (exist.)         | `?release_id=<id>`                                   | n/a| n/a     | n/a      | n/a        | n/a     | BLOCKIERT      | keine Releases in DB             |
|20 | Release-Detail (nicht exist.)   | `?release_id=999` (admin)                            | OK | OK      | 200      | leer       | OK      | GETESTET       | BUG-012                          |
|21 | Screenshot-Anzeige              | `?screen_id=1` (nicht exist.)                        | OK | OK      | 200      | leer       | OK      | GETESTET       | IMP-008                          |
|22 | Suche                           | `?show_search=1`                                     | OK | OK      | 200      | n/a        | OK      | GETESTET       | global deaktiviert (kein Bug)    |
|23 | Statistik                       | `?show_stats=1`                                      | OK | OK      | 200      | OK         | OK      | GETESTET       | OK                               |
|24 | Sortierung / Pagination         | `?orderby=…&orderseq=…&perpage=…`                    | OK | OK      | 200      | leer       | OK      | GETESTET       | BUG-020, BUG-021                 |
|25 | Datei-Download                  | `?load_file=1&release_id=<id>`                       | n/a| n/a     | n/a      | n/a        | n/a     | BLOCKIERT      | keine Files in DB                |
|26 | XSS via usercenter-Param        | `?usercenter=<script>…`                              | OK | OK      | 200      | n/a        | OK      | GETESTET       | kein Reflected XSS               |
|27 | SQLi-Versuch `orderby`          | `?orderby=1' OR 1=1--`                               | OK | OK      | 200      | leer       | OK      | GETESTET       | keine SQL-Fehler sichtbar (Whitelist-Override, s. BUG-020) |
|28 | Eingeloggt → Register           | `?usercenter=register` (als admin)                   | OK | OK      | 200      | n/a        | OK      | GETESTET       | BUG-030                          |
|29 | Unbekanntes Modul               | `?usercenter=nonexistent`                            | OK | OK      | 200      | n/a        | OK      | GETESTET       | "Unbekanntes Modul." OK          |
|30 | Cookie-Persistenz nach Logout   | Cookies nach `logout=1`                              | OK | OK      | n/a      | Cookie leer| n/a     | GETESTET       | OK                               |

Legende: GETESTET = vollständig verifiziert, TEILWEISE = partiell, BLOCKIERT = nicht testbar wegen fehlender Daten, OK = bestanden, FEHLER = Funktion defekt.

---

## Gesamtstand

| Kategorie | Wert |
|-----------|------|
| Gesamt-Testpunkte | 30 |
| GETESTET (voll) | 27 |
| BLOCKIERT (Daten/Bug) | 3 |
| kritische Bugs (blocker) | 5 (BUG-001, BUG-002, BUG-007, BUG-008, BUG-009) |
| hohe Bugs | 9 (BUG-003, BUG-004, BUG-005, BUG-010, BUG-013, BUG-016, BUG-018, BUG-019, BUG-027) |
| mittlere Bugs | 7 |
| niedrige Bugs | 9 |
| Verbesserungsvorschläge | 20 |

Alle blockierenden Bugs teilen die gleiche Root-Cause (fehlende Extraktion von POST/GET-Variablen im Header nach Ende der register_globals-Ära); Fix an einer zentralen Stelle würde mehrere Blocker gleichzeitig heilen.

## Nicht testbar

Folgende Bereiche sind nicht testbar, weil die Datenbank leer ist:
- Release-Detail mit echten IDs
- Kommentar-Post zu bestehendem Release
- Datei-Download (`load_file=1`)
- Bewertung eines Releases
- Ordner-Listen mit Inhalt

Sobald Seed-Daten oder funktionierende Registrierung vorhanden sind, sollten diese Tests erneut durchlaufen.

## Konsolen- und Netzwerk-Beobachtungen

- Keine Browser-Konsolen-Fehler aus der Anwendung – alle LOG-Einträge kommen aus Chrome-Extensions (Content-Script v2.1-debug).
- Keine PHP-Notices/Warnings im HTML-Output (Error-Reporting im Produktionsmodus).
- HTTP-Statuscodes: Alle Seiten liefern 200 (auch Error-States wie „Release nicht gefunden") → IMP-008.
- Cookies: `login_id`/`login_pw` mit `HttpOnly`, `SameSite=Strict`, aber ohne `Secure` (BUG-028).
- Content-Security-Policy / HSTS / X-Frame-Options / Referrer-Policy / X-Content-Type-Options fehlen (nicht einzeln als Bug erfasst; siehe IMP-002 Teilbereich "Sicherheits-Header").

## Phasen-Status

| Phase | Status |
|-------|--------|
| Phase 1 – Struktur erfassen | ABGESCHLOSSEN |
| Phase 2 – Alle Bereiche testen | ABGESCHLOSSEN |
| Phase 3 – Dokumentation | ABGESCHLOSSEN |
| Phase 4 – Lücken schließen | ABGESCHLOSSEN (Blockierungen dokumentiert) |
| Phase 5 – Abschlussbericht | ABGESCHLOSSEN |
