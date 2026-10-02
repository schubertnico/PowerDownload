<?php

/**
 * PowerDownload - Configuration
 *
 * Enthält keine Zugangsdaten. Die Zugangsdaten zur Datenbank kommen aus
 * (höchste Priorität zuerst):
 *   1. den Umgebungsvariablen PDL_DB_HOST, PDL_DB_PORT, PDL_DB_USER,
 *      PDL_DB_PASS, PDL_DB_NAME (Docker, SetEnv beim Hoster),
 *   2. pdl-inc/pdl_config.local.php (schreibt der Web-Installer install.php;
 *      Vorlage zum Anlegen von Hand: pdl_config.local.example.php),
 *   3. den Vorgaben in PowerDownload\LocalConfig::DEFAULT_DB. Damit gilt
 *      PowerDownload als nicht eingerichtet.
 *
 * Diese Datei wird bei jedem Update überschrieben. Eigene Zugangsdaten
 * gehören deshalb nie hierher, sondern in pdl_config.local.php.
 *
 * @package    PowerDownload
 * @author     PowerScripts
 * @copyright  2001-2002 PowerScripts, 2025 Nico Schubert
 * @license    MIT License
 */

declare(strict_types=1);

require_once __DIR__ . '/pdl_localconfig.inc.php';

$pdl_local_config = \PowerDownload\LocalConfig::load(
    static fn (string $name): string|false => getenv($name),
    __DIR__ . '/' . \PowerDownload\LocalConfig::FILENAME
);

// SQL Zugangsdaten
$config_sql_server = $pdl_local_config['db']['host'];         // SQL Server
$config_sql_port = $pdl_local_config['db']['port'];           // SQL Port
$config_sql_database = $pdl_local_config['db']['database'];   // SQL Datenbank
$config_sql_user = $pdl_local_config['db']['user'];           // SQL Benutzer
$config_sql_password = $pdl_local_config['db']['password'];   // SQL Passwort
$config_sql_persistent = $pdl_local_config['db']['persistent']; // Persistente Verbindung?
$config_sql_type = "MySQL";                                   // SQL Typ
$config_source = $pdl_local_config['source'];                 // environment, file oder defaults
unset($pdl_local_config);

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
    'admin_log' => "pdl3_admin_log",
];
