<?php
include("header.inc.php");

$release_id = isset($_REQUEST['release_id']) ? (int) $_REQUEST['release_id'] : 0;
$submit = isset($_REQUEST['submit']) ? (int) $_REQUEST['submit'] : 0;
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

if (($user_rights['delfiles'] ?? '') !== 'Y') {
    echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.');
    include("footer.inc.php");
    return;
}

if ($submit === 1) {
    if (!csrf_verify($csrf_token_post)) {
        echo pdl_admin_alert('danger', 'Sicherheits-Token ungültig oder abgelaufen.');
    } elseif (!pdl_release_exists($db_handler, $sql_table, $release_id)) {
        echo pdl_admin_alert('warning', 'Release existiert nicht (mehr).');
    } else {
        delrelease($release_id);
        pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'release', $release_id);
        echo pdl_admin_alert('success', '<strong>Release wurde gelöscht.</strong>');
        echo '<a class="btn btn-outline-light" href="or_list.php">Zurück zur Übersicht</a>';
    }
    include("footer.inc.php");
    return;
}

pdl_admin_breadcrumb([
    ['title' => 'Admin-Center', 'href' => 'index.php'],
    ['title' => 'Releases', 'href' => 'or_list.php'],
    ['title' => 'Release löschen'],
]);
echo '<h1 class="h3 pdl-page-title">Release löschen</h1>';
echo makedialog(
    "Release wirklich löschen?",
    csrf_input()
    . '<input type="hidden" name="release_id" value="' . $release_id . '">'
    . '<p class="mb-2">Beim Löschen eines Releases werden <strong>alle zugehörigen Kommentare, Files und Screens</strong> entfernt.</p>'
    . '<p class="mb-0">Wollen Sie den Release wirklich löschen?</p>',
    "Ja, jetzt löschen",
    "delrelease.php"
);
include("footer.inc.php");
