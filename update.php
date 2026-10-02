<?php

/**
 * PowerDownload - Datenbank einer bestehenden Installation aktualisieren
 *
 * Vorher: Datensicherung, neue Dateien hochladen (install.php weglassen),
 * Zugangsdaten in pdl-inc/pdl_config.local.php (Vorlage:
 * pdl-inc/pdl_config.local.example.php). Nur für angemeldete Admins mit dem
 * Recht „settings“. Zeigt zuerst eine Vorschau; geändert wird erst nach
 * „Jetzt aktualisieren“. Jeder Lauf ergänzt nur, was fehlt, und lässt sich
 * gefahrlos wiederholen.
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

use PowerDownload\Installer\DatabaseSetup;
use PowerDownload\Installer\ServerVersion;
use PowerDownload\Installer\Updater;
use PowerDownload\Installer\View;
use PowerDownload\LocalConfig;

// Bootstrap wie im Adminbereich: Konfiguration, Datenbank, Sitzung, Rechte.
$incdir = '';
$inadmin = 1;
include __DIR__ . '/pdl-inc/pdl_header.inc.php';
require_once __DIR__ . '/pdl-inc/installer/autoload.php';

header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

$pdl_update_allowed = $user_details
    && ($user_rights['adminaccess'] ?? 'N') === 'Y'
    && ($user_rights['settings'] ?? 'N') === 'Y';

if (!$pdl_update_allowed) {
    http_response_code(403);
    View::render('update_denied', 'Datenbank aktualisieren', 'Update', 0, 0, ['loggedIn' => (bool) $user_details]);
    exit;
}

$pdl_update_message = '';
$pdl_update_updater = null;
// Fehlt site_url, schlägt update.php die Adresse vor, unter der es gerade aufgerufen wird
// (dasselbe Verzeichnis wie downloads.php). Sonst hingen Links in Mails am Host-Header.
$pdl_update_site_url = PowerDownload\Installer\Wizard::suggestSiteUrl($_SERVER);
$pdl_update_new_settings = $pdl_update_site_url !== '' ? ['site_url' => $pdl_update_site_url] : [];
$pdl_update_mysqli = $db_handler->handler;

if (!$pdl_update_mysqli instanceof mysqli) {
    $pdl_update_message = 'Keine Verbindung zur Datenbank.';
} else {
    // Ab hier meldet mysqli Fehler als Ausnahme; Updater fängt sie je Aktion ab.
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $pdl_update_updater = Updater::fromFiles(__DIR__, array_values($sql_table));
    } catch (RuntimeException|UnexpectedValueException $e) {
        $pdl_update_message = $e->getMessage();
    }
}

if ($pdl_update_updater !== null && $pdl_update_mysqli instanceof mysqli && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $pdl_update_token = $_POST['csrf_token'] ?? null;

    if (csrf_verify(is_string($pdl_update_token) ? $pdl_update_token : null)) {
        try {
            $pdl_update_plan = $pdl_update_updater->plan(Updater::snapshot($pdl_update_mysqli), time(), $pdl_update_new_settings);
            $_SESSION['pdl_update_log'] = array_merge(
                Updater::apply($pdl_update_mysqli, $pdl_update_plan),
                array_map(static fn (string $note): array => ['kind' => 'note', 'label' => $note, 'ok' => true, 'message' => 'Hinweis'], $pdl_update_plan['notes']),
            );
            pdl_audit_log($db_handler, $sql_table, $user_details, 'update', 'database', 0);
        } catch (mysqli_sql_exception $e) {
            $_SESSION['pdl_update_log'] = [[
                'kind' => 'error',
                'label' => 'Datenbank lesen',
                'ok' => false,
                'message' => DatabaseSetup::friendlyError($e->getCode()),
            ]];
        }
        header('Location: update.php', true, 303);
        exit;
    }

    $pdl_update_message = 'Ihre Sitzung ist abgelaufen oder Cookies sind blockiert. Bitte laden Sie die Seite neu und starten Sie das Update erneut.';
}

$pdl_update_log = $_SESSION['pdl_update_log'] ?? null;
unset($_SESSION['pdl_update_log']);

$pdl_update_plan = ['actions' => [], 'kept' => [], 'notes' => []];
$pdl_update_server = null;

if ($pdl_update_updater !== null && $pdl_update_mysqli instanceof mysqli) {
    try {
        $pdl_update_plan = $pdl_update_updater->plan(Updater::snapshot($pdl_update_mysqli), time(), $pdl_update_new_settings);
        $pdl_update_server = ServerVersion::parse(DatabaseSetup::serverVersion($pdl_update_mysqli));
    } catch (mysqli_sql_exception $e) {
        $pdl_update_message = DatabaseSetup::friendlyError($e->getCode());
    }
}

View::render('update', 'Datenbank aktualisieren', 'Update', 0, 0, [
    'csrf' => csrf_token(),
    'message' => $pdl_update_message,
    'version' => $settings['pdlversion'] ?? '',
    'server' => $pdl_update_server,
    'configSource' => is_string($config_source ?? null) ? $config_source : LocalConfig::SOURCE_DEFAULTS,
    'plan' => $pdl_update_plan,
    'log' => is_array($pdl_update_log) ? $pdl_update_log : null,
    'installerPresent' => is_file(__DIR__ . '/install.php'),
]);
