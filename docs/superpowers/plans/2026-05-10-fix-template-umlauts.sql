-- Korrigiert ASCII-Umlaute in Beschreibungstexten der pdl3_template-Tabelle.
--
-- Hintergrund: Eine fruehere Migration hat die Beschreibungen mit ue/ae/oe
-- statt mit echten Umlauten geseedet. Im Admin-Bereich (templates.php,
-- editdeltemplatestgroup.php) wirkt das unprofessionell und ist fuer
-- Nutzer mit Sehschwaeche ein Hindernis.
--
-- Idempotent: REPLACE auf den exakten ASCII-Strings, ueberschreibt nur
-- bei Treffer.

UPDATE pdl3_template SET bez = REPLACE(bez, 'fuer', 'für') WHERE bez LIKE '%fuer%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'ueber', 'über') WHERE bez LIKE '%ueber%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'Ueber', 'Über') WHERE bez LIKE '%Ueber%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'aendern', 'ändern') WHERE bez LIKE '%aendern%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'Aendern', 'Ändern') WHERE bez LIKE '%Aendern%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'fuegen', 'fügen') WHERE bez LIKE '%fuegen%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'loeschen', 'löschen') WHERE bez LIKE '%loeschen%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'Loeschen', 'Löschen') WHERE bez LIKE '%Loeschen%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'naechs', 'nächs') WHERE bez LIKE '%naechs%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'moeglich', 'möglich') WHERE bez LIKE '%moeglich%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'gehoer', 'gehör') WHERE bez LIKE '%gehoer%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'Groesse', 'Größe') WHERE bez LIKE '%Groesse%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'groesse', 'größe') WHERE bez LIKE '%groesse%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'Hoehe', 'Höhe') WHERE bez LIKE '%Hoehe%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'waehlen', 'wählen') WHERE bez LIKE '%waehlen%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'Waehlen', 'Wählen') WHERE bez LIKE '%Waehlen%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'erlaeut', 'erläut') WHERE bez LIKE '%erlaeut%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'gefaehr', 'gefähr') WHERE bez LIKE '%gefaehr%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'ungueltig', 'ungültig') WHERE bez LIKE '%ungueltig%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'Bestaetig', 'Bestätig') WHERE bez LIKE '%Bestaetig%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'bestaetig', 'bestätig') WHERE bez LIKE '%bestaetig%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'pruefen', 'prüfen') WHERE bez LIKE '%pruefen%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'Pruefung', 'Prüfung') WHERE bez LIKE '%Pruefung%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'verfueg', 'verfüg') WHERE bez LIKE '%verfueg%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'Verfueg', 'Verfüg') WHERE bez LIKE '%Verfueg%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'tatsaech', 'tatsäch') WHERE bez LIKE '%tatsaech%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'saemtlich', 'sämtlich') WHERE bez LIKE '%saemtlich%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'naehere', 'nähere') WHERE bez LIKE '%naehere%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'naeher', 'näher') WHERE bez LIKE '%naeher%';
UPDATE pdl3_template SET bez = REPLACE(bez, 'moecht', 'möcht') WHERE bez LIKE '%moecht%';

-- Auch in pdl3_template.name fuer den Fall, dass jemand falsche Schreibweise reingeseedet hat.
UPDATE pdl3_template SET name = REPLACE(name, 'fuer', 'für') WHERE name LIKE '%fuer%';
UPDATE pdl3_template SET name = REPLACE(name, 'ueber', 'über') WHERE name LIKE '%ueber%';
UPDATE pdl3_template SET name = REPLACE(name, 'Ueber', 'Über') WHERE name LIKE '%Ueber%';
UPDATE pdl3_template SET name = REPLACE(name, 'fuegen', 'fügen') WHERE name LIKE '%fuegen%';
UPDATE pdl3_template SET name = REPLACE(name, 'loeschen', 'löschen') WHERE name LIKE '%loeschen%';
UPDATE pdl3_template SET name = REPLACE(name, 'Loeschen', 'Löschen') WHERE name LIKE '%Loeschen%';

-- Gleiches Spiel fuer pdl3_settings (falls die Tabelle name/bez Spalten besitzt).
-- Die Spalten existieren nicht in jeder Schema-Variante. Daher werden die
-- Statements in einem eigenen IF-NOT-EXISTS-aehnlichen Konstrukt gekapselt.
SET @has_name = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdl3_settings' AND column_name = 'name');
SET @has_bez = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdl3_settings' AND column_name = 'bez');

SET @sql_n = IF(@has_name > 0, "UPDATE pdl3_settings SET name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'fuer', 'für'), 'ueber', 'über'), 'aendern', 'ändern'), 'fuegen', 'fügen'), 'loeschen', 'löschen') WHERE name REGEXP 'fuer|ueber|aendern|fuegen|loeschen'", "SELECT 'skip pdl3_settings.name' AS msg");
PREPARE stmt FROM @sql_n; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql_b = IF(@has_bez > 0, "UPDATE pdl3_settings SET bez = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(bez, 'fuer', 'für'), 'ueber', 'über'), 'aendern', 'ändern'), 'fuegen', 'fügen'), 'loeschen', 'löschen'), 'moeglich', 'möglich'), 'naehere', 'nähere'), 'gehoer', 'gehör'), 'groesse', 'größe'), 'waehlen', 'wählen') WHERE bez REGEXP 'fuer|ueber|aendern|fuegen|loeschen|moeglich|naehere|gehoer|groesse|waehlen'", "SELECT 'skip pdl3_settings.bez' AS msg");
PREPARE stmt FROM @sql_b; EXECUTE stmt; DEALLOCATE PREPARE stmt;
