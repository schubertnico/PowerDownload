<?php

/**
 * PowerDownload - Database Setup
 *
 * Spielt die kanonische SQL-Initialisierung aus
 * .docker/initdb/01-pdl3-init.sql in die konfigurierte Datenbank ein.
 *
 * Diese Datei ist die Single Source of Truth fuer die DB-Einrichtung:
 * - Bei `docker compose up` mit leerem MySQL-Volume wird das SQL
 *   automatisch durch MySQL's docker-entrypoint-initdb.d-Mechanismus
 *   eingespielt (siehe .docker/docker-compose.yml).
 * - Wer keine Docker-Setup verwendet, ruft `setup.php` einmalig auf,
 *   um dieselben Tabellen und Default-Daten anzulegen.
 *
 * @package    PowerDownload
 * @author     PowerScripts
 * @copyright  2001-2002 PowerScripts, 2025 Nico Schubert
 * @license    MIT License
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Datenbank-Konfiguration (auf die Docker-Defaults aus docker-compose.yml abgestimmt).
$config_sql_server   = getenv('PDL_DB_HOST') ?: 'db';
$config_sql_database = getenv('PDL_DB_NAME') ?: 'pdl3';
$config_sql_user     = getenv('PDL_DB_USER') ?: 'pdl_user';
$config_sql_password = getenv('PDL_DB_PASS') ?: 'pdl_password';

$sql_file = __DIR__ . '/.docker/initdb/01-pdl3-init.sql';

?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PowerDownload Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="pdl-admin/admin.css" rel="stylesheet">
</head>
<body class="pdl-admin">
<nav class="navbar navbar-expand-lg pdl-admin-navbar">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold">PowerDownload - Setup</span>
    </div>
</nav>
<main class="container py-4">
    <h1 class="h3 pdl-page-title">Datenbank-Setup</h1>
<?php

if (!is_readable($sql_file)) {
    echo '<div class="alert alert-danger" role="alert"><strong>SQL-Datei nicht gefunden:</strong> '
        . htmlspecialchars($sql_file)
        . '<br>Bitte stelle sicher, dass <code>.docker/initdb/01-pdl3-init.sql</code> im Projekt vorhanden ist.</div>';
    echo '</main><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>';
    exit;
}

$mysqli = @new mysqli($config_sql_server, $config_sql_user, $config_sql_password, $config_sql_database);
if ($mysqli->connect_error) {
    echo '<div class="alert alert-danger" role="alert"><strong>Datenbank-Verbindung fehlgeschlagen:</strong> '
        . htmlspecialchars($mysqli->connect_error) . '</div>';
    echo '</main><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>';
    exit;
}
$mysqli->set_charset('utf8mb4');

echo '<div class="alert alert-success" role="alert">Datenbank-Verbindung erfolgreich: <code>'
    . htmlspecialchars($config_sql_database) . '@' . htmlspecialchars($config_sql_server) . '</code></div>';

$sql = (string) file_get_contents($sql_file);
echo '<p class="text-muted small">SQL-Quelle: <code>' . htmlspecialchars($sql_file) . '</code> ('
    . number_format(strlen($sql) / 1024, 1, ',', '.') . ' KiB)</p>';

echo '<section class="card pdl-card mb-4">';
echo '<header class="card-header"><h2 class="h5 mb-0">Ausfuehrung</h2></header>';
echo '<ul class="list-group list-group-flush">';

// Mehrere Statements durch mysqli::multi_query einspielen und alle Result-Sets durchrollen.
$ok = $mysqli->multi_query($sql);
if (!$ok) {
    echo '<li class="list-group-item bg-transparent text-body"><span class="badge text-bg-danger me-2">FAIL</span>'
        . htmlspecialchars($mysqli->error) . '</li>';
} else {
    $stmt_count = 1;
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
        if ($mysqli->errno) {
            echo '<li class="list-group-item bg-transparent text-body"><span class="badge text-bg-warning text-dark me-2">WARN</span>'
                . 'Statement #' . $stmt_count . ': ' . htmlspecialchars($mysqli->error) . '</li>';
        }
        $stmt_count++;
    } while ($mysqli->more_results() && $mysqli->next_result());
    echo '<li class="list-group-item bg-transparent text-body"><span class="badge text-bg-success me-2">OK</span>'
        . ($stmt_count - 1) . ' Statements eingespielt.</li>';
}
echo '</ul></section>';

$counts = [
    'pdl3_rights'        => 'Rechte',
    'pdl3_settings'      => 'Settings',
    'pdl3_settingsgroup' => 'Settings-Gruppen',
    'pdl3_template'      => 'Templates',
    'pdl3_templategroup' => 'Template-Gruppen',
    'pdl3_usergroup'     => 'Usergruppen',
    'pdl3_user'          => 'Benutzer',
];
echo '<section class="card pdl-card mb-4">';
echo '<header class="card-header"><h2 class="h5 mb-0">Befuellung</h2></header>';
echo '<ul class="list-group list-group-flush">';
foreach ($counts as $table => $label) {
    $res = $mysqli->query('SELECT COUNT(*) AS c FROM ' . $table);
    $n = $res ? (int) ($res->fetch_assoc()['c'] ?? 0) : 0;
    echo '<li class="list-group-item bg-transparent text-body d-flex justify-content-between">'
        . '<span>' . htmlspecialchars($label) . ' (<code>' . $table . '</code>)</span>'
        . '<span class="badge text-bg-secondary">' . $n . '</span></li>';
}
echo '</ul></section>';

?>
    <div class="alert alert-success" role="alert">
        <h2 class="h5">Setup abgeschlossen.</h2>
        <p class="mb-2"><strong>Standard-Admin-Login:</strong></p>
        <ul class="mb-0">
            <li>Benutzername: <code>admin</code></li>
            <li>Passwort: <code>admin123</code></li>
        </ul>
    </div>
    <div class="alert alert-warning" role="alert">
        <strong>Wichtig:</strong> Loesche diese <code>setup.php</code>-Datei nach der Installation, damit sie nicht von Dritten aufgerufen werden kann.
    </div>
    <p><a class="btn btn-primary" href="downloads.php">Zu PowerDownload</a></p>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$mysqli->close();
