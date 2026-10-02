<?php

declare(strict_types=1);

/*
 * PowerDownload: Vorlage für die lokale Konfiguration
 *
 * Normalerweise schreibt der Web-Installer (install.php) die Datei
 * pdl_config.local.php selbst. Diese Vorlage brauchen Sie nur, wenn Sie
 * eine bestehende Installation aktualisieren (z. B. von 3.5.0 auf 3.6.0)
 * oder die Datei von Hand anlegen möchten:
 *
 *   1. Datei kopieren und als pdl_config.local.php im selben Verzeichnis
 *      (pdl-inc/) speichern.
 *   2. Die Zugangsdaten Ihrer Datenbank eintragen (bisher standen sie in
 *      pdl-inc/pdl_config.inc.php).
 *   3. Datei hochladen. Sie wird bei Updates nicht überschrieben.
 *
 * Umgebungsvariablen PDL_DB_* haben Vorrang vor diesen Werten.
 */

return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'BENUTZERNAME',
        'password' => 'PASSWORT',
        'database' => 'DATENBANKNAME',
        'persistent' => false,
    ],
];
