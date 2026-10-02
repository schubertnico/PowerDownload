<?php
/**
 * PowerDownload - Benutzer bearbeiten (Benutzername, E-Mail, Homepage,
 * Newsletter, Benutzergruppe). Aufruf aus der Benutzerliste (users.php).
 */
include("header.inc.php");
include_once("system_helpers.inc.php");

if (!pdl_sys_can($user_rights, 'edituser')) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('edituser'));
    include("footer.inc.php");
    return;
}

$edit_id = (int) ($_POST['user_id'] ?? ($_GET['user_id'] ?? 0));
$current_user_id = (int) ($user_details['user_id'] ?? 0);
$guest_group_id = pdl_sys_guest_group_id($settings);

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Benutzer', 'href' => 'users.php'],
    ['title' => 'Benutzer bearbeiten'],
]);
echo '<h1 class="h3 pdl-page-title">Benutzer bearbeiten</h1>';

if ($edit_id <= 0) {
    echo pdl_admin_alert('info', 'Bitte wählen Sie den Benutzer in der Benutzerliste aus und klicken Sie dort auf „bearbeiten“.');
    echo '<a class="btn btn-primary" href="users.php" id="pdlEUBack">Zur Benutzerliste</a>';
    include("footer.inc.php");
    return;
}

$user_t = pdl_sys_ident($sql_table['user']);
$getuser = $db_handler->sql_fetch_array($db_handler->sql_query('SELECT * FROM ' . $user_t . ' WHERE user_id = ' . $edit_id));
if ($getuser === null) {
    echo pdl_admin_alert('warning', 'Diesen Benutzer gibt es nicht (mehr). Vielleicht wurde er inzwischen gelöscht.');
    echo '<a class="btn btn-primary" href="users.php" id="pdlEUBack">Zur Benutzerliste</a>';
    include("footer.inc.php");
    return;
}
if ($edit_id === 1) {
    echo pdl_admin_alert('warning', 'Der Hauptadministrator (Benutzer Nr. 1) ist geschützt und kann hier nicht geändert werden. Sein Passwort und seine E-Mail-Adresse ändert er selbst im Profil.');
    echo '<a class="btn btn-primary" href="users.php" id="pdlEUBack">Zur Benutzerliste</a>';
    include("footer.inc.php");
    return;
}

$groups = pdl_sys_groups($db_handler, $sql_table);
$group_ids = [];
foreach ($groups as $group) {
    if ($group['ugroup_id'] !== $guest_group_id) {
        $group_ids[] = $group['ugroup_id'];
    }
}
$is_self = $edit_id === $current_user_id;

// Formularwerte: aus der Datenbank bzw. aus dem abgeschickten Formular
$form = [
    'nick' => (string) $getuser['nick'],
    'email' => (string) $getuser['email'],
    'homepage' => pdl_sys_normalize_homepage((string) $getuser['homepage']),
    'get_letter' => (string) $getuser['get_letter'] === 'Y' ? 'Y' : 'N',
    'ugroup_id' => (int) $getuser['ugroup_id'],
];
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $form['nick'] = trim(pdl_sys_post('nick'));
    $form['email'] = trim(pdl_sys_post('email'));
    $form['homepage'] = pdl_sys_normalize_homepage(pdl_sys_post('homepage'));
    $form['get_letter'] = pdl_sys_post('get_letter') === 'Y' ? 'Y' : 'N';
    if (!$is_self) {
        $form['ugroup_id'] = (int) pdl_sys_post('nugroup_id', '0');
    }

    if (!pdl_sys_csrf_ok()) {
        $errors['_csrf'] = pdl_sys_csrf_error_text();
    }
    if ($form['nick'] === '') {
        $errors['nick'] = 'Bitte geben Sie einen Benutzernamen ein.';
    } elseif (strlen($form['nick']) > 64) {
        $errors['nick'] = 'Der Benutzername darf höchstens 64 Zeichen lang sein.';
    } else {
        $dupe = pdl_sys_scalar_int($db_handler, 'SELECT COUNT(*) FROM ' . $user_t . " WHERE nick = '"
            . $db_handler->sql_escape_string($form['nick']) . "' AND user_id <> " . $edit_id);
        if ($dupe > 0) {
            $errors['nick'] = 'Diesen Benutzernamen verwendet bereits ein anderes Konto.';
        }
    }
    if ($form['email'] === '' || filter_var($form['email'], FILTER_VALIDATE_EMAIL) === false || strlen($form['email']) > 128) {
        $errors['email'] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein, z. B. name@example.org.';
    }
    $url_error = pdl_validate_url_optional($form['homepage']);
    if ($url_error !== null || strlen($form['homepage']) > 128) {
        $errors['homepage'] = 'Bitte geben Sie eine gültige Adresse ein, die mit https:// oder http:// beginnt (höchstens 128 Zeichen), oder lassen Sie das Feld leer.';
    }
    if (!in_array($form['ugroup_id'], $group_ids, true)) {
        $errors['nugroup_id'] = 'Bitte wählen Sie eine vorhandene Benutzergruppe.';
    }

    if ($errors === []) {
        $ok = pdl_sys_exec($db_handler, 'UPDATE ' . $user_t . " SET nick = '" . $db_handler->sql_escape_string($form['nick'])
            . "', email = '" . $db_handler->sql_escape_string($form['email'])
            . "', homepage = '" . $db_handler->sql_escape_string($form['homepage'])
            . "', get_letter = '" . $form['get_letter']
            . "', ugroup_id = " . $form['ugroup_id']
            . ' WHERE user_id = ' . $edit_id);
        if ($ok) {
            pdl_audit_log($db_handler, $sql_table, $user_details, 'update', 'user', $edit_id);
            echo pdl_admin_alert('success', '<strong>Benutzer „' . htmlspecialchars($form['nick']) . '“ wurde gespeichert.</strong> '
                . '<a class="alert-link" href="users.php" id="pdlEUSavedBack">Zurück zur Benutzerliste</a>');
        } else {
            echo pdl_admin_alert('danger', '<strong>Der Benutzer wurde nicht gespeichert.</strong> Die Datenbank hat die Änderung abgelehnt. Bitte prüfen Sie die Eingaben und versuchen Sie es erneut.');
        }
    } else {
        echo pdl_admin_alert('danger', '<strong>Bitte korrigieren Sie die markierten Felder.</strong>'
            . (isset($errors['_csrf']) ? '<br>' . htmlspecialchars($errors['_csrf']) : ''));
    }
}

$invalid = static fn (string $key): string => isset($errors[$key]) ? ' is-invalid' : '';
$feedback = static fn (string $key): string => isset($errors[$key]) ? '<div class="invalid-feedback">' . htmlspecialchars($errors[$key]) . '</div>' : '';
?>
<?php echo pdl_sys_switch_style(); ?>
<form action="edituser.php" method="post" id="pdlEUForm" novalidate>
    <?php echo csrf_input(); ?>
    <input type="hidden" name="user_id" value="<?php echo $edit_id; ?>">
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Benutzerdaten</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlEUNick" class="form-label">Benutzername</label>
                <input type="text" id="pdlEUNick" name="nick" class="form-control<?php echo $invalid('nick'); ?>" required maxlength="64" style="max-width: 32rem" value="<?php echo htmlspecialchars($form['nick']); ?>" aria-describedby="pdlEUNickHelp">
                <?php echo $feedback('nick'); ?>
                <div class="form-text" id="pdlEUNickHelp">Mit diesem Namen meldet sich der Benutzer an. Er muss eindeutig sein.</div>
            </div>
            <div class="mb-3">
                <label for="pdlEUEmail" class="form-label">E-Mail-Adresse</label>
                <input type="email" id="pdlEUEmail" name="email" class="form-control<?php echo $invalid('email'); ?>" required maxlength="128" style="max-width: 32rem" value="<?php echo htmlspecialchars($form['email']); ?>" aria-describedby="pdlEUEmailHelp">
                <?php echo $feedback('email'); ?>
                <div class="form-text" id="pdlEUEmailHelp">An diese Adresse gehen Newsletter und Mails aus „Passwort vergessen“.</div>
            </div>
            <div class="mb-3">
                <label for="pdlEUHomepage" class="form-label">Homepage</label>
                <input type="url" id="pdlEUHomepage" name="homepage" class="form-control<?php echo $invalid('homepage'); ?>" maxlength="128" style="max-width: 32rem" placeholder="https://" value="<?php echo htmlspecialchars($form['homepage']); ?>" aria-describedby="pdlEUHomepageHelp">
                <?php echo $feedback('homepage'); ?>
                <div class="form-text" id="pdlEUHomepageHelp">Optional. Adressen ohne https:// werden automatisch ergänzt, https:// bleibt erhalten.</div>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="pdlEUGetLetter" name="get_letter" value="Y"<?php echo $form['get_letter'] === 'Y' ? ' checked' : ''; ?> aria-describedby="pdlEUGetLetterHelp">
                <label class="form-check-label" for="pdlEUGetLetter">Newsletter erhalten</label>
                <div class="form-text" id="pdlEUGetLetterHelp">Den Newsletter erhalten nur Benutzer, die ihn bestellt haben. Ändern Sie diese Einstellung nur auf Wunsch des Benutzers.</div>
            </div>
            <div class="mb-1">
                <label for="pdlEUUGroup" class="form-label">Benutzergruppe</label>
                <select id="pdlEUUGroup" name="nugroup_id" class="form-select<?php echo $invalid('nugroup_id'); ?>" style="max-width: 32rem" aria-describedby="pdlEUUGroupHelp"<?php echo $is_self ? ' disabled' : ''; ?>>
                    <?php
                    foreach ($groups as $group) {
                        if ($group['ugroup_id'] === $guest_group_id) {
                            continue;
                        }
                        $admin = $group['adminaccess'] === 'Y';
                        echo '<option value="' . $group['ugroup_id'] . '" data-adminaccess="' . ($admin ? '1' : '0') . '"'
                            . ($group['ugroup_id'] === $form['ugroup_id'] ? ' selected' : '') . '>'
                            . htmlspecialchars($group['name']) . ($admin ? ' (mit Admin-Zugang)' : '') . '</option>';
                    }
                    if ($form['ugroup_id'] > 0 && !in_array($form['ugroup_id'], $group_ids, true)) {
                        echo '<option value="' . $form['ugroup_id'] . '" selected>(keine gültige Gruppe – bitte wählen)</option>';
                    }
                    ?>
                </select>
                <?php echo $feedback('nugroup_id'); ?>
                <div class="form-text" id="pdlEUUGroupHelp">
                    <?php if ($is_self) { ?>
                        Ihre eigene Gruppe können Sie hier nicht ändern, damit Sie sich nicht versehentlich aus dem Adminbereich aussperren.
                    <?php } else { ?>
                        Die Gruppe bestimmt, was der Benutzer darf.<?php
                        foreach ($groups as $group) {
                            if ($group['ugroup_id'] === 1) {
                                echo ' Neue Registrierungen landen in der Gruppe „' . htmlspecialchars($group['name']) . '“.';
                            }
                        } ?>
                    <?php } ?>
                </div>
                <?php
                $selected_admin = false;
                foreach ($groups as $group) {
                    if ($group['ugroup_id'] === $form['ugroup_id'] && $group['adminaccess'] === 'Y') {
                        $selected_admin = true;
                    }
                }
                ?>
                <div class="alert alert-warning small py-2 mt-2<?php echo ($is_self || !$selected_admin) ? ' d-none' : ''; ?>" id="pdlEUAdminWarn" role="status">
                    Diese Gruppe hat Zugang zum Adminbereich. Weisen Sie sie nur Personen zu, denen Sie die Verwaltung anvertrauen.
                </div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="users.php" class="btn btn-outline-light" id="pdlEUBack">Zurück zur Benutzerliste</a>
        <button type="submit" class="btn btn-primary" id="pdlEUSave">Änderungen speichern</button>
    </div>
</form>
<script>
(function () {
    var select = document.getElementById('pdlEUUGroup');
    var warn = document.getElementById('pdlEUAdminWarn');
    if (!select || !warn || select.disabled) return;
    function sync() {
        var opt = select.options[select.selectedIndex];
        warn.classList.toggle('d-none', !opt || opt.getAttribute('data-adminaccess') !== '1');
    }
    select.addEventListener('change', sync);
    sync();
})();
</script>
<?php
include("footer.inc.php");
