<?php
/**
 * PowerDownload - Vorlagengruppe hinzufügen (ein Abschnitt im Vorlagen-Editor)
 */
include("header.inc.php");
include_once("system_helpers.inc.php");

if (!pdl_sys_can($user_rights, 'templates')) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('templates'));
    include("footer.inc.php");
    return;
}

$tgroup_t = pdl_sys_ident($sql_table['templategroup']);
$name = trim(pdl_sys_post('name'));
$errors = [];

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Erweitert'],
    ['title' => 'Vorlagengruppe hinzufügen'],
]);
echo '<h1 class="h3 pdl-page-title">Vorlagengruppe hinzufügen</h1>';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!pdl_sys_csrf_ok()) {
        $errors[] = pdl_sys_csrf_error_text();
    }
    if ($name === '' || strlen($name) > 128) {
        $errors[] = 'Bitte geben Sie einen Namen mit höchstens 128 Zeichen ein.';
    } elseif (pdl_sys_scalar_int($db_handler, 'SELECT COUNT(*) FROM ' . $tgroup_t . " WHERE name = '" . $db_handler->sql_escape_string($name) . "'") > 0) {
        $errors[] = 'Eine Vorlagengruppe mit diesem Namen gibt es bereits.';
    }
    if ($errors === []) {
        $position = min(127, pdl_sys_next_position($db_handler, $sql_table['templategroup']));
        if (pdl_sys_exec($db_handler, 'INSERT INTO ' . $tgroup_t . " (`name`, `reihenfolge`) VALUES ('" . $db_handler->sql_escape_string($name) . "', " . $position . ')')) {
            $new_id = (int) $db_handler->sql_insert_id();
            pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'templategroup', $new_id);
            echo pdl_admin_alert('success', '<strong>Vorlagengruppe „' . htmlspecialchars($name) . '“ wurde angelegt.</strong> Sie erscheint ab sofort im Vorlagen-Editor.');
            echo '<div class="d-flex flex-wrap gap-2">'
                . '<a class="btn btn-primary" href="addtemplate.php">Vorlage hinzufügen</a>'
                . '<a class="btn btn-outline-light" href="editdeltemplatestgroup.php">Vorlagen und Gruppen bearbeiten</a></div>';
            include("footer.inc.php");
            return;
        }
        $errors[] = 'Die Datenbank hat die neue Gruppe abgelehnt. Es wurde nichts gespeichert.';
    }
    echo pdl_admin_alert('danger', '<strong>Die Gruppe wurde nicht angelegt.</strong><br>' . implode('<br>', array_map('htmlspecialchars', $errors)));
}
?>
<form action="addtgroup.php" method="post" id="pdlTGroupForm" novalidate>
    <?php echo csrf_input(); ?>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Neue Vorlagengruppe</h2></header>
        <div class="card-body">
            <label for="pdlTGroupName" class="form-label">Name</label>
            <input type="text" id="pdlTGroupName" name="name" class="form-control" required maxlength="128" style="max-width: 32rem" value="<?php echo htmlspecialchars($name); ?>" aria-describedby="pdlTGroupNameHelp">
            <div id="pdlTGroupNameHelp" class="form-text">Überschrift eines Abschnitts im Vorlagen-Editor.</div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="editdeltemplatestgroup.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlTGroupSave">Vorlagengruppe anlegen</button>
    </div>
</form>
<?php
include("footer.inc.php");
