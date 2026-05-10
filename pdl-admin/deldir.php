<?php
include("header.inc.php");

$ordner_id = isset($_REQUEST['ordner_id']) ? (int) $_REQUEST['ordner_id'] : 0;
$submit = isset($_REQUEST['submit']) ? (int) $_REQUEST['submit'] : 0;
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

if (($user_rights['deldirs'] ?? '') !== 'Y') {
    echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.');
    include("footer.inc.php");
    return;
}

$subordner_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM " . $sql_table['ordner'] . " WHERE sordner_id=" . $db_handler->sql_escape_int($ordner_id)));
$release_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE ordner_id=" . $db_handler->sql_escape_int($ordner_id)));

if ($subordner_check > 0 || $release_check > 0) {
    echo pdl_admin_alert('warning', 'Ordner kann nicht gelöscht werden: Ordner enthält noch Releases oder Unterordner.');
    echo '<a class="btn btn-outline-light" href="or_list.php">Zurück zur Übersicht</a>';
    include("footer.inc.php");
    return;
}

if ($submit === 1) {
    if (!csrf_verify($csrf_token_post)) {
        echo pdl_admin_alert('danger', 'Sicherheits-Token ungültig oder abgelaufen.');
        include("footer.inc.php");
        return;
    }
    if (!pdl_ordner_exists($db_handler, $sql_table, $ordner_id) || $ordner_id === 0) {
        echo pdl_admin_alert('warning', 'Ordner existiert nicht.');
        include("footer.inc.php");
        return;
    }

    $db_handler->sql_query("DELETE FROM " . $sql_table['ordner'] . " WHERE ordner_id=" . $db_handler->sql_escape_int($ordner_id));
    pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'ordner', $ordner_id);

    echo pdl_admin_alert('success', '<strong>Ordner wurde gelöscht.</strong>');
    echo '<a class="btn btn-outline-light" href="or_list.php">Zurück zur Übersicht</a>';
    include("footer.inc.php");
    return;
}

pdl_admin_breadcrumb([
    ['title' => 'Admin-Center', 'href' => 'index.php'],
    ['title' => 'Ordner', 'href' => 'or_list.php'],
    ['title' => 'Ordner löschen'],
]);
echo '<h1 class="h3 pdl-page-title">Ordner löschen</h1>';
echo makedialog(
    "Ordner wirklich löschen?",
    csrf_input()
    . '<input type="hidden" name="ordner_id" value="' . $ordner_id . '">'
    . '<p class="mb-0">Möchten Sie den Ordner wirklich endgültig entfernen?</p>',
    "Ja, jetzt löschen",
    "deldir.php"
);
include("footer.inc.php");
