<?php
/**
 * PowerDownload - Sicherung erstellen (Datenbank als SQL-Datei herunterladen)
 *
 * Gesichert werden nur die Tabellen von PowerDownload ($sql_table). NULL
 * bleibt NULL. Anmelde-Tokens und Codes aus „Passwort vergessen“ werden
 * geleert, vorübergehende IP-Sperren nicht gesichert. Hochgeladene Dateien
 * (pdl-files/) und Screenshots (pdl-gfx/screens/) sind nicht enthalten.
 */
$incdir = "../";
$inadmin = 1;
include_once($incdir . "pdl-inc/pdl_header.inc.php");
include_once("system_helpers.inc.php");

$can_backup = pdl_sys_can($user_rights, 'backup');
$csrf_failed = false;

if ($can_backup && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!pdl_sys_csrf_ok()) {
        $csrf_failed = true;
    } else {
        $now = time();
        $existing = [];
        $res = $db_handler->sql_query('SHOW TABLES');
        while ($row = $db_handler->sql_fetch_array($res)) {
            $existing[] = (string) $row[0];
        }
        $tables = array_values(array_intersect(pdl_sys_backup_tables($sql_table), $existing));

        set_time_limit(300);
        header('Content-Type: application/sql; charset=UTF-8');
        header('Content-Disposition: attachment; filename="powerdownload-sicherung-' . date('Y-m-d-Hi', $now) . '.sql"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo pdl_sys_backup_header($settings['pdlversion'] ?? '', $now);
        foreach ($tables as $table) {
            $create = $db_handler->sql_fetch_array($db_handler->sql_query('SHOW CREATE TABLE ' . pdl_sys_ident($table)));
            echo "\n-- --------------------------------------------------------\n"
                . '-- Tabelle ' . $table . "\n"
                . "-- --------------------------------------------------------\n\n"
                . 'DROP TABLE IF EXISTS ' . pdl_sys_ident($table) . ";\n"
                . (string) ($create[1] ?? '') . ";\n\n";
            if ($table === ($sql_table['iplock'] ?? '')) {
                echo "-- Vorübergehende IP-Sperren werden nicht gesichert.\n";
                continue;
            }
            $rows = $db_handler->sql_query('SELECT * FROM ' . pdl_sys_ident($table));
            while ($row = $db_handler->sql_fetch_array($rows)) {
                echo pdl_sys_backup_insert($db_handler, $table, pdl_sys_backup_clean_row($table, $row, $sql_table));
            }
        }
        echo "\nSET FOREIGN_KEY_CHECKS = 1;\n-- Ende der Sicherung\n";
        pdl_audit_log($db_handler, $sql_table, $user_details, 'backup', 'database', count($tables));
        exit;
    }
}

include("header.inc.php");

if (!$can_backup) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('backup'));
    include("footer.inc.php");
    return;
}

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'System'],
    ['title' => 'Sicherung erstellen'],
]);
echo '<h1 class="h3 pdl-page-title">Sicherung erstellen</h1>';
if ($csrf_failed) {
    echo pdl_admin_alert('danger', pdl_sys_csrf_error_text());
}

$counts = [];
$res = $db_handler->sql_query('SHOW TABLES');
$existing = [];
while ($row = $db_handler->sql_fetch_array($res)) {
    $existing[] = (string) $row[0];
}
foreach (array_intersect(pdl_sys_backup_tables($sql_table), $existing) as $table) {
    $counts[$table] = pdl_sys_scalar_int($db_handler, 'SELECT COUNT(*) FROM ' . pdl_sys_ident($table));
}
?>
<div class="row g-4">
    <div class="col-12 col-xl-7">
        <section class="card pdl-card mb-4" id="pdlBackupCard">
            <header class="card-header"><h2 class="h5 mb-0">Datenbank sichern</h2></header>
            <div class="card-body">
                <p>Die Sicherung ist eine SQL-Datei mit allen Releases, Dateien-Einträgen, Ordnern, Kommentaren, Benutzern, Einstellungen und Vorlagen. Ihr Browser lädt sie herunter.</p>
                <ul class="mb-3">
                    <li><strong>Nicht enthalten</strong> sind die Dateien unter <code>pdl-files/</code> und die Screenshots unter <code>pdl-gfx/screens/</code>. Sichern Sie diese Ordner zusätzlich per FTP.</li>
                    <li>Die Datei enthält E-Mail-Adressen, verschlüsselte Passwörter der Benutzer und das FTP-Passwort aus den Einstellungen. Bewahren Sie sie sicher auf.</li>
                    <li>Anmelde-Tokens, Codes aus „Passwort vergessen“ und vorübergehende IP-Sperren werden nicht gesichert. Nach dem Einspielen melden sich alle Benutzer neu an.</li>
                </ul>
                <form action="backup.php" method="post" id="pdlBackupForm">
                    <?php echo csrf_input(); ?>
                    <button type="submit" class="btn btn-primary" id="pdlBackupCreate">Sicherung herunterladen</button>
                </form>
            </div>
        </section>
        <p class="text-muted small">Wiederherstellen: <a href="dobackup.php">Sicherung einspielen</a>. Erstellen Sie vor jedem Update und vor „Zähler und Kommentare zurücksetzen“ eine Sicherung.</p>
    </div>
    <div class="col-12 col-xl-5">
        <section class="card pdl-card">
            <header class="card-header"><h2 class="h6 mb-0">Diese Tabellen werden gesichert</h2></header>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0" id="pdlBackupTables">
                    <thead><tr><th scope="col">Tabelle</th><th scope="col" class="text-end">Einträge</th></tr></thead>
                    <tbody>
                    <?php foreach ($counts as $table => $count) {
                        echo '<tr><td><code>' . htmlspecialchars($table) . '</code></td><td class="text-end">'
                            . ($table === ($sql_table['iplock'] ?? '') ? 'nur Struktur' : pdl_sys_num($count)) . '</td></tr>';
                    } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
<?php
include("footer.inc.php");
