<?php
/**
 * PowerDownload - Einstellungen und Einstellungsgruppen verwalten
 *
 * Übersicht der Gruppen (Name, Reihenfolge), je Gruppe die Beschriftungen,
 * Eingabeart und Reihenfolge der Einstellungen. Löschen nur mit
 * Bestätigungsseite; mitgelieferte Einträge sind geschützt.
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
$is_post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$sgroup_id = (int) ($_POST['sgroup_id'] ?? ($_GET['sgroup_id'] ?? 0));
$setting_id = (int) ($_POST['setting_id'] ?? ($_GET['setting_id'] ?? 0));
$action = pdl_sys_post('action', pdl_sys_get('action', $sgroup_id > 0 ? 'group' : 'list'));
$prot_settings = pdl_sys_shipped_keys('settings');
$prot_groups = array_map('intval', pdl_sys_shipped_keys('settingsgroup'));
$types = pdl_sys_setting_types();

/** @return array<int, array{sgroup_id: int, name: string, reihenfolge: int, count: int}> */
$load_groups = static function () use ($db_handler, $sgroup_t, $settings_t): array {
    $groups = [];
    $res = $db_handler->sql_query('SELECT g.sgroup_id, g.name, g.reihenfolge, COUNT(s.setting_id) AS cnt FROM ' . $sgroup_t . ' AS g'
        . ' LEFT JOIN ' . $settings_t . ' AS s ON s.sgroup_id = g.sgroup_id'
        . ' GROUP BY g.sgroup_id, g.name, g.reihenfolge ORDER BY g.reihenfolge ASC, g.sgroup_id ASC');
    while ($row = $db_handler->sql_fetch_array($res)) {
        $groups[(int) $row['sgroup_id']] = [
            'sgroup_id' => (int) $row['sgroup_id'],
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
    ['title' => 'Einstellungen und Gruppen', 'href' => 'editdelsettingssgroup.php'],
];
$back = '<a class="btn btn-outline-light" href="editdelsettingssgroup.php">Zurück zur Übersicht</a>';

// ---------------------------------------------------------------------------
// Einstellungsgruppe löschen
// ---------------------------------------------------------------------------
if ($action === 'delgroup') {
    $crumbs[] = ['title' => 'Gruppe löschen'];
    pdl_admin_breadcrumb($crumbs);
    echo '<h1 class="h3 pdl-page-title">Einstellungsgruppe löschen</h1>';
    $group = $groups[$sgroup_id] ?? null;
    if ($group === null) {
        echo pdl_admin_alert('warning', 'Diese Einstellungsgruppe gibt es nicht (mehr).') . $back;
    } elseif (in_array($sgroup_id, $prot_groups, true)) {
        echo pdl_admin_alert('warning', 'Die Gruppe „' . htmlspecialchars($group['name']) . '“ gehört zu PowerDownload und kann nicht gelöscht werden.') . $back;
    } elseif ($group['count'] > 0) {
        echo pdl_admin_alert('warning', 'Die Gruppe „' . htmlspecialchars($group['name']) . '“ enthält noch ' . $group['count']
            . ' Einstellung(en). Verschieben oder löschen Sie diese zuerst.') . $back;
    } elseif ($is_post && pdl_sys_csrf_ok()) {
        if (pdl_sys_exec($db_handler, 'DELETE FROM ' . $sgroup_t . ' WHERE sgroup_id = ' . $sgroup_id)) {
            pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'settingsgroup', $sgroup_id);
            echo pdl_admin_alert('success', '<strong>Einstellungsgruppe „' . htmlspecialchars($group['name']) . '“ wurde gelöscht.</strong>') . $back;
        } else {
            echo pdl_admin_alert('danger', 'Die Datenbank hat das Löschen abgelehnt. Es wurde nichts geändert.') . $back;
        }
    } else {
        if ($is_post) {
            echo pdl_admin_alert('danger', pdl_sys_csrf_error_text());
        }
        echo makedialog(
            'Einstellungsgruppe „' . $group['name'] . '“ löschen?',
            '<input type="hidden" name="action" value="delgroup"><input type="hidden" name="sgroup_id" value="' . $sgroup_id . '">'
            . '<p class="mb-0">Die leere Gruppe und ihr Reiter auf der Einstellungsseite werden entfernt.</p>',
            'Ja, Gruppe löschen',
            'editdelsettingssgroup.php',
            'editdelsettingssgroup.php'
        );
    }
    include("footer.inc.php");
    return;
}

// ---------------------------------------------------------------------------
// Einstellung löschen
// ---------------------------------------------------------------------------
if ($action === 'delsetting') {
    $crumbs[] = ['title' => 'Einstellung löschen'];
    pdl_admin_breadcrumb($crumbs);
    echo '<h1 class="h3 pdl-page-title">Einstellung löschen</h1>';
    $row = $db_handler->sql_fetch_array($db_handler->sql_query('SELECT setting_id, variablenname, name, sgroup_id FROM ' . $settings_t . ' WHERE setting_id = ' . $setting_id));
    $group_back = $row !== null
        ? '<a class="btn btn-outline-light" href="editdelsettingssgroup.php?sgroup_id=' . (int) $row['sgroup_id'] . '">Zurück zur Gruppe</a>'
        : $back;
    if ($row === null) {
        echo pdl_admin_alert('warning', 'Diese Einstellung gibt es nicht (mehr).') . $back;
    } elseif (in_array((string) $row['variablenname'], $prot_settings, true)) {
        echo pdl_admin_alert('warning', 'Die Einstellung „' . htmlspecialchars((string) $row['name']) . '“ gehört zu PowerDownload und kann nicht gelöscht werden.') . $group_back;
    } elseif ($is_post && pdl_sys_csrf_ok()) {
        if (pdl_sys_exec($db_handler, 'DELETE FROM ' . $settings_t . ' WHERE setting_id = ' . $setting_id)) {
            pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'setting', $setting_id);
            echo pdl_admin_alert('success', '<strong>Einstellung „' . htmlspecialchars((string) $row['name']) . '“ wurde gelöscht.</strong>') . $group_back;
        } else {
            echo pdl_admin_alert('danger', 'Die Datenbank hat das Löschen abgelehnt. Es wurde nichts geändert.') . $group_back;
        }
    } else {
        if ($is_post) {
            echo pdl_admin_alert('danger', pdl_sys_csrf_error_text());
        }
        echo makedialog(
            'Einstellung „' . (string) $row['name'] . '“ löschen?',
            '<input type="hidden" name="action" value="delsetting"><input type="hidden" name="setting_id" value="' . $setting_id . '">'
            . '<p class="mb-0">Die Einstellung <code>' . htmlspecialchars((string) $row['variablenname']) . '</code> und ihr gespeicherter Wert werden entfernt. Erweiterungen, die sie abfragen, erhalten danach keinen Wert mehr.</p>',
            'Ja, Einstellung löschen',
            'editdelsettingssgroup.php',
            'editdelsettingssgroup.php?sgroup_id=' . (int) $row['sgroup_id']
        );
    }
    include("footer.inc.php");
    return;
}

// ---------------------------------------------------------------------------
// Einstellungen einer Gruppe bearbeiten
// ---------------------------------------------------------------------------
if ($action === 'group' || $action === 'settings') {
    $group = $groups[$sgroup_id] ?? null;
    $crumbs[] = ['title' => $group !== null ? $group['name'] : 'Gruppe'];
    pdl_admin_breadcrumb($crumbs);
    if ($group === null) {
        echo '<h1 class="h3 pdl-page-title">Einstellungsgruppe</h1>';
        echo pdl_admin_alert('warning', 'Diese Einstellungsgruppe gibt es nicht (mehr).') . $back;
        include("footer.inc.php");
        return;
    }
    echo '<h1 class="h3 pdl-page-title">Einstellungen der Gruppe „' . htmlspecialchars($group['name']) . '“</h1>';

    $load_rows = static function () use ($db_handler, $settings_t, $sgroup_id): array {
        $rows = [];
        $res = $db_handler->sql_query('SELECT * FROM ' . $settings_t . ' WHERE sgroup_id = ' . $sgroup_id . ' ORDER BY reihenfolge ASC, setting_id ASC');
        while ($row = $db_handler->sql_fetch_array($res)) {
            $rows[(int) $row['setting_id']] = $row;
        }
        return $rows;
    };
    $rows = $load_rows();
    $posted = is_array($_POST['setting'] ?? null) ? $_POST['setting'] : [];
    $errors = [];

    if ($is_post && $action === 'settings') {
        if (!pdl_sys_csrf_ok()) {
            $errors[] = pdl_sys_csrf_error_text();
        }
        $updates = [];
        foreach ($rows as $id => $row) {
            $entry = is_array($posted[$id] ?? null) ? $posted[$id] : null;
            if ($entry === null) {
                continue;
            }
            $str = static fn (string $k): string => trim(is_string($entry[$k] ?? null) ? $entry[$k] : '');
            $label = (string) $row['variablenname'];
            $name = $str('name');
            $bez = $str('bez');
            $order = max(0, min(32767, (int) $str('reihenfolge')));
            $eingabe = pdl_sys_build_eingabe($str('typ'), $str('optionen'));
            $new_group = (int) $str('sgroup_id');
            $var = $label;
            if (!in_array($label, $prot_settings, true) && $str('variablenname') !== '') {
                $var = $str('variablenname');
            }
            if ($name === '' || strlen($name) > 128) {
                $errors[] = $label . ': Bitte geben Sie einen Namen mit höchstens 128 Zeichen ein.';
            }
            if (strlen($bez) > 255) {
                $errors[] = $label . ': Die Beschreibung darf höchstens 255 Zeichen lang sein.';
            }
            if ($eingabe === null) {
                $errors[] = $label . ': Die Eingabeart ist ungültig (bei Auswahllisten bitte Werte wie „a=Text A|b=Text B“ angeben).';
            }
            if (!isset($groups[$new_group])) {
                $errors[] = $label . ': Bitte wählen Sie eine vorhandene Einstellungsgruppe.';
            }
            if ($var !== $label) {
                if (!pdl_sys_valid_key($var)) {
                    $errors[] = $label . ': Der Variablenname darf nur Kleinbuchstaben, Ziffern und Unterstrich enthalten.';
                } elseif (pdl_sys_scalar_int($db_handler, 'SELECT COUNT(*) FROM ' . $settings_t . " WHERE variablenname = '" . $var . "'") > 0) {
                    $errors[] = $label . ': Den Variablennamen „' . $var . '“ gibt es bereits.';
                }
            }
            if ($eingabe !== null && ($name !== (string) $row['name'] || $bez !== (string) $row['bez'] || $order !== (int) $row['reihenfolge']
                || $eingabe !== (string) $row['eingabe'] || $new_group !== (int) $row['sgroup_id'] || $var !== $label)) {
                $updates[$id] = 'UPDATE ' . $settings_t . " SET `name` = '" . $db_handler->sql_escape_string($name)
                    . "', `bez` = '" . $db_handler->sql_escape_string($bez)
                    . "', `eingabe` = '" . $db_handler->sql_escape_string($eingabe)
                    . "', `reihenfolge` = " . $order . ', `sgroup_id` = ' . $new_group
                    . ", `variablenname` = '" . $db_handler->sql_escape_string($var) . "' WHERE setting_id = " . $id;
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
                pdl_audit_log($db_handler, $sql_table, $user_details, 'update', 'settings_meta', count($updates));
                echo pdl_admin_alert('success', '<strong>Die Einstellungen wurden gespeichert</strong> (' . count($updates) . ' geändert).');
            }
            $rows = $load_rows();
            $groups = $load_groups();
            $posted = [];
        } else {
            echo pdl_admin_alert('danger', '<strong>Es wurde nichts gespeichert.</strong><br>' . implode('<br>', array_map('htmlspecialchars', $errors)));
        }
    }
    ?>
<p class="text-muted">Hier ändern Sie Beschriftung, Beschreibung, Eingabeart und Reihenfolge. Die Werte selbst stellen Sie auf der <a href="settings.php?gruppe=<?php echo $sgroup_id; ?>">Einstellungsseite</a> ein.</p>
<form action="editdelsettingssgroup.php" method="post" id="pdlSetMetaForm" novalidate>
    <?php echo csrf_input(); ?>
    <input type="hidden" name="action" value="settings">
    <input type="hidden" name="sgroup_id" value="<?php echo $sgroup_id; ?>">
    <?php
    if ($rows === []) {
        echo '<p class="text-muted">Diese Gruppe enthält keine Einstellungen.</p>';
    }
    foreach ($rows as $id => $row) {
        $var = (string) $row['variablenname'];
        $is_protected = in_array($var, $prot_settings, true);
        $entry = is_array($posted[$id] ?? null) ? $posted[$id] : [];
        $val = static fn (string $k, string $default): string => is_string($entry[$k] ?? null) ? $entry[$k] : $default;
        $parsed = pdl_sys_parse_eingabe((string) $row['eingabe']);
        $type = $val('typ', $parsed['type']);
        $p = 'setting[' . $id . ']';
        $pid = 'pdlSetMeta_' . $id;
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
                    <input type="number" min="0" id="<?php echo $pid; ?>_order" name="<?php echo $p; ?>[reihenfolge]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($val('reihenfolge', (string) $row['reihenfolge'])); ?>">
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label" for="<?php echo $pid; ?>_name">Name</label>
                    <input type="text" maxlength="128" id="<?php echo $pid; ?>_name" name="<?php echo $p; ?>[name]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($val('name', (string) $row['name'])); ?>">
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label" for="<?php echo $pid; ?>_group">Einstellungsgruppe</label>
                    <select id="<?php echo $pid; ?>_group" name="<?php echo $p; ?>[sgroup_id]" class="form-select form-select-sm">
                        <?php foreach ($groups as $gid => $g) {
                            echo '<option value="' . $gid . '"' . ((string) $gid === $val('sgroup_id', (string) $row['sgroup_id']) ? ' selected' : '') . '>' . htmlspecialchars($g['name']) . '</option>';
                        } ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="<?php echo $pid; ?>_bez">Beschreibung</label>
                    <textarea maxlength="255" id="<?php echo $pid; ?>_bez" name="<?php echo $p; ?>[bez]" class="form-control form-control-sm" rows="2"><?php echo htmlspecialchars($val('bez', (string) $row['bez'])); ?></textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="<?php echo $pid; ?>_typ">Eingabeart</label>
                    <select id="<?php echo $pid; ?>_typ" name="<?php echo $p; ?>[typ]" class="form-select form-select-sm">
                        <?php foreach ($types as $key => $label) {
                            echo '<option value="' . $key . '"' . ($key === $type ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
                        } ?>
                    </select>
                    <?php if ($parsed['legacy']) { ?><div class="form-text text-warning">Bisher: eigene Eingabeart „<?php echo htmlspecialchars((string) $row['eingabe']); ?>“ (wird als Textfeld angezeigt).</div><?php } ?>
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label" for="<?php echo $pid; ?>_opt">Auswahlwerte (nur bei Auswahlliste)</label>
                    <input type="text" id="<?php echo $pid; ?>_opt" name="<?php echo $p; ?>[optionen]" class="form-control form-control-sm font-monospace" value="<?php echo htmlspecialchars($val('optionen', pdl_sys_options_text($parsed['options']))); ?>">
                    <div class="form-text">Format: <code>wert=Beschriftung|wert2=Beschriftung 2</code></div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="<?php echo $pid; ?>_var">Variablenname</label>
                    <?php if ($is_protected) { ?>
                        <p class="form-control-plaintext py-0"><code><?php echo htmlspecialchars($var); ?></code> <small class="text-muted">(geschützt)</small></p>
                    <?php } else { ?>
                        <input type="text" maxlength="64" id="<?php echo $pid; ?>_var" name="<?php echo $p; ?>[variablenname]" class="form-control form-control-sm font-monospace" style="max-width: 20rem" value="<?php echo htmlspecialchars($val('variablenname', $var)); ?>">
                    <?php } ?>
                </div>
            </div>
            <?php if (!$is_protected) { ?>
            <div class="mt-3"><a class="btn btn-sm btn-outline-danger" href="editdelsettingssgroup.php?action=delsetting&amp;setting_id=<?php echo $id; ?>" id="<?php echo $pid; ?>_delete">Einstellung löschen …</a></div>
            <?php } ?>
        </div>
    </section>
    <?php } ?>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="editdelsettingssgroup.php" class="btn btn-outline-light">Zurück zur Übersicht</a>
        <button type="submit" class="btn btn-primary" id="pdlSetMetaSave">Einstellungen speichern</button>
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
    . '<h1 class="h3 pdl-page-title mb-0">Einstellungen und Gruppen bearbeiten</h1>'
    . '<div class="d-flex gap-2"><a class="btn btn-primary btn-sm" href="addsettings.php">Einstellung hinzufügen</a>'
    . '<a class="btn btn-outline-light btn-sm" href="addsgroup.php">Einstellungsgruppe hinzufügen</a></div></div>';

if ($is_post && $action === 'groups') {
    $posted = is_array($_POST['sgroup'] ?? null) ? $_POST['sgroup'] : [];
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
            $updates[] = 'UPDATE ' . $sgroup_t . " SET `name` = '" . $db_handler->sql_escape_string($name) . "', `reihenfolge` = " . $order . ' WHERE sgroup_id = ' . $gid;
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
            echo pdl_admin_alert('success', '<strong>Die Einstellungsgruppen wurden gespeichert</strong> (' . count($updates) . ' geändert).');
        }
        $groups = $load_groups();
    } else {
        echo pdl_admin_alert('danger', '<strong>Es wurde nichts gespeichert.</strong><br>' . implode('<br>', array_map('htmlspecialchars', $errors)));
    }
}
?>
<p class="text-muted">Die Gruppen erscheinen als Reiter auf der <a href="settings.php">Einstellungsseite</a>. Die Werte der Einstellungen ändern Sie dort; hier geht es um Namen, Reihenfolge und Eingabeart.</p>
<form action="editdelsettingssgroup.php" method="post" id="pdlSGroupsForm" novalidate>
    <?php echo csrf_input(); ?>
    <input type="hidden" name="action" value="groups">
    <section class="card pdl-card mb-3">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0" id="pdlSGroupTable">
                <thead><tr><th scope="col" style="width: 8rem">Reihenfolge</th><th scope="col">Name</th><th scope="col" class="text-end">Einstellungen</th><th scope="col" class="text-end">Aktionen</th></tr></thead>
                <tbody>
                <?php foreach ($groups as $gid => $group) { ?>
                    <tr id="pdlSGroupRow_<?php echo $gid; ?>">
                        <td><input type="number" min="0" max="127" class="form-control form-control-sm" name="sgroup[<?php echo $gid; ?>][reihenfolge]" value="<?php echo $group['reihenfolge']; ?>" aria-label="Reihenfolge von <?php echo htmlspecialchars($group['name']); ?>"></td>
                        <td><input type="text" maxlength="128" class="form-control form-control-sm" name="sgroup[<?php echo $gid; ?>][name]" value="<?php echo htmlspecialchars($group['name']); ?>" aria-label="Name der Gruppe Nr. <?php echo $gid; ?>"></td>
                        <td class="text-end"><?php echo $group['count']; ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-outline-light" href="editdelsettingssgroup.php?sgroup_id=<?php echo $gid; ?>" id="pdlSGroupEdit_<?php echo $gid; ?>">Einstellungen bearbeiten</a>
                                <?php if (!in_array($gid, $prot_groups, true) && $group['count'] === 0) { ?>
                                <a class="btn btn-outline-danger" href="editdelsettingssgroup.php?action=delgroup&amp;sgroup_id=<?php echo $gid; ?>" id="pdlSGroupDel_<?php echo $gid; ?>">löschen</a>
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
        <button type="submit" class="btn btn-primary" id="pdlSGroupsSave">Gruppen speichern</button>
    </div>
</form>
<?php
include("footer.inc.php");
