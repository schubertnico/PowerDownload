-- ===========================================================================
--  Nur für die Docker-Entwicklung. NIE auf einem Webserver einspielen.
-- ===========================================================================
--  Legt den Entwicklungs-Admin admin / admin123 an (Gruppe 2 = Administrator)
--  und setzt den Installationszeitpunkt. Echte Installationen legen ihren
--  Administrator im Web-Installer (install.php) mit eigenem Passwort an.
-- ===========================================================================

SET NAMES utf8mb4;

INSERT INTO `pdl3_user` (`user_id`, `nick`, `email`, `passwort`, `ugroup_id`, `homepage`, `get_letter`, `signatur`, `remind_code`, `remind_expires`, `lastactive`, `session_token`) VALUES (1,'admin','admin@example.com','$2y$12$0fdvpoo4d.BxQOsbKY.vlOIeD5ZQ4ILz7fb0GdZEhJO3cwwHsdt7e',2,'','N','','',0,0,'');
UPDATE `pdl3_settings` SET `wert` = UNIX_TIMESTAMP() WHERE `variablenname` = 'installed';
