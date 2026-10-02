<?php
/**
 * PowerDownload - Vorlagen und Vorlagengruppen verwalten
 *
 * Übersicht der Gruppen (Name, Reihenfolge); je Gruppe Name, Beschreibung,
 * Eingabeart, Gruppe und Reihenfolge der Vorlagen. Den Inhalt bearbeiten Sie
 * in templates.php. Löschen nur mit Bestätigungsseite; mitgelieferte
 * Einträge sind geschützt.
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
$is_post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$tgroup_id = (int) ($_POST['tgroup_id'] ?? ($_GET['tgroup_id'] ?? 0));
$template_id = (int) ($_POST['template_id'] ?? ($_GET['template_id'] ?? 0));
$action = pdl_sys_post('action', pdl_sys_get('action', $tgroup_id > 0 ? 'group' : 'list'));
$prot_templates = pdl_sys_shipped_keys('template');
$prot_groups = array_map('intval', pdl_sys_shipped_keys('templategroup'));
$types = ['textarea' => 'Mehrzeiliges Feld (HTML)', 'input' => 'Einzeiliges Feld', 'farbe' => 'Farbe'];

/** @return array<int, array{tgroup_id: int, name: string, reihenfolge: int, count: int}> */
$load_groups = static function () use ($db_handler, $tgroup_t, $template_t): array {
    $groups = [];
    $res = $db_handler->sql_query('SELECT g.tgroup_id, g.name, g.reihenfolge, COUNT(t.template_id) AS cnt FROM ' . $tgroup_t . ' AS g'
        . ' LEFT JOIN ' . $template_t . ' AS t ON t.tgroup_id = g.tgroup_id'
        . ' GROUP BY g.tgroup_id, g.name, g.reihenfolge ORDER BY g.reihenfolge ASC, g.tgroup_id ASC');
    while ($row = $db_handler->sql_fetch_array($res)) {
        $groups[(int) $row['tgroup_id']] = [
            'tgroup_id' => (int) $row['tgroup_id'],
            'name' => (string) $row['name'],
            'reihenfolge' => (int) $row['reihenfolge'],
            'count' => (int) $row['cnt'],
        ];
    }
    return $groups;
};
$groups = $load_groups();

$crumbs = [
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Erweitert'],
    ['title' => 'Vorlagen und Gruppen', 'href' => 'editdeltemplatestgroup.php'],
];
$back = '<a class="btn btn-outline-light" href="editdeltemplatestgroup.php">Zurück zur Übersicht</a>';

// ---------------------------------------------------------------------------
// Vorlagengruppe löschen
// ---------------------------------------------------------------------------
if ($action === 'delgroup') {
    $crumbs[] = ['title' => 'Gruppe löschen'];
    pdl_admin_breadcrumb($crumbs);
    echo '<h1 class="h3 pdl-page-title">Vorlagengruppe löschen</h1>';
    $group = $groups[$tgroup_id] ?? null;
    if ($group === null) {
        echo pdl_admin_alert('warning', 'Diese Vorlagengruppe gibt es nicht (mehr).') . $back;
    } elseif (in_array($tgroup_id, $prot_groups, true)) {
        echo pdl_admin_alert('warning', 'Die Gruppe „' . htmlspecialchars($group['name']) . '“ gehört zu PowerDownload und kann nicht gelöscht werden.') . $back;
    } elseif ($group['count'] > 0) {
        echo pdl_admin_alert('warning', 'Die Gruppe „' . htmlspecialchars($group['name']) . '“ enthält noch ' . $group['count']
            . ' Vorlage(n). Verschieben oder löschen Sie diese zuerst.') . $back;
    } elseif ($is_post && pdl_sys_csrf_ok()) {
        if (pdl_sys_exec($db_handler, 'DELETE FROM ' . $tgroup_t . ' WHERE tgroup_id = ' . $tgroup_id)) {
            pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'templategroup', $tgroup_id);
            echo pdl_admin_alert('success', '<strong>Vorlagengruppe „' . htmlspecialchars($group['name']) . '“ wurde gelöscht.</strong>') . $back;
        } else {
            echo pdl_admin_alert('danger', 'Die Datenbank hat das Löschen abgelehnt. Es wurde nichts geändert.') . $back;
        }
    } else {
        if ($is_post) {
            echo pdl_admin_alert('danger', pdl_sys_csrf_error_text());
        }
        echo makedialog(
            'Vorlagengruppe „' . $group['name'] . '“ löschen?',
            '<input type="hidden" name="action" value="delgroup"><input type="hidden" name="tgroup_id" value="' . $tgroup_id . '">'
            . '<p class="mb-0">Die leere Gruppe wird aus dem Vorlagen-Editor entfernt.</p>',
            'Ja, Gruppe löschen',
            'editdeltemplatestgroup.php',
            'editdeltemplatestgroup.php'
        );
    }
    include("footer.inc.php");
    return;
}

// ---------------------------------------------------------------------------
// Vorlage löschen
// ---------------------------------------------------------------------------
if ($action === 'deltemplate') {
    $crumbs[] = ['title' => 'Vorlage löschen'];
    pdl_admin_breadcrumb($crumbs);
    echo '<h1 class="h3 pdl-page-title">Vorlage löschen</h1>';
    $row = $db_handler->sql_fetch_array($db_handler->sql_query('SELECT template_id, variablenname, name, tgroup_id FROM ' . $template_t . ' WHERE template_id = ' . $template_id));
    $group_back = $row !== null
        ? '<a class="btn btn-outline-light" href="editdeltemplatestgroup.php?tgroup_id=' . (int) $row['tgroup_id'] . '">Zurück zur Gruppe</a>'
        : $back;
    if ($row === null) {
        echo pdl_admin_alert('warning', 'Diese Vorlage gibt es nicht (mehr).') . $back;
    } elseif (in_array((string) $row['variablenname'], $prot_templates, true)) {
        echo pdl_admin_alert('warning', 'Die Vorlage „' . htmlspecialchars((string) $row['name']) . '“ gehört zu PowerDownload und kann nicht gelöscht werden.') . $group_back;
    } elseif ($is_post && pdl_sys_csrf_ok()) {
        if (pdl_sys_exec($db_handler, 'DELETE FROM ' . $template_t . ' WHERE template_id = ' . $template_id)) {
            pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'template', $template_id);
            echo pdl_admin_alert('success', '<strong>Vorlage „' . htmlspecialchars((string) $row['name']) . '“ wurde gelöscht.</strong>') . $group_back;
        } else {
            echo pdl_admin_alert('danger', 'Die Datenbank hat das Löschen abgelehnt. Es wurde nichts geändert.') . $group_back;
        }
    } else {
        if ($is_post) {
            echo pdl_admin_alert('danger', pdl_sys_csrf_error_text());
        }
        echo makedialog(
            'Vorlage „' . (string) $row['name'] . '“ löschen?',
            '<input type="hidden" name="action" value="deltemplate"><input type="hidden" name="template_id" value="' . $template_id . '">'
            . '<p class="mb-0">Die Vorlage <code>' . htmlspecialchars((string) $row['variablenname']) . '</code> und ihr Inhalt werden entfernt.</p>',
            'Ja, Vorlage löschen',
            'editdeltemplatestgroup.php',
            'editdeltemplatestgroup.php?tgroup_id=' . (int) $row['tgroup_id']
        );
    }
    include("footer.inc.php");
    return;
}

// ---------------------------------------------------------------------------
// Vorlagen einer Gruppe bearbeiten (Beschriftung, nicht Inhalt)
// ---------------------------------------------------------------------------
if ($action === 'group' || $action === 'templates') {
    $group = $groups[$tgroup_id] ?? null;
    $crumbs[] = ['title' => $group !== null ? $group['name'] : 'Gruppe'];
    pdl_admin_breadcrumb($crumbs);
    if ($group === null) {
        echo '<h1 class="h3 pdl-page-title">Vorlagengruppe</h1>';
        echo pdl_admin_alert('warning', 'Diese Vorlagengruppe gibt es nicht (mehr).') . $back;
        include("footer.inc.php");
        return;
    }
    echo '<h1 class="h3 pdl-page-title">Vorlagen der Gruppe „' . htmlspecialchars($group['name']) . '“</h1>';

    $load_rows = static function () use ($db_handler, $template_t, $tgroup_id): array {
        $rows = [];
        $res = $db_handler->sql_query('SELECT template_id, variablenname, name, bez, eingabe, tgroup_id, reihenfolge FROM ' . $template_t
            . ' WHERE tgroup_id = ' . $tgroup_id . ' ORDER BY reihenfolge ASC, template_id ASC');
        while ($row = $db_handler->sql_fetch_array($res)) {
            $rows[(int) $row['template_id']] = $row;
        }
        return $rows;
    };
    $rows = $load_rows();
    $posted = is_array($_POST['tpl'] ?? null) ? $_POST['tpl'] : [];

    if ($is_post && $action === 'templates') {
        $errors = [];
        $updates = [];
        if (!pdl_sys_csrf_ok()) {
            $errors[] = pdl_sys_csrf_error_text();
        }
        foreach ($rows as $id => $row) {
            $entry = is_array($posted[$id] ?? null) ? $posted[$id] : null;
            if ($entry === null) {
                continue;
            }
            $str = static fn (string $k): string => trim(is_string($entry[$k] ?? null) ? $entry[$k] : '');
            $label = (string) $row['variablenname'];
            $name = $str('name');
            $bez = $str('bez');
            $eingabe = $str('eingabe');
            $order = max(0, min(127, (int) $str('reihenfolge')));
            $new_group = (int) $str('tgroup_id');
            $var = $label;
            if (!in_array($label, $prot_templates, true) && $str('variablenname') !== '') {
                $var = $str('variablenname');
            }
            if ($name === '' || strlen($name) > 128) {
                $errors[] = $label . ': Bitte geben Sie einen Namen mit höchstens 128 Zeichen ein.';
            }
            if (strlen($bez) > 255) {
                $errors[] = $label . ': Die Beschreibung darf höchstens 255 Zeichen lang sein.';
            }
            if (!isset($types[$eingabe])) {
                $errors[] = $label . ': Bitte wählen Sie eine Eingabeart.';
            }
            if (!isset($groups[$new_group])) {
                $errors[] = $label . ': Bitte wählen Sie eine vorhandene Vorlagengruppe.';
            }
            if ($var !== $label) {
                if (!pdl_sys_valid_key($var)) {
                    $errors[] = $label . ': Der Variablenname darf nur Kleinbuchstaben, Ziffern und Unterstrich enthalten.';
                } elseif (pdl_sys_scalar_int($db_handler, 'SELECT COUNT(*) FROM ' . $template_t . " WHERE variablenname = '" . $var . "'") > 0) {
                    $errors[] = $label . ': Den Variablennamen „' . $var . '“ gibt es bereits.';
                }
            }
            if ($name !== (string) $row['name'] || $bez !== (string) $row['bez'] || $eingabe !== (string) $row['eingabe']
                || $order !== (int) $row['reihenfolge'] || $new_group !== (int) $row['tgroup_id'] || $var !== $label) {
                $updates[] = 'UPDATE ' . $template_t . " SET `name` = '" . $db_handler->sql_escape_string($name)
                    . "', `bez` = '" . $db_handler->sql_escape_string($bez)
                    . "', `eingabe` = '" . $db_handler->sql_escape_string($eingabe)
                    . "', `reihenfolge` = " . $order . ', `tgroup_id` = ' . $new_group
                    . ", `variablenname` = '" . $db_handler->sql_escape_string($var) . "' WHERE template_id = " . $id;
            }
        }
        if ($errors === []) {
            $failed = 0;
            foreach ($updates as $sql) {
                $failed += pdl_sys_exec($db_handler, $sql) ? 0 : 1;
            }
            if ($failed > 0) {
                echo pdl_admin_alert('danger', '<strong>' . $failed . ' Änderung(en) wurden nicht gespeichert.</strong> Bitte versuchen Sie es erneut.');
            } elseif ($updates === []) {
                echo pdl_admin_alert('info', 'Sie haben nichts geändert. Es wurde nichts gespeichert.');
            } else {
                pdl_audit_log($db_handler, $sql_table, $user_details, 'update', 'templates_meta', count($updates));
                echo pdl_admin_alert('success', '<strong>Die Vorlagen wurden gespeichert</strong> (' . count($updates) . ' geändert).');
            }
            $rows = $load_rows();
            $groups = $load_groups();
            $posted = [];
        } else {
            echo pdl_admin_alert('danger', '<strong>Es wurde nichts gespeichert.</strong><br>' . implode('<br>', array_map('htmlspecialchars', $errors)));
        }
    }
    ?>
<p class="text-muted">Hier ändern Sie Name, Beschreibung, Eingabeart und Reihenfolge. Den Inhalt bearbeiten Sie unter <a href="templates.php#tg_<?php echo $tgroup_id; ?>">Vorlagen bearbeiten</a>.</p>
<form action="editdeltemplatestgroup.php" method="post" id="pdlTplMetaForm" novalidate>
    <?php echo csrf_input(); ?>
    <input type="hidden" name="action" value="templates">
    <input type="hidden" name="tgroup_id" value="<?php echo $tgroup_id; ?>">
    <?php
    if ($rows === []) {
        echo '<p class="text-muted">Diese Gruppe enthält keine Vorlagen.</p>';
    }
    foreach ($rows as $id => $row) {
        $var = (string) $row['variablenname'];
        $is_protected = in_array($var, $prot_templates, true);
        $entry = is_array($posted[$id] ?? null) ? $posted[$id] : [];
        $val = static fn (string $k, string $default): string => is_string($entry[$k] ?? null) ? $entry[$k] : $default;
        $p = 'tpl[' . $id . ']';
        $pid = 'pdlTplMeta_' . $id;
    ?>
    <section class="card pdl-card mb-3" id="<?php echo $pid; ?>">
        <header class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 class="h6 mb-0"><?php echo htmlspecialchars((string) $row['name']); ?></h2>
            <span class="small text-muted"><code><?php echo htmlspecialchars($var); ?></code><?php echo $is_protected ? ' · mitgeliefert (geschützt)' : ' · selbst angelegt'; ?></span>
        </header>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-2">
                    <label class="form-label" for="<?php echo $pid; ?>_order">Reihenfolge</label>
                    <input type="number" min="0" max="127" id="<?php echo $pid; ?>_order" name="<?php echo $p; ?>[reihenfolge]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($val('reihenfolge', (string) $row['reihenfolge'])); ?>">
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label" for="<?php echo $pid; ?>_name">Name</label>
                    <input type="text" maxlength="128" id="<?php echo $pid; ?>_name" name="<?php echo $p; ?>[name]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($val('name', (string) $row['name'])); ?>">
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label" for="<?php echo $pid; ?>_group">Vorlagengruppe</label>
                    <select id="<?php echo $pid; ?>_group" name="<?php echo $p; ?>[tgroup_id]" class="form-select form-select-sm">
                        <?php foreach ($groups as $gid => $g) {
                            echo '<option value="' . $gid . '"' . ((string) $gid === $val('tgroup_id', (string) $row['tgroup_id']) ? ' selected' : '') . '>' . htmlspecialchars($g['name']) . '</option>';
                        } ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="<?php echo $pid; ?>_bez">Beschreibung</label>
                    <textarea maxlength="255" id="<?php echo $pid; ?>_bez" name="<?php echo $p; ?>[bez]" class="form-control form-control-sm" rows="2"><?php echo htmlspecialchars($val('bez', (string) $row['bez'])); ?></textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="<?php echo $pid; ?>_eingabe">Eingabeart</label>
                    <select id="<?php echo $pid; ?>_eingabe" name="<?php echo $p; ?>[eingabe]" class="form-select form-select-sm">
                        <?php foreach ($types as $key => $label) {
                            echo '<option value="' . $key . '"' . ($key === $val('eingabe', (string) $row['eingabe']) ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
                        } ?>
                    </select>
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label" for="<?php echo $pid; ?>_var">Variablenname</label>
                    <?php if ($is_protected) { ?>
                        <p class="form-control-plaintext py-0"><code><?php echo htmlspecialchars($var); ?></code> <small class="text-muted">(geschützt)</small></p>
                    <?php } else { ?>
                        <input type="text" maxlength="64" id="<?php echo $pid; ?>_var" name="<?php echo $p; ?>[variablenname]" class="form-control form-control-sm font-monospace" style="max-width: 20rem" value="<?php echo htmlspecialchars($val('variablenname', $var)); ?>">
                    <?php } ?>
                </div>
            </div>
            <?php if (!$is_protected) { ?>
            <div class="mt-3"><a class="btn btn-sm btn-outline-danger" href="editdeltemplatestgroup.php?action=deltemplate&amp;template_id=<?php echo $id; ?>" id="<?php echo $pid; ?>_delete">Vorlage löschen …</a></div>
            <?php } ?>
        </div>
    </section>
    <?php } ?>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="editdeltemplatestgroup.php" class="btn btn-outline-light">Zurück zur Übersicht</a>
        <button type="submit" class="btn btn-primary" id="pdlTplMetaSave">Vorlagen speichern</button>
    </div>
</form>
    <?php
    include("footer.inc.php");
    return;
}

// ---------------------------------------------------------------------------
// Übersicht der Gruppen
// ---------------------------------------------------------------------------
pdl_admin_breadcrumb($crumbs);
echo '<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">'
    . '<h1 class="h3 pdl-page-title mb-0">Vorlagen und Gruppen bearbeiten</h1>'
    . '<div class="d-flex gap-2"><a class="btn btn-primary btn-sm" href="addtemplate.php">Vorlage hinzufügen</a>'
    . '<a class="btn btn-outline-light btn-sm" href="addtgroup.php">Vorlagengruppe hinzufügen</a></div></div>';

if ($is_post && $action === 'groups') {
    $posted = is_array($_POST['tgroup'] ?? null) ? $_POST['tgroup'] : [];
    $errors = [];
    $updates = [];
    if (!pdl_sys_csrf_ok()) {
        $errors[] = pdl_sys_csrf_error_text();
    }
    foreach ($groups as $gid => $group) {
        $entry = is_array($posted[$gid] ?? null) ? $posted[$gid] : null;
        if ($entry === null) {
            continue;
        }
        $name = trim(is_string($entry['name'] ?? null) ? $entry['name'] : '');
        $order = max(0, min(127, (int) ($entry['reihenfolge'] ?? 0)));
        if ($name === '' || strlen($name) > 128) {
            $errors[] = 'Gruppe Nr. ' . $gid . ': Bitte geben Sie einen Namen mit höchstens 128 Zeichen ein.';
            continue;
        }
        if ($name !== $group['name'] || $order !== $group['reihenfolge']) {
            $updates[] = 'UPDATE ' . $tgroup_t . " SET `name` = '" . $db_handler->sql_escape_string($name) . "', `reihenfolge` = " . $order . ' WHERE tgroup_id = ' . $gid;
        }
    }
    if ($errors === []) {
        $failed = 0;
        foreach ($updates as $sql) {
            $failed += pdl_sys_exec($db_handler, $sql) ? 0 : 1;
        }
        if ($failed > 0) {
            echo pdl_admin_alert('danger', '<strong>' . $failed . ' Änderung(en) wurden nicht gespeichert.</strong>');
        } elseif ($updates === []) {
            echo pdl_admin_alert('info', 'Sie haben nichts geändert. Es wurde nichts gespeichert.');
        } else {
            echo pdl_admin_alert('success', '<strong>Die Vorlagengruppen wurden gespeichert</strong> (' . count($updates) . ' geändert).');
        }
        $groups = $load_groups();
    } else {
        echo pdl_admin_alert('danger', '<strong>Es wurde nichts gespeichert.</strong><br>' . implode('<br>', array_map('htmlspecialchars', $errors)));
    }
}
?>
<p class="text-muted">Die Gruppen gliedern den <a href="templates.php">Vorlagen-Editor</a>. Hier geht es um Namen, Reihenfolge und Eingabeart, nicht um den Inhalt der Vorlagen.</p>
<form action="editdeltemplatestgroup.php" method="post" id="pdlTGroupsForm" novalidate>
    <?php echo csrf_input(); ?>
    <input type="hidden" name="action" value="groups">
    <section class="card pdl-card mb-3">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0" id="pdlTGroupTable">
                <thead><tr><th scope="col" style="width: 8rem">Reihenfolge</th><th scope="col">Name</th><th scope="col" class="text-end">Vorlagen</th><th scope="col" class="text-end">Aktionen</th></tr></thead>
                <tbody>
                <?php foreach ($groups as $gid => $group) { ?>
                    <tr id="pdlTGroupRow_<?php echo $gid; ?>">
                        <td><input type="number" min="0" max="127" class="form-control form-control-sm" name="tgroup[<?php echo $gid; ?>][reihenfolge]" value="<?php echo $group['reihenfolge']; ?>" aria-label="Reihenfolge von <?php echo htmlspecialchars($group['name']); ?>"></td>
                        <td><input type="text" maxlength="128" class="form-control form-control-sm" name="tgroup[<?php echo $gid; ?>][name]" value="<?php echo htmlspecialchars($group['name']); ?>" aria-label="Name der Gruppe Nr. <?php echo $gid; ?>"></td>
                        <td class="text-end"><?php echo $group['count']; ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-outline-light" href="editdeltemplatestgroup.php?tgroup_id=<?php echo $gid; ?>" id="pdlTGroupEdit_<?php echo $gid; ?>">Vorlagen verwalten</a>
                                <?php if (!in_array($gid, $prot_groups, true) && $group['count'] === 0) { ?>
                                <a class="btn btn-outline-danger" href="editdeltemplatestgroup.php?action=delgroup&amp;tgroup_id=<?php echo $gid; ?>" id="pdlTGroupDel_<?php echo $gid; ?>">löschen</a>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <button type="submit" class="btn btn-primary" id="pdlTGroupsSave">Gruppen speichern</button>
    </div>
</form>
<?php
include("footer.inc.php");
