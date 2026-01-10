<?php
$install_querys ="DROP TABLE IF EXISTS ". $sql_table['comments'] .";
CREATE TABLE ". $sql_table['comments'] ." (
  comment_id int(9) unsigned NOT NULL auto_increment,
  user_id int(9) NOT NULL default '0',
  release_id int(9) NOT NULL default '0',
  titel varchar(128) NOT NULL default '',
  text text NOT NULL,
  time int(14) NOT NULL default '0',
  PRIMARY KEY  (comment_id),
  KEY release_id (release_id)
) TYPE=MyISAM;

# --------------------------------------------------------

DROP TABLE IF EXISTS ". $sql_table['files'] .";
CREATE TABLE ". $sql_table['files'] ." (
  file_id int(9) unsigned NOT NULL auto_increment,
  release_id int(9) NOT NULL default '0',
  downloads int(9) NOT NULL default '0',
  url varchar(255) NOT NULL default '',
  size bigint(14) NOT NULL default '0',
  name varchar(128) NOT NULL default '',
  mirror int(9) NOT NULL default '0',
  PRIMARY KEY  (file_id),
  KEY release_id (release_id)
) TYPE=MyISAM;

# --------------------------------------------------------

DROP TABLE IF EXISTS ". $sql_table['iplock'] .";
CREATE TABLE ". $sql_table['iplock'] ." (
  ip varchar(32) NOT NULL default '',
  time int(14) NOT NULL default '0',
  file_id int(9) NOT NULL default '0',
  user_id int(9) NOT NULL default '0',
  art enum('comment','vote') NOT NULL default 'comment'
) TYPE=MyISAM;

# --------------------------------------------------------

DROP TABLE IF EXISTS ". $sql_table['ordner'] .";
CREATE TABLE ". $sql_table['ordner'] ." (
  ordner_id int(9) unsigned NOT NULL auto_increment,
  sordner_id int(9) NOT NULL default '0',
  name varchar(128) NOT NULL default '',
  text text NOT NULL,
  PRIMARY KEY  (ordner_id),
  KEY sordner_id (sordner_id)
) TYPE=MyISAM;

# --------------------------------------------------------

DROP TABLE IF EXISTS ". $sql_table['release'] .";
CREATE TABLE ". $sql_table['release'] ." (
  release_id int(9) unsigned NOT NULL auto_increment,
  name varchar(128) NOT NULL default '',
  text text NOT NULL,
  time int(14) NOT NULL default '0',
  views int(9) NOT NULL default '0',
  ordner_id int(9) NOT NULL default '0',
  uploader int(9) NOT NULL default '0',
  autor int(9) NOT NULL default '0',
  autor_nick varchar(128) NOT NULL default '',
  autor_email varchar(128) NOT NULL default '',
  autor_homepage varchar(128) NOT NULL default '',
  autor_icq int(14) NOT NULL default '0',
  released enum('Y','N') NOT NULL default 'Y',
  votes int(9) NOT NULL default '0',
  voted int(9) NOT NULL default '0',
  PRIMARY KEY  (release_id),
  KEY ordner_id (ordner_id),
  KEY released (released)
) TYPE=MyISAM;

# --------------------------------------------------------

DROP TABLE IF EXISTS ". $sql_table['replacements'] .";
CREATE TABLE ". $sql_table['replacements'] ." (
  rep_id int(9) unsigned NOT NULL auto_increment,
  old varchar(128) NOT NULL default '',
  neu varchar(255) NOT NULL default '',
  type enum('s','g','b') NOT NULL default 's',
  PRIMARY KEY  (rep_id),
  KEY type (type)
) TYPE=MyISAM;

# --------------------------------------------------------

DROP TABLE IF EXISTS ". $sql_table['rights'] .";
CREATE TABLE ". $sql_table['rights'] ." (
  right_id int(9) unsigned NOT NULL auto_increment,
  name varchar(128) NOT NULL default '',
  bez varchar(255) NOT NULL default '',
  variablenname varchar(32) NOT NULL default '',
  reihenfolge tinyint(2) NOT NULL default '0',
  PRIMARY KEY  (right_id),
  UNIQUE KEY variablenname (variablenname)
) TYPE=MyISAM;

# --------------------------------------------------------

DROP TABLE IF EXISTS ". $sql_table['screens'] .";
CREATE TABLE ". $sql_table['screens'] ." (
  screen_id int(9) unsigned NOT NULL auto_increment,
  release_id int(9) NOT NULL default '0',
  views int(9) NOT NULL default '0',
  text varchar(255) NOT NULL default '',
  PRIMARY KEY  (screen_id),
  KEY release_id (release_id)
) TYPE=MyISAM;

# --------------------------------------------------------

DROP TABLE IF EXISTS ". $sql_table['settings'] .";
CREATE TABLE ". $sql_table['settings'] ." (
  settings_id int(9) unsigned NOT NULL auto_increment,
  name varchar(128) NOT NULL default '',
  bez varchar(255) NOT NULL default '',
  wert text NOT NULL,
  eingabe text NOT NULL,
  variablenname varchar(32) NOT NULL default '',
  sgroup_id int(9) NOT NULL default '0',
  reihenfolge smallint(3) NOT NULL default '0',
  PRIMARY KEY  (settings_id),
  UNIQUE KEY variablenname (variablenname),
  KEY sgroup_id (sgroup_id)
) TYPE=MyISAM;

# --------------------------------------------------------

DROP TABLE IF EXISTS ". $sql_table['settingsgroup'] .";
CREATE TABLE ". $sql_table['settingsgroup'] ." (
  sgroup_id int(9) unsigned NOT NULL auto_increment,
  name varchar(128) NOT NULL default '',
  reihenfolge smallint(3) NOT NULL default '0',
  PRIMARY KEY  (sgroup_id)
) TYPE=MyISAM;

# --------------------------------------------------------

DROP TABLE IF EXISTS ". $sql_table['template'] .";
CREATE TABLE ". $sql_table['template'] ." (
  template_id int(9) unsigned NOT NULL auto_increment,
  name varchar(128) NOT NULL default '',
  bez text NOT NULL,
  variablenname varchar(32) NOT NULL default '',
  wert text NOT NULL,
  eingabe varchar(32) NOT NULL default '',
  tgroup_id int(9) NOT NULL default '0',
  reihenfolge tinyint(2) NOT NULL default '0',
  PRIMARY KEY  (template_id),
  UNIQUE KEY variablenname (variablenname),
  KEY tgroup_id (tgroup_id)
) TYPE=MyISAM;

# --------------------------------------------------------

DROP TABLE IF EXISTS ". $sql_table['templategroup'] .";
CREATE TABLE ". $sql_table['templategroup'] ." (
  tgroup_id int(9) unsigned NOT NULL auto_increment,
  name varchar(128) NOT NULL default '',
  reihenfolge tinyint(2) NOT NULL default '0',
  PRIMARY KEY  (tgroup_id)
) TYPE=MyISAM;

# --------------------------------------------------------

DROP TABLE IF EXISTS ". $sql_table['user'] .";
CREATE TABLE ". $sql_table['user'] ." (
  user_id int(9) unsigned NOT NULL auto_increment,
  nick varchar(128) NOT NULL default '',
  email varchar(128) NOT NULL default '',
  passwort varchar(128) NOT NULL default '',
  homepage varchar(128) NOT NULL default '',
  icq int(14) NOT NULL default '0',
  get_letter enum('Y','N') NOT NULL default 'Y',
  signatur text NOT NULL,
  ugroup_id int(9) NOT NULL default '0',
  remind_code varchar(128) NOT NULL default '',
  lastactive int(14) NOT NULL default '0',
  PRIMARY KEY  (user_id),
  UNIQUE KEY nick (nick,email),
  KEY ugroup_id (ugroup_id)
) TYPE=MyISAM;

# --------------------------------------------------------

DROP TABLE IF EXISTS ". $sql_table['usergroup'] .";
CREATE TABLE ". $sql_table['usergroup'] ." (
  ugroup_id int(9) unsigned NOT NULL auto_increment,
  name varchar(128) NOT NULL default '',
  addcomments enum('Y','N') NOT NULL default 'Y',
  addfiles enum('Y','N') NOT NULL default 'Y',
  vote enum('Y','N') NOT NULL default 'Y',
  download enum('Y','N') NOT NULL default 'Y',
  adminaccess enum('Y','N') NOT NULL default 'Y',
  editfiles enum('Y','N') NOT NULL default 'Y',
  delfiles enum('Y','N') NOT NULL default 'Y',
  edituser enum('Y','N') NOT NULL default 'Y',
  deluser enum('Y','N') NOT NULL default 'Y',
  adddirs enum('Y','N') NOT NULL default 'Y',
  editdirs enum('Y','N') NOT NULL default 'Y',
  deldirs enum('Y','N') NOT NULL default 'Y',
  writeletter enum('Y','N') NOT NULL default 'Y',
  replacement enum('Y','N') NOT NULL default 'Y',
  templates enum('Y','N') NOT NULL default 'Y',
  god enum('Y','N') NOT NULL default 'Y',
  PRIMARY KEY  (ugroup_id)
) TYPE=MyISAM;

# --------------------------------------------------------

INSERT INTO ". $sql_table['replacements'] ." VALUES (1, ':)', 'pdl-gfx/smilies/smile.gif', 's');
INSERT INTO ". $sql_table['replacements'] ." VALUES (2, ';)', 'pdl-gfx/smilies/blink.gif', 's');
INSERT INTO ". $sql_table['replacements'] ." VALUES (3, ':confused:', 'pdl-gfx/smilies/confused.gif', 's');
INSERT INTO ". $sql_table['replacements'] ." VALUES (4, ':cool:', 'pdl-gfx/smilies/cool.gif', 's');
INSERT INTO ". $sql_table['replacements'] ." VALUES (5, ':(', 'pdl-gfx/smilies/disappointed.gif', 's');
INSERT INTO ". $sql_table['replacements'] ." VALUES (6, ':eek:', 'pdl-gfx/smilies/eek.gif', 's');
INSERT INTO ". $sql_table['replacements'] ." VALUES (7, ':D', 'pdl-gfx/smilies/grins.gif', 's');
INSERT INTO ". $sql_table['replacements'] ." VALUES (8, ':a', 'pdl-gfx/smilies/angry.gif', 's');
INSERT INTO ". $sql_table['replacements'] ." VALUES (9, ':mad:', 'pdl-gfx/smilies/mad.gif', 's');
INSERT INTO ". $sql_table['replacements'] ." VALUES (10, ':rolleyes:', 'pdl-gfx/smilies/rolleyes.gif', 's');
INSERT INTO ". $sql_table['replacements'] ." VALUES (11, ':o', 'pdl-gfx/smilies/redface.gif', 's');
INSERT INTO ". $sql_table['replacements'] ." VALUES (12, ':sleep:', 'pdl-gfx/smilies/sleep.gif', 's');
INSERT INTO ". $sql_table['replacements'] ." VALUES (13, ':kotz:', 'pdl-gfx/smilies/throw.gif', 's');
INSERT INTO ". $sql_table['replacements'] ." VALUES (14, ':P', 'pdl-gfx/smilies/tongue.gif', 's');
INSERT INTO ". $sql_table['replacements'] ." VALUES (15, ';(', 'pdl-gfx/smilies/weep.gif', 's');

# --------------------------------------------------------

INSERT INTO ". $sql_table['rights'] ." VALUES (1, 'Kommentare posten', 'Hat der User das Recht Kommentare zu posten?', 'addcomments', 3);
INSERT INTO ". $sql_table['rights'] ." VALUES (2, 'Files Adden', 'Hat der User das Recht Files zu adden?', 'addfiles', 4);
INSERT INTO ". $sql_table['rights'] ." VALUES (3, 'Votes', 'Hat der User das Recht einen Release zu bewerten?', 'vote', 2);
INSERT INTO ". $sql_table['rights'] ." VALUES (4, 'Download', 'Hat der User das Recht eine Datei zu downloaden?', 'download', 1);
INSERT INTO ". $sql_table['rights'] ." VALUES (5, 'Admin Access', 'Hat der User Zugang zum Adminbereich? Ermglicht das adden von Dateien ohne Freischaltung', 'adminaccess', 5);
INSERT INTO ". $sql_table['rights'] ." VALUES (6, 'Files Editieren', 'Hat der User das Recht Files zu editieren? Admin Access wird bentigt.', 'editfiles', 6);
INSERT INTO ". $sql_table['rights'] ." VALUES (7, 'Files Lschen', 'Hat der User das Recht Files zu lschen? Admin Access wird bentigt.', 'delfiles', 7);
INSERT INTO ". $sql_table['rights'] ." VALUES (8, 'User editieren', 'Hat der User das Recht User zu editieren? Admin Access wird bentigt.', 'edituser', 8);
INSERT INTO ". $sql_table['rights'] ." VALUES (9, 'User lschen', 'Hat der User das Recht User zu lschen? Admin Access wird bentigt.', 'deluser', 9);
INSERT INTO ". $sql_table['rights'] ." VALUES (10, 'Ordner hinzufgen', 'Hat der User das Recht Ordner hinzuzufgen? Admin Access wird bentigt.', 'adddirs', 10);
INSERT INTO ". $sql_table['rights'] ." VALUES (11, 'Ordner editieren', 'Hat der User das Recht Ordner zu editieren? Admin Access wird bentigt.', 'editdirs', 11);
INSERT INTO ". $sql_table['rights'] ." VALUES (12, 'Ordner lschen', 'Hat der User das Recht Ordner zu lschen? Admin Access wird bentigt.', 'deldirs', 12);
INSERT INTO ". $sql_table['rights'] ." VALUES (13, 'Letter schreiben', 'Hat der User das Recht einen Downloadletter zu verfassen? Admin Access wird bentigt.', 'writeletter', 13);
INSERT INTO ". $sql_table['rights'] ." VALUES (14, 'Templates verwalten', 'Hat der User das Recht die Templates zu verwalten? Admin Access wird bentigt.', 'templates', 15);
INSERT INTO ". $sql_table['rights'] ." VALUES (15, 'Godadmin', 'Der User bekommt uneingeschrnkte Rechte, kann nicht von anderen Admins editiert oder gelscht werden und hat Zugang zu den Powerdownload Settings und Systemerweiterungen.', 'god', 16);
INSERT INTO ". $sql_table['rights'] ." VALUES (16, 'Replacements verwalten', 'Hat der User das Recht die Replacements zu verwalten? Admin Access wird bentigt.', 'replacement', 14);

# --------------------------------------------------------

INSERT INTO ". $sql_table['settings'] ." VALUES (1, 'Referercheck', 'Schaltet den Referercheck an/aus', 'N', 'anaus', 'referer_check', 1, 1);
INSERT INTO ". $sql_table['settings'] ." VALUES (2, 'Erlaubte Referer', 'Geben sie hier alle Referer, die bei dem Check erlaubt werden sollen. Trennung durch Leerzeichen.', 'gamesweb.com renegade-planet.de generals-world.de swat4ever.de', 'textarea', 'allowed_referer', 1, 2);
INSERT INTO ". $sql_table['settings'] ." VALUES (3, 'Kommentare', 'Schaltet das posten von Kommentaren an/aus', 'Y', 'anaus', 'enable_comments', 2, 1);
INSERT INTO ". $sql_table['settings'] ." VALUES (4, 'Suchen', 'Schaltet die Suchfunktion an/aus', 'Y', 'anaus', 'enable_search', 2, 2);
INSERT INTO ". $sql_table['settings'] ." VALUES (5, 'Treeview', 'Schaltet das Treeview an/aus', 'N', 'anaus', 'enable_treeview', 2, 3);
INSERT INTO ". $sql_table['settings'] ." VALUES (6, 'Extrernes Admin', 'Wollen sie unter jeder Datei und Ordner links zum editieren anzeigen lassen? Nur fr Admins versteht sich.', 'Y', 'anaus', 'enable_extrernadmin', 2, 4);
INSERT INTO ". $sql_table['settings'] ." VALUES (7, 'Datumsformat', 'Geben sie hier das Zeitformat an, in dem sie die Zeiten angezeigt haben wollen.', 'd.m.Y', 'input', 'date_format', 4, 2);
INSERT INTO ". $sql_table['settings'] ." VALUES (8, 'Bltterseiten', 'Geben sie hier ein wieviel Einzelseiten beim Blttern angezeigt werden. Nur ungerade Werte bitte. 0 eingeben um alle anzuzeigen.', '5', 'input', 'spages', 4, 3);
INSERT INTO ". $sql_table['settings'] ." VALUES (9, 'Files pro Seite', 'Standardwert fr Files pro Seite. Jeder User kann den Wert aber selber einstellen.', '20', 'input', 'perpage', 3, 3);
INSERT INTO ". $sql_table['settings'] ." VALUES (10, 'Ordnen nach', 'Nach was sollen die Files standardmig geordnet werden? Jeder User kann den Wert selber einstellen.', 'name', '<select name=\\\"orderby\\\">\r\n<option value=\\\"name\\\">Name</option>\r\n<option value=\\\"text\\\"\".pdlif(". $settings['orderby'] ."  == \"text\",\" selected\",\"\").\">Beschreibung</option>\r\n<option value=\\\"time\\\"\".pdlif(". $settings['orderby'] . " == \"time\",\" selected\",\"\").\">Uploaddatum</option>\r\n<option value=\\\"views\\\"\".pdlif(". $settings['orderby'] . " == \"views\",\" selected\",\"\").\">Views</option>\r\n<option value=\\\"votes\\\"\".pdlif(". $settings['orderby'] . " == \"votes\",\" selected\",\"\").\">Bewertungen</option>\r\n<option value=\\\"voted/votes\\\"\".pdlif(". $settings['orderby'] . " == \"voted/votes\",\" selected\",\"\").\">Wertung</option>\r\n</select>', 'orderby', 3, 1);
INSERT INTO ". $sql_table['settings'] ." VALUES (11, 'Auf/Absteigend', 'Standardwert ob die Files Auf oder Absteigend sortiert werden sollen.', 'ASC', '<select name=\\\"orderseq\\\">\r\n<option value=\\\"ASC\\\">Aufsteigend</option>\r\n<option value=\\\"DESC\\\"\".pdlif(". $settings['orderseq'] . " == \"DESC\",\" selected\",\"\").\">Absteigend</option>\r\n</select>', 'orderseq', 3, 2);
INSERT INTO ". $sql_table['settings'] ." VALUES (12, 'Download Speed', 'Fr die Downloadzeit Berechnung. Es kann nur eine geschwindigkeit eingegeben werden.', '85', 'input', 'dlspeed', 4, 1);
INSERT INTO ". $sql_table['settings'] ." VALUES (13, 'Trennen durch', 'Die Beschreibung kann entweder durch einen festgelegten Trennsting oder nach X Zeichen abgeschnitten werden um bei der Releaseauflisting angezeigt zu werden. Hier knnen sie festlegen, nach was getrennt werden soll.', 'zeichen', '<select name=\\\"trenn_durch\\\">\r\n<option value=\\\"zeichen\\\">nach X Zeichen</option>\r\n<option value=\\\"string\\\"\".pdlif(". $settings['trenn_durch'] . " == \"string\",\" selected\",\"\").\">durch Trennstring</option>\r\n</select>', 'trenn_durch', 5, 1);
INSERT INTO ". $sql_table['settings'] ." VALUES (14, 'Zeichen fr Trennung', 'Falls angegeben wird das Beschreibungspreview nach X Zeichen getrennt. Geben sie hier an nach wieviel Zeichen.', '95', 'input', 'trenn_zeichen', 5, 2);
INSERT INTO ". $sql_table['settings'] ." VALUES (15, 'Trennstring', 'Falls angegeben wird das Beschreibungspreview durch einen String getrennt, den sie hier festlegen knnen.', '{trenn}', 'input', 'trenn_string', 5, 3);
INSERT INTO ". $sql_table['settings'] ." VALUES (16, 'BB Code', 'Soll der BB Code aktiviert werden? BB Code gilt in Kommentaren und bei Releases', 'Y', 'anaus', 'bb_code', 6, 2);
INSERT INTO ". $sql_table['settings'] ." VALUES (17, 'Smilies', 'Sollen die Smilies aktiviert werden? Gilt sowohl in den Kommentaren als auch in den Releases.', 'Y', 'anaus', 'smilies', 6, 1);
INSERT INTO ". $sql_table['settings'] ." VALUES (18, 'Badwords in den Kommentaren', 'Im Gegensatz zum BB Code und den Smilies kann man die Badwords in den Kommentaren und in den Beschreibungen getrennt einstellen. Man selber will ja noch fruchen drfen! :D', 'Y', 'anaus', 'badwords_comments', 6, 4);
INSERT INTO ". $sql_table['settings'] ." VALUES (19, 'Badwords bei den Releases', 'Sollen die Badwords bei den Releases zensiert werden?', 'Y', 'anaus', 'badwords_releases', 6, 5);
INSERT INTO ". $sql_table['settings'] ." VALUES (20, 'Glossar', 'Schaltet den Glossar global an oder aus.', 'Y', 'anaus', 'glossary', 6, 3);
INSERT INTO ". $sql_table['settings'] ." VALUES (21, 'HTML bei den Releases', 'HTML kann wie die Badwords auch getrennt an oder ausgeschaltet werden. So wird empfohlen es bei den Releases an zu lassen und es bei den Kommentaren zu deaktivieren.', 'Y', 'anaus', 'html_releases', 6, 6);
INSERT INTO ". $sql_table['settings'] ." VALUES (22, 'HTML in den Kommentaren', 'Um zu verhindern, das die User durch einen HTML Code die ganze Seite zerstren, kann man hier HTML ausschalten. Wird auch empfohlen', 'N', 'anaus', 'html_comments', 6, 7);
INSERT INTO ". $sql_table['settings'] ." VALUES (23, 'Absender Name', 'Wie soll der Absender sein, unter dem alle Mails versendet werden?', 'PDL3 Automailer', 'input', 'mail_fromname', 7, 1);
INSERT INTO ". $sql_table['settings'] ." VALUES (24, 'Absender Adresse', 'Wie ist die Mailadresse des Absenders?', 'daemon@powerscripts.org', 'input', 'mail_fromaddr', 7, 2);
INSERT INTO ". $sql_table['settings'] ." VALUES (25, 'Auto verkleinerung verwenden', 'Hier knnen sie die Auto verkleinerung aktivieren, falls der Server es untersttzt. Dabei mssen sie nur ein groes Bild hochladen. den Rest macht das Script.', 'Y', 'anaus', 'screen_autosize', 8, 0);
INSERT INTO ". $sql_table['settings'] ." VALUES (26, 'Mae', 'Bestimmen sie hier wie hoch oder breit das Bild sein soll... Im nchsten Schritt bestimmen sie ob das Bild zur hhe oder zur Breite im Verhltniss stehen muss.', '120', 'input', 'screen_size', 8, 0);
INSERT INTO ". $sql_table['settings'] ." VALUES (27, 'Verhltniss', 'Geben sie hier ob das Grenverhltniss zur Hhe oder zur Breite sein soll.', 'width', '<select name=\\\"screen_verhalt\\\">\r\n<option value=\\\"width\\\">Breite</option>\r\n<option value=\\\"hight\\\"\".pdlif(". $settings['screen_verhalt'] . " == \"hight\",\" selected\",\"\").\">Hhe</option>\r\n</select>', 'screen_verhalt', 8, 0);
INSERT INTO ". $sql_table['settings'] ." VALUES (28, 'FTP Funktionen', 'Aktiviert, falls der Server es untersttzt den FTP Upload und den FTP Browser.', 'Y', 'anaus', 'ftp_on', 9, 0);
INSERT INTO ". $sql_table['settings'] ." VALUES (29, 'URL zum Script', 'Geben sie hier die URL zu ihrer downloads.php oder wie sie auch bei ihnen heien mag ein.', 'http://localhost.renegade-planet.de/pdl/downloads.php', 'input', 'script_file', 4, 0);
INSERT INTO ". $sql_table['settings'] ." VALUES (30, 'FTP Server', 'Geben sie hier den FTP Server ein, zu dem Connected werden soll.', '', 'input', 'ftp_server', 9, 0);
INSERT INTO ". $sql_table['settings'] ." VALUES (31, 'FTP User', 'Geben sie den Usernamen des FTP Zugangs ein.', '', 'input', 'ftp_user', 9, 0);
INSERT INTO ". $sql_table['settings'] ." VALUES (32, 'FTP Passwort', 'Geben sie das Passwort fr den FTP Zugang ein.', '', 'input', 'ftp_passwort', 9, 0);
INSERT INTO ". $sql_table['settings'] ." VALUES (33, 'FTP URL', 'Geben sie hier ein, wie spter die URL zum FTP wird, da diese manchmal nicht gleich dem FTP Server ist.', '', 'input', 'ftp_server_url', 9, 0);
INSERT INTO ". $sql_table['settings'] ." VALUES (34, 'Anzahl Tops', 'Wieviele Top Downloads werden angezeigt.', '10', 'input', 'top_count', 4, 0);
INSERT INTO ". $sql_table['settings'] ." VALUES (35, '', '', '".time()."', '', 'lastletter', 0, 0);
INSERT INTO ". $sql_table['settings'] ." VALUES (36, '', '', '".time()."', '', 'installed', 0, 0);

# --------------------------------------------------------

INSERT INTO ". $sql_table['settingsgroup'] ." VALUES (1, 'Referercheck', 5);
INSERT INTO ". $sql_table['settingsgroup'] ." VALUES (2, 'Features', 1);
INSERT INTO ". $sql_table['settingsgroup'] ." VALUES (3, 'Files ordnen', 2);
INSERT INTO ". $sql_table['settingsgroup'] ." VALUES (4, 'Sonstiges', 6);
INSERT INTO ". $sql_table['settingsgroup'] ." VALUES (5, 'Trennung', 3);
INSERT INTO ". $sql_table['settingsgroup'] ." VALUES (6, 'BB Code, HTML usw.', 4);
INSERT INTO ". $sql_table['settingsgroup'] ." VALUES (7, 'Mailversand', 0);
INSERT INTO ". $sql_table['settingsgroup'] ." VALUES (8, 'Screenshots', 0);
INSERT INTO ". $sql_table['settingsgroup'] ." VALUES (9, 'FTP Settings', 0);

# --------------------------------------------------------

INSERT INTO ". $sql_table['template'] ." VALUES (1, 'Gesamte Breite', 'Das Powerdownload System ist in einer unsichtbaren Tabelle untergebracht. Dessen breite muss hier eingegeben werden. Es knnen feste oder dynamische (%) Werte angegeben werden.', 'all_width', '95%', 'input', 2, 1);
INSERT INTO ". $sql_table['template'] ." VALUES (2, 'Header Farbe', 'Farbcode fr die Headerzeile', 'header_bg', '#700000', 'farbe', 1, 2);
INSERT INTO ". $sql_table['template'] ." VALUES (3, 'Rahmenfarbe', 'Welche Farbe hat der Rahmen der Download Tabelle?', 'table_border', '#9B0000', 'farbe', 1, 1);
INSERT INTO ". $sql_table['template'] ." VALUES (4, 'Alternativfarbe 1', 'Alternativfarbe 1 fr das Listing.', 'alt_1', '#2E0000', 'farbe', 1, 3);
INSERT INTO ". $sql_table['template'] ." VALUES (5, 'Alternativfarbe 2', 'Alternativfarbe 2 fr das Listing.', 'alt_2', '#3B0000', 'farbe', 1, 4);
INSERT INTO ". $sql_table['template'] ." VALUES (6, 'Footer Farbe', 'Die Farbe fr die Footerzeile.', 'footer_bg', '#5F0000', 'farbe', 1, 5);
INSERT INTO ". $sql_table['template'] ." VALUES (8, 'Ordner Zeile', 'Einzelne Zeile fr die Ordnerbersicht', 'ordner_row', '        <tr>\r\n          <td bgcolor=\"{alt_1}\">\r\n            <a href=\"{script_file}ordner_id={id}\">{name}</a>\r\n          </td>\r\n          <td bgcolor=\"{alt_2}\">\r\n            {files}\r\n          </td>\r\n          <td bgcolor=\"{alt_1}\">\r\n            {subdirs}\r\n          </td>\r\n          <td bgcolor=\"{alt_2}\">\r\n            {text}\r\n          </td>\r\n        </tr>\r\n', 'textarea', 4, 2);
INSERT INTO ". $sql_table['template'] ." VALUES (44, 'Box Rated Downloads', 'Die Box fr die Best Bewertetsten Downloads.\n{rows} wird durch die Zeilen ersetz.', 'rated_box', '<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"200\">\r\n  <tr>\r\n    <td bgcolor=\"{table_border}\">\r\n      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">\r\n        <tr>\r\n          <td bgcolor=\"{header_bg}\" colspan=\"3\" align=\"center\">\r\n            <b>Top {count} Wertung<b>\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>#</b>\r\n          </td>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>Name</b>\r\n          </td>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>Wertung</b>\r\n          </td>\r\n        </tr>\r\n\n{rows}\n        <tr>\r\n          <td bgcolor=\"{footer_bg}\" colspan=\"3\">\r\n            &nbsp;\r\n          </td>\r\n        </tr>\r\n      </table>\r\n    </td>\r\n  </tr>\r\n</table>\r\n', 'textarea', 8, 7);
INSERT INTO ". $sql_table['template'] ." VALUES (43, 'Box Latest Downloads', 'Die Box fr die Neuesten Downloads.\n{rows} wird durch die Zeilen ersetz.', 'latest_box', '<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"200\">\r\n  <tr>\r\n    <td bgcolor=\"{table_border}\">\r\n      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">\r\n        <tr>\r\n          <td bgcolor=\"{header_bg}\" colspan=\"3\" align=\"center\">\r\n            <b>Latest {count} Downloads</b>\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>#</b>\r\n          </td>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>Name</b>\r\n          </td>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>Datum</b>\r\n          </td>\r\n        </tr>\r\n\n{rows}\n        <tr>\r\n          <td bgcolor=\"{footer_bg}\" colspan=\"3\">\r\n            &nbsp;\r\n          </td>\r\n        </tr>\r\n      </table>\r\n    </td>\r\n  </tr>\r\n</table>\r\n', 'textarea', 8, 5);
INSERT INTO ". $sql_table['template'] ." VALUES (12, 'Files Zeile', 'Hier wird die Datei eines Releases angezeigt.', 'dfiles_row', '        <tr>\r\n          <td bgcolor=\"{alt}\">\r\n            <a href=\"{script_file}load_file={id}&filename={filename}\" target=\"_blank\">{name}</a>\r\n          </td>\r\n          <td bgcolor=\"{alt}\">\r\n            {dlspeed}\r\n          </td>\r\n          <td bgcolor=\"{alt}\">\r\n            {size}\r\n          </td>\r\n          <td bgcolor=\"{alt}\">\r\n            {traffic}\r\n          </td>\r\n          <td bgcolor=\"{alt}\">\r\n            {downloads}\r\n          </td>\r\n        </tr>\r\n', 'textarea', 3, 2);
INSERT INTO ". $sql_table['template'] ." VALUES (13, 'Download Detailseite', 'Zeigt alle Details eines Releases und auch die Files dazu.', 'file_detail', '<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\r\n <tr>\r\n   <td bgcolor=\"{table_border}\">\r\n     <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">\r\n       <tr>\r\n         <td bgcolor=\"{header_bg}\" colspan=\"2\" align=\"center\">\r\n           <b>{name}</b>\r\n         </td>\r\n       </tr>\r\n       <tr>\r\n         <td bgcolor=\"{alt_1}\">\r\n           <b>Uploaddatum</b>\r\n         </td>\r\n         <td bgcolor=\"{alt_1}\">\r\n           {time}\r\n         </td>\r\n       </tr>\r\n       <tr>\r\n         <td bgcolor=\"{alt_2}\">\r\n           <b>Uploader</b>\r\n         </td>\r\n         <td bgcolor=\"{alt_2}\">\r\n           {uploader}\r\n         </td>\r\n       </tr>\r\n       <tr>\r\n         <td bgcolor=\"{alt_1}\">\r\n           <b>Autor</b>\r\n         </td>\r\n         <td bgcolor=\"{alt_1}\">\r\n           {autor}\r\n         </td>\r\n       </tr>\r\n       <tr>\r\n         <td bgcolor=\"{alt_2}\">\r\n           <b>Views</b>\r\n         </td>\r\n         <td bgcolor=\"{alt_2}\">\r\n           {views}\r\n         </td>\r\n       </tr>\r\n       <tr>\r\n         <td bgcolor=\"{alt_1}\">\r\n           <b>Bewertung</b>\r\n         </td>\r\n         <td bgcolor=\"{alt_1}\">\r\n           Bewertungen: {votes}<br>\r\n           Durchschnittliche Bewertung: {vote}\r\n           {vote_form}\r\n         </td>\r\n       </tr>\r\n       <tr>\r\n         <td bgcolor=\"{alt_2}\" colspan=\"2\" align=\"center\">\r\n           <b>Beschreibung</b>\r\n         </td>\r\n       </tr>\r\n       <tr>\r\n         <td bgcolor=\"{alt_1}\" colspan=\"2\">\r\n           {text}\r\n         </td>\r\n       </tr>\r\n       <tr>\r\n         <td bgcolor=\"{alt_2}\" colspan=\"2\" align=\"center\">\r\n           <b>Screenshots</b> (klicken zum vergrern)\r\n         </td>\r\n       </tr>\r\n       <tr>\r\n         <td bgcolor=\"{alt_1}\" colspan=\"2\" align=\"center\">\r\n           {screens}\r\n         </td>\r\n       </tr>\r\n       <tr>\r\n         <td bgcolor=\"{footer_bg}\" colspan=\"2\" align=\"center\">\r\n           <b>Files</b>\r\n         </td>\r\n       </tr>\r\n     </table>\r\n   </td>\r\n </tr>\r\n</table>\r\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\r\n <tr>\r\n   <td bgcolor=\"{table_border}\">\r\n     <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">\r\n       <tr>\r\n         <td bgcolor=\"{header_bg}\">\r\n           <b>Datei (Download)</b>\r\n         </td>\r\n         <td bgcolor=\"{header_bg}\">\r\n           <b>DL Zeit ({speed}KB/s)</b>\r\n         </td>\r\n         <td bgcolor=\"{header_bg}\">\r\n           <b>Gre</b>\r\n         </td>\r\n         <td bgcolor=\"{header_bg}\">\r\n           <b>Traffic</b>\r\n         </td>\r\n         <td bgcolor=\"{header_bg}\">\r\n           <b>Downloads</b>\r\n         </td>\r\n       </tr>\r\n{files}\r\n       <tr>\r\n         <td bgcolor=\"{footer_bg}\">\r\n           <b>Total:</b> {total_files} Files\r\n         </td>\r\n         <td bgcolor=\"{footer_bg}\">\r\n           {dlspeed}\r\n         </td>\r\n         <td bgcolor=\"{footer_bg}\">\r\n           {total_size}\r\n         </td>\r\n         <td bgcolor=\"{footer_bg}\">\r\n           {total_traffic}\r\n         </td>\r\n         <td bgcolor=\"{footer_bg}\">\r\n           {total_downloads}\r\n         </td>\r\n       </tr>\r\n     </table>\r\n   </td>\r\n </tr>\r\n</table>\r\n', 'textarea', 3, 1);
INSERT INTO ". $sql_table['template'] ." VALUES (14, 'Release Zeile', 'Datenzeile der Releasebersicht.', 'release_row', '        <tr>\r\n          <td bgcolor=\"{alt}\">\r\n            <a href=\"{script_file}release_id={id}\">{name}</a>\r\n          </td>\r\n          <td bgcolor=\"{alt}\">\r\n            {size}\r\n          </td>\r\n          <td bgcolor=\"{alt}\">\r\n            {time}\r\n          </td>\r\n          <td bgcolor=\"{alt}\">\r\n            {text}\r\n          </td>\r\n        </tr>\r\n', 'textarea', 5, 2);
INSERT INTO ". $sql_table['template'] ." VALUES (16, 'Zusatz', 'Ein kleiner Text unter der Datei. Wie z.B. Der Webmaster bittet sie auf diese Seite zu linken und nicht direkt auf die Datei.', 'own_footer', 'Der Webmaster fordert Sie auf, auf diese Seite zu linken und <b>nicht</b> auf den Download selber.<br>\r\nEs entstehen sehr viele Traffickosten dadurch.<br>\r\nDas direkte Verlinken kann Strafrechtlich verfolgt werden.<br>', 'textarea', 3, 3);
INSERT INTO ". $sql_table['template'] ." VALUES (17, 'Login Formular', 'Hier kann man das Login Formular fr die User selber anpassen.', 'ulogin_form', '<center>\r\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"450\">\r\n  <tr>\r\n    <td bgcolor=\"{table_border}\">\r\n      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">\r\n        <tr>\r\n          <td bgcolor=\"{header_bg}\" align=\"center\">\r\n            <b>Bitte einloggen.</b>\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{alt_1}\" align=\"center\">\r\n            <small>Bitte die Zugangsdaten eingeben. Cookies mssen an sein.</small>\r\n            <table border=\"0\">\r\n              <tr>\r\n                <td>\r\n                  <input name=\"nick\" size=\"25\">\r\n                </td>\r\n                <td>\r\n                  <input name=\"pw\" size=\"25\" type=\"password\">\r\n                </td>\r\n                <td>\r\n                  <input type=\"submit\" value=\"Login\">\r\n                </td>\r\n              </tr>\r\n              <tr>\r\n                <td>\r\n                  <small>Dein Nick</small>\r\n                </td>\r\n                <td colspan=\"2\">\r\n                  <small>Dein Passwort</small>\r\n                </td>\r\n              </tr>\r\n            </table>\r\n            <a href=\"{script_file}usercenter=lost\">Zugangsdaten vergessen?</a>\r\n          </td>\r\n        </tr>\r\n      </table>\r\n    </td>\r\n  </tr>\r\n</table>\r\n</center>\r\n<br><br>\r\n', 'textarea', 6, 1);
INSERT INTO ". $sql_table['template'] ." VALUES (18, 'Passwort zuschicken', 'Geben sie hier das Template fr das Passwort zuschicken Formular ein.', 'ulost_form', '<center>\r\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"450\">\r\n  <tr>\r\n    <td bgcolor=\"{table_border}\">\r\n      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">\r\n        <tr>\r\n          <td bgcolor=\"{header_bg}\" align=\"center\">\r\n            <b>Passwort vergessen</b>\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{alt_1}\" align=\"center\">\r\n            <small>Bitte geben sie ihre E-Mail Adresse ein. Sie erhalten danach eine Besttigungsurl.</small>\r\n            <table border=\"0\">\r\n              <tr>\r\n                <td>\r\n                  E-Mail Adresse\r\n                </td>\r\n                <td>\r\n                  <input name=\"email\" size=\"25\" type=\"text\">\r\n                </td>\r\n                <td>\r\n                  <input type=\"submit\" value=\"Passwort anfordern\">\r\n                </td>\r\n              </tr>\r\n            </table>\r\n          </td>\r\n        </tr>\r\n      </table>\r\n    </td>\r\n  </tr>\r\n</table>\r\n</center>\r\n<br><br>\r\n', 'textarea', 6, 2);
INSERT INTO ". $sql_table['template'] ." VALUES (19, 'Stats', 'Die Kurzen Traffic/Datenbank Stats, die ins Men kommen.', 'stats', '<b>Datenbank</b><br>\r\n{files} Files<br>\r\n{size}<br>\r\n<b>Traffic</b><br>\r\n{downloads} Files<br>\r\n{traffic}<br>\r\n<b>Durchschnitt</b><br>\r\n{durch_downloads} Files/Tag<br>\r\n{durch_traffic}/Tag<br>\r\n<a href=\"{script_file}show_stats=1\">Ausfhrliche Systemstatistik</a>', 'textarea', 7, 0);
INSERT INTO ". $sql_table['template'] ." VALUES (42, 'Box Flop Downloads', 'Die Box fr die Flop Downloads.\n{rows} wird durch die Zeilen ersetz.', 'flop_box', '<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"200\">\r\n  <tr>\r\n    <td bgcolor=\"{table_border}\">\r\n      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">\r\n        <tr>\r\n          <td bgcolor=\"{header_bg}\" colspan=\"3\" align=\"center\">\r\n            <b>Flop {count} Downloads</b>\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>#</b>\r\n          </td>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>Name</b>\r\n          </td>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>Downloads</b>\r\n          </td>\r\n        </tr>\r\n\n{rows}\n        <tr>\r\n          <td bgcolor=\"{footer_bg}\" colspan=\"3\">\r\n            &nbsp;\r\n          </td>\r\n        </tr>\r\n      </table>\r\n    </td>\r\n  </tr>\r\n</table>\r\n', 'textarea', 8, 3);
INSERT INTO ". $sql_table['template'] ." VALUES (22, 'Zeile Top Downloads', 'Eine Datenzeile bei den Top Downloads.', 'top_row', '        <tr>\r\n          <td bgcolor=\"{alt}\">\r\n            {count}\r\n          </td>\r\n          <td bgcolor=\"{alt}\">\r\n            <a href=\"{script_file}release_id={id}\">{name}</a>\r\n          </td>\r\n          <td bgcolor=\"{alt}\">\r\n            {downloads}\r\n          </td>\r\n        </tr>\r\n', 'textarea', 8, 2);
INSERT INTO ". $sql_table['template'] ." VALUES (41, 'Box Top Downloads', 'Die Box fr die Top Downloads.\n{rows} wird durch die Zeilen ersetz.', 'top_box', '<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"200\">\r\n  <tr>\r\n    <td bgcolor=\"{table_border}\">\r\n      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">\r\n        <tr>\r\n          <td bgcolor=\"{header_bg}\" colspan=\"3\" align=\"center\">\r\n            <b>Top {count} Downloads</b>\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>#</b>\r\n          </td>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>Name</b>\r\n          </td>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>Downloads</b>\r\n          </td>\r\n        </tr>\r\n\n{rows}\n        <tr>\r\n          <td bgcolor=\"{footer_bg}\" colspan=\"3\">\r\n            &nbsp;\r\n          </td>\r\n        </tr>\r\n      </table>\r\n    </td>\r\n  </tr>\r\n</table>', 'textarea', 8, 1);
INSERT INTO ". $sql_table['template'] ." VALUES (25, 'Zeile Flop Downloads', 'Datenzeile fr die Flop Downloads', 'flop_row', '        <tr>\r\n          <td bgcolor=\"{alt}\">\r\n            {count}\r\n          </td>\r\n          <td bgcolor=\"{alt}\">\r\n            <a href=\"{script_file}release_id={id}\">{name}</a>\r\n          </td>\r\n          <td bgcolor=\"{alt}\">\r\n            {downloads}\r\n          </td>\r\n        </tr>\r\n', 'textarea', 8, 4);
INSERT INTO ". $sql_table['template'] ." VALUES (40, 'Box Release bersicht', 'Die Box fr die Release bersicht.\n{rows} wird durch die Zeilen ersetz.', 'release_box', '<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\r\n  <tr>\r\n    <td bgcolor=\"{table_border}\">\r\n      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">\r\n        <tr>\r\n          <td bgcolor=\"{header_bg}\" colspan=\"4\" align=\"center\">\r\n            <b>Release</b>\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>Name</b>\r\n          </td>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>Gre</b>\r\n          </td>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>Uploaddatum</b>\r\n          </td>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>Beschreibung</b>\r\n          </td>\r\n        </tr>\r\n\n{rows}\n        <tr>\r\n          <td bgcolor=\"{footer_bg}\" colspan=\"4\" align=\"center\">\r\n            {list}&nbsp;\r\n          </td>\r\n        </tr>\r\n      </table>\r\n    </td>\r\n  </tr>\r\n</table>\r\n<br><br>\r\n', 'textarea', 5, 1);
INSERT INTO ". $sql_table['template'] ." VALUES (27, 'Zeile Latest Downloads', 'Datenzeile der Latest Downloads', 'latest_row', '        <tr>\r\n          <td bgcolor=\"{alt}\">\r\n            {count}\r\n          </td>\r\n          <td bgcolor=\"{alt}\">\r\n            <a href=\"{script_file}release_id={id}\">{name}</a>\r\n          </td>\r\n          <td bgcolor=\"{alt}\">\r\n            {time}\r\n          </td>\r\n        </tr>\r\n', 'textarea', 8, 6);
INSERT INTO ". $sql_table['template'] ." VALUES (30, 'Zeile Best Rated Downloads', 'Datenzeile der Best Rated Downloads', 'rated_row', '        <tr>\r\n          <td bgcolor=\"{alt}\">\r\n            {count}\r\n          </td>\r\n          <td bgcolor=\"{alt}\">\r\n            <a href=\"{script_file}release_id={id}\">{name}</a>\r\n          </td>\r\n          <td bgcolor=\"{alt}\">\r\n            {vote}\r\n          </td>\r\n        </tr>\r\n', 'textarea', 8, 8);
INSERT INTO ". $sql_table['template'] ." VALUES (39, 'Box Ordner bersicht', 'Die Box fr die Ordner bersicht.\n{rows} wird durch die Zeilen ersetz.', 'ordner_box', '<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\r\n  <tr>\r\n    <td bgcolor=\"{table_border}\">\r\n      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">\r\n        <tr>\r\n          <td bgcolor=\"{header_bg}\" colspan=\"4\" align=\"center\">\r\n            <b>Sub-Ordner</b>\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>Name</b>\r\n          </td>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>Release</b>\r\n          </td>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>Sub-Ordner</b>\r\n          </td>\r\n          <td bgcolor=\"{header_bg}\">\r\n            <b>Beschreibung</b>\r\n          </td>\r\n        </tr>\r\n\n{rows}\n        <tr>\r\n          <td bgcolor=\"{footer_bg}\" colspan=\"4\">\r\n            &nbsp;\r\n          </td>\r\n        </tr>\r\n      </table>\r\n    </td>\r\n  </tr>\r\n</table>\r\n<br><br>\r\n', 'textarea', 4, 1);
INSERT INTO ". $sql_table['template'] ." VALUES (32, 'Passwort Besttigungscode 1', 'Email in dem der Passwort Besttigungscode versendet wird.', 'mail_lost1', 'Hallo {user},\r\n\r\nSie haben ein neues Passwort fr ihren Account beantragt.\r\nSollte dies der Fall sein, besttigen sie bitte das zuschicken ihrer Daten indem sie folgende Seite besuchen.\r\n\r\nURL: {url}\r\n\r\nSollten sie kein Passwort fr ihren Account beantragt haben, ignorieren sie diese E-Mail.\r\n\r\nMfG\r\nArpad Borsos aka \"Swatinem\"\r\nDeveloper of PowerDownload', 'textarea', 9, 0);
INSERT INTO ". $sql_table['template'] ." VALUES (33, 'Passwort', 'Hier wird die Email bestimmt die versendet wird, wenn ein User sein Passwort zugeschickt bekommt.', 'mail_lost2', 'Hallo {user},\r\n\r\nSie haben ein neues Passwort fr ihren Account beantragt. Das alte Passwort wird somit unwirksam.\r\n\r\nNeues Passwort: {new_pw}\r\nBenutzername: {user}\r\n\r\nMfG\r\nArpad Borsos aka \"Swatinem\"\r\nDeveloper of PowerDownload', 'textarea', 9, 0);
INSERT INTO ". $sql_table['template'] ." VALUES (34, 'Profil', 'Das Template fr das Profil', 'uprofil_form', '<center>\r\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"450\">\r\n  <tr>\r\n    <td bgcolor=\"{table_border}\">\r\n      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">\r\n        <tr>\r\n          <td bgcolor=\"{header_bg}\" align=\"center\" colspan=\"2\">\r\n            <b>Profil</b>\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{alt_1}\">\r\n            Email Adresse\r\n          </td>\r\n          <td bgcolor=\"{alt_1}\">\r\n            <input type=\"text\" name=\"email\" value=\"{email}\" size=\"35\">\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{alt_2}\">\r\n            Download Letter erhalten<br>\r\n            <small>Wollen sie einen Download Letter erhalten indem die neuen Downloads der Vergangenen Tage zu sehen sind?</small>\r\n          </td>\r\n          <td bgcolor=\"{alt_2}\">\r\n            <input type=\"checkbox\" name=\"get_letter\" value=\"Y\"{get_letter}> Ja, Letter erhalten\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{alt_1}\">\r\n            Homepage\r\n          </td>\r\n          <td bgcolor=\"{alt_1}\">\r\n            <input type=\"text\" name=\"homepage\" value=\"{homepage}\" size=\"35\">\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{alt_2}\">\r\n            ICQ Nummer\r\n          </td>\r\n          <td bgcolor=\"{alt_2}\">\r\n            <input type=\"text\" name=\"icq\" value=\"{icq}\" size=\"35\">\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{alt_1}\">\r\n            Altes Passwort<br>\r\n            <small>Um ihr Profil zu ndern mssen sie hier nochmal das Passwort reinschreiben</small>\r\n          </td>\r\n          <td bgcolor=\"{alt_1}\">\r\n            <input type=\"password\" name=\"pw_old\" size=\"35\">\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{alt_2}\">\r\n            Neues Passwort\r\n          </td>\r\n          <td bgcolor=\"{alt_2}\">\r\n            <input type=\"password\" name=\"pw_new\" size=\"35\">\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{alt_1}\">\r\n            Besttigung\r\n          </td>\r\n          <td bgcolor=\"{alt_1}\">\r\n            <input type=\"password\" name=\"pw_new2\" size=\"35\">\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{footer_bg}\" align=\"center\" colspan=\"2\">\r\n            <input type=\"submit\" value=\"Profil ndern\">\r\n          </td>\r\n        </tr>\r\n      </table>\r\n    </td>\r\n  </tr>\r\n</table>\r\n</center>\r\n<br><br>\r\n', 'textarea', 6, 0);
INSERT INTO ". $sql_table['template'] ." VALUES (35, 'Anmelde Formular', 'Hier knnen sie das Anmelde Formular frei verndern.', 'uregister_form', '<center>\r\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"450\">\r\n  <tr>\r\n    <td bgcolor=\"{table_border}\">\r\n      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">\r\n        <tr>\r\n          <td bgcolor=\"{header_bg}\" align=\"center\" colspan=\"2\">\r\n            <b>Anmelden</b>\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{alt_2}\">\r\n            Nickname\r\n          </td>\r\n          <td bgcolor=\"{alt_2}\">\r\n            <input type=\"text\" name=\"nick\" size=\"35\">\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{alt_1}\">\r\n            Email Adresse\r\n          </td>\r\n          <td bgcolor=\"{alt_1}\">\r\n            <input type=\"text\" name=\"email\" size=\"35\">\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{alt_2}\">\r\n            Download Letter erhalten<br>\r\n            <small>Wollen sie einen Download Letter erhalten indem die neuen Downloads der Vergangenen Tage zu sehen sind?</small>\r\n          </td>\r\n          <td bgcolor=\"{alt_2}\">\r\n            <input type=\"checkbox\" name=\"get_letter\" value=\"Y\" checked> Ja, Letter erhalten\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{alt_1}\">\r\n            Homepage\r\n          </td>\r\n          <td bgcolor=\"{alt_1}\">\r\n            <input type=\"text\" name=\"homepage\" size=\"35\">\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{alt_2}\">\r\n            ICQ Nummer\r\n          </td>\r\n          <td bgcolor=\"{alt_2}\">\r\n            <input type=\"text\" name=\"icq\" size=\"35\">\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{alt_1}\">\r\n            Passwort\r\n          </td>\r\n          <td bgcolor=\"{alt_1}\">\r\n            <input type=\"password\" name=\"pw_new\" size=\"35\">\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{alt_2}\">\r\n            Besttigung\r\n          </td>\r\n          <td bgcolor=\"{alt_2}\">\r\n            <input type=\"password\" name=\"pw_new2\" size=\"35\">\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{footer_bg}\" align=\"center\" colspan=\"2\">\r\n            <input type=\"submit\" value=\"Anmelden\">\r\n          </td>\r\n        </tr>\r\n      </table>\r\n    </td>\r\n  </tr>\r\n</table>\r\n</center>\r\n<br><br>\r\n', 'textarea', 6, 0);
INSERT INTO ". $sql_table['template'] ." VALUES (36, 'Registrierungsmail', 'Hier knnen sie den Inhalt der bei der Registrierung versendeten Mail bestimmen.', 'mail_register', 'Hallo {nick}.\r\nDanke fr ihre Anmeldung. Sie knnen sich nun unter {script_file}usercenter=login mit folgenden Daten einloggen.\r\n\r\nNickname: {nick}\r\nPasswort: {pw}\r\n\r\nNochmals danke fr Ihre Anmeldung.\r\n\r\nMfG\r\nArpad Borsos aka \"Swatinem\"\r\nDeveloper of PowerDownload', 'textarea', 9, 0);
INSERT INTO ". $sql_table['template'] ." VALUES (37, 'Kommentare', 'Hier knnen sie das aussehen der Kommentare ndern.', 'comments', '<br>\r\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\r\n  <tr>\r\n    <td bgcolor=\"{table_border}\">\r\n      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">\r\n        <tr>\r\n          <td bgcolor=\"{header_bg}\" colspan=\"2\" align=\"center\">\r\n            <b>{titel}</b>\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{alt_1}\" colspan=\"2\">\r\n            {text}\r\n          </td>\r\n        </tr>\r\n        <tr>\r\n          <td bgcolor=\"{footer_bg}\" width=\"50%\">\r\n            {time}\r\n          </td>\r\n          <td bgcolor=\"{footer_bg}\" align=\"right\">\r\n            by {autor}\r\n          </td>\r\n        </tr>\r\n      </table>\r\n    </td>\r\n  </tr>\r\n</table>\r\n<br>\r\n', 'textarea', 10, 0);
INSERT INTO ". $sql_table['template'] ." VALUES (38, 'Kommentare Formular', 'Das Formular fr das Kommentare posten.', 'comments_form', '<center>\r\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"70%\">\r\n <tr>\r\n   <td bgcolor=\"{table_border}\">\r\n     <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">\r\n       <tr>\r\n         <td bgcolor=\"{header_bg}\" colspan=\"2\" align=\"center\">\r\n           <b>Kommentar schreiben</b>\r\n         </td>\r\n       </tr>\r\n       <tr>\r\n         <td bgcolor=\"{alt_2}\">\r\n           <b>Eingeloggt als</b>\r\n         </td>\r\n         <td bgcolor=\"{alt_2}\">\r\n           {user}\r\n         </td>\r\n       </tr>\r\n       <tr>\r\n         <td bgcolor=\"{alt_1}\">\r\n           <b>Titel</b>\r\n         </td>\r\n         <td bgcolor=\"{alt_1}\">\r\n           <input type=\"text\" name=\"titel\" size=\"30\" value=\"...\">\r\n         </td>\r\n       </tr>\r\n       <tr>\r\n         <td bgcolor=\"{alt_2}\">\r\n           <b>Kommentar</b><br>\r\n           HTML ist {html}<br>\r\n           BB Code ist {bbcode}<br>\r\n           Glossar ist {glossar}<br>\r\n           Smilies sind {smilies}<br>\r\n           Zensur ist {zensur}\r\n         </td>\r\n         <td bgcolor=\"{alt_2}\">\r\n           <textarea name=\"text\" cols=\"60\" rows=\"10\"></textarea>\r\n         </td>\r\n       </tr>\r\n       <tr>\r\n         <td bgcolor=\"{footer_bg}\" align=\"center\" colspan=\"2\">\r\n           <input type=\"submit\" value=\"Kommentar abschicken\">\r\n         </td>\r\n       </tr>\r\n     </table>\r\n   </td>\r\n </tr>\r\n</table>\r\n<center>\r\n', 'textarea', 10, 0);

# --------------------------------------------------------

INSERT INTO ". $sql_table['templategroup'] ." VALUES (1, 'Farben', 1);
INSERT INTO ". $sql_table['templategroup'] ." VALUES (2, 'Breite', 2);
INSERT INTO ". $sql_table['templategroup'] ." VALUES (3, 'Download Detailseite', 6);
INSERT INTO ". $sql_table['templategroup'] ." VALUES (4, 'Ordnerbersicht', 4);
INSERT INTO ". $sql_table['templategroup'] ." VALUES (5, 'Releasebersicht', 5);
INSERT INTO ". $sql_table['templategroup'] ." VALUES (6, 'Usercenter', 10);
INSERT INTO ". $sql_table['templategroup'] ." VALUES (7, 'Men Templates', 8);
INSERT INTO ". $sql_table['templategroup'] ." VALUES (8, 'Tops', 9);
INSERT INTO ". $sql_table['templategroup'] ." VALUES (9, 'Emails', 7);
INSERT INTO ". $sql_table['templategroup'] ." VALUES (10, 'Kommentare', 6);

# --------------------------------------------------------

INSERT INTO ". $sql_table['usergroup'] ." VALUES (1, 'Godadmin', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y');
INSERT INTO ". $sql_table['usergroup'] ." VALUES (2, 'Member', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N');
INSERT INTO ". $sql_table['usergroup'] ." VALUES (3, 'Gast', 'Y', 'N', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N');
INSERT INTO ". $sql_table['usergroup'] ." VALUES (4, 'Admin', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N');
INSERT INTO ". $sql_table['usergroup'] ." VALUES (5, 'Superadmin', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'N', 'N');
";
?>
