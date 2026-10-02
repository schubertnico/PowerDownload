<?php
/**
 * PowerDownload - Vorlage hinzufügen (für Erweiterungen)
 */
include("header.inc.php");
include_once("system_helpers.inc.php");

if (!pdl_sys_can($user_rights, 'templates')) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('templates'));
    include("footer.inc.php");
    return;
}

$template_t = pdl_sys_ident($sql_table['template']);
$tgroup_t = pdl_sys_ident($sql_table['templategroup']);
$types = ['textarea' => 'Mehrzeiliges Feld (HTML)', 'input' => 'Einzeiliges Feld', 'farbe' => 'Farbe'];

$groups = [];
$res = $db_handler->sql_query('SELECT tgroup_id, name FROM ' . $tgroup_t . ' ORDER BY reihenfolge ASC, tgroup_id ASC');
while ($row = $db_handler->sql_fetch_array($res)) {
    $groups[(int) $row['tgroup_id']] = (string) $row['name'];
}

$form = [
    'name' => trim(pdl_sys_post('name')),
    'bez' => trim(pdl_sys_post('bez')),
    'variablenname' => trim(pdl_sys_post('variablenname')),
    'eingabe' => pdl_sys_post('eingabe', 'textarea'),
    'wert' => str_replace("\r\n", "\n", pdl_sys_post('wert')),
    'tgroup_id' => (int) pdl_sys_post('tgroup_id', (string) (array_key_first($groups) ?? 0)),
];
$errors = [];

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Erweitert'],
    ['title' => 'Vorlage hinzufügen'],
]);
echo '<h1 class="h3 pdl-page-title">Vorlage hinzufügen</h1>';

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
    } elseif (pdl_sys_scalar_int($db_handler, 'SELECT COUNT(*) FROM ' . $template_t . " WHERE variablenname = '" . $form['variablenname'] . "'") > 0) {
        $errors['variablenname'] = 'Diesen Variablennamen verwendet bereits eine andere Vorlage.';
    }
    if (!isset($types[$form['eingabe']])) {
        $errors['eingabe'] = 'Bitte wählen Sie eine Eingabeart.';
    }
    if (!isset($groups[$form['tgroup_id']])) {
        $errors['tgroup_id'] = 'Bitte wählen Sie eine Vorlagengruppe.';
    }

    if ($errors === []) {
        $position = min(127, pdl_sys_next_position($db_handler, $sql_table['template'], 'tgroup_id', $form['tgroup_id']));
        $ok = pdl_sys_exec($db_handler, 'INSERT INTO ' . $template_t
            . ' (`variablenname`, `name`, `bez`, `eingabe`, `wert`, `tgroup_id`, `reihenfolge`) VALUES ('
            . "'" . $form['variablenname'] . "', '" . $db_handler->sql_escape_string($form['name']) . "', '"
            . $db_handler->sql_escape_string($form['bez']) . "', '" . $form['eingabe'] . "', '"
            . $db_handler->sql_escape_string($form['wert']) . "', " . $form['tgroup_id'] . ', ' . $position . ')');
        if ($ok) {
            pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'template', (int) $db_handler->sql_insert_id());
            echo pdl_admin_alert('success', '<strong>Vorlage „' . htmlspecialchars($form['name']) . '“ wurde angelegt.</strong> '
                . 'Im Code steht sie als <code>$template[\'' . htmlspecialchars($form['variablenname']) . '\']</code> zur Verfügung.');
            echo '<div class="d-flex flex-wrap gap-2">'
                . '<a class="btn btn-primary" href="templates.php#tg_' . $form['tgroup_id'] . '">Zum Vorlagen-Editor</a>'
                . '<a class="btn btn-outline-light" href="addtemplate.php">Weitere Vorlage hinzufügen</a></div>';
            include("footer.inc.php");
            return;
        }
        $errors['_db'] = 'Die Datenbank hat die neue Vorlage abgelehnt. Es wurde nichts gespeichert.';
    }
    echo pdl_admin_alert('danger', '<strong>Die Vorlage wurde nicht angelegt.</strong><br>' . implode('<br>', array_map('htmlspecialchars', $errors)));
}

$invalid = static fn (string $key): string => isset($errors[$key]) ? ' is-invalid' : '';
?>
<div class="alert alert-info">
    Eine neue Vorlage wirkt erst, wenn eine Erweiterung oder eine andere Vorlage sie verwendet. Die mitgelieferten Vorlagen ändern Sie unter <a class="alert-link" href="templates.php">Vorlagen bearbeiten</a>.
</div>
<form action="addtemplate.php" method="post" id="pdlTemplForm" novalidate>
    <?php echo csrf_input(); ?>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Neue Vorlage</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlTemplName" class="form-label">Name</label>
                <input type="text" id="pdlTemplName" name="name" class="form-control<?php echo $invalid('name'); ?>" required maxlength="128" style="max-width: 32rem" value="<?php echo htmlspecialchars($form['name']); ?>">
                <div class="form-text">Überschrift im Vorlagen-Editor.</div>
            </div>
            <div class="mb-3">
                <label for="pdlTemplBez" class="form-label">Beschreibung</label>
                <textarea id="pdlTemplBez" name="bez" class="form-control<?php echo $invalid('bez'); ?>" rows="2" maxlength="255"><?php echo htmlspecialchars($form['bez']); ?></textarea>
                <div class="form-text">Wo und wofür die Vorlage verwendet wird.</div>
            </div>
            <div class="mb-3">
                <label for="pdlTemplVar" class="form-label">Variablenname</label>
                <input type="text" id="pdlTemplVar" name="variablenname" class="form-control font-monospace<?php echo $invalid('variablenname'); ?>" required maxlength="64" style="max-width: 20rem" value="<?php echo htmlspecialchars($form['variablenname']); ?>">
                <div class="form-text">Im Code verfügbar als <code>$template['variablenname']</code>.</div>
            </div>
            <div class="mb-3">
                <label for="pdlTemplEingabe" class="form-label">Eingabeart</label>
                <select id="pdlTemplEingabe" name="eingabe" class="form-select<?php echo $invalid('eingabe'); ?>" style="max-width: 20rem">
                    <?php foreach ($types as $key => $label) {
                        echo '<option value="' . $key . '"' . ($key === $form['eingabe'] ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
                    } ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="pdlTemplWert" class="form-label">Inhalt</label>
                <textarea id="pdlTemplWert" name="wert" class="form-control font-monospace" rows="10"><?php echo htmlspecialchars($form['wert']); ?></textarea>
                <div class="form-text">HTML mit Platzhaltern wie <code>{name}</code>; siehe <a href="showtempvars.php">Vorlagen-Platzhalter</a>.</div>
            </div>
            <div class="mb-3">
                <label for="pdlTemplGroup" class="form-label">Vorlagengruppe</label>
                <select id="pdlTemplGroup" name="tgroup_id" class="form-select<?php echo $invalid('tgroup_id'); ?>" style="max-width: 20rem">
                    <?php foreach ($groups as $gid => $gname) {
                        echo '<option value="' . $gid . '"' . ($gid === $form['tgroup_id'] ? ' selected' : '') . '>' . htmlspecialchars($gname) . '</option>';
                    } ?>
                </select>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="editdeltemplatestgroup.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlTemplSave">Vorlage anlegen</button>
    </div>
</form>
<?php
include("footer.inc.php");
