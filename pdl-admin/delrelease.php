<?php
include("header.inc.php");

$release_id = isset($_GET['release_id']) ? (int) $_GET['release_id'] : (isset($_POST['release_id']) ? (int) $_POST['release_id'] : 0);
$submit = isset($_GET['submit']) ? (int) $_GET['submit'] : 0;
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

if (!pdl_admin_require_right('delfiles')) {
    include("footer.inc.php");
    return;
}

$release = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT release_id, name, ordner_id FROM " . $sql_table['release'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "' LIMIT 1"
));
$ordner_id = is_array($release) ? (int) $release['ordner_id'] : 0;
$overview = 'or_list.php?ordner_id=' . $ordner_id . '#pdl-aktuell';

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Ordner und Releases', 'href' => $overview],
    ['title' => 'Release löschen'],
]);
echo '<h1 class="h3 pdl-page-title">Release löschen</h1>';

if (!is_array($release)) {
    echo pdl_admin_result('warning', 'Dieses Release gibt es nicht (mehr).', [
        ['label' => 'Zur Übersicht', 'href' => 'or_list.php', 'primary' => true],
    ]);
    include("footer.inc.php");
    return;
}
$release_name = htmlspecialchars((string) $release['name'], ENT_QUOTES, 'UTF-8');

if ($submit === 1) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !csrf_verify($csrf_token_post)) {
        echo pdl_admin_alert('danger', 'Die Bestätigung war ungültig oder ist abgelaufen. Es wurde nichts gelöscht. Bitte bestätigen Sie das Löschen erneut.');
    } elseif (delrelease($release_id)) {
        pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'release', $release_id);
        echo pdl_admin_result('success', '<strong>Das Release „' . $release_name . '“ wurde gelöscht.</strong>', [
            ['label' => 'Zur Übersicht', 'href' => $overview, 'id' => 'pdlNextOverview', 'primary' => true],
        ]);
        include("footer.inc.php");
        return;
    } else {
        echo pdl_admin_db_error('Das Release konnte nicht vollständig gelöscht werden.');
    }
}

$rid = $db_handler->sql_escape_int($release_id);
$count = static function (string $table, string $where = '') use ($db_handler, $sql_table, $rid): int {
    $row = $db_handler->sql_fetch_array($db_handler->sql_query(
        "SELECT COUNT(*) AS c FROM " . $sql_table[$table] . " WHERE release_id='" . $rid . "'" . $where
    ));
    return (int) ($row['c'] ?? 0);
};
$n_files = $count('files', " AND mirror='0'");
$n_mirrors = $count('files', " AND mirror<>'0'");
$n_screens = $count('screens');
$n_comments = $count('comments');

echo makedialog(
    "Release wirklich löschen?",
    '<input type="hidden" name="release_id" value="' . $release_id . '">'
    . '<p class="mb-2">Sie löschen das Release <strong>„' . $release_name . '“</strong> endgültig. Dabei werden auch entfernt:</p>'
    . '<ul class="mb-2">'
    . '<li>' . pdl_admin_count_label($n_files, 'Datei', 'Dateien')
    . ($n_mirrors > 0 ? ' und ' . pdl_admin_count_label($n_mirrors, 'Spiegel-Server', 'Spiegel-Server') : '')
    . ' (hochgeladene Dateien werden auch vom Server gelöscht)</li>'
    . '<li>' . pdl_admin_count_label($n_screens, 'Screenshot', 'Screenshots') . '</li>'
    . '<li>' . pdl_admin_count_label($n_comments, 'Kommentar', 'Kommentare') . '</li>'
    . '</ul>'
    . '<p class="mb-0">Das lässt sich nicht rückgängig machen.</p>',
    "Ja, Release löschen",
    "delrelease.php",
    $overview
);
include("footer.inc.php");
