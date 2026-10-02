<?php
/**
 * PowerDownload - Zähler und Kommentare zurücksetzen
 *
 * Setzt Aufrufe, Downloads und Bewertungen auf 0 und löscht alle Kommentare
 * und vorübergehenden IP-Sperren. Releases, Dateien, Ordner, Benutzer und
 * Einstellungen bleiben. Nur per POST mit CSRF-Token und Bestätigung; alle
 * Änderungen in einer Transaktion.
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
    ['title' => 'Zähler und Kommentare zurücksetzen'],
]);
echo '<h1 class="h3 pdl-page-title">Zähler und Kommentare zurücksetzen</h1>';

$counts = pdl_sys_reset_counts($db_handler, $sql_table);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!pdl_sys_csrf_ok()) {
        echo pdl_admin_alert('danger', pdl_sys_csrf_error_text());
    } elseif (pdl_sys_post('confirm') !== '1') {
        echo pdl_admin_alert('danger', 'Bitte bestätigen Sie, dass alle Kommentare gelöscht werden sollen. Es wurde nichts zurückgesetzt.');
    } else {
        $result = pdl_sys_reset_run($db_handler, $sql_table, $counts['has_screen_views']);
        if ($result['ok']) {
            pdl_audit_log($db_handler, $sql_table, $user_details, 'reset', 'counters', $counts['comments']);
            echo pdl_admin_alert('success', '<strong>Zähler und Kommentare wurden zurückgesetzt.</strong> '
                . pdl_sys_count($counts['comments'], 'Kommentar', 'Kommentare') . ' gelöscht; '
                . pdl_sys_count($counts['downloads'], 'Download', 'Downloads') . ', ' . pdl_sys_count($counts['views'], 'Aufruf', 'Aufrufe') . ' und '
                . pdl_sys_count($counts['votes'], 'Bewertung', 'Bewertungen') . ' auf 0 gesetzt.');
            echo '<a class="btn btn-outline-light" href="index.php">Zur Übersicht</a>';
            include("footer.inc.php");
            return;
        }
        echo pdl_admin_alert('danger', '<strong>Es wurde nichts zurückgesetzt.</strong> Die Datenbank hat den Vorgang abgelehnt (' . htmlspecialchars($result['error']) . ').');
    }
}

$list = '<li><strong>Alle Kommentare werden gelöscht</strong> (derzeit ' . pdl_sys_count($counts['comments'], 'Kommentar', 'Kommentare') . ').</li>'
    . '<li>Die Downloads aller Dateien werden auf 0 gesetzt (derzeit zusammen ' . pdl_sys_num($counts['downloads']) . ').</li>'
    . '<li>Die Aufrufe aller Releases werden auf 0 gesetzt (derzeit zusammen ' . pdl_sys_num($counts['views']) . ').</li>'
    . '<li>Alle Bewertungen werden gelöscht (derzeit ' . pdl_sys_num($counts['votes']) . ').</li>'
    . ($counts['has_screen_views'] ? '<li>Die Aufrufe der Screenshots werden auf 0 gesetzt.</li>' : '')
    . '<li>Alle vorübergehenden Sperren werden aufgehoben (derzeit ' . pdl_sys_num($counts['locks']) . '), z. B. gegen mehrfaches Bewerten oder Zählen und nach zu vielen Anmeldeversuchen.</li>';

echo makedialog(
    'Zähler und Kommentare wirklich zurücksetzen?',
    '<p class="mb-2">Gedacht für den Start nach einer Testphase. Dabei passiert Folgendes:</p>'
    . '<ul class="mb-3" id="pdlResetList">' . $list . '</ul>'
    . '<p class="mb-3"><strong>Unverändert bleiben</strong> Releases, Dateien, Ordner, Screenshots, Benutzer, Einstellungen und Vorlagen.</p>'
    . '<p class="mb-3">Das lässt sich nicht rückgängig machen. <a href="backup.php">Erstellen Sie vorher eine Sicherung</a>, wenn Sie die Kommentare noch brauchen.</p>'
    . '<div class="form-check"><input class="form-check-input" type="checkbox" id="pdlResetConfirm" name="confirm" value="1" required>'
    . '<label class="form-check-label" for="pdlResetConfirm">Ich habe verstanden, dass alle Kommentare und Zählerstände verloren gehen.</label></div>',
    'Ja, zurücksetzen',
    'reset.php',
    'index.php'
);
include("footer.inc.php");
