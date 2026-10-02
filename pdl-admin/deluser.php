<?php
/**
 * PowerDownload - Benutzer löschen (Bestätigungsseite, Löschen nur per POST
 * mit CSRF-Token). Aufruf aus der Benutzerliste (users.php).
 */
include("header.inc.php");
include_once("system_helpers.inc.php");

if (!pdl_sys_can($user_rights, 'deluser')) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('deluser'));
    include("footer.inc.php");
    return;
}

$del_id = (int) ($_POST['user_id'] ?? ($_GET['user_id'] ?? 0));
$current_user_id = (int) ($user_details['user_id'] ?? 0);
$user_t = pdl_sys_ident($sql_table['user']);

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Benutzer', 'href' => 'users.php'],
    ['title' => 'Benutzer löschen'],
]);
echo '<h1 class="h3 pdl-page-title">Benutzer löschen</h1>';

$back = '<a class="btn btn-outline-light" href="users.php" id="pdlDelUserBack">Zurück zur Benutzerliste</a>';
$deluser = $del_id > 0
    ? $db_handler->sql_fetch_array($db_handler->sql_query(
        'SELECT u.user_id, u.nick, u.email, g.adminaccess FROM ' . $user_t . ' AS u'
        . ' LEFT JOIN ' . pdl_sys_ident($sql_table['usergroup']) . ' AS g ON g.ugroup_id = u.ugroup_id'
        . ' WHERE u.user_id = ' . $del_id
    ))
    : null;

if ($deluser === null) {
    echo pdl_admin_alert('info', 'Bitte wählen Sie den Benutzer in der Benutzerliste aus und klicken Sie dort auf „löschen“.');
    echo $back;
    include("footer.inc.php");
    return;
}
$nick = (string) $deluser['nick'];
if ($del_id === 1) {
    echo pdl_admin_alert('warning', 'Der Hauptadministrator (Benutzer Nr. 1) ist geschützt und kann nicht gelöscht werden.');
    echo $back;
    include("footer.inc.php");
    return;
}
if ($del_id === $current_user_id) {
    echo pdl_admin_alert('warning', 'Ihr eigenes Konto können Sie hier nicht löschen. Melden Sie sich dafür mit einem anderen Administratorkonto an oder löschen Sie das Konto im Profil.');
    echo $back;
    include("footer.inc.php");
    return;
}
// Konten mit Admin-Zugang darf nur löschen, wer auch die Einstellungen verwaltet.
if (!pdl_sys_may_delete_user((string) ($deluser['adminaccess'] ?? 'N'), $user_rights)) {
    echo pdl_admin_alert('warning', '<strong>' . htmlspecialchars($nick) . ' gehört zu einer Gruppe mit Admin-Zugang.</strong> '
        . 'Solche Konten darf nur löschen, wer zusätzlich das Recht „Einstellungen verwalten“ hat. Bitte wenden Sie sich an einen Administrator.');
    echo $back;
    include("footer.inc.php");
    return;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!pdl_sys_csrf_ok()) {
        echo pdl_admin_alert('danger', pdl_sys_csrf_error_text());
    } elseif (pdl_sys_exec($db_handler, 'DELETE FROM ' . $user_t . ' WHERE user_id = ' . $del_id) && pdl_sys_affected_rows($db_handler) !== 0) {
        pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'user', $del_id);
        echo pdl_admin_alert('success', '<strong>Benutzer „' . htmlspecialchars($nick) . '“ wurde gelöscht.</strong>');
        echo $back;
        include("footer.inc.php");
        return;
    } else {
        echo pdl_admin_alert('danger', '<strong>Der Benutzer wurde nicht gelöscht.</strong> Die Datenbank hat den Vorgang abgelehnt. Bitte versuchen Sie es erneut.');
    }
}

echo makedialog(
    'Benutzer „' . $nick . '“ wirklich löschen?',
    '<input type="hidden" name="user_id" value="' . $del_id . '">'
    . '<p class="mb-2">Das Benutzerkonto <strong>' . htmlspecialchars($nick) . '</strong> (' . htmlspecialchars((string) $deluser['email']) . ') wird dauerhaft gelöscht. Der Benutzer kann sich danach nicht mehr anmelden.</p>'
    . '<p class="mb-0">Kommentare und Releases dieses Benutzers bleiben erhalten; als Autor erscheint dann „Gelöscht“.</p>',
    'Ja, Benutzer löschen',
    'deluser.php',
    'users.php'
);

include("footer.inc.php");
