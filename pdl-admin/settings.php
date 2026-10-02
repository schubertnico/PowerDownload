<?php
/**
 * PowerDownload - Einstellungen
 *
 * Eine Seite mit Reitern je Einstellungsgruppe. Eingabeart je Einstellung
 * (Spalte „eingabe“): Schalter, Zahl, Auswahlliste, E-Mail, URL, Passwort,
 * Text. Gespeichert werden nur bekannte Einstellungen, nur geänderte Werte
 * und nur nach erfolgreicher Prüfung.
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

// Pflichtfelder (ohne sie funktioniert PowerDownload nicht)
$required = ['script_file', 'date_format', 'mail_fromaddr'];

/**
 * Einstellungen nach Gruppe.
 *
 * @return array{groups: list<array{sgroup_id: int, name: string}>, rows: array<string, array{setting_id: int, variablenname: string, name: string, bez: string, wert: string, eingabe: string, sgroup_id: int}>}
 */
$load = static function () use ($db_handler, $settings_t, $sgroup_t): array {
    $groups = [];
    $res = $db_handler->sql_query('SELECT sgroup_id, name FROM ' . $sgroup_t . ' ORDER BY reihenfolge ASC, sgroup_id ASC');
    while ($row = $db_handler->sql_fetch_array($res)) {
        $groups[] = ['sgroup_id' => (int) $row['sgroup_id'], 'name' => (string) $row['name']];
    }
    $rows = [];
    $res = $db_handler->sql_query('SELECT setting_id, variablenname, name, bez, wert, eingabe, sgroup_id FROM ' . $settings_t
        . ' WHERE sgroup_id > 0 ORDER BY reihenfolge ASC, setting_id ASC');
    while ($row = $db_handler->sql_fetch_array($res)) {
        $rows[(string) $row['variablenname']] = [
            'setting_id' => (int) $row['setting_id'],
            'variablenname' => (string) $row['variablenname'],
            'name' => (string) $row['name'],
            'bez' => (string) $row['bez'],
            'wert' => (string) $row['wert'],
            'eingabe' => (string) $row['eingabe'],
            'sgroup_id' => (int) $row['sgroup_id'],
        ];
    }
    return ['groups' => $groups, 'rows' => $rows];
};

$data = $load();
$values = [];
foreach ($data['rows'] as $var => $row) {
    $values[$var] = $row['wert'];
}
$errors = [];
$notice = '';
$active_tab = (int) pdl_sys_post('active_tab', pdl_sys_get('gruppe', '0'));

// Gastgruppe als Auswahl der Benutzergruppen (ohne Admin-Zugang und ohne
// Mitglieder), die Adresse der Download-Seite als URL-Feld – auch wenn die
// Spalte „eingabe“ in älteren Datenbanken noch „input“ lautet.
$guest_options = ['0' => '(keine – nicht angemeldete Besucher dürfen nur herunterladen)'];
foreach (pdl_sys_groups($db_handler, $sql_table) as $group) {
    $is_current = (string) $group['ugroup_id'] === ($values['guest_group_id'] ?? '');
    if ($group['adminaccess'] !== 'Y' && ($group['members'] === 0 || $is_current)) {
        $guest_options[(string) $group['ugroup_id']] = $group['name'] . ' (Nr. ' . $group['ugroup_id'] . ')';
    }
}
/** @param array{eingabe: string, variablenname: string} $row */
$parse_for = static function (array $row) use ($guest_options): array {
    $parsed = pdl_sys_parse_eingabe($row['eingabe']);
    if ($row['variablenname'] === 'guest_group_id' && in_array($parsed['type'], ['input', 'zahl'], true)) {
        return ['type' => 'auswahl', 'options' => $guest_options, 'legacy' => false];
    }
    if ($row['variablenname'] === 'site_url' && $parsed['type'] === 'input') {
        return ['type' => 'url', 'options' => [], 'legacy' => false];
    }
    return $parsed;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $posted = is_array($_POST['setting'] ?? null) ? $_POST['setting'] : [];
    $changes = [];
    if (!pdl_sys_csrf_ok()) {
        $notice = pdl_admin_alert('danger', pdl_sys_csrf_error_text() . ' Ihre Eingaben wurden nicht gespeichert.');
    } else {
        foreach ($data['rows'] as $var => $row) {
            if (!array_key_exists($var, $posted) || !is_string($posted[$var])) {
                continue;
            }
            $parsed = $parse_for($row);
            $new = $parsed['type'] === 'textarea' ? str_replace("\r\n", "\n", $posted[$var]) : trim($posted[$var]);
            if ($parsed['type'] === 'passwort' && $new === '') {
                continue; // leer = gespeichertes Passwort behalten
            }
            if ($new === str_replace("\r\n", "\n", $row['wert'])) {
                continue;
            }
            $values[$var] = $new;
            $error = pdl_sys_validate_setting($parsed, $new, in_array($var, $required, true));
            if ($error !== null) {
                $errors[$var] = $error;
                continue;
            }
            $changes[$var] = $new;
        }

        if ($errors !== []) {
            $notice = pdl_admin_alert('danger', '<strong>Es wurde nichts gespeichert.</strong> Bitte korrigieren Sie '
                . (count($errors) === 1 ? 'das markierte Feld' : 'die ' . count($errors) . ' markierten Felder') . '.');
        } elseif ($changes === []) {
            $notice = pdl_admin_alert('info', 'Sie haben nichts geändert. Es wurde nichts gespeichert.');
        } else {
            $failed = [];
            foreach ($changes as $var => $new) {
                $ok = pdl_sys_exec($db_handler, 'UPDATE ' . $settings_t . " SET wert = '" . $db_handler->sql_escape_string($new)
                    . "' WHERE setting_id = " . $data['rows'][$var]['setting_id']);
                if ($ok) {
                    $settings[$var] = $new;
                } else {
                    $failed[] = $data['rows'][$var]['name'];
                }
            }
            if ($failed === []) {
                pdl_audit_log($db_handler, $sql_table, $user_details, 'update', 'settings', count($changes));
                $names = [];
                foreach (array_keys($changes) as $var) {
                    $names[] = '„' . htmlspecialchars($data['rows'][$var]['name']) . '“';
                }
                $notice = pdl_admin_alert('success', '<strong>Die Einstellungen wurden gespeichert.</strong> Geändert: ' . implode(', ', $names) . '.');
            } else {
                $notice = pdl_admin_alert('danger', '<strong>Nicht alle Einstellungen wurden gespeichert.</strong> Fehlgeschlagen: '
                    . htmlspecialchars(implode(', ', $failed)) . '. Bitte versuchen Sie es erneut.');
            }
            $data = $load();
            foreach ($data['rows'] as $var => $row) {
                $values[$var] = $row['wert'];
            }
        }
    }
}

// Aktiven Reiter bestimmen: gewünschter, sonst erster mit Fehler, sonst erster
$group_ids = array_map(static fn (array $g): int => $g['sgroup_id'], $data['groups']);
if ($errors !== []) {
    $first_error = array_key_first($errors);
    $active_tab = $data['rows'][$first_error]['sgroup_id'] ?? $active_tab;
}
if (!in_array($active_tab, $group_ids, true)) {
    $active_tab = $group_ids[0] ?? 0;
}

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'System'],
    ['title' => 'Einstellungen'],
]);
echo '<h1 class="h3 pdl-page-title">Einstellungen</h1>';
echo '<div id="pdlSettingsNotice">' . $notice . '</div>';
echo pdl_sys_switch_style();
?>
<style>
.pdl-settings-savebar { position: sticky; bottom: 0; z-index: 1020; background: var(--pdl-admin-surface, #1e1e1e); border-top: 2px solid var(--pdl-admin-accent, #9b0000); padding: .6rem .75rem; margin: 1.5rem -12px 0 -12px; }
.pdl-settings-tabs .nav-link { cursor: pointer; }
.pdl-setting { padding-bottom: 1.25rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--pdl-admin-border, rgba(255,255,255,.12)); }
.pdl-setting:last-child { border-bottom: 0; margin-bottom: 0; padding-bottom: 0; }
</style>
<form action="settings.php" method="post" id="pdlSettingsForm" novalidate>
    <?php echo csrf_input(); ?>
    <input type="hidden" name="active_tab" id="pdlSettingsActiveTab" value="<?php echo $active_tab; ?>">
    <nav aria-label="Einstellungsgruppen" class="mb-3">
        <ul class="nav nav-pills flex-wrap gap-2 pdl-settings-tabs" id="pdlSettingsTabs" role="tablist">
            <?php foreach ($data['groups'] as $group) {
                $gid = $group['sgroup_id'];
                $active = $gid === $active_tab;
                $count_errors = 0;
                foreach ($errors as $var => $unused) {
                    if (($data['rows'][$var]['sgroup_id'] ?? 0) === $gid) {
                        $count_errors++;
                    }
                }
            ?>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link<?php echo $active ? ' active' : ' bg-secondary-subtle'; ?>" id="pdlSettingsTab_<?php echo $gid; ?>"
                        data-bs-toggle="pill" data-bs-target="#sgroup_<?php echo $gid; ?>" data-sgroup="<?php echo $gid; ?>"
                        role="tab" aria-controls="sgroup_<?php echo $gid; ?>" aria-selected="<?php echo $active ? 'true' : 'false'; ?>">
                    <?php echo htmlspecialchars($group['name']); ?><?php if ($count_errors > 0) { ?> <span class="badge text-bg-danger"><?php echo $count_errors; ?></span><?php } ?>
                </button>
            </li>
            <?php } ?>
        </ul>
    </nav>

    <div class="tab-content">
    <?php foreach ($data['groups'] as $group) {
        $gid = $group['sgroup_id'];
        $active = $gid === $active_tab;
    ?>
        <section class="card pdl-card mb-4 tab-pane fade<?php echo $active ? ' show active' : ''; ?>" id="sgroup_<?php echo $gid; ?>" role="tabpanel" aria-labelledby="pdlSettingsTab_<?php echo $gid; ?>" data-sgroup-name="<?php echo htmlspecialchars($group['name']); ?>">
            <header class="card-header"><h2 class="h5 mb-0"><?php echo htmlspecialchars($group['name']); ?></h2></header>
            <div class="card-body">
            <?php
            $count = 0;
            foreach ($data['rows'] as $var => $row) {
                if ($row['sgroup_id'] !== $gid) {
                    continue;
                }
                $count++;
                $parsed = $parse_for($row);
                $field_id = 'setting_' . $var;
                $help_id = $field_id . '_help';
                $is_switch = $parsed['type'] === 'anaus';
                echo '<div class="pdl-setting" id="block_' . htmlspecialchars($field_id) . '">';
                if ($is_switch) {
                    echo '<div class="form-label fw-bold mb-1">' . htmlspecialchars($row['name']) . '</div>';
                } else {
                    echo '<label for="' . htmlspecialchars($field_id) . '" class="form-label fw-bold">' . htmlspecialchars($row['name']) . '</label>';
                }
                echo pdl_sys_setting_field_html($var, $parsed, $values[$var] ?? $row['wert'], $help_id, isset($errors[$var]), $row['wert'] !== '');
                if (isset($errors[$var])) {
                    echo '<div class="invalid-feedback d-block">' . htmlspecialchars($errors[$var]) . '</div>';
                }
                $help = $row['bez'];
                if ($parsed['type'] === 'passwort') {
                    $help .= ($help !== '' ? ' ' : '') . ($row['wert'] !== '' ? 'Ein Passwort ist gespeichert. Lassen Sie das Feld leer, um es zu behalten.' : 'Derzeit ist kein Passwort gespeichert.');
                }
                if ($parsed['legacy']) {
                    $help .= ' (Eigene Eingabeart aus einer älteren Version; wird als Textfeld angezeigt.)';
                }
                echo '<div id="' . htmlspecialchars($help_id) . '" class="form-text">' . htmlspecialchars(trim($help)) . '</div>';
                echo '</div>';
            }
            if ($count === 0) {
                echo '<p class="text-muted mb-0">Diese Gruppe enthält keine Einstellungen.</p>';
            }
            ?>
            </div>
        </section>
    <?php } ?>
    </div>

    <div class="pdl-settings-savebar d-flex flex-wrap gap-2 align-items-center">
        <span class="form-text small mb-0">Gespeichert werden alle Reiter auf einmal.</span>
        <div class="ms-auto d-flex gap-2">
            <a href="settings.php" class="btn btn-outline-light btn-sm" id="pdlSettingsCancel">Änderungen verwerfen</a>
            <button type="submit" class="btn btn-primary" id="pdlSettingsSave">Einstellungen speichern</button>
        </div>
    </div>
</form>
<script>
(function () {
    var hidden = document.getElementById('pdlSettingsActiveTab');
    document.querySelectorAll('#pdlSettingsTabs [data-sgroup]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#pdlSettingsTabs .nav-link').forEach(function (b) { b.classList.add('bg-secondary-subtle'); });
            btn.classList.remove('bg-secondary-subtle');
            if (hidden) hidden.value = btn.getAttribute('data-sgroup');
            if (!window.bootstrap) {
                // Ohne Bootstrap-Skript: Reiter selbst umschalten
                document.querySelectorAll('.tab-pane').forEach(function (p) { p.classList.remove('show', 'active'); });
                document.querySelectorAll('#pdlSettingsTabs .nav-link').forEach(function (b) { b.classList.remove('active'); });
                var pane = document.querySelector(btn.getAttribute('data-bs-target'));
                if (pane) pane.classList.add('show', 'active');
                btn.classList.add('active');
            }
        });
    });
    // Sprung per Anker (z. B. settings.php#sgroup_7) öffnet den Reiter
    if (location.hash && /^#sgroup_\d+$/.test(location.hash)) {
        var target = document.querySelector('#pdlSettingsTabs [data-bs-target="' + location.hash + '"]');
        if (target) target.click();
    }
    // Beschriftung der Schalter: An / Aus
    document.querySelectorAll('.pdl-setting-switch input[type="checkbox"]').forEach(function (inp) {
        var label = inp.parentNode.querySelector('label[data-on]');
        if (!label) return;
        inp.addEventListener('change', function () {
            label.textContent = inp.checked ? label.getAttribute('data-on') : label.getAttribute('data-off');
        });
    });
})();
</script>
<?php
include("footer.inc.php");
