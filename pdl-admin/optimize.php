<?php
/**
 * PowerDownload - Datenbank optimieren
 *
 * Führt OPTIMIZE TABLE für die Tabellen von PowerDownload aus und zeigt je
 * Tabelle Einträge, Größe vorher/nachher und das Ergebnis. Nur per POST mit
 * CSRF-Token.
 */
include("header.inc.php");
include_once("system_helpers.inc.php");

if (!pdl_sys_can($user_rights, 'backup')) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('backup'));
    include("footer.inc.php");
    return;
}

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'System'],
    ['title' => 'Datenbank optimieren'],
]);
echo '<h1 class="h3 pdl-page-title">Datenbank optimieren</h1>';

$existing = [];
$res = $db_handler->sql_query('SHOW TABLES');
while ($row = $db_handler->sql_fetch_array($res)) {
    $existing[] = (string) $row[0];
}
$tables = array_values(array_intersect(pdl_sys_backup_tables($sql_table), $existing));

/**
 * Aktuelle Größe einer Tabelle (Daten + Index) und Zahl der Einträge.
 *
 * @return array{size: int, free: int, rows: int}
 */
$status = static function (string $table) use ($db_handler): array {
    $row = $db_handler->sql_fetch_array($db_handler->sql_query("SHOW TABLE STATUS LIKE '" . $db_handler->sql_escape_string($table) . "'"));
    return [
        'size' => (int) ($row['Data_length'] ?? 0) + (int) ($row['Index_length'] ?? 0),
        'free' => (int) ($row['Data_free'] ?? 0),
        'rows' => pdl_sys_scalar_int($db_handler, 'SELECT COUNT(*) FROM ' . pdl_sys_ident($table)),
    ];
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && pdl_sys_csrf_ok()) {
    set_time_limit(300);
    // Aktuelle Werte statt zwischengespeicherter Statistik (MySQL 8; MariaDB ignoriert das)
    $db_handler->sql_query('SET SESSION information_schema_stats_expiry = 0');
    $results = [];
    $sum_before = 0;
    $sum_after = 0;
    $problems = 0;
    foreach ($tables as $table) {
        $before = $status($table);
        $messages = [];
        $ok = true;
        $opt = $db_handler->sql_query('OPTIMIZE TABLE ' . pdl_sys_ident($table));
        if ($opt === false) {
            $ok = false;
            $messages[] = pdl_sys_db_error($db_handler);
        } else {
            while ($msg = $db_handler->sql_fetch_array($opt)) {
                $type = strtolower((string) ($msg['Msg_type'] ?? ''));
                $text = (string) ($msg['Msg_text'] ?? '');
                if ($type === 'error') {
                    $ok = false;
                    $messages[] = $text;
                }
            }
        }
        $after = $status($table);
        $problems += $ok ? 0 : 1;
        $sum_before += $before['size'];
        $sum_after += $after['size'];
        $results[] = ['table' => $table, 'rows' => $after['rows'], 'before' => $before['size'], 'after' => $after['size'], 'ok' => $ok, 'messages' => $messages];
    }
    pdl_audit_log($db_handler, $sql_table, $user_details, 'optimize', 'database', count($tables));

    $saved = max(0, $sum_before - $sum_after);
    if ($problems === 0) {
        echo pdl_admin_alert('success', '<strong>' . count($tables) . ' Tabellen wurden geprüft und optimiert.</strong> '
            . ($saved > 0
                ? 'Freigegeben: ' . pdl_sys_bytes($saved) . '.'
                : 'Es war kein ungenutzter Speicher freizugeben – die Datenbank ist bereits kompakt.'));
    } else {
        echo pdl_admin_alert('warning', '<strong>' . $problems . ' von ' . count($tables) . ' Tabellen meldeten einen Fehler.</strong> Details stehen in der Tabelle.');
    }
    ?>
<section class="card pdl-card mb-4" id="pdlOptimizeResult">
    <header class="card-header"><h2 class="h5 mb-0">Ergebnis</h2></header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead><tr><th scope="col">Tabelle</th><th scope="col" class="text-end">Einträge</th><th scope="col" class="text-end">Größe vorher</th><th scope="col" class="text-end">Größe nachher</th><th scope="col">Ergebnis</th></tr></thead>
            <tbody>
            <?php foreach ($results as $r) { ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($r['table']); ?></code></td>
                    <td class="text-end"><?php echo pdl_sys_num($r['rows']); ?></td>
                    <td class="text-end"><?php echo pdl_sys_bytes($r['before']); ?></td>
                    <td class="text-end"><?php echo pdl_sys_bytes($r['after']); ?></td>
                    <td><?php echo $r['ok'] ? '<span class="text-success">in Ordnung</span>' : '<span class="text-danger">' . htmlspecialchars(implode(' ', $r['messages'])) . '</span>'; ?></td>
                </tr>
            <?php } ?>
            </tbody>
            <tfoot class="table-group-divider"><tr class="fw-bold">
                <th scope="row">Gesamt (<?php echo count($results); ?> Tabellen)</th>
                <td></td>
                <td class="text-end"><?php echo pdl_sys_bytes($sum_before); ?></td>
                <td class="text-end"><?php echo pdl_sys_bytes($sum_after); ?></td>
                <td></td>
            </tr></tfoot>
        </table>
    </div>
    <div class="card-footer small text-muted">MySQL legt Speicher in Blöcken an (bei InnoDB 16 KB je Seite). Bei kleinen Datenbanken ändert sich die Größe deshalb oft nicht; spürbar wird die Optimierung erst nach vielen gelöschten Einträgen.</div>
</section>
<a class="btn btn-outline-light" href="index.php">Zur Übersicht</a>
    <?php
    include("footer.inc.php");
    return;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    echo pdl_admin_alert('danger', pdl_sys_csrf_error_text());
}

$total_size = 0;
foreach ($tables as $table) {
    $total_size += $status($table)['size'];
}
echo makedialog(
    'Datenbank jetzt optimieren?',
    '<p class="mb-2">PowerDownload prüft seine <strong>' . count($tables) . ' Tabellen</strong> (derzeit zusammen ' . pdl_sys_bytes($total_size) . ') und baut sie neu auf. '
    . 'Dabei wird Speicher freigegeben, den gelöschte Einträge noch belegen. Daten gehen nicht verloren.</p>'
    . '<p class="mb-0">Bei kleinen Datenbanken dauert das nur Sekunden. Sinnvoll ist es nach dem Löschen vieler Releases oder Kommentare, sonst etwa alle paar Monate.</p>',
    'Ja, jetzt optimieren',
    'optimize.php',
    'index.php'
);
include("footer.inc.php");
