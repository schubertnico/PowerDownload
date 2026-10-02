<?php
include("header.inc.php");

$file_id = isset($_GET['file_id']) ? (int)$_GET['file_id'] : (isset($_POST['file_id']) ? (int)$_POST['file_id'] : 0);
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

if (!pdl_admin_require_right('delfiles')) {
    include("footer.inc.php");
    return;
}

$file = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT file_id, release_id, name, url FROM " . $sql_table['files'] . " WHERE file_id='" . $db_handler->sql_escape_int($file_id) . "' LIMIT 1"
));
$release_id = is_array($file) ? (int) $file['release_id'] : 0;
$back = 'editrelease.php?release_id=' . $release_id . '#pdlEdFiles';
$release = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT name, ordner_id FROM " . $sql_table['release'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "' LIMIT 1"
));

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Ordner und Releases', 'href' => 'or_list.php?ordner_id=' . (int) ($release['ordner_id'] ?? 0) . '#pdl-aktuell'],
    ['title' => (string) ($release['name'] ?? 'Release'), 'href' => $back],
    ['title' => 'Datei löschen'],
]);
echo '<h1 class="h3 pdl-page-title">Datei löschen</h1>';

if (!is_array($file)) {
    echo pdl_admin_result('warning', 'Diese Datei gibt es nicht (mehr).', [
        ['label' => 'Zur Übersicht', 'href' => 'or_list.php', 'primary' => true],
    ]);
    include("footer.inc.php");
    return;
}
$file_name = htmlspecialchars((string) $file['name'], ENT_QUOTES, 'UTF-8');

// Spiegel-Server dieser Datei werden mit gelöscht.
$mirrors = [];
$mirror_res = $db_handler->sql_query("SELECT file_id, name, url FROM " . $sql_table['files'] . " WHERE mirror='" . $db_handler->sql_escape_int($file_id) . "'");
while ($mirror_row = $db_handler->sql_fetch_array($mirror_res)) {
    $mirrors[] = $mirror_row;
}

if ($submit === 1) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !csrf_verify($csrf_token_post)) {
        echo pdl_admin_alert('danger', 'Die Bestätigung war ungültig oder ist abgelaufen. Es wurde nichts gelöscht. Bitte bestätigen Sie das Löschen erneut.');
    } else {
        $fid = $db_handler->sql_escape_int($file_id);
        $ok = $db_handler->sql_query("DELETE FROM " . $sql_table['files'] . " WHERE file_id='" . $fid . "' OR mirror='" . $fid . "'");
        if ($ok === true) {
            $removed = 0;
            foreach (array_merge([$file], $mirrors) as $row) {
                if (pdl_admin_delete_local_file((string) ($row['url'] ?? ''))) {
                    $removed++;
                }
            }
            pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'file', $file_id);
            echo pdl_admin_result(
                'success',
                '<strong>Die Datei „' . $file_name . '“ wurde gelöscht.</strong>'
                . ($mirrors !== [] ? ' Ebenso ' . pdl_admin_count_label(count($mirrors), 'Spiegel-Server', 'Spiegel-Server') . '.' : '')
                . ($removed > 0 ? ' Die hochgeladene Datei wurde vom Server entfernt.' : ''),
                [['label' => 'Zurück zum Release', 'href' => $back, 'id' => 'pdlNextEditRelease', 'primary' => true]]
            );
            include("footer.inc.php");
            return;
        }
        echo pdl_admin_db_error('Die Datei konnte nicht gelöscht werden.');
    }
}

$mirror_info = '';
if ($mirrors !== []) {
    $names = array_map(static fn (array $m): string => '„' . htmlspecialchars((string) $m['name'], ENT_QUOTES, 'UTF-8') . '“', $mirrors);
    $mirror_info = '<p class="mb-2">Mit ihr werden auch ihre Spiegel-Server gelöscht: ' . implode(', ', $names) . '.</p>';
}
$local_info = pdl_admin_local_file((string) $file['url']) !== null
    ? '<p class="mb-2">Die hochgeladene Datei wird dabei auch vom Server gelöscht.</p>'
    : '';

echo makedialog(
    "Datei wirklich löschen?",
    '<input type="hidden" name="file_id" value="' . $file_id . '">'
    . '<p class="mb-2">Sie löschen die Datei <strong>„' . $file_name . '“</strong> samt Download-Zähler.</p>'
    . $mirror_info
    . $local_info
    . '<p class="mb-0">Das lässt sich nicht rückgängig machen.</p>',
    "Ja, Datei löschen",
    "delfile.php",
    $back
);
include("footer.inc.php");
