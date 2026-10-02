<?php
include("header.inc.php");

$screen_id = isset($_GET['screen_id']) ? (int)$_GET['screen_id'] : (isset($_POST['screen_id']) ? (int)$_POST['screen_id'] : 0);
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

if (!pdl_admin_require_right('delfiles')) {
    include("footer.inc.php");
    return;
}

$screen = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT screen_id, release_id, text FROM " . $sql_table['screens'] . " WHERE screen_id='" . $db_handler->sql_escape_int($screen_id) . "' LIMIT 1"
));
$release_id = is_array($screen) ? (int) $screen['release_id'] : 0;
$back = 'editrelease.php?release_id=' . $release_id . '#pdlEdScreens';
$release = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT name, ordner_id FROM " . $sql_table['release'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "' LIMIT 1"
));

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Ordner und Releases', 'href' => 'or_list.php?ordner_id=' . (int) ($release['ordner_id'] ?? 0) . '#pdl-aktuell'],
    ['title' => (string) ($release['name'] ?? 'Release'), 'href' => $back],
    ['title' => 'Screenshot löschen'],
]);
echo '<h1 class="h3 pdl-page-title">Screenshot löschen</h1>';

if (!is_array($screen)) {
    echo pdl_admin_result('warning', 'Diesen Screenshot gibt es nicht (mehr).', [
        ['label' => 'Zur Übersicht', 'href' => 'or_list.php', 'primary' => true],
    ]);
    include("footer.inc.php");
    return;
}

if ($submit === 1) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !csrf_verify($csrf_token_post)) {
        echo pdl_admin_alert('danger', 'Die Bestätigung war ungültig oder ist abgelaufen. Es wurde nichts gelöscht. Bitte bestätigen Sie das Löschen erneut.');
    } elseif ($db_handler->sql_query("DELETE FROM " . $sql_table['screens'] . " WHERE screen_id='" . $db_handler->sql_escape_int($screen_id) . "'") === true) {
        pdl_admin_delete_screen_files($release_id, $screen_id);
        pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'screen', $screen_id);
        echo pdl_admin_result('success', '<strong>Der Screenshot wurde gelöscht.</strong> Auch die Bilddateien wurden vom Server entfernt.', [
            ['label' => 'Zurück zum Release', 'href' => $back, 'id' => 'pdlNextEditRelease', 'primary' => true],
        ]);
        include("footer.inc.php");
        return;
    } else {
        echo pdl_admin_db_error('Der Screenshot konnte nicht gelöscht werden.');
    }
}

$paths = pdl_admin_screen_paths($release_id, $screen_id);
$preview = is_file($paths['k'])
    ? '<p class="mb-2"><img src="' . htmlspecialchars($paths['k']) . '" alt="Vorschau des Screenshots" class="img-thumbnail" style="max-height: 120px;"></p>'
    : '';
$caption = trim((string) ($screen['text'] ?? ''));
echo makedialog(
    "Screenshot wirklich löschen?",
    '<input type="hidden" name="screen_id" value="' . $screen_id . '">'
    . $preview
    . ($caption !== '' ? '<p class="mb-2">Untertitel: „' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '“</p>' : '')
    . '<p class="mb-0">Der Screenshot und sein Vorschaubild werden endgültig gelöscht.</p>',
    "Ja, Screenshot löschen",
    "delscreen.php",
    $back
);
include("footer.inc.php");
