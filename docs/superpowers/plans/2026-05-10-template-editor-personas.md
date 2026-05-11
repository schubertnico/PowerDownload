# Template-Editor (templates.php) — 10 Personas und Anforderungen

## Persona 1 — Marcel, 38, Senior-Webentwickler
- Kennt HTML/CSS/JS auswendig.
- Erwartet: Syntax-Highlighting, Tab-Indent, Zeilennummern, Vollbild-Modus, schnelles Speichern via Tastatur (Strg+S).
- Frust: kleine Textareas, kein Highlighting, Tab springt aus dem Feld.

## Persona 2 — Sandra, 52, Marketing-Managerin (kein HTML-Wissen)
- Soll nur kleine Texte austauschen ("Bitte einloggen" → "Jetzt anmelden").
- Erwartet: deutliche Beschreibung WAS dieses Template tut, klar markierte Platzhalter, Reset-Knopf wenn etwas kaputt gegangen ist.
- Frust: Wand aus HTML, keine Hinweise was geändert werden darf.

## Persona 3 — Jonas, 24, Junior-Designer
- Lernt durch Ausprobieren.
- Erwartet: Live-Preview, sichtbare Liste verfügbarer Platzhalter mit Beispielausgabe, Klick-zum-Einfügen.
- Frust: muss zwischen mehreren Tabs wechseln, keine sofortige Rückmeldung.

## Persona 4 — Frau Müller, 65, Vereinsvorstand
- Schlechte Augen, nutzt Browser-Zoom + Lesebrille.
- Erwartet: große Schrift, hoher Kontrast, klar beschriftete Buttons ("Speichern" statt nur Disketten-Icon), Touch-friendly.
- Frust: 10px-Editor, graue Schrift, mysterische Icons.

## Persona 5 — Tobias, 30, mobiler Admin
- Pflegt unterwegs vom Tablet/Smartphone.
- Erwartet: responsiver Editor, Vollbild-Modus auf kleinem Schirm, Touch-taugliche Buttons (≥ 44×44 px).
- Frust: horizontales Scrollen, fixe Breiten.

## Persona 6 — Anna, 28, mit Sehschwäche
- Browser-Zoom 200 %, hohe Kontraste, Screenreader gelegentlich.
- Erwartet: Schriftgröße einstellbar im Editor, Fokus-Outline klar, semantische Struktur (`<label for>`, `aria-describedby`, `aria-current`).
- Frust: Editor-Theme mit grauer Schrift auf grauem BG, kein sichtbarer Fokusrahmen.

## Persona 7 — Lukas, 19, Praktikant
- Klickt erst, denkt später.
- Erwartet: Reset-Button (zurück zum Standard), Geändert-Indikator (rot/animiert), Bestätigung beim Verlassen mit ungespeicherten Änderungen, Undo via Strg+Z.
- Frust: Alles kaputt, keine Wiederherstellung.

## Persona 8 — Frau Dr. Schmidt, 45, Datenschutz-/Sicherheits-Beauftragte
- Reviewt Templates auf XSS, eval, Datenleaks.
- Erwartet: Sicherheits-Hinweis-Box (Was passiert mit `<script>`?), Hinweis auf eval-/Replace-Logik, Liste der erlaubten Platzhalter.
- Frust: keine Doku, keine Warnung, freier eval()-Pfad.

## Persona 9 — Peter, 35, QA-Tester
- Vergleicht aktuellen Stand mit Soll.
- Erwartet: kompakter Quick-Reference-Bereich mit allen Platzhaltern + Erklärungen, Volltext-Suche im Editor (Strg+F), Sprungnavigation zwischen Templates.
- Frust: Platzhalter-Doku ist auf einer anderen Seite.

## Persona 10 — Mehmet, 42, Tastatur-Power-User
- Fast nur Tastatur, dvorak-Layout.
- Erwartet: alle Funktionen tastaturbedienbar, Tab-Navigation zwischen Editoren, Strg+S speichert, Strg+/ kommentiert, Esc verlässt Vollbild.
- Frust: nur Maus-Bedienung, Tab springt unsinnig.

---

## Konsolidierte Anforderungen

| # | Anforderung | Persona(s) |
|---|-------------|------------|
| 1 | Syntax-Highlighting (HTML) | 1, 3, 6 |
| 2 | Zeilennummern | 1, 9 |
| 3 | Tab-Indent (statt Fokuswechsel) | 1, 10 |
| 4 | Vollbild-Modus | 1, 5 |
| 5 | Schriftgröße einstellbar | 4, 6 |
| 6 | Hoher Kontrast (Theme) | 4, 6 |
| 7 | Touch-taugliche Buttons (≥ 44 px) | 4, 5 |
| 8 | Sticky Sidebar mit klickbaren Platzhaltern | 2, 3, 9 |
| 9 | Beschreibung des Templates prominent | 2 |
| 10 | Reset auf Original-Wert | 2, 7 |
| 11 | Geändert-Indikator | 7 |
| 12 | Strg+S speichert | 1, 10 |
| 13 | Strg+F sucht | 1, 9 |
| 14 | Esc verlässt Vollbild | 1, 10 |
| 15 | Beforeunload-Bestätigung bei ungespeicherten Änderungen | 7 |
| 16 | Sicherheits-Hinweise (Plaintext, kein eval) | 8 |
| 17 | Sticky Save-Bar unten | 1, 5, 7 |
| 18 | Sprungnavigation Templates (Quick-Jump) | 1, 9 |
| 19 | Sichtbare Help-Box mit Platzhalterliste | 2, 3, 8, 9 |
| 20 | Mobile-friendly Layout | 5 |

## Technische Lösung

- **CodeMirror 5** (klein, stabil, gut getestet) via CDN — Syntax-Highlighting,
  Zeilennummern, Tab-Indent, Strg+F-Suche, Strg+/-Kommentar.
- **Sticky Sidebar** rechts mit Platzhalter-Liste — Klick fügt am Cursor ein.
- **Vollbild-Modus** über CodeMirror's Add-On `display/fullscreen` (Esc-Bedienung).
- **Reset-Knopf** pro Editor — speichert Initialwert beim Laden in `data-original`.
- **Geändert-Indikator** — Klasse `.pdl-tpl-dirty` auf Editor wenn `isClean()` false.
- **Sticky Save-Bar** unten — bleibt sichtbar beim Scrollen.
- **Beforeunload** — `window.onbeforeunload` warnt bei dirty-Editoren.
- **Sicherheits-Hinweis-Card** oben — kurz und sichtbar.
- **Schriftgröße-Picker** — A− / A0 / A+ Buttons im Editor-Header.
- **Quick-Jump-Nav** — Bootstrap nav-pills (bereits vorhanden).
- **Keyboard-Shortcuts** — globaler `keydown`-Listener für Strg+S.
