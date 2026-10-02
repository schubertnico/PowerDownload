-- ===========================================================================
--  PowerDownload: Datenbankschema mit Grunddaten
-- ===========================================================================
--  Eine Quelle für alle Installationswege: den Web-Installer (install.php),
--  den Docker-Init (.docker/docker-compose.yml), die Tests und die
--  Video-Pipeline. Enthält bewusst KEIN DROP TABLE und KEIN Benutzerkonto:
--  Den ersten Administrator legt der Installer an, für die Docker-Entwicklung
--  .docker/initdb/02-dev-admin.sql.
--
--  Eine Anweisung je Datensatz, damit Änderungen an einzelnen Vorlagen oder
--  Einstellungen als kleine Diffs sichtbar bleiben.
--  Kollation utf8mb4_unicode_ci: läuft auf MySQL 8 und MariaDB 10.6+.
-- ===========================================================================

SET NAMES utf8mb4;


CREATE TABLE `pdl3_admin_log` (
  `log_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0',
  `action` varchar(32) NOT NULL,
  `target_type` varchar(32) NOT NULL,
  `target_id` int NOT NULL DEFAULT '0',
  `time` int NOT NULL,
  `ip` varchar(45) NOT NULL DEFAULT '',
  PRIMARY KEY (`log_id`),
  KEY `idx_target` (`target_type`,`target_id`),
  KEY `idx_user_time` (`user_id`,`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pdl3_comments` (
  `comment_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0',
  `release_id` int NOT NULL DEFAULT '0',
  `titel` varchar(128) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `time` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_id`),
  KEY `release_id` (`release_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pdl3_files` (
  `file_id` int unsigned NOT NULL AUTO_INCREMENT,
  `release_id` int NOT NULL DEFAULT '0',
  `downloads` int NOT NULL DEFAULT '0',
  `url` varchar(255) NOT NULL DEFAULT '',
  `size` bigint NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL DEFAULT '',
  `mirror` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`file_id`),
  KEY `release_id` (`release_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pdl3_iplock` (
  `ip` varchar(45) NOT NULL DEFAULT '',
  `time` int NOT NULL DEFAULT '0',
  `file_id` int NOT NULL DEFAULT '0',
  `user_id` int NOT NULL DEFAULT '0',
  `art` enum('comment','vote','login','register','lostpw','download') NOT NULL DEFAULT 'comment'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pdl3_ordner` (
  `ordner_id` int unsigned NOT NULL AUTO_INCREMENT,
  `sordner_id` int NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  PRIMARY KEY (`ordner_id`),
  KEY `sordner_id` (`sordner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pdl3_release` (
  `release_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `time` int NOT NULL DEFAULT '0',
  `views` int NOT NULL DEFAULT '0',
  `ordner_id` int NOT NULL DEFAULT '0',
  `uploader` int NOT NULL DEFAULT '0',
  `autor` int NOT NULL DEFAULT '0',
  `autor_nick` varchar(128) NOT NULL DEFAULT '',
  `autor_email` varchar(128) NOT NULL DEFAULT '',
  `autor_homepage` varchar(128) NOT NULL DEFAULT '',
  `released` enum('Y','N') NOT NULL DEFAULT 'Y',
  `votes` int NOT NULL DEFAULT '0',
  `voted` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`release_id`),
  KEY `ordner_id` (`ordner_id`),
  KEY `released` (`released`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pdl3_replacements` (
  `rep_id` int unsigned NOT NULL AUTO_INCREMENT,
  `old` varchar(128) NOT NULL DEFAULT '',
  `neu` varchar(255) NOT NULL DEFAULT '',
  `type` enum('s','g','b') NOT NULL DEFAULT 's',
  PRIMARY KEY (`rep_id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Standard-Smilies (Bilder unter pdl-gfx/smilies/)
INSERT INTO `pdl3_replacements` (`rep_id`, `old`, `neu`, `type`) VALUES (1,':-)','pdl-gfx/smilies/smile.gif','s');
INSERT INTO `pdl3_replacements` (`rep_id`, `old`, `neu`, `type`) VALUES (2,':-D','pdl-gfx/smilies/grins.gif','s');
INSERT INTO `pdl3_replacements` (`rep_id`, `old`, `neu`, `type`) VALUES (3,';-)','pdl-gfx/smilies/blink.gif','s');
INSERT INTO `pdl3_replacements` (`rep_id`, `old`, `neu`, `type`) VALUES (4,':-P','pdl-gfx/smilies/tongue.gif','s');
INSERT INTO `pdl3_replacements` (`rep_id`, `old`, `neu`, `type`) VALUES (5,'8-)','pdl-gfx/smilies/cool.gif','s');
INSERT INTO `pdl3_replacements` (`rep_id`, `old`, `neu`, `type`) VALUES (6,':-(','pdl-gfx/smilies/disappointed.gif','s');

CREATE TABLE `pdl3_rights` (
  `right_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `bez` varchar(255) NOT NULL DEFAULT '',
  `variablenname` varchar(32) NOT NULL DEFAULT '',
  `reihenfolge` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`right_id`),
  UNIQUE KEY `variablenname` (`variablenname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (1,'Downloads erlaubt','Darf Dateien über den Download-Link herunterladen.','download',1);
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (2,'Releases bewerten','Darf Releases mit 1 bis 10 Punkten bewerten.','vote',2);
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (3,'Kommentare schreiben','Darf Kommentare zu Releases schreiben.','addcomments',3);
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (4,'Releases und Dateien hinzufügen','Darf Releases anlegen sowie Dateien und Screenshots hinzufügen. Benötigt Admin-Zugang. Faktisch ein Admin-Recht: Hochgeladene Dateien liegen auf Ihrem Server.','addfiles',4);
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (5,'Admin-Zugang','Darf den Adminbereich öffnen. Grundlage für alle folgenden Verwaltungsrechte.','adminaccess',5);
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (6,'Releases und Dateien bearbeiten','Darf Releases, Dateien und Screenshots bearbeiten. Benötigt Admin-Zugang.','editfiles',6);
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (7,'Releases und Dateien löschen','Darf Releases, Dateien und Screenshots löschen. Benötigt Admin-Zugang.','delfiles',7);
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (8,'Ordner hinzufügen','Darf Ordner anlegen. Benötigt Admin-Zugang.','adddirs',8);
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (9,'Ordner bearbeiten','Darf Ordner umbenennen und verschieben. Benötigt Admin-Zugang.','editdirs',9);
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (10,'Ordner löschen','Darf leere Ordner löschen. Benötigt Admin-Zugang.','deldirs',10);
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (11,'Benutzer hinzufügen','Derzeit ohne Wirkung: Benutzerkonten entstehen über die Registrierung.','adduser',11);
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (12,'Benutzer bearbeiten','Darf Benutzerkonten bearbeiten, Gruppen zuweisen und den Newsletter verschicken. Faktisch ein Admin-Recht, denn es erlaubt auch die Gruppe Administrator.','edituser',12);
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (13,'Benutzer löschen','Darf Benutzerkonten löschen. Zusammen mit „Benutzer bearbeiten“ auch Benutzergruppen verwalten.','deluser',13);
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (14,'Einstellungen verwalten','Darf alle Einstellungen ändern, auch die FTP-Zugangsdaten, und Benutzerrechte anlegen. Faktisch ein Admin-Recht.','settings',14);
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (15,'Vorlagen verwalten','Darf Vorlagen ändern und damit beliebiges HTML und JavaScript in alle Seiten einbauen. Faktisch ein Admin-Recht.','templates',15);
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (16,'Ersetzungen verwalten','Darf Zensur, Glossar und Smileys verwalten. Benötigt Admin-Zugang. Faktisch ein Admin-Recht: Glossar-Texte dürfen HTML enthalten.','replacements',16);
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (17,'Sicherung und Wartung','Darf Sicherungen erstellen und einspielen, die Datenbank optimieren sowie Zähler und Kommentare zurücksetzen. Faktisch ein Admin-Recht.','backup',17);
INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (18,'Kommentare moderieren','Darf Kommentare anderer Benutzer bearbeiten und löschen. Benötigt Admin-Zugang.','comment',18);

CREATE TABLE `pdl3_screens` (
  `screen_id` int unsigned NOT NULL AUTO_INCREMENT,
  `release_id` int NOT NULL DEFAULT '0',
  `text` varchar(255) NOT NULL DEFAULT '',
  `views` int NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL DEFAULT '',
  `datei` varchar(255) NOT NULL DEFAULT '',
  `thumb` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`screen_id`),
  KEY `release_id` (`release_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pdl3_settings` (
  `setting_id` int unsigned NOT NULL AUTO_INCREMENT,
  `variablenname` varchar(64) NOT NULL DEFAULT '',
  `name` varchar(128) NOT NULL DEFAULT '',
  `bez` varchar(255) NOT NULL DEFAULT '',
  `wert` text NOT NULL,
  `eingabe` varchar(64) NOT NULL DEFAULT 'input',
  `sgroup_id` int NOT NULL DEFAULT '0',
  `reihenfolge` smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `variablenname` (`variablenname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (1,'script_file','Aufruf der Download-Seite','Datei und Parameter, auf die alle Links zeigen, mit ? oder & am Ende. Ab Werk: downloads.php? – ändern Sie das nur, wenn PowerDownload in eine andere Seite eingebunden ist.','downloads.php?','input',4,5);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (2,'date_format','Datumsformat','Format aller angezeigten Daten in PHP-Schreibweise: d.m.Y ergibt 02.10.2026, d.m.Y H:i zusätzlich die Uhrzeit.','d.m.Y','input',4,2);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (3,'dlspeed','Angenommene Download-Geschwindigkeit','In KB pro Sekunde. Daraus berechnet PowerDownload die geschätzte Downloadzeit einer Datei.','56','zahl',4,1);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (4,'perpage','Releases pro Seite','Wie viele Releases eine Ordnerseite und die Suche auf einmal zeigen. Weitere Releases erreichen Besucher über die Seitenzahlen.','10','zahl',3,3);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (5,'orderby','Sortieren nach','Reihenfolge der Releases in den Ordnern und in der Suche. Neueste zuerst: „Datum“ und Sortierrichtung „Absteigend“.','name','auswahl:name|time|views|votes|voted/votes',3,1);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (6,'orderseq','Sortierrichtung','Aufsteigend zeigt A vor Z und ältere vor neueren Releases, absteigend umgekehrt.','ASC','auswahl:ASC|DESC',3,2);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (7,'enable_treeview','Ordnerbaum','Zeigt im öffentlichen Bereich alle Ordner als aufklappbaren Baum.','N','anaus',2,3);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (8,'enable_extrernadmin','Bearbeiten-Links auf der Webseite','Zeigt angemeldeten Administratoren auf den öffentlichen Seiten Links zum Bearbeiten von Ordnern und Releases.','Y','anaus',2,4);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (9,'referer_check','Hotlink-Schutz (Referer-Prüfung)','Erlaubt Downloads über den Download-Link nur, wenn der Besucher von Ihrer Seite oder einer erlaubten Adresse kommt.','N','anaus',1,1);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (10,'spages','Seitenzahlen beim Blättern','Wie viele Seitenzahlen die Blätterleiste höchstens zeigt. Ungerade Werte wirken ausgewogen; 0 zeigt alle Seiten.','10','zahl',4,3);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (12,'enable_comments','Kommentare','Schaltet die Kommentare unter den Releases ein oder aus.','Y','anaus',2,1);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (13,'captcha_enabled','Rechenaufgabe gegen Spam','Fragt bei der Registrierung und bei „Passwort vergessen“ eine einfache Rechenaufgabe ab, um Spam-Programme abzuhalten.','N','anaus',2,5);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (14,'allowed_referer','Erlaubte Adressen','Weitere Domains, von denen aus Downloads erlaubt sind, getrennt durch Leerzeichen, z. B. partner.de www.partner.de. Wirkt nur mit eingeschaltetem Hotlink-Schutz.','','textarea',1,2);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (15,'enable_search','Suche','Schaltet die öffentliche Suche ein oder aus.','Y','anaus',2,2);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (16,'trenn_durch','Vorschautext kürzen','Wie der Vorschautext in den Ordnerlisten gekürzt wird: nach einer festen Zeichenzahl oder an der Trennmarke im Beschreibungstext.','zeichen','auswahl:zeichen|string',5,1);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (17,'trenn_zeichen','Länge des Vorschautexts','Nach wie vielen Zeichen der Vorschautext abgeschnitten wird (bei „Nach einer festen Zeichenzahl“).','95','zahl',5,2);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (18,'trenn_string','Trennmarke','Steht diese Marke im Beschreibungstext, zeigt die Ordnerliste nur den Teil davor (bei „An der Trennmarke im Text“). Die Detailseite zeigt den ganzen Text ohne Marke.','{trenn}','input',5,3);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (19,'bb_code','BB-Code','Wandelt BB-Code wie [b]fett[/b] in Release-Texten und Kommentaren in Formatierung um.','Y','anaus',6,2);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (20,'smilies','Smileys','Ersetzt Kürzel wie :) in Release-Texten und Kommentaren durch Bilder (siehe Ersetzungen).','Y','anaus',6,1);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (21,'badwords_comments','Zensur in Kommentaren','Ersetzt Wörter aus der Zensurliste in Kommentaren durch Sternchen.','Y','anaus',6,4);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (22,'badwords_releases','Zensur in Release-Texten','Ersetzt Wörter aus der Zensurliste in Release-Beschreibungen durch Sternchen.','Y','anaus',6,5);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (23,'glossary','Glossar','Ersetzt Begriffe aus dem Glossar in Release-Texten und Kommentaren, z. B. durch einen Link.','Y','anaus',6,3);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (24,'html_releases','HTML in Release-Texten','Erlaubt HTML in Release-Beschreibungen. Empfohlen: An, denn Releases legt nur Ihr Team an.','Y','anaus',6,6);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (25,'html_comments','HTML in Kommentaren','Erlaubt HTML in Kommentaren. Empfohlen: Aus, denn jeder angemeldete Benutzer kann kommentieren.','N','anaus',6,7);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (26,'mail_fromname','Absendername','Name, der bei allen E-Mails (Registrierung, Passwort vergessen, Newsletter) als Absender erscheint.','PDL3 Automailer','input',7,1);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (27,'mail_fromaddr','Absenderadresse','E-Mail-Adresse, von der alle E-Mails verschickt werden. Verwenden Sie eine Adresse Ihrer eigenen Domain, sonst landen die E-Mails leicht im Spam.','daemon@example.com','email',7,2);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (28,'screen_autosize','Vorschaubild automatisch erzeugen','Erzeugt beim Hochladen eines Screenshots das kleine Vorschaubild selbst (benötigt die PHP-Erweiterung GD).','Y','anaus',8,1);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (29,'screen_size','Größe des Vorschaubilds','Breite bzw. Höhe des automatisch erzeugten Vorschaubilds in Pixeln.','120','zahl',8,2);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (30,'screen_verhalt','Bezugsseite des Vorschaubilds','Ob die Größe für die Breite oder die Höhe gilt. Die andere Seite berechnet PowerDownload passend.','width','auswahl:width|height',8,3);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (31,'ftp_on','FTP-Funktionen','Schaltet FTP-Browser und FTP-Upload im Adminbereich ein (benötigt die PHP-Erweiterung ftp und einen FTP-Server).','N','anaus',9,1);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (32,'ftp_server','FTP-Server','Name oder IP-Adresse des FTP-Servers, z. B. ftp.example.org.','','input',9,2);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (33,'ftp_user','FTP-Benutzername','Benutzername für den FTP-Zugang.','','input',9,3);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (34,'ftp_passwort','FTP-Passwort','Passwort für den FTP-Zugang. Es steht unverschlüsselt in der Datenbank und in jeder Sicherung.','','passwort',9,4);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (35,'ftp_server_url','Öffentliche Adresse der FTP-Dateien','Adresse, unter der per FTP hochgeladene Dateien für Besucher erreichbar sind, z. B. https://example.org/downloads/.','','url',9,5);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (36,'top_count','Einträge in den Bestenlisten','Wie viele Releases die Boxen Top, Flop, Neueste und Bestbewertet auf der Startseite zeigen.','10','zahl',4,4);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (37,'lastletter','','','0','',0,0);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (38,'installed','','','0','',0,0);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (39,'site_description','Seitenbeschreibung','Kurze Beschreibung Ihrer Download-Seite für Suchmaschinen (Meta-Beschreibung).','PowerDownload – Datei- und Release-Verwaltung mit Statistik, Suche und Benutzerbereich.','input',4,0);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (40,'site_url','Adresse der Download-Seite','Vollständige Adresse mit https://, unter der downloads.php liegt, z. B. https://www.example.org/downloads (mit oder ohne Schrägstrich am Ende). Links in E-Mails bauen darauf auf.','','input',4,6);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (41,'sitename','Name der Download-Seite','Name Ihrer Download-Seite, z. B. „Downloads Fotoclub Lichtblick“. Erscheint in den E-Mails an Ihre Besucher.','PowerDownload','input',4,0);
INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (50,'guest_group_id','Gruppe für nicht angemeldete Besucher','ID der Benutzergruppe, deren Rechte Besucher ohne Anmeldung erhalten (Vorgabe 3 = „Gast“). Admin-Rechte erhalten Gäste nie; fehlt die Gruppe, dürfen Gäste nur herunterladen.','3','input',2,6);

CREATE TABLE `pdl3_settingsgroup` (
  `sgroup_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `reihenfolge` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`sgroup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `pdl3_settingsgroup` (`sgroup_id`, `name`, `reihenfolge`) VALUES (1,'Hotlink-Schutz',5);
INSERT INTO `pdl3_settingsgroup` (`sgroup_id`, `name`, `reihenfolge`) VALUES (2,'Funktionen',1);
INSERT INTO `pdl3_settingsgroup` (`sgroup_id`, `name`, `reihenfolge`) VALUES (3,'Sortierung',2);
INSERT INTO `pdl3_settingsgroup` (`sgroup_id`, `name`, `reihenfolge`) VALUES (4,'Allgemein',6);
INSERT INTO `pdl3_settingsgroup` (`sgroup_id`, `name`, `reihenfolge`) VALUES (5,'Vorschautext',3);
INSERT INTO `pdl3_settingsgroup` (`sgroup_id`, `name`, `reihenfolge`) VALUES (6,'Textformatierung',4);
INSERT INTO `pdl3_settingsgroup` (`sgroup_id`, `name`, `reihenfolge`) VALUES (7,'E-Mail',7);
INSERT INTO `pdl3_settingsgroup` (`sgroup_id`, `name`, `reihenfolge`) VALUES (8,'Screenshots',8);
INSERT INTO `pdl3_settingsgroup` (`sgroup_id`, `name`, `reihenfolge`) VALUES (9,'FTP',9);

CREATE TABLE `pdl3_template` (
  `template_id` int unsigned NOT NULL AUTO_INCREMENT,
  `variablenname` varchar(64) NOT NULL DEFAULT '',
  `name` varchar(128) NOT NULL DEFAULT '',
  `bez` varchar(255) NOT NULL DEFAULT '',
  `eingabe` varchar(64) NOT NULL DEFAULT 'input',
  `wert` text NOT NULL,
  `tgroup_id` int NOT NULL DEFAULT '0',
  `reihenfolge` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`template_id`),
  UNIQUE KEY `variablenname` (`variablenname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (2,'table_border','Rahmenfarbe {table_border}','Farbe für den Platzhalter {table_border} in eigenen Vorlagen.','farbe','#9B0000',3,2);
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (3,'header_bg','Kopfzeilenfarbe {header_bg}','Farbe für den Platzhalter {header_bg} in eigenen Vorlagen.','farbe','#700000',3,3);
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (4,'footer_bg','Fußzeilenfarbe {footer_bg}','Farbe für den Platzhalter {footer_bg} in eigenen Vorlagen.','farbe','#700000',3,4);
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (5,'alt_1','Zeilenfarbe 1 {alt_1}','Farbe für {alt_1}; {alt} wechselt in Zeilen-Vorlagen zwischen Zeilenfarbe 1 und 2.','farbe','#1A1A1A',3,5);
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (6,'alt_2','Zeilenfarbe 2 {alt_2}','Farbe für {alt_2}; {alt} wechselt in Zeilen-Vorlagen zwischen Zeilenfarbe 1 und 2.','farbe','#2A2A2A',3,6);
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (11,'comments_form','Kommentarformular','Felder des Kommentarformulars (ohne <form>). Platzhalter: {user}, {titel_value}, {text_value}, {format_hint}. Die Felder name=\"titel\" und name=\"text\" sind Pflicht.','textarea','<p class=\"small text-muted mb-3\">Sie schreiben als <strong>{user}</strong>.</p>\n<div class=\"mb-3\">\n<label for=\"pdlCommentTitel\" class=\"form-label\">Titel</label>\n<input type=\"text\" class=\"form-control\" id=\"pdlCommentTitel\" name=\"titel\" maxlength=\"128\" value=\"{titel_value}\" required>\n</div>\n<div class=\"mb-3\">\n<label for=\"pdlCommentText\" class=\"form-label\">Kommentar</label>\n<textarea class=\"form-control\" id=\"pdlCommentText\" name=\"text\" rows=\"5\" maxlength=\"5000\" required aria-describedby=\"pdlCommentHelp\">{text_value}</textarea>\n<div id=\"pdlCommentHelp\" class=\"form-text\">{format_hint}</div>\n</div>\n<button type=\"submit\" class=\"btn btn-primary\" id=\"pdlCommentSubmit\">Kommentar absenden</button>',4,5);
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (12,'stats','Statistik-Box','Vorlage für die Statistik-Box auf der Startseite. Platzhalter: {files}, {size}, {downloads}, {traffic}, {durch_downloads}, {durch_traffic}','textarea','<ul class=\"list-unstyled mb-0 pdl-stats-list\">\n<li><span>Dateien</span> <strong>{files}</strong></li>\n<li><span>Gesamtgröße</span> <strong>{size}</strong></li>\n<li><span>Downloads</span> <strong>{downloads}</strong></li>\n<li><span>Traffic</span> <strong>{traffic}</strong></li>\n<li><span>Ø Downloads pro Tag</span> <strong>{durch_downloads}</strong></li>\n<li><span>Ø Traffic pro Tag</span> <strong>{durch_traffic}</strong></li>\n</ul>',5,1);
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (13,'top_box','Top-Downloads-Box','Rahmen der Top-Downloads-Box. Platzhalter: {rows}','textarea','<ol class=\"list-group list-group-flush list-group-numbered pdl-widget-list\">\n{rows}\n</ol>',5,2);
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (14,'top_row','Top-Downloads-Zeile','Eine Zeile der Top-Downloads-Box. Platzhalter: {script_file}, {id}, {name}, {downloads}, {downloads_text}, {count}','textarea','<li class=\"list-group-item d-flex justify-content-between align-items-start gap-2 px-1\"><a class=\"me-auto\" href=\"{script_file}release_id={id}\">{name}</a><span class=\"badge text-bg-secondary text-nowrap\">{downloads_text}</span></li>',5,3);
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (15,'flop_box','Flop-Downloads-Box','Rahmen der Flop-Downloads-Box. Platzhalter: {rows}','textarea','<ol class=\"list-group list-group-flush list-group-numbered pdl-widget-list\">\n{rows}\n</ol>',5,4);
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (16,'flop_row','Flop-Downloads-Zeile','Eine Zeile der Flop-Downloads-Box. Platzhalter: {script_file}, {id}, {name}, {downloads}, {downloads_text}, {count}','textarea','<li class=\"list-group-item d-flex justify-content-between align-items-start gap-2 px-1\"><a class=\"me-auto\" href=\"{script_file}release_id={id}\">{name}</a><span class=\"badge text-bg-secondary text-nowrap\">{downloads_text}</span></li>',5,5);
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (17,'latest_box','Neueste-Releases-Box','Rahmen der Box „Neueste Releases“. Platzhalter: {rows}','textarea','<ol class=\"list-group list-group-flush list-group-numbered pdl-widget-list\">\n{rows}\n</ol>',5,6);
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (18,'latest_row','Neueste-Releases-Zeile','Eine Zeile der Box „Neueste Releases“. Platzhalter: {script_file}, {id}, {name}, {time}, {count}','textarea','<li class=\"list-group-item d-flex justify-content-between align-items-start gap-2 px-1\"><a class=\"me-auto\" href=\"{script_file}release_id={id}\">{name}</a><span class=\"small text-muted text-nowrap\">{time}</span></li>',5,7);
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (19,'rated_box','Bestbewertet-Box','Rahmen der Box „Bestbewertet“. Platzhalter: {rows}','textarea','<ol class=\"list-group list-group-flush list-group-numbered pdl-widget-list\">\n{rows}\n</ol>',5,8);
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (20,'rated_row','Bestbewertet-Zeile','Eine Zeile der Box „Bestbewertet“. Platzhalter: {script_file}, {id}, {name}, {vote}, {votes}, {count}','textarea','<li class=\"list-group-item d-flex justify-content-between align-items-start gap-2 px-1\"><a class=\"me-auto\" href=\"{script_file}release_id={id}\">{name}</a><span class=\"badge text-bg-secondary text-nowrap\">{vote}/10</span></li>',5,9);
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (50,'mail_register','E-Mail: Registrierung','Text der Bestätigung nach der Registrierung. Platzhalter: {nick}, {email}, {login_url}, {sitename}, {site_url}. Das Passwort wird nie verschickt.','textarea','Hallo {nick},\n\nvielen Dank für Ihre Registrierung bei {sitename}. Ihr Benutzerkonto ist eingerichtet.\n\nBenutzername: {nick}\nE-Mail-Adresse: {email}\n\nHier können Sie sich anmelden:\n{login_url}\n\nIhr Passwort steht aus Sicherheitsgründen nicht in dieser E-Mail. Falls Sie es vergessen, legen Sie über „Passwort vergessen“ jederzeit ein neues fest.\n\nViele Grüße\n{sitename}\n{site_url}\n',9,1);
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (51,'mail_lost1','E-Mail: Passwort vergessen','Text mit dem Link zum Festlegen eines neuen Passworts. Platzhalter: {nick}, {email}, {link}, {ttl}, {sitename}, {site_url}.','textarea','Hallo {nick},\n\nfür Ihr Benutzerkonto bei {sitename} wurde ein neues Passwort angefordert.\n\nÜber diesen Link legen Sie ein neues Passwort fest:\n{link}\n\nDer Link ist {ttl} gültig und funktioniert nur einmal.\n\nFalls Sie kein neues Passwort angefordert haben, ignorieren Sie diese E-Mail bitte. Ihr bisheriges Passwort bleibt dann gültig.\n\nViele Grüße\n{sitename}\n{site_url}\n',9,2);
INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (52,'mail_lost2','E-Mail: Passwort geändert','Bestätigung nach einem Passwortwechsel. Platzhalter: {nick}, {email}, {login_url}, {lost_url}, {sitename}, {site_url}.','textarea','Hallo {nick},\n\ndas Passwort für Ihr Benutzerkonto bei {sitename} wurde soeben geändert.\n\nHier können Sie sich mit dem neuen Passwort anmelden:\n{login_url}\n\nFalls Sie Ihr Passwort nicht selbst geändert haben, legen Sie bitte sofort über „Passwort vergessen“ ein neues fest:\n{lost_url}\n\nViele Grüße\n{sitename}\n{site_url}\n',9,3);

CREATE TABLE `pdl3_templategroup` (
  `tgroup_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `reihenfolge` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`tgroup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `pdl3_templategroup` (`tgroup_id`, `name`, `reihenfolge`) VALUES (3,'Farben und Styles',1);
INSERT INTO `pdl3_templategroup` (`tgroup_id`, `name`, `reihenfolge`) VALUES (4,'Formulare',2);
INSERT INTO `pdl3_templategroup` (`tgroup_id`, `name`, `reihenfolge`) VALUES (5,'Info-Boxen',3);
INSERT INTO `pdl3_templategroup` (`tgroup_id`, `name`, `reihenfolge`) VALUES (9,'E-Mails',4);

CREATE TABLE `pdl3_user` (
  `user_id` int unsigned NOT NULL AUTO_INCREMENT,
  `nick` varchar(64) NOT NULL DEFAULT '',
  `email` varchar(128) NOT NULL DEFAULT '',
  `passwort` varchar(128) NOT NULL DEFAULT '',
  `ugroup_id` int NOT NULL DEFAULT '1',
  `homepage` varchar(128) NOT NULL DEFAULT '',
  `get_letter` enum('Y','N') NOT NULL DEFAULT 'N',
  `signatur` text,
  `remind_code` varchar(128) NOT NULL DEFAULT '',
  `remind_expires` int NOT NULL DEFAULT '0',
  `lastactive` int NOT NULL DEFAULT '0',
  `session_token` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `nick` (`nick`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pdl3_usergroup` (
  `ugroup_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `addcomments` enum('Y','N') NOT NULL DEFAULT 'Y',
  `download` enum('Y','N') NOT NULL DEFAULT 'Y',
  `comment` enum('Y','N') NOT NULL DEFAULT 'Y',
  `vote` enum('Y','N') NOT NULL DEFAULT 'Y',
  `adminaccess` enum('Y','N') NOT NULL DEFAULT 'N',
  `addfiles` enum('Y','N') NOT NULL DEFAULT 'N',
  `editfiles` enum('Y','N') NOT NULL DEFAULT 'N',
  `delfiles` enum('Y','N') NOT NULL DEFAULT 'N',
  `adddirs` enum('Y','N') NOT NULL DEFAULT 'N',
  `editdirs` enum('Y','N') NOT NULL DEFAULT 'N',
  `deldirs` enum('Y','N') NOT NULL DEFAULT 'N',
  `adduser` enum('Y','N') NOT NULL DEFAULT 'N',
  `edituser` enum('Y','N') NOT NULL DEFAULT 'N',
  `deluser` enum('Y','N') NOT NULL DEFAULT 'N',
  `settings` enum('Y','N') NOT NULL DEFAULT 'N',
  `templates` enum('Y','N') NOT NULL DEFAULT 'N',
  `replacements` enum('Y','N') NOT NULL DEFAULT 'N',
  `backup` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`ugroup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `pdl3_usergroup` (`ugroup_id`, `name`, `addcomments`, `download`, `comment`, `vote`, `adminaccess`, `addfiles`, `editfiles`, `delfiles`, `adddirs`, `editdirs`, `deldirs`, `adduser`, `edituser`, `deluser`, `settings`, `templates`, `replacements`, `backup`) VALUES (1,'Mitglied','Y','Y','N','Y','N','N','N','N','N','N','N','N','N','N','N','N','N','N');
INSERT INTO `pdl3_usergroup` (`ugroup_id`, `name`, `addcomments`, `download`, `comment`, `vote`, `adminaccess`, `addfiles`, `editfiles`, `delfiles`, `adddirs`, `editdirs`, `deldirs`, `adduser`, `edituser`, `deluser`, `settings`, `templates`, `replacements`, `backup`) VALUES (2,'Administrator','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y');
INSERT INTO `pdl3_usergroup` (`ugroup_id`, `name`, `addcomments`, `download`, `comment`, `vote`, `adminaccess`, `addfiles`, `editfiles`, `delfiles`, `adddirs`, `editdirs`, `deldirs`, `adduser`, `edituser`, `deluser`, `settings`, `templates`, `replacements`, `backup`) VALUES (3,'Gast','N','Y','N','N','N','N','N','N','N','N','N','N','N','N','N','N','N','N');
