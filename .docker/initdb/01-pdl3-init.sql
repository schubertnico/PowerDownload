
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `pdl3_rights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdl3_rights` (
  `right_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `bez` varchar(255) NOT NULL DEFAULT '',
  `variablenname` varchar(32) NOT NULL DEFAULT '',
  `reihenfolge` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`right_id`),
  UNIQUE KEY `variablenname` (`variablenname`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `pdl3_rights` (`right_id`, `name`, `bez`, `variablenname`, `reihenfolge`) VALUES (1,'Downloads erlaubt','Darf der Nutzer Dateien herunterladen?','download',1),(2,'Releases bewerten','Darf der Nutzer Releases bewerten?','vote',2),(3,'Kommentare hinzufügen','Darf der Nutzer Kommentare schreiben?','addcomments',3),(4,'Dateien hinzufügen','Darf der Nutzer Dateien hinzufügen?','addfiles',4),(5,'Admin-Zugang','Darf der Nutzer das Admin-Center öffnen? Notwendige Grundlage für alle weiteren Admin-Rechte.','adminaccess',5),(6,'Dateien bearbeiten','Darf der Nutzer Dateien bearbeiten? Admin-Zugang erforderlich.','editfiles',6),(7,'Dateien löschen','Darf der Nutzer Dateien löschen? Admin-Zugang erforderlich.','delfiles',7),(8,'Ordner hinzufügen','Darf der Nutzer Ordner anlegen? Admin-Zugang erforderlich.','adddirs',8),(9,'Ordner bearbeiten','Darf der Nutzer Ordner bearbeiten? Admin-Zugang erforderlich.','editdirs',9),(10,'Ordner löschen','Darf der Nutzer Ordner löschen? Admin-Zugang erforderlich.','deldirs',10),(11,'Benutzer hinzufügen','Darf der Nutzer neue Benutzer anlegen? Admin-Zugang erforderlich.','adduser',11),(12,'Benutzer bearbeiten','Darf der Nutzer Benutzer bearbeiten? Admin-Zugang erforderlich.','edituser',12),(13,'Benutzer löschen','Darf der Nutzer Benutzer löschen? Admin-Zugang erforderlich.','deluser',13),(14,'Settings verwalten','Darf der Nutzer die globalen Einstellungen ändern? Admin-Zugang erforderlich.','settings',14),(15,'Templates verwalten','Darf der Nutzer Templates verwalten? Admin-Zugang erforderlich.','templates',15),(16,'Ersetzungen verwalten','Darf der Nutzer Ersetzungen (Smilies, Glossar, Zensur) verwalten? Admin-Zugang erforderlich.','replacements',16),(17,'Datenbank-Backup','Darf der Nutzer Backups erstellen oder einspielen? Admin-Zugang erforderlich.','backup',17),(18,'Kommentare moderieren','Darf der Nutzer Kommentare anderer Benutzer sehen und moderieren?','comment',18);
DROP TABLE IF EXISTS `pdl3_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES (1,'script_file','Script-URL','URL zur downloads.php (mit ? oder ?& als Suffix). Wird in generierten Links verwendet.','downloads.php?','input',4,5),(2,'date_format','Datumsformat','PHP-Datumsformat für alle angezeigten Zeitstempel (z. B. d.m.Y, Y-m-d H:i).','d.m.Y','input',4,2),(3,'dlspeed','Download-Speed-Annahme','Angenommene Downloadgeschwindigkeit in kB/s für die geschätzte Downloadzeit.','56','input',4,1),(4,'perpage','Dateien pro Seite','Standardwert für Dateien pro Seite. User können den Wert in ihrem Profil überschreiben.','10','input',3,3),(5,'orderby','Sortier-Feld','Standardfeld, nach dem sortiert wird (z. B. name, date, downloads).','name','input',3,1),(6,'orderseq','Sortier-Reihenfolge','Standard-Sortierrichtung: ASC = aufsteigend, DESC = absteigend.','ASC','input',3,2),(7,'enable_treeview','Treeview','Aktiviert die Treeview-Ordnerdarstellung im öffentlichen Bereich.','N','anaus',2,3),(8,'enable_extrernadmin','Inline-Admin-Links','Zeigt unter Dateien/Ordnern direkt Bearbeiten-Links für Admins (nur für Admins sichtbar).','Y','anaus',2,4),(9,'referer_check','Referer-Check','Schaltet den Referer-Check für Downloads an oder aus.','N','anaus',1,1),(10,'spages','Blätterseiten','Anzahl Einzelseiten beim Blättern. Ungerade Werte empfohlen; 0 = alle anzeigen.','10','input',4,3),(12,'enable_comments','Kommentare','Schaltet das Posten von Kommentaren an oder aus.','Y','anaus',2,1),(13,'captcha_enabled','Math-Captcha','Aktiviert ein einfaches Rechen-Captcha (Addition zweier Ziffern) auf Registrierung und Passwort vergessen.','N','anaus',2,5),(14,'allowed_referer','Erlaubte Referer','Liste erlaubter Referer (durch Leerzeichen getrennt). Greift nur, wenn der Referer-Check aktiv ist.','','textarea',1,2),(15,'enable_search','Suche','Schaltet die öffentliche Suchfunktion an oder aus.','Y','anaus',2,2),(16,'trenn_durch','Beschreibungs-Trennung','Wie soll die Release-Beschreibungsvorschau gekürzt werden? Werte: \"zeichen\" oder \"string\".','zeichen','input',5,1),(17,'trenn_zeichen','Trennung nach Zeichen','Nach wie vielen Zeichen wird die Beschreibungsvorschau abgeschnitten (wenn trenn_durch = zeichen)?','95','input',5,2),(18,'trenn_string','Trenn-String','Trenn-Marker im Text (wenn trenn_durch = string), z. B. {trenn}.','{trenn}','input',5,3),(19,'bb_code','BB-Code','BB-Code-Parser für Kommentare und Release-Beschreibungen.','Y','anaus',6,2),(20,'smilies','Smilies','Smilies-Ersetzung in Kommentaren und Release-Beschreibungen.','Y','anaus',6,1),(21,'badwords_comments','Zensur in Kommentaren','Aktiviert die Badword-Filterung in Kommentaren.','Y','anaus',6,4),(22,'badwords_releases','Zensur in Release-Texten','Aktiviert die Badword-Filterung in Release-Beschreibungen.','Y','anaus',6,5),(23,'glossary','Glossar','Aktiviert die Glossar-Ersetzung in Kommentaren und Beschreibungen.','Y','anaus',6,3),(24,'html_releases','HTML in Releases','Erlaubt HTML in Release-Beschreibungen (empfohlen: An, weil Admins-only).','Y','anaus',6,6),(25,'html_comments','HTML in Kommentaren','Erlaubt HTML in Kommentaren (empfohlen: Aus, weil unsicher).','N','anaus',6,7),(26,'mail_fromname','Absender-Name','Anzeigename des Absenders aller versendeten Mails.','PDL3 Automailer','input',7,1),(27,'mail_fromaddr','Absender-Adresse','Absender-E-Mail-Adresse aller versendeten Mails.','daemon@example.com','input',7,2),(28,'screen_autosize','Screenshot-Verkleinerung','Aktiviert die automatische Verkleinerung großer Screenshots (PHP-GD erforderlich).','Y','anaus',8,1),(29,'screen_size','Screenshot-Größe','Zielgröße (in Pixeln) für die automatische Screenshot-Verkleinerung.','120','input',8,2),(30,'screen_verhalt','Screenshot-Verhältnis','Bezugsseite für die Größe: width (Breite) oder hight (Höhe).','width','input',8,3),(31,'ftp_on','FTP-Funktionen','Aktiviert FTP-Upload und FTP-Browser (PHP-ftp-Extension erforderlich).','N','anaus',9,1),(32,'ftp_server','FTP-Server','Hostname oder IP des FTP-Servers.','','input',9,2),(33,'ftp_user','FTP-Benutzername','Benutzername für den FTP-Zugang.','','input',9,3),(34,'ftp_passwort','FTP-Passwort','Passwort für den FTP-Zugang.','','input',9,4),(35,'ftp_server_url','FTP-Anzeige-URL','Öffentliche URL, unter der die hochgeladenen FTP-Dateien für Besucher erreichbar sind.','','input',9,5),(36,'top_count','Anzahl Top-Downloads','Wieviele Einträge in der Top-Downloads-Box auf der Startseite angezeigt werden.','10','input',4,4),(37,'lastletter','','','0','',0,0),(38,'installed','','','0','',0,0),(39,'site_description','Site-Beschreibung','Wird als <meta name=\"description\"> in den öffentlichen Bereich eingeblendet.','PowerDownload - Datei- und Release-Verwaltung mit Statistik, Suche und Userbereich.','input',4,0);
DROP TABLE IF EXISTS `pdl3_settingsgroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdl3_settingsgroup` (
  `sgroup_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `reihenfolge` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`sgroup_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `pdl3_settingsgroup` (`sgroup_id`, `name`, `reihenfolge`) VALUES (1,'Referercheck',5),(2,'Features',1),(3,'Files ordnen',2),(4,'Sonstiges',6),(5,'Trennung',3),(6,'BB-Code, HTML usw.',4),(7,'Mailversand',7),(8,'Screenshots',8),(9,'FTP-Einstellungen',9);
DROP TABLE IF EXISTS `pdl3_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES (1,'all_width','Gesamtbreite','Breite der Haupttabelle','input','100%',3,1),(2,'table_border','Tabellenrahmen','Farbe des Tabellenrahmens','farbe','#9B0000',3,2),(3,'header_bg','Header Hintergrund','Hintergrundfarbe des Headers','farbe','#700000',3,3),(4,'footer_bg','Footer Hintergrund','Hintergrundfarbe des Footers','farbe','#700000',3,4),(5,'alt_1','Alternativfarbe 1','Erste Zeilenfarbe','farbe','#1A1A1A',3,5),(6,'alt_2','Alternativfarbe 2','Zweite Zeilenfarbe','farbe','#2A2A2A',3,6),(7,'ulogin_form','Login Formular','HTML-Template für das Login-Formular','textarea','<table border=\"0\" cellpadding=\"5\" cellspacing=\"1\" bgcolor=\"#333333\">\n<tr><td bgcolor=\"#444444\" colspan=\"2\" align=\"center\"><b>Login</b></td></tr>\n<tr><td bgcolor=\"#222222\">Nickname:</td><td bgcolor=\"#222222\"><input type=\"text\" name=\"nick\" size=\"20\"></td></tr>\n<tr><td bgcolor=\"#222222\">Passwort:</td><td bgcolor=\"#222222\"><input type=\"password\" name=\"pw\" size=\"20\"></td></tr>\n<tr><td bgcolor=\"#444444\" colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Login\"> <a href=\"downloads.php?usercenter=lost\">Passwort vergessen?</a></td></tr>\n</table>',4,1),(8,'uregister_form','Registrierung Formular','HTML-Template für die Registrierung','textarea','<table border=\"0\" cellpadding=\"5\" cellspacing=\"1\" bgcolor=\"#333333\">\n<tr><td bgcolor=\"#444444\" colspan=\"2\" align=\"center\"><b>Registrierung</b></td></tr>\n<tr><td bgcolor=\"#222222\">Nickname:</td><td bgcolor=\"#222222\"><input type=\"text\" name=\"nick\" size=\"20\" value=\"{nick}\"></td></tr>\n<tr><td bgcolor=\"#222222\">E-Mail:</td><td bgcolor=\"#222222\"><input type=\"text\" name=\"email\" size=\"20\" value=\"{email}\"></td></tr>\n<tr><td bgcolor=\"#222222\">Passwort:</td><td bgcolor=\"#222222\"><input type=\"password\" name=\"pw_new\" size=\"20\"></td></tr>\n<tr><td bgcolor=\"#222222\">Passwort (Wdh.):</td><td bgcolor=\"#222222\"><input type=\"password\" name=\"pw_new2\" size=\"20\"></td></tr>\n<tr><td bgcolor=\"#222222\">Homepage:</td><td bgcolor=\"#222222\"><input type=\"text\" name=\"homepage\" size=\"20\" value=\"{homepage}\"></td></tr>\n<tr><td bgcolor=\"#222222\">Newsletter:</td><td bgcolor=\"#222222\"><label><input type=\"checkbox\" name=\"get_letter\" value=\"Y\"{get_letter}> Ja, ich möchte den Newsletter abonnieren</label></td></tr>\n<tr><td bgcolor=\"#444444\" colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Registrieren\"></td></tr>\n</table>',4,2),(9,'ulost_form','Passwort vergessen','HTML-Template für „Passwort vergessen\"','textarea','<table border=\"0\" cellpadding=\"5\" cellspacing=\"1\" bgcolor=\"#333333\">\n<tr><td bgcolor=\"#444444\" colspan=\"2\" align=\"center\"><b>Passwort vergessen</b></td></tr>\n<tr><td bgcolor=\"#222222\">E-Mail:</td><td bgcolor=\"#222222\"><input type=\"text\" name=\"email\" size=\"30\"></td></tr>\n<tr><td bgcolor=\"#444444\" colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Absenden\"></td></tr>\n</table>',4,3),(10,'uprofil_form','Profil Formular','HTML-Template für das Benutzerprofil','textarea','<table border=\"0\" cellpadding=\"5\" cellspacing=\"1\" bgcolor=\"#333333\">\n<tr><td bgcolor=\"#444444\" colspan=\"2\" align=\"center\"><b>Profil bearbeiten</b></td></tr>\n<tr><td bgcolor=\"#222222\">E-Mail:</td><td bgcolor=\"#222222\"><input type=\"text\" name=\"email\" size=\"30\" value=\"{email}\"></td></tr>\n<tr><td bgcolor=\"#222222\">Homepage:</td><td bgcolor=\"#222222\"><input type=\"text\" name=\"homepage\" size=\"30\" value=\"{homepage}\"></td></tr>\n<tr><td bgcolor=\"#222222\">Newsletter:</td><td bgcolor=\"#222222\"><label><input type=\"checkbox\" name=\"get_letter\" value=\"Y\"{get_letter}> Ja, ich möchte den Newsletter abonnieren</label></td></tr>\n<tr><td bgcolor=\"#222222\">Altes Passwort:</td><td bgcolor=\"#222222\"><input type=\"password\" name=\"pw_old\" size=\"20\"></td></tr>\n<tr><td bgcolor=\"#222222\">Neues Passwort:</td><td bgcolor=\"#222222\"><input type=\"password\" name=\"pw_new\" size=\"20\"></td></tr>\n<tr><td bgcolor=\"#222222\">Neues Passwort (Wdh.):</td><td bgcolor=\"#222222\"><input type=\"password\" name=\"pw_new2\" size=\"20\"></td></tr>\n<tr><td bgcolor=\"#444444\" colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Speichern\"></td></tr>\n</table>',4,4),(11,'comments_form','Kommentar Formular','HTML-Template für die Kommentar-Eingabe','textarea','<table border=\"0\" cellpadding=\"5\" cellspacing=\"1\" bgcolor=\"#333333\">\n<tr><td bgcolor=\"#444444\" colspan=\"2\" align=\"center\"><b>Kommentar schreiben</b></td></tr>\n<tr><td bgcolor=\"#222222\">Benutzer:</td><td bgcolor=\"#222222\">{user}</td></tr>\n<tr><td bgcolor=\"#222222\">Titel:</td><td bgcolor=\"#222222\"><input type=\"text\" name=\"titel\" size=\"40\"></td></tr>\n<tr><td bgcolor=\"#222222\">Text:</td><td bgcolor=\"#222222\"><textarea name=\"text\" cols=\"40\" rows=\"5\"></textarea></td></tr>\n<tr><td bgcolor=\"#222222\">HTML:</td><td bgcolor=\"#222222\">{html}</td></tr>\n<tr><td bgcolor=\"#222222\">BBCode:</td><td bgcolor=\"#222222\">{bbcode}</td></tr>\n<tr><td bgcolor=\"#222222\">Smilies:</td><td bgcolor=\"#222222\">{smilies}</td></tr>\n<tr><td bgcolor=\"#444444\" colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Absenden\"></td></tr>\n</table>',4,5),(12,'stats','Statistik Box','HTML Template für die Statistik-Box auf der Startseite','textarea','<table border=\"0\" cellpadding=\"5\" cellspacing=\"1\" bgcolor=\"#333333\" width=\"100%\">\n<tr><td bgcolor=\"#222222\">Dateien: {files}</td></tr>\n<tr><td bgcolor=\"#222222\">Gesamtgroesse: {size}</td></tr>\n<tr><td bgcolor=\"#222222\">Downloads: {downloads}</td></tr>\n<tr><td bgcolor=\"#222222\">Traffic: {traffic}</td></tr>\n<tr><td bgcolor=\"#222222\">Downloads/Tag: {durch_downloads}</td></tr>\n<tr><td bgcolor=\"#222222\">Traffic/Tag: {durch_traffic}</td></tr>\n</table>',5,1),(13,'top_box','Top-Downloads Box','Rahmen-HTML für die Top-Downloads-Box (Platzhalter {rows})','textarea','<table border=\"0\" cellpadding=\"5\" cellspacing=\"1\" bgcolor=\"#333333\" width=\"100%\">\n{rows}\n</table>',5,2),(14,'top_row','Top-Downloads Zeile','HTML Template für eine Zeile innerhalb der Top-Downloads-Box','textarea','<tr><td bgcolor=\"#222222\">{count}. <a href=\"downloads.php?release_id={id}\">{name}</a> ({downloads} Downloads)</td></tr>',5,3),(15,'flop_box','Flop-Downloads Box','Rahmen-HTML für die Flop-Downloads-Box (Platzhalter {rows})','textarea','<table border=\"0\" cellpadding=\"5\" cellspacing=\"1\" bgcolor=\"#333333\" width=\"100%\">\n{rows}\n</table>',5,4),(16,'flop_row','Flop-Downloads Zeile','HTML Template für eine Zeile innerhalb der Flop-Downloads-Box','textarea','<tr><td bgcolor=\"#222222\">{count}. <a href=\"downloads.php?release_id={id}\">{name}</a> ({downloads} Downloads)</td></tr>',5,5),(17,'latest_box','Neueste Downloads Box','Rahmen-HTML für die \"Neueste Downloads\" Box (Platzhalter {rows})','textarea','<table border=\"0\" cellpadding=\"5\" cellspacing=\"1\" bgcolor=\"#333333\" width=\"100%\">\n{rows}\n</table>',5,6),(18,'latest_row','Neueste Downloads Zeile','HTML Template für eine Zeile innerhalb der \"Neueste Downloads\" Box','textarea','<tr><td bgcolor=\"#222222\">{count}. <a href=\"downloads.php?release_id={id}\">{name}</a></td></tr>',5,7),(19,'rated_box','Best bewertete Box','Rahmen-HTML für die \"Best bewertete\" Box (Platzhalter {rows})','textarea','<table border=\"0\" cellpadding=\"5\" cellspacing=\"1\" bgcolor=\"#333333\" width=\"100%\">\n{rows}\n</table>',5,8),(20,'rated_row','Best bewertete Zeile','HTML Template für eine Zeile innerhalb der \"Best bewertete\" Box','textarea','<tr><td bgcolor=\"#222222\">{count}. <a href=\"downloads.php?release_id={id}\">{name}</a> (Note: {vote})</td></tr>',5,9);
DROP TABLE IF EXISTS `pdl3_templategroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdl3_templategroup` (
  `tgroup_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `reihenfolge` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`tgroup_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `pdl3_templategroup` (`tgroup_id`, `name`, `reihenfolge`) VALUES (3,'Farben und Styles',1),(4,'Formulare',2),(5,'Info-Boxen',3);
DROP TABLE IF EXISTS `pdl3_usergroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `pdl3_usergroup` (`ugroup_id`, `name`, `addcomments`, `download`, `comment`, `vote`, `adminaccess`, `addfiles`, `editfiles`, `delfiles`, `adddirs`, `editdirs`, `deldirs`, `adduser`, `edituser`, `deluser`, `settings`, `templates`, `replacements`, `backup`) VALUES (1,'Gast','Y','Y','N','N','N','N','N','N','N','N','N','N','N','N','N','N','N','N'),(2,'Administrator','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y');
DROP TABLE IF EXISTS `pdl3_admin_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `pdl3_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdl3_comments` (
  `comment_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0',
  `release_id` int NOT NULL DEFAULT '0',
  `titel` varchar(128) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `time` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_id`),
  KEY `release_id` (`release_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `pdl3_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `pdl3_iplock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdl3_iplock` (
  `ip` varchar(32) NOT NULL DEFAULT '',
  `time` int NOT NULL DEFAULT '0',
  `file_id` int NOT NULL DEFAULT '0',
  `user_id` int NOT NULL DEFAULT '0',
  `art` enum('comment','vote','login','register','lostpw') NOT NULL DEFAULT 'comment'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `pdl3_ordner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdl3_ordner` (
  `ordner_id` int unsigned NOT NULL AUTO_INCREMENT,
  `sordner_id` int NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  PRIMARY KEY (`ordner_id`),
  KEY `sordner_id` (`sordner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `pdl3_release`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `pdl3_replacements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdl3_replacements` (
  `rep_id` int unsigned NOT NULL AUTO_INCREMENT,
  `old` varchar(128) NOT NULL DEFAULT '',
  `neu` varchar(255) NOT NULL DEFAULT '',
  `type` enum('s','g','b') NOT NULL DEFAULT 's',
  PRIMARY KEY (`rep_id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `pdl3_screens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdl3_screens` (
  `screen_id` int unsigned NOT NULL AUTO_INCREMENT,
  `release_id` int NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL DEFAULT '',
  `datei` varchar(255) NOT NULL DEFAULT '',
  `thumb` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`screen_id`),
  KEY `release_id` (`release_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `pdl3_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdl3_user` (
  `user_id` int unsigned NOT NULL AUTO_INCREMENT,
  `nick` varchar(64) NOT NULL DEFAULT '',
  `email` varchar(128) NOT NULL DEFAULT '',
  `passwort` varchar(128) NOT NULL DEFAULT '',
  `ugroup_id` int NOT NULL DEFAULT '1',
  `homepage` varchar(128) NOT NULL DEFAULT '',
  `get_letter` enum('Y','N') NOT NULL DEFAULT 'Y',
  `signatur` text,
  `remind_code` varchar(128) NOT NULL DEFAULT '',
  `remind_expires` int NOT NULL DEFAULT '0',
  `lastactive` int NOT NULL DEFAULT '0',
  `session_token` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `nick` (`nick`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `pdl3_user` (`user_id`, `nick`, `email`, `passwort`, `ugroup_id`, `homepage`, `get_letter`, `signatur`, `remind_code`, `remind_expires`, `lastactive`, `session_token`) VALUES (1,'admin','admin@example.com','$2y$12$0fdvpoo4d.BxQOsbKY.vlOIeD5ZQ4ILz7fb0GdZEhJO3cwwHsdt7e',2,'','Y','','',0,0,'');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

