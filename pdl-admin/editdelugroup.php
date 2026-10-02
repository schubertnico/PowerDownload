<?php
/**
 * PowerDownload - Benutzergruppen verwalten (Übersicht, bearbeiten, löschen)
 *
 * - Gruppe 2 (Administrator) ist unveränderlich.
 * - Gruppe 1 (Mitglieder), Gruppe 3 und die Gastgruppe lassen sich bearbeiten,
 *   aber nicht löschen. Die Gastgruppe gilt für nicht angemeldete Besucher
 *   und kann keine Benutzer haben.
 * - Beim Löschen werden die Mitglieder vorher in eine Zielgruppe verschoben.
 * - Spaltennamen der Rechte kommen nur aus pdl3_rights (Whitelist).
 */
include("header.inc.php");
include_once("system_helpers.inc.php");

if (!pdl_sys_can($user_rights, 'edituser', 'deluser')) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('edituser', 'deluser'));
    include("footer.inc.php");
    return;
}

$eugroup_id = (int) ($_POST['eugroup_id'] ?? ($_GET['eugroup_id'] ?? 0));
$action = pdl_sys_post('action', pdl_sys_get('action', $eugroup_id > 0 ? 'edit' : 'list'));
$is_post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

$group_t = pdl_sys_ident($sql_table['usergroup']);
$user_t = pdl_sys_ident($sql_table['user']);
$guest_group_id = pdl_sys_guest_group_id($settings);
$protected_ids = pdl_sys_protected_group_ids($settings);
$groups = pdl_sys_groups($db_handler, $sql_table);
$groups_by_id = [];
foreach ($groups as $group) {
    $groups_by_id[$group['ugroup_id']] = $group;
}

/**
 * Kurzbeschreibung der Rolle einer Gruppe.
 */
$role_badge = static function (int $id) use ($settings): string {
    switch (pdl_sys_group_role($id, $settings)) {
        case 'admin':
            return '<span class="badge text-bg-secondary ms-2">Geschützt: alle Rechte</span>';
        case 'member':
            return '<span class="badge text-bg-info ms-2">Neue Registrierungen</span>';
        case 'guest':
            return '<span class="badge text-bg-info ms-2">Nicht angemeldete Besucher</span>';
        case 'protected':
            return '<span class="badge text-bg-secondary ms-2">Geschützt</span>';
        default:
            return '';
    }
};

$crumbs = [
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Benutzer', 'href' => 'users.php'],
    ['title' => 'Benutzergruppen', 'href' => 'editdelugroup.php'],
];
$back = '<a class="btn btn-outline-light" href="editdelugroup.php" id="pdlUGroupBack">Zurück zur Gruppenübersicht</a>';

// ---------------------------------------------------------------------------
// Gruppe löschen (Bestätigung per GET, Ausführung per POST mit Token)
// ---------------------------------------------------------------------------
if ($action === 'delete') {
    $crumbs[] = ['title' => 'Benutzergruppe löschen'];
    pdl_admin_breadcrumb($crumbs);
    echo '<h1 class="h3 pdl-page-title">Benutzergruppe löschen</h1>';

    $group = $groups_by_id[$eugroup_id] ?? null;
    if ($group === null) {
        echo pdl_admin_alert('warning', 'Diese Benutzergruppe gibt es nicht (mehr).') . $back;
        include("footer.inc.php");
        return;
    }
    if (in_array($eugroup_id, $protected_ids, true)) {
        echo pdl_admin_alert('warning', 'Die Gruppe „' . htmlspecialchars($group['name']) . '“ gehört zur Grundausstattung und kann nicht gelöscht werden.') . $back;
        include("footer.inc.php");
        return;
    }

    $targets = [];
    foreach ($groups as $candidate) {
        if ($candidate['ugroup_id'] !== $eugroup_id && $candidate['ugroup_id'] !== $guest_group_id) {
            $targets[$candidate['ugroup_id']] = $candidate['name'];
        }
    }
    $move_to = (int) pdl_sys_post('move_to', isset($targets[1]) ? '1' : (string) array_key_first($targets));

    if ($is_post) {
        $error = '';
        if (!pdl_sys_csrf_ok()) {
            $error = pdl_sys_csrf_error_text();
        } elseif ($group['members'] > 0 && !isset($targets[$move_to])) {
            $error = 'Bitte wählen Sie eine Gruppe, in die die Mitglieder verschoben werden.';
        } else {
            $db_handler->sql_query('START TRANSACTION');
            $moved_ok = $group['members'] === 0
                || pdl_sys_exec($db_handler, 'UPDATE ' . $user_t . ' SET ugroup_id = ' . $move_to . ' WHERE ugroup_id = ' . $eugroup_id);
            $moved = $group['members'] === 0 ? 0 : max(0, pdl_sys_affected_rows($db_handler));
            $deleted_ok = $moved_ok && pdl_sys_exec($db_handler, 'DELETE FROM ' . $group_t . ' WHERE ugroup_id = ' . $eugroup_id);
            if ($deleted_ok) {
                $db_handler->sql_query('COMMIT');
                pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'usergroup', $eugroup_id);
                $text = '<strong>Benutzergruppe „' . htmlspecialchars($group['name']) . '“ wurde gelöscht.</strong>';
                if ($moved > 0) {
                    $text .= ' ' . ($moved === 1 ? 'Ein Benutzer wurde' : pdl_sys_num($moved) . ' Benutzer wurden')
                        . ' in die Gruppe „' . htmlspecialchars($targets[$move_to] ?? '') . '“ verschoben.';
                }
                echo pdl_admin_alert('success', $text) . $back;
                include("footer.inc.php");
                return;
            }
            $db_handler->sql_query('ROLLBACK');
            $error = 'Die Datenbank hat das Löschen abgelehnt. Es wurde nichts geändert.';
        }
        echo pdl_admin_alert('danger', htmlspecialchars($error));
    }

    $text = '<input type="hidden" name="action" value="delete">'
        . '<input type="hidden" name="eugroup_id" value="' . $eugroup_id . '">';
    if ($group['members'] > 0) {
        $options = '';
        foreach ($targets as $id => $target_name) {
            $options .= '<option value="' . $id . '"' . ($id === $move_to ? ' selected' : '') . '>' . htmlspecialchars($target_name) . '</option>';
        }
        $text .= '<p class="mb-2">In der Gruppe sind <strong>' . ($group['members'] === 1 ? 'ein Benutzer' : pdl_sys_num($group['members']) . ' Benutzer') . '</strong>. '
            . 'Damit niemand ohne Gruppe bleibt, werden sie vorher verschoben.</p>'
            . '<label for="pdlUGroupMoveTo" class="form-label">Mitglieder verschieben nach</label>'
            . '<select class="form-select mb-2" id="pdlUGroupMoveTo" name="move_to">' . $options . '</select>'
            . '<p class="form-text mb-0">Die Benutzer erhalten damit die Rechte der gewählten Gruppe.</p>';
    } else {
        $text .= '<p class="mb-0">Die Gruppe hat keine Mitglieder.</p>';
    }
    echo makedialog('Benutzergruppe „' . $group['name'] . '“ löschen?', $text, 'Ja, Gruppe löschen', 'editdelugroup.php', 'editdelugroup.php');
    include("footer.inc.php");
    return;
}

// ---------------------------------------------------------------------------
// Gruppe bearbeiten
// ---------------------------------------------------------------------------
if ($action === 'edit' || $action === 'save') {
    $group = $groups_by_id[$eugroup_id] ?? null;
    $crumbs[] = ['title' => 'Benutzergruppe bearbeiten'];
    pdl_admin_breadcrumb($crumbs);
    echo '<h1 class="h3 pdl-page-title">Benutzergruppe bearbeiten</h1>';

    if ($group === null) {
        echo pdl_admin_alert('warning', 'Diese Benutzergruppe gibt es nicht (mehr).') . $back;
        include("footer.inc.php");
        return;
    }
    if (pdl_sys_group_role($eugroup_id, $settings) === 'admin') {
        echo pdl_admin_alert('info', 'Die Gruppe „' . htmlspecialchars($group['name']) . '“ hat immer alle Rechte und lässt sich nicht ändern. So kann sich niemand versehentlich aus dem Adminbereich aussperren.') . $back;
        include("footer.inc.php");
        return;
    }

    $is_guest = pdl_sys_group_role($eugroup_id, $settings) === 'guest';
    $rights = pdl_sys_rights_list($db_handler, $sql_table);
    $row = $db_handler->sql_fetch_array($db_handler->sql_query('SELECT * FROM ' . $group_t . ' WHERE ugroup_id = ' . $eugroup_id)) ?? [];
    $name = (string) ($row['name'] ?? '');
    $values = [];
    foreach ($rights as $right) {
        $values[$right['variablenname']] = (($row[$right['variablenname']] ?? 'N') === 'Y') ? 'Y' : 'N';
    }

    if ($is_post) {
        $name = trim(pdl_sys_post('name'));
        $values = pdl_sys_rights_from_post($rights, $_POST['rights'] ?? null, $is_guest);
        $errors = [];
        if (!pdl_sys_csrf_ok()) {
            $errors[] = pdl_sys_csrf_error_text();
        }
        if ($name === '') {
            $errors[] = 'Bitte geben Sie einen Namen für die Gruppe ein.';
        } elseif (strlen($name) > 128) {
            $errors[] = 'Der Name darf höchstens 128 Zeichen lang sein.';
        } elseif (pdl_sys_scalar_int($db_handler, 'SELECT COUNT(*) FROM ' . $group_t . " WHERE name = '" . $db_handler->sql_escape_string($name) . "' AND ugroup_id <> " . $eugroup_id) > 0) {
            $errors[] = 'Eine andere Benutzergruppe heißt bereits so. Bitte wählen Sie einen anderen Namen.';
        }
        if ($errors === []) {
            $sets = ["`name` = '" . $db_handler->sql_escape_string($name) . "'"];
            foreach ($values as $var => $value) {
                $sets[] = pdl_sys_ident($var) . " = '" . ($value === 'Y' ? 'Y' : 'N') . "'";
            }
            if (pdl_sys_exec($db_handler, 'UPDATE ' . $group_t . ' SET ' . implode(', ', $sets) . ' WHERE ugroup_id = ' . $eugroup_id)) {
                pdl_audit_log($db_handler, $sql_table, $user_details, 'update', 'usergroup', $eugroup_id);
                echo pdl_admin_alert('success', '<strong>Benutzergruppe „' . htmlspecialchars($name) . '“ wurde gespeichert.</strong> '
                    . '<a class="alert-link" href="editdelugroup.php">Zurück zur Gruppenübersicht</a>');
            } else {
                $errors[] = 'Die Datenbank hat die Änderung abgelehnt. Bitte versuchen Sie es erneut.';
            }
        }
        if ($errors !== []) {
            echo pdl_admin_alert('danger', '<strong>Die Gruppe wurde nicht gespeichert.</strong><br>' . implode('<br>', array_map('htmlspecialchars', $errors)));
        }
    }

    if ($is_guest) {
        echo pdl_admin_alert('info', '<strong>Gastgruppe:</strong> Diese Rechte gelten für nicht angemeldete Besucher. '
            . 'Der Gruppe lassen sich keine Benutzerkonten zuordnen, und Verwaltungsrechte sind für Gäste nicht möglich.');
    } elseif (pdl_sys_group_role($eugroup_id, $settings) === 'member') {
        echo pdl_admin_alert('info', 'In diese Gruppe kommen alle Benutzer, die sich selbst registrieren. Sie kann nicht gelöscht werden.');
    }
    ?>
<form action="editdelugroup.php" method="post" id="pdlUGroupForm" novalidate>
    <?php echo csrf_input(); ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="eugroup_id" value="<?php echo $eugroup_id; ?>">
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Allgemein</h2></header>
        <div class="card-body">
            <label for="pdlEUGName" class="form-label">Name</label>
            <input type="text" id="pdlEUGName" name="name" class="form-control" required maxlength="128" style="max-width: 32rem" value="<?php echo htmlspecialchars($name); ?>">
            <div class="form-text"><?php echo $is_guest ? 'Gilt für nicht angemeldete Besucher.' : 'Mitglieder: ' . pdl_sys_num($group['members']) . '. Benutzer ordnen Sie in der Benutzerliste über „bearbeiten“ zu.'; ?></div>
        </div>
    </section>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Rechte</h2></header>
        <div class="card-body">
            <?php echo pdl_sys_rights_switches_html($rights, $values, $is_guest); ?>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <?php if (!in_array($eugroup_id, $protected_ids, true)) { ?>
        <a href="editdelugroup.php?action=delete&amp;eugroup_id=<?php echo $eugroup_id; ?>" class="btn btn-outline-danger me-md-auto" id="pdlUGroupDelete">Gruppe löschen …</a>
        <?php } ?>
        <a href="editdelugroup.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlUGroupSave">Benutzergruppe speichern</button>
    </div>
</form>
    <?php
    echo pdl_sys_rights_switches_script();
    include("footer.inc.php");
    return;
}

// ---------------------------------------------------------------------------
// Übersicht
// ---------------------------------------------------------------------------
$crumbs[] = ['title' => 'Übersicht'];
pdl_admin_breadcrumb($crumbs);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 pdl-page-title mb-0">Benutzergruppen</h1>
    <a class="btn btn-primary btn-sm" href="addugroup.php" id="pdlUGroupNew">Neue Benutzergruppe</a>
</div>
<p class="text-muted">Jedes Benutzerkonto gehört zu genau einer Gruppe. Die Gruppe bestimmt, was der Benutzer darf.</p>
<section class="card pdl-card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle" id="pdlUGroupTable">
            <thead>
                <tr><th scope="col">Name</th><th scope="col" class="text-end">Mitglieder</th><th scope="col">Adminbereich</th><th scope="col" class="text-end">Aktionen</th></tr>
            </thead>
            <tbody>
            <?php foreach ($groups as $group) {
                $id = $group['ugroup_id'];
                $role = pdl_sys_group_role($id, $settings);
            ?>
                <tr id="pdlUGroupRow_<?php echo $id; ?>">
                    <td><strong><?php echo htmlspecialchars($group['name']); ?></strong><?php echo $role_badge($id); ?></td>
                    <td class="text-end"><?php echo $role === 'guest' ? '–' : pdl_sys_num($group['members']); ?></td>
                    <td><?php echo $group['adminaccess'] === 'Y' ? 'ja' : 'nein'; ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <?php if ($role !== 'admin') { ?>
                            <a class="btn btn-outline-light" href="editdelugroup.php?eugroup_id=<?php echo $id; ?>" id="pdlUGroupEdit_<?php echo $id; ?>">bearbeiten</a>
                            <?php } ?>
                            <?php if ($role === '' ) { ?>
                            <a class="btn btn-outline-danger" href="editdelugroup.php?action=delete&amp;eugroup_id=<?php echo $id; ?>" id="pdlUGroupDel_<?php echo $id; ?>">löschen</a>
                            <?php } ?>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            <?php if ($groups === []) { ?>
                <tr><td colspan="4" class="text-muted">Keine Benutzergruppen vorhanden.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</section>
<?php
include("footer.inc.php");
