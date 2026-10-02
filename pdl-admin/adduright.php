<?php
/**
 * PowerDownload - Benutzerrecht anlegen (für Erweiterungen)
 *
 * Legt einen Eintrag in pdl3_rights an und erweitert pdl3_usergroup um eine
 * Spalte. Erfordert „Einstellungen verwalten“ und eine Bestätigung, weil die
 * Tabellenstruktur geändert wird. Der Variablenname muss ^[a-z_]+$ entsprechen.
 */
include("header.inc.php");
include_once("system_helpers.inc.php");

if (!pdl_sys_can($user_rights, 'settings')) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('settings'));
    include("footer.inc.php");
    return;
}

$rights_t = pdl_sys_ident($sql_table['rights']);
$group_t = pdl_sys_ident($sql_table['usergroup']);
$form = [
    'name' => trim(pdl_sys_post('name')),
    'bez' => trim(pdl_sys_post('bez')),
    'variablenname' => trim(pdl_sys_post('variablenname')),
    'standard' => pdl_sys_post('standard') === 'Y' ? 'Y' : 'N',
];
$step = pdl_sys_post('step');
$errors = [];

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Erweitert'],
    ['title' => 'Benutzerrechte', 'href' => 'editdeluright.php'],
    ['title' => 'Benutzerrecht hinzufügen'],
]);
echo '<h1 class="h3 pdl-page-title">Benutzerrecht hinzufügen</h1>';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!pdl_sys_csrf_ok()) {
        $errors['_csrf'] = pdl_sys_csrf_error_text();
    }
    if ($form['name'] === '' || strlen($form['name']) > 128) {
        $errors['name'] = 'Bitte geben Sie einen Namen mit höchstens 128 Zeichen ein.';
    }
    if (strlen($form['bez']) > 255) {
        $errors['bez'] = 'Die Beschreibung darf höchstens 255 Zeichen lang sein.';
    }
    if (!pdl_sys_valid_right_name($form['variablenname'])) {
        $errors['variablenname'] = 'Erlaubt sind nur Kleinbuchstaben a–z und der Unterstrich, 2 bis 32 Zeichen, z. B. „newsletter“ oder „upload_gross“.';
    } else {
        $exists_right = pdl_sys_scalar_int($db_handler, 'SELECT COUNT(*) FROM ' . $rights_t . " WHERE variablenname = '" . $form['variablenname'] . "'") > 0;
        $exists_column = in_array($form['variablenname'], pdl_sys_table_columns($db_handler, $sql_table['usergroup']), true);
        if ($exists_right || $exists_column) {
            $errors['variablenname'] = 'Diesen Variablennamen gibt es bereits.';
        }
    }

    if ($errors === [] && $step === 'confirm') {
        // 1. Eintrag in pdl3_rights, 2. Spalte anlegen, 3. Administratorgruppe erhält das Recht
        $position = pdl_sys_next_position($db_handler, $sql_table['rights']);
        $inserted = pdl_sys_exec($db_handler, 'INSERT INTO ' . $rights_t . ' (`name`, `bez`, `variablenname`, `reihenfolge`) VALUES ('
            . "'" . $db_handler->sql_escape_string($form['name']) . "', '" . $db_handler->sql_escape_string($form['bez']) . "', '"
            . $form['variablenname'] . "', " . min(127, $position) . ')');
        $right_id = $inserted ? (int) $db_handler->sql_insert_id() : 0;
        $altered = $inserted && pdl_sys_exec($db_handler, 'ALTER TABLE ' . $group_t . ' ADD ' . pdl_sys_ident($form['variablenname'])
            . " ENUM('Y','N') NOT NULL DEFAULT '" . $form['standard'] . "'");
        if ($altered) {
            pdl_sys_exec($db_handler, 'UPDATE ' . $group_t . ' SET ' . pdl_sys_ident($form['variablenname']) . " = 'Y' WHERE ugroup_id = 2");
            $guest = pdl_sys_guest_group_id($settings);
            if ($guest > 0) {
                pdl_sys_exec($db_handler, 'UPDATE ' . $group_t . ' SET ' . pdl_sys_ident($form['variablenname']) . " = 'N' WHERE ugroup_id = " . $guest);
            }
            pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'right', $right_id);
            echo pdl_admin_alert('success', '<strong>Benutzerrecht „' . htmlspecialchars($form['name']) . '“ wurde angelegt.</strong> '
                . 'Sie finden es ab sofort bei den Benutzergruppen. Im Code steht es als <code>$user_rights[\'' . htmlspecialchars($form['variablenname']) . '\']</code> zur Verfügung.');
            echo '<div class="d-flex flex-wrap gap-2"><a class="btn btn-primary" href="editdelugroup.php">Benutzergruppen</a>'
                . '<a class="btn btn-outline-light" href="editdeluright.php">Benutzerrechte bearbeiten</a></div>';
            include("footer.inc.php");
            return;
        }
        if ($inserted) {
            // Spalte ließ sich nicht anlegen: Eintrag wieder entfernen
            pdl_sys_exec($db_handler, 'DELETE FROM ' . $rights_t . ' WHERE right_id = ' . $right_id);
        }
        $errors['_db'] = 'Die Datenbank hat das neue Recht abgelehnt (' . pdl_sys_db_error($db_handler) . '). Es wurde nichts geändert.';
    }

    if ($errors === []) {
        // Bestätigungsseite: die Tabellenstruktur wird geändert
        $hidden = '<input type="hidden" name="step" value="confirm">';
        foreach ($form as $key => $value) {
            $hidden .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '">';
        }
        echo makedialog(
            'Benutzerrecht „' . $form['name'] . '“ anlegen?',
            $hidden
            . '<p class="mb-2">PowerDownload erweitert dafür die Tabelle <code>' . htmlspecialchars($sql_table['usergroup']) . '</code> um die Spalte <code>'
            . htmlspecialchars($form['variablenname']) . '</code>. Bestehende Gruppen erhalten den Wert <strong>' . ($form['standard'] === 'Y' ? 'Ja' : 'Nein') . '</strong>, die Administratorgruppe immer „Ja“.</p>'
            . '<p class="mb-0">Erstellen Sie vorher eine Sicherung. Ein neues Recht wirkt erst, wenn eine Erweiterung es im Code abfragt.</p>',
            'Ja, Recht anlegen',
            'adduright.php',
            'editdeluright.php'
        );
        include("footer.inc.php");
        return;
    }

    $messages = [];
    foreach ($errors as $error) {
        $messages[] = htmlspecialchars($error);
    }
    echo pdl_admin_alert('danger', '<strong>Das Recht wurde nicht angelegt.</strong><br>' . implode('<br>', $messages));
}
?>
<div class="alert alert-info">
    Benutzerrechte brauchen Sie nur für Erweiterungen, die selbst prüfen, ob ein Benutzer etwas darf.
    Die mitgelieferten Rechte vergeben Sie unter <a class="alert-link" href="editdelugroup.php">Benutzergruppen</a>.
</div>
<form action="adduright.php" method="post" id="pdlURForm" novalidate>
    <?php echo csrf_input(); ?>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Neues Benutzerrecht</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlURName" class="form-label">Name</label>
                <input type="text" id="pdlURName" name="name" class="form-control<?php echo isset($errors['name']) ? ' is-invalid' : ''; ?>" required maxlength="128" style="max-width: 32rem" value="<?php echo htmlspecialchars($form['name']); ?>">
                <div class="form-text">So erscheint das Recht in den Benutzergruppen, z. B. „Große Dateien hochladen“.</div>
            </div>
            <div class="mb-3">
                <label for="pdlURBez" class="form-label">Beschreibung</label>
                <input type="text" id="pdlURBez" name="bez" class="form-control<?php echo isset($errors['bez']) ? ' is-invalid' : ''; ?>" maxlength="255" value="<?php echo htmlspecialchars($form['bez']); ?>">
                <div class="form-text">Ein Satz, der erklärt, was das Recht erlaubt.</div>
            </div>
            <div class="mb-3">
                <label for="pdlURVar" class="form-label">Variablenname</label>
                <input type="text" id="pdlURVar" name="variablenname" class="form-control font-monospace<?php echo isset($errors['variablenname']) ? ' is-invalid' : ''; ?>" required maxlength="32" pattern="[a-z_]+" style="max-width: 20rem" value="<?php echo htmlspecialchars($form['variablenname']); ?>">
                <div class="form-text">Nur Kleinbuchstaben a–z und Unterstrich. Im Code als <code>$user_rights['variablenname']</code> verfügbar. Lässt sich später nicht ändern.</div>
            </div>
            <div class="mb-3">
                <label for="pdlURStandard" class="form-label">Vorgabe für bestehende Gruppen</label>
                <select id="pdlURStandard" name="standard" class="form-select" style="max-width: 12rem">
                    <option value="N"<?php echo $form['standard'] === 'N' ? ' selected' : ''; ?>>Nein</option>
                    <option value="Y"<?php echo $form['standard'] === 'Y' ? ' selected' : ''; ?>>Ja</option>
                </select>
                <div class="form-text">Gilt für alle bestehenden Gruppen; bei neuen Gruppen legen Sie es beim Anlegen fest. Die Administratorgruppe erhält das Recht immer.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="editdeluright.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlURSave">Weiter</button>
    </div>
</form>
<?php
include("footer.inc.php");
