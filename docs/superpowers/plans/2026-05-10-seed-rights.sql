-- Seed der pdl3_rights-Tabelle.
--
-- Die Tabelle wird im Original-Setup ueber install_querys.inc gefuellt,
-- das aktuelle setup.php hat die Eintraege bisher nicht angelegt.
-- Ohne diese Eintraege rendert editdelugroup.php / addugroup.php keine
-- Rechte-Auswahl, weil die WHILE-Schleife ueber pdl3_rights iteriert.
--
-- Idempotent: nutzt INSERT IGNORE. Bei bestehenden Datensaetzen werden
-- nur die Beschreibungen aktualisiert (UTF-8-Umlaute statt ASCII).

INSERT IGNORE INTO pdl3_rights (right_id, name, bez, variablenname, reihenfolge) VALUES
  (1, 'Download', 'Hat der User das Recht eine Datei zu downloaden?', 'download', 1),
  (2, 'Votes', 'Hat der User das Recht einen Release zu bewerten?', 'vote', 2),
  (3, 'Kommentare posten', 'Hat der User das Recht Kommentare zu posten?', 'addcomments', 3),
  (4, 'Files Adden', 'Hat der User das Recht Files zu adden?', 'addfiles', 4),
  (5, 'Admin Access', 'Hat der User Zugang zum Adminbereich? Ermöglicht das Adden von Dateien ohne Freischaltung.', 'adminaccess', 5),
  (6, 'Files editieren', 'Hat der User das Recht Files zu editieren? Admin Access wird benötigt.', 'editfiles', 6),
  (7, 'Files löschen', 'Hat der User das Recht Files zu löschen? Admin Access wird benötigt.', 'delfiles', 7),
  (8, 'Ordner hinzufügen', 'Hat der User das Recht Ordner hinzuzufügen? Admin Access wird benötigt.', 'adddirs', 8),
  (9, 'Ordner editieren', 'Hat der User das Recht Ordner zu editieren? Admin Access wird benötigt.', 'editdirs', 9),
  (10, 'Ordner löschen', 'Hat der User das Recht Ordner zu löschen? Admin Access wird benötigt.', 'deldirs', 10),
  (11, 'User hinzufügen', 'Hat der User das Recht neue User anzulegen? Admin Access wird benötigt.', 'adduser', 11),
  (12, 'User editieren', 'Hat der User das Recht User zu editieren? Admin Access wird benötigt.', 'edituser', 12),
  (13, 'User löschen', 'Hat der User das Recht User zu löschen? Admin Access wird benötigt.', 'deluser', 13),
  (14, 'Settings verwalten', 'Hat der User das Recht die globalen Settings zu ändern? Admin Access wird benötigt.', 'settings', 14),
  (15, 'Templates verwalten', 'Hat der User das Recht die Templates zu verwalten? Admin Access wird benötigt.', 'templates', 15),
  (16, 'Replacements verwalten', 'Hat der User das Recht die Replacements (Smilies, Glossar, Zensur) zu verwalten? Admin Access wird benötigt.', 'replacements', 16),
  (17, 'Datenbank-Backup', 'Hat der User das Recht ein Datenbank-Backup zu erstellen oder einzuspielen? Admin Access wird benötigt.', 'backup', 17),
  (18, 'Kommentare moderieren', 'Hat der User das Recht Kommentare anderer Nutzer zu sehen und zu moderieren?', 'comment', 18);

-- Bestehende Datensaetze (z.B. aus aelteren Migrationen) auf UTF-8-Umlaute heben.
UPDATE pdl3_rights SET name = 'Files löschen', bez = 'Hat der User das Recht Files zu löschen? Admin Access wird benötigt.' WHERE variablenname = 'delfiles';
UPDATE pdl3_rights SET name = 'Ordner hinzufügen', bez = 'Hat der User das Recht Ordner hinzuzufügen? Admin Access wird benötigt.' WHERE variablenname = 'adddirs';
UPDATE pdl3_rights SET name = 'Ordner löschen', bez = 'Hat der User das Recht Ordner zu löschen? Admin Access wird benötigt.' WHERE variablenname = 'deldirs';
UPDATE pdl3_rights SET name = 'User hinzufügen', bez = 'Hat der User das Recht neue User anzulegen? Admin Access wird benötigt.' WHERE variablenname = 'adduser';
UPDATE pdl3_rights SET name = 'User löschen', bez = 'Hat der User das Recht User zu löschen? Admin Access wird benötigt.' WHERE variablenname = 'deluser';
UPDATE pdl3_rights SET bez = 'Hat der User das Recht Files zu editieren? Admin Access wird benötigt.' WHERE variablenname = 'editfiles';
UPDATE pdl3_rights SET bez = 'Hat der User das Recht Ordner zu editieren? Admin Access wird benötigt.' WHERE variablenname = 'editdirs';
UPDATE pdl3_rights SET bez = 'Hat der User das Recht User zu editieren? Admin Access wird benötigt.' WHERE variablenname = 'edituser';
UPDATE pdl3_rights SET bez = 'Hat der User das Recht die globalen Settings zu ändern? Admin Access wird benötigt.' WHERE variablenname = 'settings';
UPDATE pdl3_rights SET bez = 'Hat der User das Recht die Templates zu verwalten? Admin Access wird benötigt.' WHERE variablenname = 'templates';
UPDATE pdl3_rights SET bez = 'Hat der User das Recht die Replacements (Smilies, Glossar, Zensur) zu verwalten? Admin Access wird benötigt.' WHERE variablenname = 'replacements';
UPDATE pdl3_rights SET bez = 'Hat der User das Recht ein Datenbank-Backup zu erstellen oder einzuspielen? Admin Access wird benötigt.' WHERE variablenname = 'backup';
UPDATE pdl3_rights SET bez = 'Hat der User Zugang zum Adminbereich? Ermöglicht das Adden von Dateien ohne Freischaltung.' WHERE variablenname = 'adminaccess';
