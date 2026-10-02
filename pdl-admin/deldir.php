<?php
include("header.inc.php");

$ordner_id = isset($_GET['ordner_id']) ? (int) $_GET['ordner_id'] : (isset($_POST['ordner_id']) ? (int) $_POST['ordner_id'] : 0);
$submit = isset($_GET['submit']) ? (int) $_GET['submit'] : 0;
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

if (!pdl_admin_require_right('deldirs')) {
    include("footer.inc.php");
    return;
}

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Ordner und Releases', 'href' => 'or_list.php'],
    ['title' => 'Ordner löschen'],
]);
echo '<h1 class="h3 pdl-page-title">Ordner löschen</h1>';

$ordner = $ordner_id > 0 ? $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT ordner_id, sordner_id, name FROM " . $sql_table['ordner'] . " WHERE ordner_id='" . $db_handler->sql_escape_int($ordner_id) . "' LIMIT 1"
)) : null;

if (!is_array($ordner)) {
    echo pdl_admin_result('warning', 'Diesen Ordner gibt es nicht (mehr).', [
        ['label' => 'Zur Übersicht', 'href' => 'or_list.php', 'primary' => true],
    ]);
    include("footer.inc.php");
    return;
}
$ordner_name = htmlspecialchars((string) $ordner['name'], ENT_QUOTES, 'UTF-8');
$parent_overview = 'or_list.php?ordner_id=' . (int) $ordner['sordner_id'] . '#pdl-aktuell';

$subordner_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT ordner_id FROM " . $sql_table['ordner'] . " WHERE sordner_id='" . $db_handler->sql_escape_int($ordner_id) . "'"));
$release_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT release_id FROM " . $sql_table['release'] . " WHERE ordner_id='" . $db_handler->sql_escape_int($ordner_id) . "'"));

if ($subordner_check > 0 || $release_check > 0) {
    $actions = [['label' => 'Zur Übersicht', 'href' => 'or_list.php?ordner_id=' . $ordner_id . '#pdl-aktuell', 'id' => 'pdlNextOverview']];
    if (pdl_admin_has_right('editdirs')) {
        array_unshift($actions, ['label' => 'Inhalte verschieben', 'href' => 'editdir.php?ordner_id=' . $ordner_id . '#pdlEdOInhalte', 'id' => 'pdlNextEditDir', 'primary' => true]);
    }
    echo pdl_admin_result(
        'warning',
        '<strong>Der Ordner „' . $ordner_name . '“ ist nicht leer.</strong> Er enthält noch '
        . pdl_admin_count_label($release_check, 'Release', 'Releases') . ' und ' . pdl_admin_count_label($subordner_check, 'Unterordner', 'Unterordner')
        . '. Verschieben oder löschen Sie diese zuerst über „Ordner bearbeiten“.',
        $actions
    );
    include("footer.inc.php");
    return;
}

if ($submit === 1) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !csrf_verify($csrf_token_post)) {
        echo pdl_admin_alert('danger', 'Die Bestätigung war ungültig oder ist abgelaufen. Es wurde nichts gelöscht. Bitte bestätigen Sie das Löschen erneut.');
    } elseif ($db_handler->sql_query("DELETE FROM " . $sql_table['ordner'] . " WHERE ordner_id='" . $db_handler->sql_escape_int($ordner_id) . "'") === true) {
        pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'ordner', $ordner_id);
        echo pdl_admin_result('success', '<strong>Der Ordner „' . $ordner_name . '“ wurde gelöscht.</strong>', [
            ['label' => 'Zur Übersicht', 'href' => $parent_overview, 'id' => 'pdlNextOverview', 'primary' => true],
        ]);
        include("footer.inc.php");
        return;
    } else {
        echo pdl_admin_db_error('Der Ordner konnte nicht gelöscht werden.');
    }
}

echo makedialog(
    "Ordner wirklich löschen?",
    '<input type="hidden" name="ordner_id" value="' . $ordner_id . '">'
    . '<p class="mb-0">Möchten Sie den leeren Ordner <strong>„' . $ordner_name . '“</strong> endgültig löschen?</p>',
    "Ja, Ordner löschen",
    "deldir.php",
    $parent_overview
);
include("footer.inc.php");
