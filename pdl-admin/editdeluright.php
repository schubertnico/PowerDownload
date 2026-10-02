<?php
/**
 * PowerDownload - Benutzerrechte verwalten (Name, Beschreibung, Reihenfolge;
 * selbst angelegte Rechte löschen)
 *
 * Erfordert „Einstellungen verwalten“. Beim Löschen kommt der Spaltenname
 * aus der Datenbank (per right_id), nie aus dem Formular; die 18
 * mitgelieferten Rechte sind geschützt.
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
$is_post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$action = pdl_sys_post('action', pdl_sys_get('action'));
$right_id = (int) ($_POST['right_id'] ?? ($_GET['right_id'] ?? 0));
$protected = pdl_sys_shipped_keys('rights');
$admin_like = pdl_sys_admin_like_rights();

$crumbs = [
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Erweitert'],
    ['title' => 'Benutzerrechte', 'href' => 'editdeluright.php'],
];
$back = '<a class="btn btn-outline-light" href="editdeluright.php">Zurück zu den Benutzerrechten</a>';

/** Alle Einträge aus pdl3_rights (auch solche mit fehlender Spalte). */
$load_rights = static function () use ($db_handler, $rights_t): array {
    $list = [];
    $res = $db_handler->sql_query('SELECT right_id, name, bez, variablenname, reihenfolge FROM ' . $rights_t . ' ORDER BY reihenfolge ASC, right_id ASC');
    while ($row = $db_handler->sql_fetch_array($res)) {
        $list[(int) $row['right_id']] = [
            'right_id' => (int) $row['right_id'],
            'name' => (string) $row['name'],
            'bez' => (string) $row['bez'],
            'variablenname' => (string) $row['variablenname'],
            'reihenfolge' => (int) $row['reihenfolge'],
        ];
    }
    return $list;
};
$all_rights = $load_rights();

// ---------------------------------------------------------------------------
// Recht löschen (Bestätigung, dann POST mit Token)
// ---------------------------------------------------------------------------
if ($action === 'delete') {
    $crumbs[] = ['title' => 'Benutzerrecht löschen'];
    pdl_admin_breadcrumb($crumbs);
    echo '<h1 class="h3 pdl-page-title">Benutzerrecht löschen</h1>';

    $right = $all_rights[$right_id] ?? null;
    if ($right === null) {
        echo pdl_admin_alert('warning', 'Dieses Benutzerrecht gibt es nicht (mehr).') . $back;
        include("footer.inc.php");
        return;
    }
    $var = $right['variablenname'];
    $is_protected = in_array($var, $protected, true);
    if ($is_protected) {
        echo pdl_admin_alert('warning', 'Das Recht „' . htmlspecialchars($right['name']) . '“ gehört zu PowerDownload und kann nicht gelöscht werden.') . $back;
        include("footer.inc.php");
        return;
    }
    $has_column = pdl_sys_valid_right_name($var) && in_array($var, pdl_sys_table_columns($db_handler, $sql_table['usergroup']), true);

    if ($is_post) {
        if (!pdl_sys_csrf_ok()) {
            echo pdl_admin_alert('danger', pdl_sys_csrf_error_text());
        } else {
            $dropped = !$has_column || pdl_sys_exec($db_handler, 'ALTER TABLE ' . $group_t . ' DROP COLUMN ' . pdl_sys_ident($var));
            if ($dropped && pdl_sys_exec($db_handler, 'DELETE FROM ' . $rights_t . ' WHERE right_id = ' . $right_id)) {
                pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'right', $right_id);
                echo pdl_admin_alert('success', '<strong>Benutzerrecht „' . htmlspecialchars($right['name']) . '“ wurde gelöscht.</strong>') . $back;
                include("footer.inc.php");
                return;
            }
            echo pdl_admin_alert('danger', '<strong>Das Recht wurde nicht gelöscht.</strong> Die Datenbank hat den Vorgang abgelehnt (' . htmlspecialchars(pdl_sys_db_error($db_handler)) . ').');
        }
    }

    echo makedialog(
        'Benutzerrecht „' . $right['name'] . '“ löschen?',
        '<input type="hidden" name="action" value="delete">'
        . '<input type="hidden" name="right_id" value="' . $right_id . '">'
        . '<p class="mb-2">Das Recht wird aus allen Benutzergruppen entfernt'
        . ($has_column ? ' und die Spalte <code>' . htmlspecialchars($var) . '</code> aus der Tabelle <code>' . htmlspecialchars($sql_table['usergroup']) . '</code> gelöscht' : '')
        . '.</p><p class="mb-0">Erweiterungen, die dieses Recht abfragen, behandeln danach jeden Benutzer so, als hätte er es nicht.</p>',
        'Ja, Recht löschen',
        'editdeluright.php',
        'editdeluright.php'
    );
    include("footer.inc.php");
    return;
}

// ---------------------------------------------------------------------------
// Beschriftungen und Reihenfolge speichern
// ---------------------------------------------------------------------------
$crumbs[] = ['title' => 'Übersicht'];
pdl_admin_breadcrumb($crumbs);
echo '<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">'
    . '<h1 class="h3 pdl-page-title mb-0">Benutzerrechte bearbeiten</h1>'
    . '<a class="btn btn-primary btn-sm" href="adduright.php">Benutzerrecht hinzufügen</a></div>';

$posted = is_array($_POST['rights'] ?? null) ? $_POST['rights'] : [];
if ($is_post && $action === 'save') {
    $errors = [];
    if (!pdl_sys_csrf_ok()) {
        $errors[] = pdl_sys_csrf_error_text();
    }
    $updates = [];
    foreach ($all_rights as $id => $right) {
        $entry = is_array($posted[$id] ?? null) ? $posted[$id] : null;
        if ($entry === null) {
            continue;
        }
        $name = trim(is_string($entry['name'] ?? null) ? $entry['name'] : '');
        $bez = trim(is_string($entry['bez'] ?? null) ? $entry['bez'] : '');
        $order = max(0, min(127, (int) ($entry['reihenfolge'] ?? 0)));
        if ($name === '' || strlen($name) > 128) {
            $errors[] = 'Recht „' . $right['variablenname'] . '“: Bitte geben Sie einen Namen mit höchstens 128 Zeichen ein.';
            continue;
        }
        if (strlen($bez) > 255) {
            $errors[] = 'Recht „' . $right['variablenname'] . '“: Die Beschreibung darf höchstens 255 Zeichen lang sein.';
            continue;
        }
        if ($name !== $right['name'] || $bez !== $right['bez'] || $order !== $right['reihenfolge']) {
            $updates[$id] = "UPDATE " . $rights_t . " SET `name` = '" . $db_handler->sql_escape_string($name)
                . "', `bez` = '" . $db_handler->sql_escape_string($bez) . "', `reihenfolge` = " . $order . ' WHERE right_id = ' . $id;
        }
    }
    if ($errors === []) {
        $failed = 0;
        foreach ($updates as $sql) {
            if (!pdl_sys_exec($db_handler, $sql)) {
                $failed++;
            }
        }
        if ($failed > 0) {
            echo pdl_admin_alert('danger', '<strong>' . $failed . ' Änderung(en) wurden nicht gespeichert.</strong> Bitte versuchen Sie es erneut.');
        } elseif ($updates === []) {
            echo pdl_admin_alert('info', 'Es gab keine Änderungen. Es wurde nichts gespeichert.');
        } else {
            echo pdl_admin_alert('success', '<strong>Die Benutzerrechte wurden gespeichert</strong> (' . count($updates) . ' geändert).');
        }
        $all_rights = $load_rights();
    } else {
        echo pdl_admin_alert('danger', '<strong>Es wurde nichts gespeichert.</strong><br>' . implode('<br>', array_map('htmlspecialchars', $errors)));
    }
}

$columns = pdl_sys_table_columns($db_handler, $sql_table['usergroup']);
?>
<p class="text-muted">Hier ändern Sie, wie die Rechte in den Benutzergruppen heißen und in welcher Reihenfolge sie erscheinen. Welche Gruppe ein Recht hat, legen Sie unter <a href="editdelugroup.php">Benutzergruppen</a> fest.</p>
<form action="editdeluright.php" method="post" id="pdlURightsForm" novalidate>
    <?php echo csrf_input(); ?>
    <input type="hidden" name="action" value="save">
    <?php foreach ($all_rights as $id => $right) {
        $var = $right['variablenname'];
        $is_protected = in_array($var, $protected, true);
        $missing = !in_array($var, $columns, true);
    ?>
    <section class="card pdl-card mb-3" id="pdlRightCard_<?php echo $id; ?>">
        <header class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 class="h6 mb-0"><?php echo htmlspecialchars($right['name']); ?>
                <?php if (isset($admin_like[$var])) { ?><span class="badge text-bg-warning ms-1" title="<?php echo htmlspecialchars($admin_like[$var]); ?>">Admin-Recht</span><?php } ?>
                <?php if (pdl_sys_requires_adminaccess($var)) { ?><span class="badge text-bg-secondary ms-1">benötigt Admin-Zugang</span><?php } ?>
            </h2>
            <span class="small text-muted"><code><?php echo htmlspecialchars($var); ?></code><?php echo $is_protected ? ' · mitgeliefert' : ' · selbst angelegt'; ?></span>
        </header>
        <div class="card-body">
            <?php if ($missing) { echo pdl_admin_alert('warning', 'Zu diesem Recht fehlt die Spalte in der Tabelle der Benutzergruppen. Es erscheint deshalb nicht in den Gruppen.'); } ?>
            <div class="row g-3">
                <div class="col-12 col-md-2">
                    <label class="form-label" for="pdlRightOrder_<?php echo $id; ?>">Reihenfolge</label>
                    <input type="number" min="0" max="127" id="pdlRightOrder_<?php echo $id; ?>" name="rights[<?php echo $id; ?>][reihenfolge]" class="form-control" value="<?php echo $right['reihenfolge']; ?>">
                </div>
                <div class="col-12 col-md-10">
                    <label class="form-label" for="pdlRightName_<?php echo $id; ?>">Name</label>
                    <input type="text" maxlength="128" id="pdlRightName_<?php echo $id; ?>" name="rights[<?php echo $id; ?>][name]" class="form-control" value="<?php echo htmlspecialchars($right['name']); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="pdlRightBez_<?php echo $id; ?>">Beschreibung</label>
                    <textarea maxlength="255" id="pdlRightBez_<?php echo $id; ?>" name="rights[<?php echo $id; ?>][bez]" class="form-control" rows="2"><?php echo htmlspecialchars($right['bez']); ?></textarea>
                </div>
            </div>
            <?php if (!$is_protected) { ?>
            <div class="mt-3"><a class="btn btn-sm btn-outline-danger" href="editdeluright.php?action=delete&amp;right_id=<?php echo $id; ?>" id="pdlRightDelete_<?php echo $id; ?>">Recht löschen …</a></div>
            <?php } ?>
        </div>
    </section>
    <?php } ?>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="index.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlURightsSave">Benutzerrechte speichern</button>
    </div>
</form>
<?php
include("footer.inc.php");
