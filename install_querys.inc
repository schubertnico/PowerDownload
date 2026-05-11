<?php

/**
 * PowerDownload - Legacy Install Queries
 *
 * Wird von install_303.php geladen. Liefert die Variable
 * $install_querys, die anschliessend per split_query() in
 * einzelne Statements zerlegt und ausgefuehrt wird.
 *
 * Single Source of Truth: .docker/initdb/01-pdl3-init.sql.
 * Dieselbe Datei wird auch von setup.php und vom MySQL-
 * Docker-Init (docker-entrypoint-initdb.d) verwendet — damit
 * ist sichergestellt, dass jede Installationsvariante
 * exakt dasselbe Schema und dieselben Defaults erzeugt.
 *
 * @package    PowerDownload
 * @license    MIT License
 */

$pdl_init_sql_path = __DIR__ . '/.docker/initdb/01-pdl3-init.sql';

if (!is_readable($pdl_init_sql_path)) {
    trigger_error(
        'install_querys.inc: SQL-Quelle nicht lesbar: ' . $pdl_init_sql_path,
        E_USER_ERROR
    );
    $install_querys = '';
} else {
    $install_querys = (string) file_get_contents($pdl_init_sql_path);
}
