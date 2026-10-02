<?php
/**
 * PowerDownload - Einstellungsgruppe hinzufügen (ein neuer Reiter auf der
 * Einstellungsseite)
 */
include("header.inc.php");
include_once("system_helpers.inc.php");

if (!pdl_sys_can($user_rights, 'settings')) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('settings'));
    include("footer.inc.php");
    return;
}

$sgroup_t = pdl_sys_ident($sql_table['settingsgroup']);
$name = trim(pdl_sys_post('name'));
$errors = [];

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Erweitert'],
    ['title' => 'Einstellungsgruppe hinzufügen'],
]);
echo '<h1 class="h3 pdl-page-title">Einstellungsgruppe hinzufügen</h1>';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!pdl_sys_csrf_ok()) {
        $errors[] = pdl_sys_csrf_error_text();
    }
    if ($name === '' || strlen($name) > 128) {
        $errors[] = 'Bitte geben Sie einen Namen mit höchstens 128 Zeichen ein.';
    } elseif (pdl_sys_scalar_int($db_handler, 'SELECT COUNT(*) FROM ' . $sgroup_t . " WHERE name = '" . $db_handler->sql_escape_string($name) . "'") > 0) {
        $errors[] = 'Eine Einstellungsgruppe mit diesem Namen gibt es bereits.';
    }
    if ($errors === []) {
        $position = min(127, pdl_sys_next_position($db_handler, $sql_table['settingsgroup']));
        if (pdl_sys_exec($db_handler, 'INSERT INTO ' . $sgroup_t . " (`name`, `reihenfolge`) VALUES ('" . $db_handler->sql_escape_string($name) . "', " . $position . ')')) {
            $new_id = (int) $db_handler->sql_insert_id();
            pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'settingsgroup', $new_id);
            echo pdl_admin_alert('success', '<strong>Einstellungsgruppe „' . htmlspecialchars($name) . '“ wurde angelegt.</strong> '
                . 'Sie erscheint ab sofort als Reiter auf der Einstellungsseite.');
            echo '<div class="d-flex flex-wrap gap-2">'
                . '<a class="btn btn-primary" href="addsettings.php">Einstellung hinzufügen</a>'
                . '<a class="btn btn-outline-light" href="editdelsettingssgroup.php">Einstellungen und Gruppen bearbeiten</a></div>';
            include("footer.inc.php");
            return;
        }
        $errors[] = 'Die Datenbank hat die neue Gruppe abgelehnt. Es wurde nichts gespeichert.';
    }
    echo pdl_admin_alert('danger', '<strong>Die Gruppe wurde nicht angelegt.</strong><br>' . implode('<br>', array_map('htmlspecialchars', $errors)));
}
?>
<form action="addsgroup.php" method="post" id="pdlSGroupForm" novalidate>
    <?php echo csrf_input(); ?>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Neue Einstellungsgruppe</h2></header>
        <div class="card-body">
            <label for="pdlSGroupName" class="form-label">Name</label>
            <input type="text" id="pdlSGroupName" name="name" class="form-control" required maxlength="128" style="max-width: 32rem" value="<?php echo htmlspecialchars($name); ?>" aria-describedby="pdlSGroupNameHelp">
            <div id="pdlSGroupNameHelp" class="form-text">Beschriftung des Reiters auf der Einstellungsseite.</div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="editdelsettingssgroup.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlSGroupSave">Einstellungsgruppe anlegen</button>
    </div>
</form>
<?php
include("footer.inc.php");
