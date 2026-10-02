<?php
/**
 * PowerDownload - Einstellung hinzufügen (für Erweiterungen)
 */
include("header.inc.php");
include_once("system_helpers.inc.php");

if (!pdl_sys_can($user_rights, 'settings')) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('settings'));
    include("footer.inc.php");
    return;
}

$settings_t = pdl_sys_ident($sql_table['settings']);
$sgroup_t = pdl_sys_ident($sql_table['settingsgroup']);
$types = pdl_sys_setting_types();

$groups = [];
$res = $db_handler->sql_query('SELECT sgroup_id, name FROM ' . $sgroup_t . ' ORDER BY reihenfolge ASC, sgroup_id ASC');
while ($row = $db_handler->sql_fetch_array($res)) {
    $groups[(int) $row['sgroup_id']] = (string) $row['name'];
}

$form = [
    'name' => trim(pdl_sys_post('name')),
    'bez' => trim(pdl_sys_post('bez')),
    'variablenname' => trim(pdl_sys_post('variablenname')),
    'typ' => pdl_sys_post('typ', 'input'),
    'optionen' => trim(pdl_sys_post('optionen')),
    'wert' => pdl_sys_post('wert'),
    'sgroup_id' => (int) pdl_sys_post('sgroup_id', (string) (array_key_first($groups) ?? 0)),
];
$errors = [];

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Erweitert'],
    ['title' => 'Einstellung hinzufügen'],
]);
echo '<h1 class="h3 pdl-page-title">Einstellung hinzufügen</h1>';

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
    if (!pdl_sys_valid_key($form['variablenname'])) {
        $errors['variablenname'] = 'Erlaubt sind Kleinbuchstaben, Ziffern und Unterstrich; das erste Zeichen ist ein Buchstabe (2 bis 64 Zeichen).';
    } elseif (pdl_sys_scalar_int($db_handler, 'SELECT COUNT(*) FROM ' . $settings_t . " WHERE variablenname = '" . $form['variablenname'] . "'") > 0) {
        $errors['variablenname'] = 'Diesen Variablennamen verwendet bereits eine andere Einstellung.';
    }
    $eingabe = pdl_sys_build_eingabe($form['typ'], $form['optionen']);
    if ($eingabe === null) {
        $errors['typ'] = $form['typ'] === 'auswahl'
            ? 'Bitte geben Sie die Auswahlwerte an, z. B. „klein=Klein|mittel=Mittel“ (höchstens 55 Zeichen).'
            : 'Bitte wählen Sie eine Eingabeart.';
    } else {
        $parsed_new = pdl_sys_parse_eingabe($eingabe);
        $must_be_valid = in_array($parsed_new['type'], ['anaus', 'auswahl', 'zahl'], true) || $form['wert'] !== '';
        $value_error = $must_be_valid ? pdl_sys_validate_setting($parsed_new, $form['wert']) : null;
        if ($value_error !== null) {
            $errors['wert'] = 'Anfangswert: ' . $value_error;
        }
    }
    if (!isset($groups[$form['sgroup_id']])) {
        $errors['sgroup_id'] = 'Bitte wählen Sie eine Einstellungsgruppe.';
    }

    if ($errors === [] && $eingabe !== null) {
        $position = pdl_sys_next_position($db_handler, $sql_table['settings'], 'sgroup_id', $form['sgroup_id']);
        $ok = pdl_sys_exec($db_handler, 'INSERT INTO ' . $settings_t
            . ' (`variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES ('
            . "'" . $form['variablenname'] . "', '" . $db_handler->sql_escape_string($form['name']) . "', '"
            . $db_handler->sql_escape_string($form['bez']) . "', '" . $db_handler->sql_escape_string($form['wert']) . "', '"
            . $db_handler->sql_escape_string($eingabe) . "', " . $form['sgroup_id'] . ', ' . min(32767, $position) . ')');
        if ($ok) {
            pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'setting', (int) $db_handler->sql_insert_id());
            echo pdl_admin_alert('success', '<strong>Einstellung „' . htmlspecialchars($form['name']) . '“ wurde angelegt.</strong> '
                . 'Sie steht im Code als <code>$settings[\'' . htmlspecialchars($form['variablenname']) . '\']</code> zur Verfügung.');
            echo '<div class="d-flex flex-wrap gap-2">'
                . '<a class="btn btn-primary" href="settings.php?gruppe=' . $form['sgroup_id'] . '#sgroup_' . $form['sgroup_id'] . '">Zur Einstellungsseite</a>'
                . '<a class="btn btn-outline-light" href="addsettings.php">Weitere Einstellung hinzufügen</a></div>';
            include("footer.inc.php");
            return;
        }
        $errors['_db'] = 'Die Datenbank hat die neue Einstellung abgelehnt. Es wurde nichts gespeichert.';
    }
    echo pdl_admin_alert('danger', '<strong>Die Einstellung wurde nicht angelegt.</strong><br>' . implode('<br>', array_map('htmlspecialchars', $errors)));
}

$invalid = static fn (string $key): string => isset($errors[$key]) ? ' is-invalid' : '';
?>
<div class="alert alert-info">
    Eigene Einstellungen brauchen Sie nur für Erweiterungen. Eine neue Einstellung erscheint sofort auf der Einstellungsseite; wirken kann sie erst, wenn eine Erweiterung sie im Code abfragt.
</div>
<form action="addsettings.php" method="post" id="pdlSetForm" novalidate>
    <?php echo csrf_input(); ?>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Neue Einstellung</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlSetName" class="form-label">Name</label>
                <input type="text" id="pdlSetName" name="name" class="form-control<?php echo $invalid('name'); ?>" required maxlength="128" style="max-width: 32rem" value="<?php echo htmlspecialchars($form['name']); ?>">
                <div class="form-text">Überschrift des Feldes auf der Einstellungsseite.</div>
            </div>
            <div class="mb-3">
                <label for="pdlSetBez" class="form-label">Beschreibung</label>
                <textarea id="pdlSetBez" name="bez" class="form-control<?php echo $invalid('bez'); ?>" rows="2" maxlength="255"><?php echo htmlspecialchars($form['bez']); ?></textarea>
                <div class="form-text">Steht unter dem Feld und erklärt, was die Einstellung bewirkt.</div>
            </div>
            <div class="mb-3">
                <label for="pdlSetVar" class="form-label">Variablenname</label>
                <input type="text" id="pdlSetVar" name="variablenname" class="form-control font-monospace<?php echo $invalid('variablenname'); ?>" required maxlength="64" style="max-width: 20rem" value="<?php echo htmlspecialchars($form['variablenname']); ?>">
                <div class="form-text">Im Code verfügbar als <code>$settings['variablenname']</code>. Kleinbuchstaben, Ziffern und Unterstrich.</div>
            </div>
            <div class="mb-3">
                <label for="pdlSetEingabe" class="form-label">Eingabeart</label>
                <select id="pdlSetEingabe" name="typ" class="form-select<?php echo $invalid('typ'); ?>" style="max-width: 20rem">
                    <?php foreach ($types as $key => $label) {
                        echo '<option value="' . $key . '"' . ($key === $form['typ'] ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
                    } ?>
                </select>
            </div>
            <div class="mb-3" id="pdlSetOptionsBlock">
                <label for="pdlSetOptions" class="form-label">Auswahlwerte (nur bei Auswahlliste)</label>
                <input type="text" id="pdlSetOptions" name="optionen" class="form-control font-monospace" maxlength="55" value="<?php echo htmlspecialchars($form['optionen']); ?>">
                <div class="form-text">Wert und Beschriftung mit „=“, Einträge mit „|“ trennen, z. B. <code>klein=Klein|mittel=Mittel</code>. Bekannte Werte wie <code>name|time</code> erhalten ihre Beschriftung automatisch.</div>
            </div>
            <div class="mb-3">
                <label for="pdlSetWert" class="form-label">Anfangswert</label>
                <input type="text" id="pdlSetWert" name="wert" class="form-control<?php echo $invalid('wert'); ?>" style="max-width: 32rem" value="<?php echo htmlspecialchars($form['wert']); ?>">
                <div class="form-text">Bei Schaltern „Y“ (an) oder „N“ (aus), bei Auswahllisten einer der Werte.</div>
            </div>
            <div class="mb-3">
                <label for="pdlSetSGroup" class="form-label">Einstellungsgruppe</label>
                <select id="pdlSetSGroup" name="sgroup_id" class="form-select<?php echo $invalid('sgroup_id'); ?>" style="max-width: 20rem">
                    <?php foreach ($groups as $gid => $gname) {
                        echo '<option value="' . $gid . '"' . ($gid === $form['sgroup_id'] ? ' selected' : '') . '>' . htmlspecialchars($gname) . '</option>';
                    } ?>
                </select>
                <div class="form-text">Auf welchem Reiter der Einstellungsseite die Einstellung erscheint.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="editdelsettingssgroup.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlSetSave">Einstellung anlegen</button>
    </div>
</form>
<script>
(function () {
    var type = document.getElementById('pdlSetEingabe');
    var block = document.getElementById('pdlSetOptionsBlock');
    if (!type || !block) return;
    function sync() { block.classList.toggle('d-none', type.value !== 'auswahl'); }
    type.addEventListener('change', sync);
    sync();
})();
</script>
<?php
include("footer.inc.php");
