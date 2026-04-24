-- =============================================================
-- Migration: Seed missing info-box templates and fix newsletter
--            checkbox label (BUG-011, BUG-023)
-- Date:      2026-04-23
--
-- BUG-011: Die Info-Widgets (Stats, Top, Flop, Latest, Rated) hatten
--          keine passenden Template-Eintraege in pdl3_template, weshalb
--          die Boxen auf downloads.php leer blieben.
--
-- BUG-023: In den Templates uregister_form und uprofil_form fehlte
--          ein sprechendes Label fuer die Newsletter-Checkbox.
--
-- Die Migration ist idempotent: Inserts nutzen INSERT IGNORE anhand
-- des UNIQUE-Index auf variablenname, Updates sind ohnehin idempotent.
-- =============================================================

USE pdl3;

-- -------------------------------------------------------------
-- BUG-011: Templategroup fuer Info-Boxen (falls noch nicht da)
-- -------------------------------------------------------------
INSERT INTO pdl3_templategroup (tgroup_id, name, reihenfolge)
VALUES (5, 'Info-Boxen', 3)
ON DUPLICATE KEY UPDATE name = VALUES(name), reihenfolge = VALUES(reihenfolge);

-- -------------------------------------------------------------
-- BUG-011: Stats-Box
-- -------------------------------------------------------------
INSERT IGNORE INTO pdl3_template (variablenname, name, bez, eingabe, wert, tgroup_id, reihenfolge) VALUES (
    'stats',
    'Statistik Box',
    'HTML Template fuer die Statistik-Box auf der Startseite',
    'textarea',
    '<table border="0" cellpadding="5" cellspacing="1" bgcolor="#333333" width="100%">\n<tr><td bgcolor="#444444" align="center"><b>Statistik</b></td></tr>\n<tr><td bgcolor="#222222">Dateien: {files}</td></tr>\n<tr><td bgcolor="#222222">Gesamtgroesse: {size}</td></tr>\n<tr><td bgcolor="#222222">Downloads: {downloads}</td></tr>\n<tr><td bgcolor="#222222">Traffic: {traffic}</td></tr>\n<tr><td bgcolor="#222222">Downloads/Tag: {durch_downloads}</td></tr>\n<tr><td bgcolor="#222222">Traffic/Tag: {durch_traffic}</td></tr>\n</table>',
    5, 1
);

-- -------------------------------------------------------------
-- BUG-011: Top-Downloads
-- -------------------------------------------------------------
INSERT IGNORE INTO pdl3_template (variablenname, name, bez, eingabe, wert, tgroup_id, reihenfolge) VALUES (
    'top_box',
    'Top-Downloads Box',
    'Rahmen-HTML fuer die Top-Downloads-Box (Platzhalter {rows})',
    'textarea',
    '<table border="0" cellpadding="5" cellspacing="1" bgcolor="#333333" width="100%">\n<tr><td bgcolor="#444444" align="center"><b>Top Downloads</b></td></tr>\n{rows}\n</table>',
    5, 2
);

INSERT IGNORE INTO pdl3_template (variablenname, name, bez, eingabe, wert, tgroup_id, reihenfolge) VALUES (
    'top_row',
    'Top-Downloads Zeile',
    'HTML Template fuer eine Zeile innerhalb der Top-Downloads-Box',
    'textarea',
    '<tr><td bgcolor="#222222">{count}. <a href="downloads.php?release_id={id}">{name}</a> ({downloads} Downloads)</td></tr>',
    5, 3
);

-- -------------------------------------------------------------
-- BUG-011: Flop-Downloads
-- -------------------------------------------------------------
INSERT IGNORE INTO pdl3_template (variablenname, name, bez, eingabe, wert, tgroup_id, reihenfolge) VALUES (
    'flop_box',
    'Flop-Downloads Box',
    'Rahmen-HTML fuer die Flop-Downloads-Box (Platzhalter {rows})',
    'textarea',
    '<table border="0" cellpadding="5" cellspacing="1" bgcolor="#333333" width="100%">\n<tr><td bgcolor="#444444" align="center"><b>Flop Downloads</b></td></tr>\n{rows}\n</table>',
    5, 4
);

INSERT IGNORE INTO pdl3_template (variablenname, name, bez, eingabe, wert, tgroup_id, reihenfolge) VALUES (
    'flop_row',
    'Flop-Downloads Zeile',
    'HTML Template fuer eine Zeile innerhalb der Flop-Downloads-Box',
    'textarea',
    '<tr><td bgcolor="#222222">{count}. <a href="downloads.php?release_id={id}">{name}</a> ({downloads} Downloads)</td></tr>',
    5, 5
);

-- -------------------------------------------------------------
-- BUG-011: Latest-Downloads
-- -------------------------------------------------------------
INSERT IGNORE INTO pdl3_template (variablenname, name, bez, eingabe, wert, tgroup_id, reihenfolge) VALUES (
    'latest_box',
    'Neueste Downloads Box',
    'Rahmen-HTML fuer die "Neueste Downloads" Box (Platzhalter {rows})',
    'textarea',
    '<table border="0" cellpadding="5" cellspacing="1" bgcolor="#333333" width="100%">\n<tr><td bgcolor="#444444" align="center"><b>Neueste Downloads</b></td></tr>\n{rows}\n</table>',
    5, 6
);

INSERT IGNORE INTO pdl3_template (variablenname, name, bez, eingabe, wert, tgroup_id, reihenfolge) VALUES (
    'latest_row',
    'Neueste Downloads Zeile',
    'HTML Template fuer eine Zeile innerhalb der "Neueste Downloads" Box',
    'textarea',
    '<tr><td bgcolor="#222222">{count}. <a href="downloads.php?release_id={id}">{name}</a></td></tr>',
    5, 7
);

-- -------------------------------------------------------------
-- BUG-011: Best-Rated
-- -------------------------------------------------------------
INSERT IGNORE INTO pdl3_template (variablenname, name, bez, eingabe, wert, tgroup_id, reihenfolge) VALUES (
    'rated_box',
    'Best bewertete Box',
    'Rahmen-HTML fuer die "Best bewertete" Box (Platzhalter {rows})',
    'textarea',
    '<table border="0" cellpadding="5" cellspacing="1" bgcolor="#333333" width="100%">\n<tr><td bgcolor="#444444" align="center"><b>Best bewertet</b></td></tr>\n{rows}\n</table>',
    5, 8
);

INSERT IGNORE INTO pdl3_template (variablenname, name, bez, eingabe, wert, tgroup_id, reihenfolge) VALUES (
    'rated_row',
    'Best bewertete Zeile',
    'HTML Template fuer eine Zeile innerhalb der "Best bewertete" Box',
    'textarea',
    '<tr><td bgcolor="#222222">{count}. <a href="downloads.php?release_id={id}">{name}</a> (Note: {vote})</td></tr>',
    5, 9
);

-- =============================================================
-- BUG-023: Newsletter-Checkbox mit sprechendem Label
-- =============================================================

-- uregister_form: Platzhalter {get_letter} fuer "checked"-State
--                 hinzufuegen und sprechendes Label setzen.
UPDATE pdl3_template SET wert =
'<table border="0" cellpadding="5" cellspacing="1" bgcolor="#333333">
<tr><td bgcolor="#444444" colspan="2" align="center"><b>Registrierung</b></td></tr>
<tr><td bgcolor="#222222">Nickname:</td><td bgcolor="#222222"><input type="text" name="nick" size="20" value="{nick}"></td></tr>
<tr><td bgcolor="#222222">E-Mail:</td><td bgcolor="#222222"><input type="text" name="email" size="20" value="{email}"></td></tr>
<tr><td bgcolor="#222222">Passwort:</td><td bgcolor="#222222"><input type="password" name="pw_new" size="20"></td></tr>
<tr><td bgcolor="#222222">Passwort (Wdh.):</td><td bgcolor="#222222"><input type="password" name="pw_new2" size="20"></td></tr>
<tr><td bgcolor="#222222">Homepage:</td><td bgcolor="#222222"><input type="text" name="homepage" size="20" value="{homepage}"></td></tr>
<tr><td bgcolor="#222222">ICQ:</td><td bgcolor="#222222"><input type="text" name="icq" size="20" value="{icq}"></td></tr>
<tr><td bgcolor="#222222">Newsletter:</td><td bgcolor="#222222"><label><input type="checkbox" name="get_letter" value="Y"{get_letter}> Ja, ich moechte den Newsletter abonnieren</label></td></tr>
<tr><td bgcolor="#444444" colspan="2" align="center"><input type="submit" value="Registrieren"></td></tr>
</table>'
WHERE variablenname = 'uregister_form';

-- uprofil_form: {get_letter}-Platzhalter war schon drin, nur Label nachruesten.
UPDATE pdl3_template SET wert =
'<table border="0" cellpadding="5" cellspacing="1" bgcolor="#333333">
<tr><td bgcolor="#444444" colspan="2" align="center"><b>Profil bearbeiten</b></td></tr>
<tr><td bgcolor="#222222">E-Mail:</td><td bgcolor="#222222"><input type="text" name="email" size="30" value="{email}"></td></tr>
<tr><td bgcolor="#222222">Homepage:</td><td bgcolor="#222222"><input type="text" name="homepage" size="30" value="{homepage}"></td></tr>
<tr><td bgcolor="#222222">ICQ:</td><td bgcolor="#222222"><input type="text" name="icq" size="15" value="{icq}"></td></tr>
<tr><td bgcolor="#222222">Newsletter:</td><td bgcolor="#222222"><label><input type="checkbox" name="get_letter" value="Y"{get_letter}> Ja, ich moechte den Newsletter abonnieren</label></td></tr>
<tr><td bgcolor="#222222">Altes Passwort:</td><td bgcolor="#222222"><input type="password" name="pw_old" size="20"></td></tr>
<tr><td bgcolor="#222222">Neues Passwort:</td><td bgcolor="#222222"><input type="password" name="pw_new" size="20"></td></tr>
<tr><td bgcolor="#222222">Neues Passwort (Wdh.):</td><td bgcolor="#222222"><input type="password" name="pw_new2" size="20"></td></tr>
<tr><td bgcolor="#444444" colspan="2" align="center"><input type="submit" value="Speichern"></td></tr>
</table>'
WHERE variablenname = 'uprofil_form';
