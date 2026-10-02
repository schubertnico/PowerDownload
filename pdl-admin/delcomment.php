<?php
include("header.inc.php");

$comment_id = isset($_GET['comment_id']) ? (int) $_GET['comment_id'] : (isset($_POST['comment_id']) ? (int) $_POST['comment_id'] : 0);
$submit = isset($_GET['submit']) ? (int) $_GET['submit'] : 0;
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

if (!pdl_admin_require_right('comment')) {
    include("footer.inc.php");
    return;
}

$comment = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT comment_id, release_id, titel, user_id, time FROM " . $sql_table['comments'] . " WHERE comment_id='" . $db_handler->sql_escape_int($comment_id) . "' LIMIT 1"
));
$release_id = is_array($comment) ? (int) $comment['release_id'] : 0;
$release = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT name, ordner_id FROM " . $sql_table['release'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "' LIMIT 1"
));
// Ohne „Releases und Dateien bearbeiten“ führt der Rückweg zur Kommentarliste.
$can_edit_release = pdl_admin_has_right('editfiles');
$back = $can_edit_release
    ? 'editrelease.php?release_id=' . $release_id . '#pdlEdComments'
    : 'comments.php?release_id=' . $release_id;

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    $can_edit_release
        ? ['title' => 'Ordner und Releases', 'href' => 'or_list.php?ordner_id=' . (int) ($release['ordner_id'] ?? 0) . '#pdl-aktuell']
        : ['title' => 'Kommentare', 'href' => 'comments.php'],
    ['title' => (string) ($release['name'] ?? 'Release'), 'href' => $back],
    ['title' => 'Kommentar löschen'],
]);
echo '<h1 class="h3 pdl-page-title">Kommentar löschen</h1>';

if (!is_array($comment)) {
    echo pdl_admin_result('warning', 'Diesen Kommentar gibt es nicht (mehr).', [
        ['label' => 'Zur Übersicht', 'href' => 'index.php', 'primary' => true],
    ]);
    include("footer.inc.php");
    return;
}
$titel = htmlspecialchars((string) $comment['titel'], ENT_QUOTES, 'UTF-8');

if ($submit === 1) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !csrf_verify($csrf_token_post)) {
        echo pdl_admin_alert('danger', 'Die Bestätigung war ungültig oder ist abgelaufen. Es wurde nichts gelöscht. Bitte bestätigen Sie das Löschen erneut.');
    } elseif ($db_handler->sql_query("DELETE FROM " . $sql_table['comments'] . " WHERE comment_id='" . $db_handler->sql_escape_int($comment_id) . "'") === true) {
        pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'comment', $comment_id);
        echo pdl_admin_result('success', '<strong>Der Kommentar „' . $titel . '“ wurde gelöscht.</strong>', [
            ['label' => 'Zurück zum Release', 'href' => $back, 'id' => 'pdlNextEditRelease', 'primary' => true],
        ]);
        include("footer.inc.php");
        return;
    } else {
        echo pdl_admin_db_error('Der Kommentar konnte nicht gelöscht werden.');
    }
}

$author = (int) ($comment['user_id'] ?? 0) === 0 ? 'Gast' : user((int) $comment['user_id']);
echo makedialog(
    "Kommentar wirklich löschen?",
    '<input type="hidden" name="comment_id" value="' . $comment_id . '">'
    . '<p class="mb-2">Sie löschen den Kommentar <strong>„' . $titel . '“</strong> von ' . $author
    . ' (' . htmlspecialchars(date((string) ($settings['date_format'] ?? 'd.m.Y'), (int) ($comment['time'] ?? 0))) . ').</p>'
    . '<p class="mb-0">Das lässt sich nicht rückgängig machen.</p>',
    "Ja, Kommentar löschen",
    "delcomment.php",
    $back
);
include("footer.inc.php");
