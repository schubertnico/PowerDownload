<?php

/**
 * PowerDownload - Simple Database Setup
 *
 * @package    PowerDownload
 * @author     PowerScripts
 * @copyright  2001-2002 PowerScripts, 2025 Nico Schubert
 * @license    MIT License
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Database configuration
$config_sql_server = "db";
$config_sql_database = "pdl3";
$config_sql_user = "pdl_user";
$config_sql_password = "pdl_password";

echo "<html><head><title>PowerDownload Setup</title></head><body style='background:#000;color:#fff;font-family:monospace;padding:20px;'>";
echo "<h1>PowerDownload v3.0.3 - Database Setup</h1>";

// Connect to database
$mysqli = new mysqli($config_sql_server, $config_sql_user, $config_sql_password, $config_sql_database);

if ($mysqli->connect_error) {
    die("<p style='color:red;'>Connection failed: " . htmlspecialchars($mysqli->connect_error) . "</p>");
}

echo "<p style='color:green;'>Connected to database successfully.</p>";

// SQL statements for table creation
$sql_statements = [
    "DROP TABLE IF EXISTS pdl3_comments",
    "CREATE TABLE pdl3_comments (
        comment_id int(9) unsigned NOT NULL auto_increment,
        user_id int(9) NOT NULL default '0',
        release_id int(9) NOT NULL default '0',
        titel varchar(128) NOT NULL default '',
        text text NOT NULL,
        time int(14) NOT NULL default '0',
        PRIMARY KEY (comment_id),
        KEY release_id (release_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "DROP TABLE IF EXISTS pdl3_files",
    "CREATE TABLE pdl3_files (
        file_id int(9) unsigned NOT NULL auto_increment,
        release_id int(9) NOT NULL default '0',
        downloads int(9) NOT NULL default '0',
        url varchar(255) NOT NULL default '',
        size bigint(14) NOT NULL default '0',
        name varchar(128) NOT NULL default '',
        mirror int(9) NOT NULL default '0',
        PRIMARY KEY (file_id),
        KEY release_id (release_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "DROP TABLE IF EXISTS pdl3_iplock",
    "CREATE TABLE pdl3_iplock (
        ip varchar(32) NOT NULL default '',
        time int(14) NOT NULL default '0',
        file_id int(9) NOT NULL default '0',
        user_id int(9) NOT NULL default '0',
        art enum('comment','vote') NOT NULL default 'comment'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "DROP TABLE IF EXISTS pdl3_ordner",
    "CREATE TABLE pdl3_ordner (
        ordner_id int(9) unsigned NOT NULL auto_increment,
        sordner_id int(9) NOT NULL default '0',
        name varchar(128) NOT NULL default '',
        text text NOT NULL,
        PRIMARY KEY (ordner_id),
        KEY sordner_id (sordner_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "DROP TABLE IF EXISTS pdl3_release",
    "CREATE TABLE pdl3_release (
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
        PRIMARY KEY (release_id),
        KEY ordner_id (ordner_id),
        KEY released (released)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "DROP TABLE IF EXISTS pdl3_replacements",
    "CREATE TABLE pdl3_replacements (
        rep_id int(9) unsigned NOT NULL auto_increment,
        old varchar(128) NOT NULL default '',
        neu varchar(255) NOT NULL default '',
        type enum('s','g','b') NOT NULL default 's',
        PRIMARY KEY (rep_id),
        KEY type (type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "DROP TABLE IF EXISTS pdl3_rights",
    "CREATE TABLE pdl3_rights (
        right_id int(9) unsigned NOT NULL auto_increment,
        name varchar(128) NOT NULL default '',
        bez varchar(255) NOT NULL default '',
        variablenname varchar(32) NOT NULL default '',
        reihenfolge tinyint(2) NOT NULL default '0',
        PRIMARY KEY (right_id),
        UNIQUE KEY variablenname (variablenname)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "DROP TABLE IF EXISTS pdl3_screens",
    "CREATE TABLE pdl3_screens (
        screen_id int(9) unsigned NOT NULL auto_increment,
        release_id int(9) NOT NULL default '0',
        name varchar(128) NOT NULL default '',
        datei varchar(255) NOT NULL default '',
        thumb varchar(255) NOT NULL default '',
        PRIMARY KEY (screen_id),
        KEY release_id (release_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "DROP TABLE IF EXISTS pdl3_settings",
    "CREATE TABLE pdl3_settings (
        setting_id int(9) unsigned NOT NULL auto_increment,
        variablenname varchar(64) NOT NULL default '',
        wert text NOT NULL,
        sgroup_id int(9) NOT NULL default '0',
        PRIMARY KEY (setting_id),
        UNIQUE KEY variablenname (variablenname)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "DROP TABLE IF EXISTS pdl3_settingsgroup",
    "CREATE TABLE pdl3_settingsgroup (
        sgroup_id int(9) unsigned NOT NULL auto_increment,
        name varchar(128) NOT NULL default '',
        reihenfolge tinyint(2) NOT NULL default '0',
        PRIMARY KEY (sgroup_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "DROP TABLE IF EXISTS pdl3_template",
    "CREATE TABLE pdl3_template (
        template_id int(9) unsigned NOT NULL auto_increment,
        variablenname varchar(64) NOT NULL default '',
        wert text NOT NULL,
        tgroup_id int(9) NOT NULL default '0',
        PRIMARY KEY (template_id),
        UNIQUE KEY variablenname (variablenname)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "DROP TABLE IF EXISTS pdl3_templategroup",
    "CREATE TABLE pdl3_templategroup (
        tgroup_id int(9) unsigned NOT NULL auto_increment,
        name varchar(128) NOT NULL default '',
        reihenfolge tinyint(2) NOT NULL default '0',
        PRIMARY KEY (tgroup_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "DROP TABLE IF EXISTS pdl3_user",
    "CREATE TABLE pdl3_user (
        user_id int(9) unsigned NOT NULL auto_increment,
        nick varchar(64) NOT NULL default '',
        email varchar(128) NOT NULL default '',
        passwort varchar(64) NOT NULL default '',
        ugroup_id int(9) NOT NULL default '1',
        icq int(14) NOT NULL default '0',
        homepage varchar(128) NOT NULL default '',
        PRIMARY KEY (user_id),
        UNIQUE KEY nick (nick)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "DROP TABLE IF EXISTS pdl3_usergroup",
    "CREATE TABLE pdl3_usergroup (
        ugroup_id int(9) unsigned NOT NULL auto_increment,
        name varchar(128) NOT NULL default '',
        download enum('Y','N') NOT NULL default 'Y',
        comment enum('Y','N') NOT NULL default 'Y',
        vote enum('Y','N') NOT NULL default 'Y',
        adminaccess enum('Y','N') NOT NULL default 'N',
        addfiles enum('Y','N') NOT NULL default 'N',
        editfiles enum('Y','N') NOT NULL default 'N',
        delfiles enum('Y','N') NOT NULL default 'N',
        adddirs enum('Y','N') NOT NULL default 'N',
        editdirs enum('Y','N') NOT NULL default 'N',
        deldirs enum('Y','N') NOT NULL default 'N',
        adduser enum('Y','N') NOT NULL default 'N',
        edituser enum('Y','N') NOT NULL default 'N',
        deluser enum('Y','N') NOT NULL default 'N',
        settings enum('Y','N') NOT NULL default 'N',
        templates enum('Y','N') NOT NULL default 'N',
        replacements enum('Y','N') NOT NULL default 'N',
        backup enum('Y','N') NOT NULL default 'N',
        PRIMARY KEY (ugroup_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

echo "<h2>Creating tables...</h2><pre>";

foreach ($sql_statements as $sql) {
    if ($mysqli->query($sql)) {
        echo "<span style='color:green;'>OK:</span> " . htmlspecialchars(substr($sql, 0, 60)) . "...\n";
    } else {
        echo "<span style='color:red;'>ERROR:</span> " . htmlspecialchars($mysqli->error) . "\n";
    }
}

// Insert default data
echo "</pre><h2>Inserting default data...</h2><pre>";

$default_data = [
    // Default user group (Guest)
    "INSERT INTO pdl3_usergroup (ugroup_id, name, download, comment, vote) VALUES (1, 'Gast', 'Y', 'N', 'N')",
    // Admin group
    "INSERT INTO pdl3_usergroup (ugroup_id, name, download, comment, vote, adminaccess, addfiles, editfiles, delfiles, adddirs, editdirs, deldirs, adduser, edituser, deluser, settings, templates, replacements, backup) VALUES (2, 'Administrator', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y')",
    // Default admin user (admin/admin123)
    "INSERT INTO pdl3_user (nick, email, passwort, ugroup_id) VALUES ('admin', 'admin@example.com', '" . md5('admin123') . "', 2)",
    // Settings
    "INSERT INTO pdl3_settings (variablenname, wert) VALUES ('script_file', 'downloads.php')",
    "INSERT INTO pdl3_settings (variablenname, wert) VALUES ('date_format', 'd.m.Y')",
    "INSERT INTO pdl3_settings (variablenname, wert) VALUES ('dlspeed', '56')",
    "INSERT INTO pdl3_settings (variablenname, wert) VALUES ('perpage', '10')",
    "INSERT INTO pdl3_settings (variablenname, wert) VALUES ('orderby', 'name')",
    "INSERT INTO pdl3_settings (variablenname, wert) VALUES ('orderseq', 'ASC')",
    "INSERT INTO pdl3_settings (variablenname, wert) VALUES ('enable_treeview', 'N')",
    "INSERT INTO pdl3_settings (variablenname, wert) VALUES ('enable_extrernadmin', 'Y')",
    "INSERT INTO pdl3_settings (variablenname, wert) VALUES ('referer_check', 'N')",
    "INSERT INTO pdl3_settings (variablenname, wert) VALUES ('spages', '10')",
    // Template settings
    "INSERT INTO pdl3_template (variablenname, wert) VALUES ('all_width', '100%')",
    "INSERT INTO pdl3_template (variablenname, wert) VALUES ('table_border', '#9B0000')",
    "INSERT INTO pdl3_template (variablenname, wert) VALUES ('header_bg', '#700000')",
    "INSERT INTO pdl3_template (variablenname, wert) VALUES ('footer_bg', '#700000')",
    "INSERT INTO pdl3_template (variablenname, wert) VALUES ('alt_1', '#1A1A1A')",
    "INSERT INTO pdl3_template (variablenname, wert) VALUES ('alt_2', '#2A2A2A')",
];

foreach ($default_data as $sql) {
    if ($mysqli->query($sql)) {
        echo "<span style='color:green;'>OK:</span> " . htmlspecialchars(substr($sql, 0, 80)) . "...\n";
    } else {
        echo "<span style='color:orange;'>WARN:</span> " . htmlspecialchars($mysqli->error) . "\n";
    }
}

echo "</pre>";
echo "<h2 style='color:green;'>Setup complete!</h2>";
echo "<p><strong>Default Admin Login:</strong></p>";
echo "<ul><li>Username: <code>admin</code></li><li>Password: <code>admin123</code></li></ul>";
echo "<p><a href='downloads.php' style='color:#0af;'>Go to PowerDownload</a></p>";
echo "<p style='color:orange;'><strong>Important:</strong> Delete this setup.php file after installation!</p>";

$mysqli->close();
echo "</body></html>";
