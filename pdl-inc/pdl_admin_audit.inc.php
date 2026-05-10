<?php

/**
 * PowerDownload - Admin-Audit-Log
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

/**
 * Stellt sicher, dass die Audit-Log-Tabelle existiert. Idempotent.
 *
 * @param pdl_db_class $db
 * @param array<string, string> $sqlTable
 */
function pdl_audit_ensure_table($db, array $sqlTable): void
{
    $table = (string) ($sqlTable['admin_log'] ?? 'pdl3_admin_log');
    $db->sql_query(
        "CREATE TABLE IF NOT EXISTS `" . $table . "` (
            log_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT NOT NULL DEFAULT 0,
            action VARCHAR(32) NOT NULL,
            target_type VARCHAR(32) NOT NULL,
            target_id INT NOT NULL DEFAULT 0,
            time INT NOT NULL,
            ip VARCHAR(45) NOT NULL DEFAULT '',
            PRIMARY KEY (log_id),
            KEY idx_target (target_type, target_id),
            KEY idx_user_time (user_id, time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

/**
 * Schreibt einen Eintrag in das Admin-Audit-Log.
 *
 * @param pdl_db_class $db
 * @param array<string, string> $sqlTable
 * @param array<string, mixed>|null $userDetails
 */
function pdl_audit_log(
    $db,
    array $sqlTable,
    ?array $userDetails,
    string $action,
    string $targetType,
    int $targetId,
    ?string $ip = null
): void {
    pdl_audit_ensure_table($db, $sqlTable);

    $table = (string) ($sqlTable['admin_log'] ?? 'pdl3_admin_log');
    $userId = (int) ($userDetails['user_id'] ?? 0);
    $time = time();
    $ipAddr = $ip ?? (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    $db->sql_query(
        "INSERT INTO `" . $table . "` (user_id, action, target_type, target_id, time, ip) VALUES ("
        . $db->sql_escape_int($userId) . ", "
        . "'" . $db->sql_escape_string($action) . "', "
        . "'" . $db->sql_escape_string($targetType) . "', "
        . $db->sql_escape_int($targetId) . ", "
        . $db->sql_escape_int($time) . ", "
        . "'" . $db->sql_escape_string($ipAddr) . "')"
    );
}

/**
 * Liefert die zuletzt geschriebenen Audit-Log-Zeilen (für Tests/Reports).
 *
 * @param pdl_db_class $db
 * @param array<string, string> $sqlTable
 * @return list<array<int|string, mixed>>
 */
function pdl_audit_recent($db, array $sqlTable, int $limit = 20): array
{
    pdl_audit_ensure_table($db, $sqlTable);
    $table = (string) ($sqlTable['admin_log'] ?? 'pdl3_admin_log');
    $limit = max(1, min($limit, 1000));
    $res = $db->sql_query("SELECT * FROM `" . $table . "` ORDER BY log_id DESC LIMIT " . $limit);
    $out = [];
    while ($row = $db->sql_fetch_array($res)) {
        $out[] = $row;
    }
    return $out;
}
