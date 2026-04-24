<?php

/**
 * PowerDownload - Configuration
 *
 * @package    PowerDownload
 * @author     PowerScripts
 * @copyright  2001-2002 PowerScripts, 2025 Nico Schubert
 * @license    MIT License
 */

declare(strict_types=1);

// SQL Zugangsdaten
$config_sql_server = "db";           // SQL Server (use "db" for Docker, "localhost" for local)
$config_sql_database = "pdl3";       // SQL Datenbank
$config_sql_user = "pdl_user";       // SQL Benutzer
$config_sql_password = "pdl_password"; // SQL Passwort
$config_sql_persistent = false;      // Soll eine persistente Verbindung aufgebaut werden?
$config_sql_type = "MySQL";          // SQL Typ

// Tabellen Namen
$sql_table = [
    'comments' => "pdl3_comments",
    'files' => "pdl3_files",
    'iplock' => "pdl3_iplock",
    'ordner' => "pdl3_ordner",
    'release' => "pdl3_release",
    'replacements' => "pdl3_replacements",
    'rights' => "pdl3_rights",
    'screens' => "pdl3_screens",
    'settings' => "pdl3_settings",
    'settingsgroup' => "pdl3_settingsgroup",
    'template' => "pdl3_template",
    'templategroup' => "pdl3_templategroup",
    'user' => "pdl3_user",
    'usergroup' => "pdl3_usergroup",
];
