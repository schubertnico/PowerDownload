-- Entfernt ICQ-Felder aus dem PowerDownload-Schema.
-- ICQ wurde 2024 endgueltig abgeschaltet und ist kein relevanter
-- Kontaktkanal mehr. Wir entfernen die DB-Spalten + alle Template-
-- Platzhalter, damit die Profil-/Registrierungs-Forms aufgeraeumt sind.
--
-- Idempotent: prueft per information_schema, ob die Spalten/Templates
-- existieren, bevor sie entfernt werden.

-- 1) DB-Spalten entfernen (ALTER TABLE DROP COLUMN, falls vorhanden).
SET @has_user_icq = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdl3_user' AND column_name = 'icq');
SET @sql = IF(@has_user_icq > 0, 'ALTER TABLE pdl3_user DROP COLUMN icq', 'SELECT "skip pdl3_user.icq" AS msg');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_release_icq = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdl3_release' AND column_name = 'autor_icq');
SET @sql = IF(@has_release_icq > 0, 'ALTER TABLE pdl3_release DROP COLUMN autor_icq', 'SELECT "skip pdl3_release.autor_icq" AS msg');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) ICQ-Zeilen aus den DB-Templates herausschneiden.
-- Wir matchen die exakte ICQ-Zeile inkl. der nachfolgenden \n und ersetzen
-- sie durch leeren String.
UPDATE pdl3_template
SET wert = REPLACE(
    wert,
    '<tr><td bgcolor="#222222">ICQ:</td><td bgcolor="#222222"><input type="text" name="icq" size="15" value="{icq}"></td></tr>\n',
    ''
)
WHERE variablenname = 'uprofil_form';

UPDATE pdl3_template
SET wert = REPLACE(
    wert,
    '<tr><td bgcolor="#222222">ICQ:</td><td bgcolor="#222222"><input type="text" name="icq" size="20" value="{icq}"></td></tr>\n',
    ''
)
WHERE variablenname = 'uregister_form';

-- Sicherheitsnetz: jede {icq}-Substitution, die noch existiert, durch leer ersetzen.
UPDATE pdl3_template SET wert = REPLACE(wert, '{icq}', '') WHERE wert LIKE '%{icq}%';

-- 3) Aus pdl3_rights eventuell vorhandene 'icq'-Referenzen entfernen
--    (kommt im aktuellen Seed nicht vor, aber idempotent).
DELETE FROM pdl3_rights WHERE variablenname = 'icq' OR variablenname = 'autor_icq';
