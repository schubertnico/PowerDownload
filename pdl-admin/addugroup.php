<?php
/**
 * PowerDownload - Benutzergruppe anlegen
 *
 * Die Spaltennamen der Rechte stammen ausschließlich aus pdl3_rights
 * (geprüft gegen ^[a-z_]+$ und die vorhandenen Spalten), nie aus dem Formular.
 */
include("header.inc.php");
include_once("system_helpers.inc.php");

if (!pdl_sys_can($user_rights, 'edituser', 'deluser')) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('edituser', 'deluser'));
    include("footer.inc.php");
    return;
}

$rights = pdl_sys_rights_list($db_handler, $sql_table);
$group_t = pdl_sys_ident($sql_table['usergroup']);
$name = '';
$values = [];
foreach ($rights as $right) {
    // Vorgabe für neue Gruppen: im öffentlichen Bereich alles erlaubt, sonst nichts
    $values[$right['variablenname']] = in_array($right['variablenname'], ['download', 'addcomments', 'vote'], true) ? 'Y' : 'N';
}
$errors = [];

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Benutzer', 'href' => 'users.php'],
    ['title' => 'Benutzergruppen', 'href' => 'editdelugroup.php'],
    ['title' => 'Benutzergruppe anlegen'],
]);
echo '<h1 class="h3 pdl-page-title">Benutzergruppe anlegen</h1>';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name = trim(pdl_sys_post('name'));
    $values = pdl_sys_rights_from_post($rights, $_POST['rights'] ?? null);

    if (!pdl_sys_csrf_ok()) {
        $errors[] = pdl_sys_csrf_error_text();
    }
    if ($name === '') {
        $errors[] = 'Bitte geben Sie einen Namen für die Gruppe ein.';
    } elseif (strlen($name) > 128) {
        $errors[] = 'Der Name darf höchstens 128 Zeichen lang sein.';
    } elseif (pdl_sys_scalar_int($db_handler, 'SELECT COUNT(*) FROM ' . $group_t . " WHERE name = '" . $db_handler->sql_escape_string($name) . "'") > 0) {
        $errors[] = 'Eine Benutzergruppe mit diesem Namen gibt es bereits. Bitte wählen Sie einen anderen Namen.';
    }
    if ($rights === []) {
        $errors[] = 'Es sind keine Rechte definiert. Bitte prüfen Sie die Tabelle der Benutzerrechte.';
    }

    if ($errors === []) {
        $columns = ['`name`'];
        $sql_values = ["'" . $db_handler->sql_escape_string($name) . "'"];
        foreach ($values as $var => $value) {
            $columns[] = pdl_sys_ident($var);
            $sql_values[] = "'" . ($value === 'Y' ? 'Y' : 'N') . "'";
        }
        if (pdl_sys_exec($db_handler, 'INSERT INTO ' . $group_t . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $sql_values) . ')')) {
            $new_id = (int) $db_handler->sql_insert_id();
            pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'usergroup', $new_id);
            echo pdl_admin_alert('success', '<strong>Benutzergruppe „' . htmlspecialchars($name) . '“ wurde angelegt.</strong> '
                . 'Benutzer ordnen Sie der Gruppe in der Benutzerliste über „bearbeiten“ zu.');
            echo '<div class="d-flex flex-wrap gap-2">'
                . '<a class="btn btn-primary" href="users.php" id="pdlUGroupToUsers">Zur Benutzerliste</a>'
                . '<a class="btn btn-outline-light" href="editdelugroup.php?eugroup_id=' . $new_id . '" id="pdlUGroupEditNew">Gruppe bearbeiten</a>'
                . '<a class="btn btn-outline-light" href="addugroup.php" id="pdlUGroupAddMore">Weitere Gruppe anlegen</a>'
                . '</div>';
            include("footer.inc.php");
            return;
        }
        $errors[] = 'Die Datenbank hat die neue Gruppe abgelehnt. Bitte versuchen Sie es erneut.';
    }
    echo pdl_admin_alert('danger', '<strong>Die Gruppe wurde nicht angelegt.</strong><br>' . implode('<br>', array_map('htmlspecialchars', $errors)));
}
?>
<form action="addugroup.php" method="post" id="pdlUGroupForm" novalidate>
    <?php echo csrf_input(); ?>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Allgemein</h2></header>
        <div class="card-body">
            <label for="pdlUGroupName" class="form-label">Name</label>
            <input type="text" id="pdlUGroupName" name="name" class="form-control" required maxlength="128" style="max-width: 32rem" value="<?php echo htmlspecialchars($name); ?>" aria-describedby="pdlUGroupNameHelp">
            <div id="pdlUGroupNameHelp" class="form-text">Zum Beispiel „Redaktion“. Der Name erscheint in der Benutzerliste.</div>
        </div>
    </section>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Rechte</h2></header>
        <div class="card-body">
            <?php
            if ($rights === []) {
                echo pdl_admin_alert('warning', '<strong>Keine Rechte definiert.</strong> Die Tabelle <code>' . htmlspecialchars($sql_table['rights']) . '</code> ist leer oder passt nicht zu den Spalten der Benutzergruppen. Führen Sie bitte das Update aus (update.php).');
            }
            echo pdl_sys_rights_switches_html($rights, $values);
            ?>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="editdelugroup.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlUGroupSave">Benutzergruppe anlegen</button>
    </div>
</form>
<?php
echo pdl_sys_rights_switches_script();
include("footer.inc.php");
