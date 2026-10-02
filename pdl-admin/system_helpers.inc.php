<?php

/**
 * PowerDownload - Hilfsfunktionen für die System-Seiten des Adminbereichs
 *
 * Benutzer, Benutzergruppen, Benutzerrechte, Einstellungen, Vorlagen,
 * Newsletter, Sicherung und Wartung. Die Funktionen geben nichts aus und
 * lassen sich deshalb in Unit-Tests mit einer Attrappe der Datenbankklasse
 * prüfen (tests/Unit/SystemHelpersTest.php).
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Anfragewerte
// ---------------------------------------------------------------------------

/**
 * Liefert einen POST-Wert als Zeichenkette (Arrays und Fehlendes ergeben $default).
 */
function pdl_sys_post(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? null;
    return is_string($value) ? $value : $default;
}

/**
 * Liefert einen GET-Wert als Zeichenkette (Arrays und Fehlendes ergeben $default).
 */
function pdl_sys_get(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? null;
    return is_string($value) ? $value : $default;
}

/**
 * Prüft das CSRF-Token aus dem POST-Formular.
 */
function pdl_sys_csrf_ok(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && csrf_verify(pdl_sys_post('csrf_token'));
}

/**
 * Einheitlicher Text, wenn das CSRF-Token fehlt oder abgelaufen ist.
 */
function pdl_sys_csrf_error_text(): string
{
    return 'Sicherheits-Token ungültig oder abgelaufen. Bitte laden Sie die Seite neu und versuchen Sie es noch einmal.';
}

// ---------------------------------------------------------------------------
// Datenbank
// ---------------------------------------------------------------------------

/**
 * Bezeichner (Tabelle, Spalte) in Backticks. Erlaubt sind nur Buchstaben,
 * Ziffern und Unterstrich; alles andere ist ein Programmierfehler.
 */
function pdl_sys_ident(string $name): string
{
    if (preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,63}$/', $name) !== 1) {
        throw new InvalidArgumentException('Ungültiger SQL-Bezeichner: ' . $name);
    }
    return '`' . $name . '`';
}

/**
 * Letzte Fehlermeldung der Datenbank ('' ohne echte Verbindung).
 *
 * @param pdl_db_class $db
 */
function pdl_sys_db_error($db): string
{
    $handler = $db->handler ?? null;
    if ($handler instanceof mysqli) {
        return mysqli_error($handler);
    }
    return '';
}

/**
 * Anzahl der von der letzten Anweisung geänderten Zeilen (-1 ohne Verbindung).
 *
 * @param pdl_db_class $db
 */
function pdl_sys_affected_rows($db): int
{
    $handler = $db->handler ?? null;
    if ($handler instanceof mysqli) {
        return (int) mysqli_affected_rows($handler);
    }
    return -1;
}

/**
 * Führt eine schreibende Anweisung aus; Fehler landen im PHP-Fehlerlog.
 *
 * @param pdl_db_class $db
 */
function pdl_sys_exec($db, string $sql): bool
{
    $result = $db->sql_query($sql);
    if ($result === false) {
        error_log('PowerDownload SQL-Fehler: ' . pdl_sys_db_error($db) . ' | ' . substr($sql, 0, 500));
        return false;
    }
    return true;
}

/**
 * Erste Spalte der ersten Zeile als Ganzzahl (für COUNT/SUM/MAX).
 *
 * @param pdl_db_class $db
 */
function pdl_sys_scalar_int($db, string $sql): int
{
    $row = $db->sql_fetch_array($db->sql_query($sql));
    if (!is_array($row)) {
        return 0;
    }
    $value = $row[0] ?? reset($row);
    return (int) $value;
}

/**
 * Spaltennamen einer Tabelle.
 *
 * @param pdl_db_class $db
 * @return list<string>
 */
function pdl_sys_table_columns($db, string $table): array
{
    $res = $db->sql_query('SHOW COLUMNS FROM ' . pdl_sys_ident($table));
    $columns = [];
    while ($row = $db->sql_fetch_array($res)) {
        $field = (string) ($row['Field'] ?? ($row[0] ?? ''));
        if ($field !== '') {
            $columns[] = $field;
        }
    }
    return $columns;
}

/**
 * Nächste freie Position (MAX(reihenfolge)+1), optional innerhalb einer Gruppe.
 *
 * @param pdl_db_class $db
 */
function pdl_sys_next_position($db, string $table, string $group_column = '', int $group_id = 0): int
{
    $sql = 'SELECT COALESCE(MAX(reihenfolge), 0) FROM ' . pdl_sys_ident($table);
    if ($group_column !== '') {
        $sql .= ' WHERE ' . pdl_sys_ident($group_column) . ' = ' . $group_id;
    }
    return pdl_sys_scalar_int($db, $sql) + 1;
}

// ---------------------------------------------------------------------------
// Ausgelieferte Einträge (für Schutzlisten)
// ---------------------------------------------------------------------------

/**
 * Schlüssel, die PowerDownload selbst ausliefert. Quelle ist
 * pdl-inc/pdl3_schema.sql; dazu kommen feste Listen, damit der Schutz auch
 * greift, wenn die Datei fehlt.
 *
 * $what: settings | template | rights | settingsgroup | templategroup
 *
 * @return list<string>
 */
function pdl_sys_shipped_keys(string $what): array
{
    static $cache = [];
    if (isset($cache[$what])) {
        return $cache[$what];
    }

    $fallback = [
        'settings' => [
            'script_file', 'date_format', 'dlspeed', 'perpage', 'orderby', 'orderseq', 'enable_treeview',
            'enable_extrernadmin', 'referer_check', 'spages', 'enable_comments', 'captcha_enabled',
            'allowed_referer', 'enable_search', 'trenn_durch', 'trenn_zeichen', 'trenn_string', 'bb_code',
            'smilies', 'badwords_comments', 'badwords_releases', 'glossary', 'html_releases', 'html_comments',
            'mail_fromname', 'mail_fromaddr', 'screen_autosize', 'screen_size', 'screen_verhalt', 'ftp_on',
            'ftp_server', 'ftp_user', 'ftp_passwort', 'ftp_server_url', 'top_count', 'lastletter', 'installed',
            'site_description', 'site_url', 'site_name', 'sitename', 'guest_group_id',
        ],
        'template' => [
            'all_width', 'header_bg', 'table_border', 'alt_1', 'alt_2', 'footer_bg', 'ordner_row', 'ordner_box',
            'dfiles_row', 'file_detail', 'release_row', 'release_box', 'own_footer', 'ulogin_form', 'ulost_form',
            'uregister_form', 'uprofil_form', 'stats', 'top_row', 'top_box', 'flop_row', 'flop_box', 'latest_row',
            'latest_box', 'rated_row', 'rated_box', 'mail_lost1', 'mail_lost2', 'mail_register', 'comments',
            'comments_form',
        ],
        'rights' => pdl_sys_core_rights(),
        'settingsgroup' => ['1', '2', '3', '4', '5', '6', '7', '8', '9'],
        'templategroup' => ['3', '4', '5'],
    ];
    $keys = $fallback[$what] ?? [];

    $file = dirname(__DIR__) . '/pdl-inc/pdl3_schema.sql';
    $schema = is_file($file) ? (string) file_get_contents($file) : '';
    if ($schema !== '') {
        $matches = [];
        if ($what === 'settings' || $what === 'template') {
            preg_match_all('/^INSERT INTO `pdl3_' . $what . '` \([^)]*\) VALUES \(\d+,\'([a-z0-9_]+)\'/m', $schema, $matches);
        } elseif ($what === 'rights') {
            preg_match_all("/^INSERT INTO `pdl3_rights` \\([^)]*\\) VALUES \\(\\d+,'(?:[^'\\\\]|\\\\.)*','(?:[^'\\\\]|\\\\.)*','([a-z_]+)'/m", $schema, $matches);
        } elseif ($what === 'settingsgroup' || $what === 'templategroup') {
            preg_match_all('/^INSERT INTO `pdl3_' . $what . '` \([^)]*\) VALUES \((\d+),/m', $schema, $matches);
        }
        foreach ($matches[1] ?? [] as $key) {
            $keys[] = $key;
        }
    }

    $cache[$what] = array_values(array_unique($keys));
    return $cache[$what];
}

// ---------------------------------------------------------------------------
// Rechte
// ---------------------------------------------------------------------------

/**
 * Die 18 mitgelieferten Rechte (Spalten von pdl3_usergroup).
 *
 * @return list<string>
 */
function pdl_sys_core_rights(): array
{
    return [
        'download', 'vote', 'addcomments', 'addfiles', 'adminaccess', 'editfiles', 'delfiles', 'adddirs',
        'editdirs', 'deldirs', 'adduser', 'edituser', 'deluser', 'settings', 'templates', 'replacements',
        'backup', 'comment',
    ];
}

/**
 * Rechte für den öffentlichen Bereich (wirken auch ohne Admin-Zugang).
 *
 * @return list<string>
 */
function pdl_sys_public_rights(): array
{
    return ['download', 'vote', 'addcomments'];
}

/**
 * Rechte, die faktisch Administratorrechte sind.
 *
 * @return array<string, string> Recht => Begründung
 */
function pdl_sys_admin_like_rights(): array
{
    return [
        'addfiles' => 'kann Dateien auf Ihren Webspace laden, bei eingerichtetem FTP in beliebige Verzeichnisse, auch HTML und JavaScript',
        'edituser' => 'kann jedes Konto in die Gruppe „Administrator“ verschieben',
        'settings' => 'sieht und ändert alle Einstellungen, auch FTP-Zugangsdaten, und legt Benutzerrechte an',
        'templates' => 'kann beliebiges HTML und JavaScript in alle Seiten einbauen',
        'replacements' => 'kann über Glossar-Einträge beliebiges HTML und JavaScript in Release-Texte und Kommentare einbauen',
        'backup' => 'kann eine Sicherung einspielen und damit alle Daten überschreiben',
    ];
}

/**
 * Benötigt dieses Recht „Admin-Zugang“, um zu wirken?
 */
function pdl_sys_requires_adminaccess(string $right): bool
{
    return in_array($right, pdl_sys_core_rights(), true)
        && !in_array($right, pdl_sys_public_rights(), true)
        && $right !== 'adminaccess';
}

/**
 * Anzeigenamen der Rechte, die die System-Seiten prüfen.
 *
 * @return array<string, string>
 */
function pdl_sys_right_labels(): array
{
    return [
        'adminaccess' => 'Admin-Zugang',
        'edituser' => 'Benutzer bearbeiten',
        'deluser' => 'Benutzer löschen',
        'settings' => 'Einstellungen verwalten',
        'templates' => 'Vorlagen verwalten',
        'replacements' => 'Ersetzungen verwalten',
        'backup' => 'Sicherung und Wartung',
    ];
}

/**
 * Darf der Benutzer die Seite nutzen? Verlangt immer „Admin-Zugang“ und
 * zusätzlich alle genannten Rechte.
 *
 * @param array<int|string, mixed> $user_rights
 */
function pdl_sys_can(array $user_rights, string ...$needed): bool
{
    if (($user_rights['adminaccess'] ?? 'N') !== 'Y') {
        return false;
    }
    foreach ($needed as $right) {
        if (($user_rights[$right] ?? 'N') !== 'Y') {
            return false;
        }
    }
    return true;
}

/**
 * Hinweis für fehlende Rechte (bereits HTML-maskiert).
 */
function pdl_sys_denied_text(string ...$needed): string
{
    if (empty($GLOBALS['user_details'])) {
        return '<strong>Bitte melden Sie sich zuerst an.</strong> <a class="alert-link" href="index.php">Zur Anmeldung</a>';
    }
    $labels = pdl_sys_right_labels();
    $names = [];
    foreach ($needed === [] ? ['adminaccess'] : $needed as $right) {
        $names[] = '„' . htmlspecialchars($labels[$right] ?? $right, ENT_QUOTES, 'UTF-8') . '“';
    }
    $last = array_pop($names);
    $list = $names === [] ? $last : implode(', ', $names) . ' und ' . $last;
    $word = $names === [] ? 'das Recht ' : 'die Rechte ';
    return '<strong>Kein Zugriff.</strong> Für diese Seite benötigen Sie ' . $word . $list
        . ' (zusätzlich zum Admin-Zugang). Bitte wenden Sie sich an einen Administrator.';
}

/**
 * Gültiger Variablenname für ein Benutzerrecht (= Spaltenname in pdl3_usergroup).
 */
function pdl_sys_valid_right_name(string $name): bool
{
    return strlen($name) >= 2 && strlen($name) <= 32
        && preg_match('/^[a-z_]+$/', $name) === 1
        && !in_array($name, ['ugroup_id', 'name'], true);
}

/**
 * Rechte aus pdl3_rights, deren Variablenname gültig ist und als Spalte in
 * pdl3_usergroup existiert. Nur diese Namen dürfen in SQL-Anweisungen.
 *
 * @param pdl_db_class $db
 * @param array<string, string> $sql_table
 * @return list<array{right_id: int, name: string, bez: string, variablenname: string, reihenfolge: int}>
 */
function pdl_sys_rights_list($db, array $sql_table): array
{
    $columns = pdl_sys_table_columns($db, $sql_table['usergroup']);
    $res = $db->sql_query('SELECT right_id, name, bez, variablenname, reihenfolge FROM '
        . pdl_sys_ident($sql_table['rights']) . ' ORDER BY reihenfolge ASC, right_id ASC');
    $rights = [];
    while ($row = $db->sql_fetch_array($res)) {
        $var = (string) ($row['variablenname'] ?? '');
        if (!pdl_sys_valid_right_name($var) || !in_array($var, $columns, true)) {
            continue;
        }
        $rights[] = [
            'right_id' => (int) ($row['right_id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'bez' => (string) ($row['bez'] ?? ''),
            'variablenname' => $var,
            'reihenfolge' => (int) ($row['reihenfolge'] ?? 0),
        ];
    }
    return $rights;
}

/**
 * Rechte-Werte aus dem Formular. Nur Namen aus $rights werden übernommen;
 * ohne Admin-Zugang (und für die Gastgruppe) sind Verwaltungsrechte immer „N“.
 *
 * @param list<array{right_id: int, name: string, bez: string, variablenname: string, reihenfolge: int}> $rights
 * @return array<string, string>
 */
function pdl_sys_rights_from_post(array $rights, mixed $posted, bool $guest = false): array
{
    $posted = is_array($posted) ? $posted : [];
    $values = [];
    foreach ($rights as $right) {
        $var = $right['variablenname'];
        $values[$var] = (($posted[$var] ?? '') === 'Y') ? 'Y' : 'N';
    }
    $admin = !$guest && ($values['adminaccess'] ?? 'N') === 'Y';
    foreach (array_keys($values) as $var) {
        if (($guest && $var === 'adminaccess') || (!$admin && pdl_sys_requires_adminaccess($var))) {
            $values[$var] = 'N';
        }
    }
    return $values;
}

/**
 * Schalter für die Rechte einer Benutzergruppe.
 *
 * @param list<array{right_id: int, name: string, bez: string, variablenname: string, reihenfolge: int}> $rights
 * @param array<int|string, mixed> $values Variablenname => Y/N
 */
function pdl_sys_rights_switches_html(array $rights, array $values, bool $guest = false): string
{
    $sections = ['public' => [], 'admin' => [], 'other' => []];
    foreach ($rights as $right) {
        $var = $right['variablenname'];
        if (in_array($var, pdl_sys_public_rights(), true)) {
            $sections['public'][] = $right;
        } elseif ($var === 'adminaccess' || pdl_sys_requires_adminaccess($var)) {
            $sections['admin'][] = $right;
        } else {
            $sections['other'][] = $right;
        }
    }
    if ($guest) {
        $sections['admin'] = [];
    }

    // „Admin-Zugang“ steht im Adminbereich immer zuerst
    usort($sections['admin'], static fn (array $a, array $b): int => ($b['variablenname'] === 'adminaccess') <=> ($a['variablenname'] === 'adminaccess'));

    $admin_like = pdl_sys_admin_like_rights();
    $titles = [
        'public' => 'Öffentlicher Bereich',
        'admin' => 'Adminbereich',
        'other' => 'Weitere Rechte',
    ];
    $html = pdl_sys_switch_style();
    foreach ($sections as $key => $list) {
        if ($list === []) {
            continue;
        }
        $html .= '<fieldset class="mb-4" id="pdlRightsSection_' . $key . '">'
            . '<legend class="h6 fw-bold">' . $titles[$key] . '</legend>';
        if ($key === 'admin') {
            $names = [];
            foreach ($rights as $right) {
                if (isset($admin_like[$right['variablenname']])) {
                    $names[] = '„' . htmlspecialchars($right['name'], ENT_QUOTES, 'UTF-8') . '“';
                }
            }
            $html .= '<div class="alert alert-warning small py-2" id="pdlRightsAdminHint">'
                . '<strong>Faktisch Administratorrechte:</strong> ' . implode(', ', $names)
                . '. Wer eines dieser Rechte hat, kann sich selbst alle weiteren Rechte verschaffen. '
                . 'Vergeben Sie sie nur an Personen, denen Sie auch das Administratorkonto anvertrauen würden.</div>';
        }
        $html .= '<div class="row g-0 column-gap-4">';
        foreach ($list as $right) {
            $var = $right['variablenname'];
            $id = 'pdlRight_' . $var;
            $checked = (($values[$var] ?? 'N') === 'Y');
            $needs_admin = pdl_sys_requires_adminaccess($var);
            $col = $var === 'adminaccess' ? 'col-12' : 'col-12 col-xl-5';
            $html .= '<div class="' . $col . ' pdl-right-col"><div class="form-check form-switch mb-3 pdl-right" data-right="' . $var . '">'
                . '<input class="form-check-input" type="checkbox" role="switch" id="' . $id . '" name="rights[' . $var . ']" value="Y"'
                . ($checked ? ' checked' : '')
                . ($var === 'adminaccess' ? ' data-role="adminaccess"' : '')
                . ($needs_admin ? ' data-requires-adminaccess="1"' : '')
                . ' aria-describedby="' . $id . '_help">'
                . '<label class="form-check-label fw-semibold" for="' . $id . '">' . htmlspecialchars($right['name'], ENT_QUOTES, 'UTF-8') . '</label>';
            if (isset($admin_like[$var])) {
                $html .= ' <span class="badge text-bg-warning ms-1" title="' . htmlspecialchars($admin_like[$var], ENT_QUOTES, 'UTF-8') . '">Admin-Recht</span>';
            }
            $html .= '<div class="form-text" id="' . $id . '_help">' . htmlspecialchars($right['bez'], ENT_QUOTES, 'UTF-8');
            if ($needs_admin && !str_contains($right['bez'], 'Admin-Zugang')) {
                $html .= ' Benötigt „Admin-Zugang“.';
            }
            $html .= '</div></div></div>';
        }
        $html .= '</div></fieldset>';
    }
    return $html;
}

/**
 * Besser sichtbare Schalter im dunklen Adminbereich (auch im Zustand „aus“).
 */
function pdl_sys_switch_style(): string
{
    static $done = false;
    if ($done) {
        return '';
    }
    $done = true;
    $knob = "data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23d0d0d0'/%3e%3c/svg%3e";
    return '<style>'
        . '.pdl-admin .form-switch .form-check-input{width:2.6em;height:1.35em;margin-top:.1em;cursor:pointer}'
        . '.pdl-admin .form-switch .form-check-input:not(:checked){background-color:#3a3a3a;border-color:#9a9a9a;background-image:url("' . $knob . '")}'
        . '.pdl-admin .form-switch .form-check-label{padding-left:.4em;cursor:pointer}'
        . '.pdl-admin .form-switch .form-check-input:disabled{opacity:.35}'
        . '</style>';
}

/**
 * Kleines Skript: Ohne „Admin-Zugang“ sind die abhängigen Schalter gesperrt.
 * Keine Browser-Dialoge; der Hinweis steht unter jedem Schalter.
 */
function pdl_sys_rights_switches_script(): string
{
    return <<<'JS'
<script>
(function () {
    var admin = document.querySelector('input[data-role="adminaccess"]');
    if (!admin) return;
    var deps = document.querySelectorAll('input[data-requires-adminaccess="1"]');
    function sync() {
        deps.forEach(function (inp) {
            if (admin.checked) {
                if (inp.disabled && inp.dataset.wasChecked === '1') inp.checked = true;
                inp.disabled = false;
            } else {
                if (!inp.disabled) inp.dataset.wasChecked = inp.checked ? '1' : '0';
                inp.checked = false;
                inp.disabled = true;
            }
            var box = inp.closest('.pdl-right');
            if (box) box.classList.toggle('opacity-50', !admin.checked);
        });
    }
    admin.addEventListener('change', sync);
    sync();
})();
</script>
JS;
}

// ---------------------------------------------------------------------------
// Benutzergruppen
// ---------------------------------------------------------------------------

/**
 * ID der Gastgruppe (nicht angemeldete Besucher), 0 = keine.
 *
 * @param array<int|string, mixed> $settings
 */
function pdl_sys_guest_group_id(array $settings): int
{
    return max(0, (int) ($settings['guest_group_id'] ?? 0));
}

/**
 * Gruppen, die nicht gelöscht werden dürfen: 1 (Mitglied), 2 (Administrator),
 * 3 (Gast) sowie die eingestellte Gastgruppe.
 *
 * @param array<int|string, mixed> $settings
 * @return list<int>
 */
function pdl_sys_protected_group_ids(array $settings): array
{
    $ids = [1, 2, 3];
    $guest = pdl_sys_guest_group_id($settings);
    if ($guest > 0 && !in_array($guest, $ids, true)) {
        $ids[] = $guest;
    }
    return $ids;
}

/**
 * Rolle einer Gruppe: admin | member | guest | protected | ''.
 *
 * @param array<int|string, mixed> $settings
 */
function pdl_sys_group_role(int $ugroup_id, array $settings): string
{
    if ($ugroup_id === 2) {
        return 'admin';
    }
    $guest = pdl_sys_guest_group_id($settings);
    if ($guest > 0 && $ugroup_id === $guest) {
        return 'guest';
    }
    if ($ugroup_id === 1) {
        return 'member';
    }
    if (in_array($ugroup_id, pdl_sys_protected_group_ids($settings), true)) {
        return 'protected';
    }
    return '';
}

/**
 * Alle Gruppen mit Mitgliederzahl.
 *
 * @param pdl_db_class $db
 * @param array<string, string> $sql_table
 * @return list<array{ugroup_id: int, name: string, members: int, letter: int, adminaccess: string}>
 */
function pdl_sys_groups($db, array $sql_table): array
{
    $g = pdl_sys_ident($sql_table['usergroup']);
    $u = pdl_sys_ident($sql_table['user']);
    $res = $db->sql_query(
        'SELECT g.ugroup_id, g.name, g.adminaccess, COUNT(u.user_id) AS members,'
        . " SUM(CASE WHEN u.get_letter = 'Y' THEN 1 ELSE 0 END) AS letter"
        . ' FROM ' . $g . ' AS g LEFT JOIN ' . $u . ' AS u ON u.ugroup_id = g.ugroup_id'
        . ' GROUP BY g.ugroup_id, g.name, g.adminaccess ORDER BY g.ugroup_id ASC'
    );
    $groups = [];
    while ($row = $db->sql_fetch_array($res)) {
        $groups[] = [
            'ugroup_id' => (int) ($row['ugroup_id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'members' => (int) ($row['members'] ?? 0),
            'letter' => (int) ($row['letter'] ?? 0),
            'adminaccess' => (string) ($row['adminaccess'] ?? 'N'),
        ];
    }
    return $groups;
}

// ---------------------------------------------------------------------------
// Benutzer
// ---------------------------------------------------------------------------

/**
 * Darf der angemeldete Benutzer ein Konto löschen, dessen Gruppe den Wert
 * $target_adminaccess für „Admin-Zugang“ hat? Konten mit Admin-Zugang löscht
 * nur, wer neben „Benutzer löschen“ auch „Einstellungen verwalten“ hat.
 *
 * @param array<int|string, mixed> $user_rights
 */
function pdl_sys_may_delete_user(string $target_adminaccess, array $user_rights): bool
{
    if (!pdl_sys_can($user_rights, 'deluser')) {
        return false;
    }
    return $target_adminaccess !== 'Y' || pdl_sys_can($user_rights, 'settings');
}

/**
 * Bereinigt eine Homepage-Angabe: leer bleibt leer, https bleibt https,
 * fehlendes Schema wird zu https://, Altlasten wie „http://https://…“ und
 * ein bloßes „http://“ werden repariert.
 */
function pdl_sys_normalize_homepage(string $value): string
{
    $value = trim($value);
    if (preg_match('#^https?://$#i', $value) === 1) {
        return '';
    }
    while (preg_match('#^https?://(https?://.+)$#i', $value, $m) === 1) {
        $value = $m[1];
    }
    if ($value !== '' && preg_match('#^[a-z][a-z0-9+.-]*://#i', $value) !== 1) {
        $value = 'https://' . $value;
    }
    return $value;
}

// ---------------------------------------------------------------------------
// Einstellungen
// ---------------------------------------------------------------------------

/**
 * Eingabearten für Einstellungen.
 *
 * @return array<string, string> Typ => Beschriftung
 */
function pdl_sys_setting_types(): array
{
    return [
        'input' => 'Textfeld',
        'textarea' => 'Mehrzeiliges Textfeld',
        'anaus' => 'Schalter (an/aus)',
        'zahl' => 'Zahl',
        'auswahl' => 'Auswahlliste',
        'email' => 'E-Mail-Adresse',
        'url' => 'Internetadresse (URL)',
        'passwort' => 'Passwort (verdeckt)',
    ];
}

/**
 * Beschriftungen für bekannte Auswahlwerte, damit die Spalte „eingabe“
 * (höchstens 64 Zeichen) nur die Werte enthalten muss, z. B.
 * „auswahl:name|time|views|votes|voted/votes“.
 */
function pdl_sys_option_label(string $value): string
{
    $labels = [
        'name' => 'Name',
        'time' => 'Datum',
        'views' => 'Aufrufe',
        'votes' => 'Anzahl der Bewertungen',
        'voted' => 'Summe der Bewertungen',
        'voted/votes' => 'Durchschnittliche Bewertung',
        'text' => 'Beschreibung',
        'ASC' => 'Aufsteigend (A–Z, älteste zuerst)',
        'DESC' => 'Absteigend (Z–A, neueste zuerst)',
        'zeichen' => 'Nach einer festen Zeichenzahl',
        'string' => 'An der Trennmarke im Text',
        'width' => 'Breite',
        'height' => 'Höhe',
        'Y' => 'An',
        'N' => 'Aus',
    ];
    return $labels[$value] ?? $value;
}

/**
 * Zerlegt die Spalte „eingabe“. Auswahllisten: „auswahl:wert|wert2“ oder
 * „auswahl:wert=Text|wert2=Text 2“. Unbekannte Altwerte (eigenes HTML aus
 * 3.x) gelten als Textfeld.
 *
 * @return array{type: string, options: array<int|string, string>, legacy: bool}
 */
function pdl_sys_parse_eingabe(string $eingabe): array
{
    $eingabe = trim($eingabe);
    if (str_starts_with($eingabe, 'auswahl:')) {
        $options = [];
        foreach (explode('|', substr($eingabe, 8)) as $pair) {
            $pair = trim($pair);
            if ($pair === '') {
                continue;
            }
            $pos = strpos($pair, '=');
            $key = $pos === false ? $pair : trim(substr($pair, 0, $pos));
            $label = $pos === false ? pdl_sys_option_label($pair) : trim(substr($pair, $pos + 1));
            if ($key !== '') {
                $options[$key] = $label !== '' ? $label : $key;
            }
        }
        if ($options !== []) {
            return ['type' => 'auswahl', 'options' => $options, 'legacy' => false];
        }
        return ['type' => 'input', 'options' => [], 'legacy' => true];
    }
    if ($eingabe === '') {
        return ['type' => 'input', 'options' => [], 'legacy' => false];
    }
    if (isset(pdl_sys_setting_types()[$eingabe]) && $eingabe !== 'auswahl') {
        return ['type' => $eingabe, 'options' => [], 'legacy' => false];
    }
    return ['type' => 'input', 'options' => [], 'legacy' => true];
}

/**
 * Baut den Wert für die Spalte „eingabe“; null bei ungültiger Angabe.
 * $options für Auswahllisten: „wert=Text|wert2=Text 2“ (auch zeilenweise).
 */
function pdl_sys_build_eingabe(string $type, string $options = ''): ?string
{
    if (!isset(pdl_sys_setting_types()[$type])) {
        return null;
    }
    if ($type !== 'auswahl') {
        return $type;
    }
    $parts = preg_split('/[|\r\n]+/', $options) ?: [];
    $clean = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $clean[] = $part;
        }
    }
    if ($clean === []) {
        return null;
    }
    $eingabe = 'auswahl:' . implode('|', $clean);
    if (strlen($eingabe) > 64 || pdl_sys_parse_eingabe($eingabe)['type'] !== 'auswahl') {
        return null;
    }
    return $eingabe;
}

/**
 * Auswahlwerte als Text für das Formular („wert=Text|wert2=Text 2“).
 *
 * @param array<int|string, string> $options
 */
function pdl_sys_options_text(array $options): string
{
    $pairs = [];
    foreach ($options as $key => $label) {
        $key = (string) $key;
        $pairs[] = $label === pdl_sys_option_label($key) ? $key : $key . '=' . $label;
    }
    return implode('|', $pairs);
}

/**
 * Prüft einen neuen Wert. Liefert null oder eine Fehlermeldung.
 *
 * @param array{type: string, options: array<int|string, string>, legacy: bool} $parsed
 */
function pdl_sys_validate_setting(array $parsed, string $value, bool $required = false): ?string
{
    if ($required && trim($value) === '') {
        return 'Dieses Feld darf nicht leer sein.';
    }
    switch ($parsed['type']) {
        case 'anaus':
            return in_array($value, ['Y', 'N'], true) ? null : 'Bitte wählen Sie „An“ oder „Aus“.';
        case 'zahl':
            return preg_match('/^\d{1,9}$/', trim($value)) === 1 ? null : 'Bitte geben Sie eine ganze Zahl ab 0 ein.';
        case 'auswahl':
            return array_key_exists($value, $parsed['options']) ? null : 'Bitte wählen Sie einen Wert aus der Liste.';
        case 'email':
            if (trim($value) === '') {
                return null;
            }
            return filter_var(trim($value), FILTER_VALIDATE_EMAIL) !== false ? null : 'Bitte geben Sie eine gültige E-Mail-Adresse ein, z. B. downloads@example.org.';
        case 'url':
            if (trim($value) === '') {
                return null;
            }
            if (filter_var(trim($value), FILTER_VALIDATE_URL) === false || preg_match('#^https?://#i', trim($value)) !== 1) {
                return 'Bitte geben Sie eine vollständige Adresse ein, die mit https:// oder http:// beginnt.';
            }
            return null;
        default:
            return null;
    }
}

/**
 * Eingabefeld einer Einstellung (Name des Feldes: setting[variablenname],
 * ID: setting_variablenname).
 *
 * @param array{type: string, options: array<int|string, string>, legacy: bool} $parsed
 */
function pdl_sys_setting_field_html(string $var, array $parsed, string $value, string $help_id, bool $invalid = false, bool $has_secret = false): string
{
    $id = 'setting_' . $var;
    $name = 'setting[' . $var . ']';
    $esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    $inv = $invalid ? ' is-invalid' : '';
    $describe = ' aria-describedby="' . $esc($help_id) . '"';

    switch ($parsed['type']) {
        case 'anaus':
            $on = $value === 'Y';
            return '<div class="form-check form-switch pdl-setting-switch">'
                . '<input type="hidden" name="' . $esc($name) . '" value="N">'
                . '<input class="form-check-input' . $inv . '" type="checkbox" role="switch" id="' . $esc($id) . '" name="' . $esc($name) . '" value="Y"'
                . ($on ? ' checked' : '') . $describe . '>'
                . '<label class="form-check-label" for="' . $esc($id) . '" data-on="An" data-off="Aus">' . ($on ? 'An' : 'Aus') . '</label>'
                . '</div>';
        case 'zahl':
            return '<input type="number" min="0" step="1" inputmode="numeric" class="form-control' . $inv . '" style="max-width: 12rem"'
                . ' id="' . $esc($id) . '" name="' . $esc($name) . '" value="' . $esc($value) . '"' . $describe . '>';
        case 'auswahl':
            $html = '<select class="form-select' . $inv . '" style="max-width: 32rem" id="' . $esc($id) . '" name="' . $esc($name) . '"' . $describe . '>';
            if (!array_key_exists($value, $parsed['options'])) {
                $html .= '<option value="' . $esc($value) . '" selected>' . $esc($value === '' ? '(leer)' : $value) . ' – ungültig, bitte neu wählen</option>';
            }
            foreach ($parsed['options'] as $key => $label) {
                $html .= '<option value="' . $esc((string) $key) . '"' . ((string) $key === $value ? ' selected' : '') . '>' . $esc($label) . '</option>';
            }
            return $html . '</select>';
        case 'email':
            return '<input type="email" class="form-control' . $inv . '" style="max-width: 36rem" autocomplete="off"'
                . ' id="' . $esc($id) . '" name="' . $esc($name) . '" value="' . $esc($value) . '"' . $describe . '>';
        case 'url':
            return '<input type="url" class="form-control' . $inv . '" style="max-width: 48rem" placeholder="https://"'
                . ' id="' . $esc($id) . '" name="' . $esc($name) . '" value="' . $esc($value) . '"' . $describe . '>';
        case 'passwort':
            return '<input type="password" class="form-control' . $inv . '" style="max-width: 24rem" autocomplete="new-password"'
                . ' placeholder="' . ($has_secret ? '•••••••• (gespeichert)' : '') . '"'
                . ' id="' . $esc($id) . '" name="' . $esc($name) . '" value=""' . $describe . '>';
        case 'textarea':
            return '<textarea class="form-control' . $inv . '" rows="3" id="' . $esc($id) . '" name="' . $esc($name) . '"' . $describe . '>'
                . $esc($value) . '</textarea>';
        default:
            return '<input type="text" class="form-control' . $inv . '" style="max-width: 48rem"'
                . ' id="' . $esc($id) . '" name="' . $esc($name) . '" value="' . $esc($value) . '"' . $describe . '>';
    }
}

/**
 * Gültiger Variablenname für neue Einstellungen und Vorlagen.
 */
function pdl_sys_valid_key(string $name): bool
{
    return preg_match('/^[a-z][a-z0-9_]{1,63}$/', $name) === 1;
}

// ---------------------------------------------------------------------------
// Newsletter
// ---------------------------------------------------------------------------

/**
 * Zeitraum des Newsletters.
 *
 * $mode: seit_letztem | 30tage | datum; $date im Format JJJJ-MM-TT.
 * Ohne bisherigen Versand gilt „seit_letztem“ als „30tage“.
 *
 * @return array{mode: string, since: int, phrase: string}
 */
function pdl_sys_letter_period(string $mode, string $date, int $lastletter, int $now): array
{
    if ($mode === 'datum' && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) === 1 && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
        $since = mktime(0, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1]) ?: 0;
        return ['mode' => 'datum', 'since' => $since, 'phrase' => 'seit dem ' . date('d.m.Y', $since)];
    }
    if ($mode !== '30tage' && $lastletter > 0) {
        return [
            'mode' => 'seit_letztem',
            'since' => $lastletter,
            'phrase' => 'seit dem letzten Newsletter am ' . date('d.m.Y', $lastletter),
        ];
    }
    return ['mode' => '30tage', 'since' => $now - 30 * 86400, 'phrase' => 'in den letzten 30 Tagen'];
}

/**
 * Vorschautext eines Releases für die Mail: ohne BB-Code und HTML, an der
 * Trennmarke bzw. nach $limit Zeichen an einer Wortgrenze gekürzt.
 * Mit $cutAtMarker = false wird die Marke nur entfernt (Trennung nach
 * Zeichen), damit sie nie wörtlich in der Mail steht.
 */
function pdl_sys_letter_teaser(string $text, string $marker = '', int $limit = 250, bool $cutAtMarker = true): string
{
    if ($marker !== '' && str_contains($text, $marker)) {
        $text = $cutAtMarker ? (string) strstr($text, $marker, true) : str_replace($marker, ' ', $text);
    }
    $text = (string) preg_replace('/\[(\/?)[a-z*]+(?:=[^\]]*)?\]/i', '', $text);
    $text = (string) preg_replace('/<br\s*\/?>/i', "\n", $text);
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = trim((string) preg_replace('/\s+/u', ' ', $text));

    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($chars) <= $limit) {
        return $text;
    }
    $cut = implode('', array_slice($chars, 0, $limit));
    $space = strrpos($cut, ' ');
    if ($space !== false && $space > (int) (strlen($cut) * 0.6)) {
        $cut = substr($cut, 0, $space);
    }
    return rtrim($cut, " ,.;:-") . ' …';
}

/**
 * Text des Newsletters.
 *
 * @param list<array{release_id: int, name: string, text: string, time: int}> $releases
 * @param array{mode: string, since: int, phrase: string} $period
 * @param callable(string): string $url Absoluter Link zu einer Abfrage, z. B. 'release_id=5'
 */
function pdl_sys_letter_text(array $releases, array $period, string $sitename, int $now, callable $url, string $marker = '', bool $cutAtMarker = true): string
{
    $count = count($releases);
    $intro = ucfirst($period['phrase']);
    if ($count === 0) {
        $intro .= ' sind keine neuen Releases erschienen.';
    } elseif ($count === 1) {
        $intro .= ' ist 1 neues Release erschienen:';
    } else {
        $intro .= ' sind ' . $count . ' neue Releases erschienen:';
    }

    $line = str_repeat('-', 50);
    $text = "Hallo,\n\nhier ist der Newsletter von " . $sitename . ' vom ' . date('d.m.Y', $now) . ".\n\n" . $intro . "\n\n";
    foreach ($releases as $release) {
        $text .= $line . "\n" . $release['name'] . ' (' . date('d.m.Y', $release['time']) . ")\n" . $line . "\n";
        $teaser = pdl_sys_letter_teaser($release['text'], $marker, 250, $cutAtMarker);
        if ($teaser !== '') {
            $text .= $teaser . "\n\n";
        }
        $text .= "Mehr Informationen und Download:\n" . $url('release_id=' . $release['release_id']) . "\n\n";
    }
    $text .= $line . "\n"
        . 'Sie erhalten diesen Newsletter, weil Sie ihn in Ihrem Benutzerkonto bei ' . $sitename . " bestellt haben.\n"
        . "Zum Abbestellen melden Sie sich an und entfernen im Profil den Haken bei „Newsletter erhalten“:\n"
        . $url('usercenter=profil') . "\n";
    return $text;
}

/**
 * Zerlegt eingegebene Adressen (getrennt durch Semikolon, Komma oder Leerraum).
 *
 * @return array{valid: list<string>, invalid: list<string>}
 */
function pdl_sys_parse_addresses(string $raw): array
{
    $valid = [];
    $invalid = [];
    $seen = [];
    foreach (preg_split('/[;,\s]+/', $raw) ?: [] as $address) {
        $address = trim($address);
        if ($address === '') {
            continue;
        }
        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            $invalid[] = $address;
            continue;
        }
        $key = strtolower($address);
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $valid[] = $address;
        }
    }
    return ['valid' => $valid, 'invalid' => $invalid];
}

/**
 * Empfänger: Konten der gewählten Gruppen mit Newsletter-Haken, ohne
 * Gastgruppe, ohne Dubletten und ungültige Adressen.
 *
 * @param pdl_db_class $db
 * @param array<string, string> $sql_table
 * @param list<int> $group_ids
 * @return list<string>
 */
function pdl_sys_letter_recipients($db, array $sql_table, array $group_ids, int $guest_group_id): array
{
    return array_column(pdl_sys_letter_recipient_rows($db, $sql_table, $group_ids, $guest_group_id), 'email');
}

/**
 * Empfänger wie pdl_sys_letter_recipients(), aufsteigend nach Benutzer-ID
 * und mit dieser ID. Eine doppelte Adresse zählt beim ersten Konto.
 *
 * @param pdl_db_class $db
 * @param array<string, string> $sql_table
 * @param list<int> $group_ids
 * @return list<array{user_id: int, email: string}>
 */
function pdl_sys_letter_recipient_rows($db, array $sql_table, array $group_ids, int $guest_group_id): array
{
    $ids = [];
    foreach ($group_ids as $id) {
        $id = (int) $id;
        if ($id > 0 && $id !== $guest_group_id) {
            $ids[$id] = $id;
        }
    }
    if ($ids === []) {
        return [];
    }
    $res = $db->sql_query('SELECT user_id, email FROM ' . pdl_sys_ident($sql_table['user'])
        . " WHERE get_letter = 'Y' AND ugroup_id IN (" . implode(',', $ids) . ') ORDER BY user_id ASC');
    $list = [];
    $seen = [];
    while ($row = $db->sql_fetch_array($res)) {
        foreach (pdl_sys_parse_addresses((string) ($row['email'] ?? ''))['valid'] as $address) {
            $key = strtolower($address);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $list[] = ['user_id' => (int) ($row['user_id'] ?? 0), 'email' => $address];
            }
        }
    }
    return $list;
}

/**
 * Versandstand des Newsletters (Einstellung „letter_job“, unsichtbar wie
 * „lastletter“). Enthält keine Adressen, nur Kennung, Zeitpunkte, Zähler und
 * die zuletzt bediente Benutzer-ID.
 *
 * @param pdl_db_class $db
 * @param array<string, string> $sql_table
 * @return array{fingerprint: string, started: int, heartbeat: int, last_user_id: int, sent: int, failed: int, finished: int}|null
 */
function pdl_sys_letter_job_load($db, array $sql_table): ?array
{
    $row = $db->sql_fetch_array($db->sql_query('SELECT wert FROM ' . pdl_sys_ident($sql_table['settings'])
        . " WHERE variablenname = 'letter_job' LIMIT 1"));
    $data = is_array($row) ? json_decode((string) ($row['wert'] ?? ''), true) : null;
    if (!is_array($data) || !is_string($data['fingerprint'] ?? null) || $data['fingerprint'] === '') {
        return null;
    }
    $int = static fn (string $key): int => is_int($data[$key] ?? null) ? $data[$key] : 0;
    return [
        'fingerprint' => $data['fingerprint'],
        'started' => $int('started'),
        'heartbeat' => $int('heartbeat'),
        'last_user_id' => $int('last_user_id'),
        'sent' => $int('sent'),
        'failed' => $int('failed'),
        'finished' => $int('finished'),
    ];
}

/**
 * Speichert den Versandstand. Liefert false, wenn die Datenbank ablehnt.
 *
 * @param pdl_db_class $db
 * @param array<string, string> $sql_table
 * @param array{fingerprint: string, started: int, heartbeat: int, last_user_id: int, sent: int, failed: int, finished: int} $job
 */
function pdl_sys_letter_job_save($db, array $sql_table, array $job): bool
{
    return pdl_sys_setting_store($db, $sql_table, 'letter_job', (string) json_encode($job));
}

/**
 * Schreibt eine unsichtbare Einstellung (Gruppe 0), legt sie bei Bedarf an.
 *
 * @param pdl_db_class $db
 * @param array<string, string> $sql_table
 */
function pdl_sys_setting_store($db, array $sql_table, string $variablenname, string $wert): bool
{
    if (!pdl_sys_valid_key($variablenname)) {
        return false;
    }
    $settings_t = pdl_sys_ident($sql_table['settings']);
    $exists = pdl_sys_scalar_int($db, 'SELECT COUNT(*) FROM ' . $settings_t . " WHERE variablenname = '" . $variablenname . "'") > 0;
    return $exists
        ? pdl_sys_exec($db, 'UPDATE ' . $settings_t . " SET wert = '" . $db->sql_escape_string($wert) . "' WHERE variablenname = '" . $variablenname . "'")
        : pdl_sys_exec($db, 'INSERT INTO ' . $settings_t . " (`variablenname`, `name`, `bez`, `wert`, `eingabe`, `sgroup_id`, `reihenfolge`) VALUES ('"
            . $variablenname . "', '', '', '" . $db->sql_escape_string($wert) . "', '', 0, 0)");
}

/**
 * Was soll mit einem Newsletter (Kennung $fingerprint) geschehen?
 *
 * - „done“: genau dieser Newsletter wurde in den letzten 24 Stunden
 *   vollständig verschickt – nicht noch einmal senden.
 * - „running“: Der Versand läuft gerade in einer anderen Anfrage (Lebenszeichen
 *   jünger als zwei Minuten) – nicht parallel senden.
 * - „resume“: Der Versand wurde abgebrochen – bei der nächsten Benutzer-ID
 *   fortsetzen.
 * - „new“: neuer Versand.
 *
 * @param array{fingerprint: string, started: int, heartbeat: int, last_user_id: int, sent: int, failed: int, finished: int}|null $job
 */
function pdl_sys_letter_job_state(?array $job, string $fingerprint, int $now): string
{
    if ($job === null || $job['fingerprint'] !== $fingerprint) {
        return 'new';
    }
    if ($job['finished'] > 0) {
        return $job['finished'] > $now - 86400 ? 'done' : 'new';
    }
    return $job['heartbeat'] > $now - 120 ? 'running' : 'resume';
}

// ---------------------------------------------------------------------------
// Sicherung
// ---------------------------------------------------------------------------

/**
 * Tabellen von PowerDownload laut Konfiguration ($sql_table).
 *
 * @param array<string, string> $sql_table
 * @return list<string>
 */
function pdl_sys_backup_tables(array $sql_table): array
{
    $tables = [];
    foreach ($sql_table as $table) {
        if (preg_match('/^[A-Za-z0-9_]{1,64}$/', $table) === 1) {
            $tables[$table] = $table;
        }
    }
    return array_values($tables);
}

/**
 * SQL-Literal für einen Wert; NULL bleibt NULL.
 *
 * @param pdl_db_class $db
 */
function pdl_sys_backup_value($db, mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }
    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    return "'" . $db->sql_escape_string((string) $value) . "'";
}

/**
 * Entfernt Geheimnisse aus einer Zeile: Anmelde-Tokens und Codes aus
 * „Passwort vergessen“ gehören nicht in eine Sicherung.
 *
 * @param array<int|string, mixed> $row
 * @return array<string, mixed>
 */
function pdl_sys_backup_clean_row(string $table, array $row, array $sql_table): array
{
    $clean = [];
    foreach ($row as $key => $value) {
        if (is_string($key)) {
            $clean[$key] = $value;
        }
    }
    if ($table === ($sql_table['user'] ?? '')) {
        if (array_key_exists('session_token', $clean)) {
            $clean['session_token'] = '';
        }
        if (array_key_exists('remind_code', $clean)) {
            $clean['remind_code'] = '';
        }
        if (array_key_exists('remind_expires', $clean)) {
            $clean['remind_expires'] = 0;
        }
    }
    return $clean;
}

/**
 * INSERT-Anweisung mit Spaltenliste für eine Zeile.
 *
 * @param pdl_db_class $db
 * @param array<string, mixed> $row
 */
function pdl_sys_backup_insert($db, string $table, array $row): string
{
    $columns = [];
    $values = [];
    foreach ($row as $column => $value) {
        $columns[] = pdl_sys_ident($column);
        $values[] = pdl_sys_backup_value($db, $value);
    }
    return 'INSERT INTO ' . pdl_sys_ident($table) . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n";
}

/**
 * Kopf der Sicherungsdatei.
 */
function pdl_sys_backup_header(string $version, int $now): string
{
    return "-- PowerDownload " . $version . " Sicherung (Datenbank)\n"
        . "-- Erstellt am " . date('d.m.Y', $now) . ' um ' . date('H:i', $now) . " Uhr\n"
        . "-- Enthält nur die Tabellen von PowerDownload. Anmelde-Tokens und Codes aus\n"
        . "-- „Passwort vergessen“ sind geleert. NICHT enthalten: hochgeladene Dateien\n"
        . "-- unter pdl-files/ und Screenshots unter pdl-gfx/screens/.\n\n"
        . "SET NAMES utf8mb4;\n"
        . "SET FOREIGN_KEY_CHECKS = 0;\n";
}

/**
 * Sieht der Text nach einer Sicherung von PowerDownload aus?
 */
function pdl_sys_backup_is_powerdownload(string $sql): bool
{
    return preg_match('/^\s*(--|#)\s*PowerDownload\b/i', substr($sql, 0, 300)) === 1;
}

/**
 * Zerlegt SQL in einzelne Anweisungen. Beachtet Zeichenketten ('…', "…",
 * `…`) mit Backslash-Maskierung und Kommentare (--, #, / * * /).
 *
 * @return list<string>
 */
function pdl_sys_split_sql(string $sql): array
{
    $statements = [];
    $current = '';
    $len = strlen($sql);
    $quote = '';
    for ($i = 0; $i < $len; $i++) {
        $c = $sql[$i];
        if ($quote !== '') {
            $current .= $c;
            if ($c === '\\' && $quote !== '`' && $i + 1 < $len) {
                $current .= $sql[++$i];
            } elseif ($c === $quote) {
                if ($i + 1 < $len && $sql[$i + 1] === $quote) {
                    $current .= $sql[++$i];
                } else {
                    $quote = '';
                }
            }
            continue;
        }
        if ($c === "'" || $c === '"' || $c === '`') {
            $quote = $c;
            $current .= $c;
            continue;
        }
        if ($c === '#' || ($c === '-' && substr($sql, $i, 3) === '-- ') || ($c === '-' && substr($sql, $i, 3) === "--\n")) {
            $end = strpos($sql, "\n", $i);
            $i = $end === false ? $len : $end;
            $current .= "\n";
            continue;
        }
        if ($c === '/' && ($sql[$i + 1] ?? '') === '*') {
            $end = strpos($sql, '*/', $i + 2);
            $i = $end === false ? $len : $end + 1;
            $current .= ' ';
            continue;
        }
        if ($c === ';') {
            if (trim($current) !== '') {
                $statements[] = trim($current);
            }
            $current = '';
            continue;
        }
        $current .= $c;
    }
    if (trim($current) !== '') {
        $statements[] = trim($current);
    }
    return $statements;
}

/**
 * Spielt SQL ein und zählt Erfolge und Fehler.
 *
 * @param pdl_db_class $db
 * @return array{total: int, ok: int, failed: int, errors: list<string>}
 */
function pdl_sys_backup_import($db, string $sql): array
{
    $result = ['total' => 0, 'ok' => 0, 'failed' => 0, 'errors' => []];
    foreach (pdl_sys_split_sql($sql) as $statement) {
        $result['total']++;
        if ($db->sql_query($statement) === false) {
            $result['failed']++;
            if (count($result['errors']) < 5) {
                $error = pdl_sys_db_error($db);
                $result['errors'][] = ($error !== '' ? $error : 'Unbekannter Fehler') . ' – ' . substr(preg_replace('/\s+/', ' ', $statement) ?? '', 0, 120);
            }
        } else {
            $result['ok']++;
        }
    }
    return $result;
}

// ---------------------------------------------------------------------------
// Zähler und Kommentare zurücksetzen
// ---------------------------------------------------------------------------

/**
 * Hat die Tabelle die Spalte? (für Felder, die je nach Version fehlen)
 *
 * @param pdl_db_class $db
 */
function pdl_sys_has_column($db, string $table, string $column): bool
{
    return in_array($column, pdl_sys_table_columns($db, $table), true);
}

/**
 * Was „Zähler und Kommentare zurücksetzen“ betreffen würde.
 *
 * @param pdl_db_class $db
 * @param array<string, string> $sql_table
 * @return array{comments: int, downloads: int, files: int, views: int, votes: int, releases: int, locks: int, screen_views: int, has_screen_views: bool}
 */
function pdl_sys_reset_counts($db, array $sql_table): array
{
    $release = pdl_sys_ident($sql_table['release']);
    $files = pdl_sys_ident($sql_table['files']);
    $has_screen_views = pdl_sys_has_column($db, $sql_table['screens'], 'views');
    return [
        'comments' => pdl_sys_scalar_int($db, 'SELECT COUNT(*) FROM ' . pdl_sys_ident($sql_table['comments'])),
        'downloads' => pdl_sys_scalar_int($db, 'SELECT COALESCE(SUM(downloads), 0) FROM ' . $files),
        'files' => pdl_sys_scalar_int($db, 'SELECT COUNT(*) FROM ' . $files . ' WHERE downloads > 0'),
        'views' => pdl_sys_scalar_int($db, 'SELECT COALESCE(SUM(views), 0) FROM ' . $release),
        'votes' => pdl_sys_scalar_int($db, 'SELECT COALESCE(SUM(votes), 0) FROM ' . $release),
        'releases' => pdl_sys_scalar_int($db, 'SELECT COUNT(*) FROM ' . $release),
        'locks' => pdl_sys_scalar_int($db, 'SELECT COUNT(*) FROM ' . pdl_sys_ident($sql_table['iplock'])),
        'screen_views' => $has_screen_views ? pdl_sys_scalar_int($db, 'SELECT COALESCE(SUM(views), 0) FROM ' . pdl_sys_ident($sql_table['screens'])) : 0,
        'has_screen_views' => $has_screen_views,
    ];
}

/**
 * Setzt Aufrufe, Downloads und Bewertungen auf 0 und löscht alle Kommentare
 * und IP-Sperren. Alles oder nichts (Transaktion).
 *
 * @param pdl_db_class $db
 * @param array<string, string> $sql_table
 * @return array{ok: bool, error: string}
 */
function pdl_sys_reset_run($db, array $sql_table, bool $has_screen_views): array
{
    $statements = [
        'UPDATE ' . pdl_sys_ident($sql_table['release']) . ' SET views = 0, votes = 0, voted = 0',
        'UPDATE ' . pdl_sys_ident($sql_table['files']) . ' SET downloads = 0',
        'DELETE FROM ' . pdl_sys_ident($sql_table['comments']),
        'DELETE FROM ' . pdl_sys_ident($sql_table['iplock']),
    ];
    if ($has_screen_views) {
        $statements[] = 'UPDATE ' . pdl_sys_ident($sql_table['screens']) . ' SET views = 0';
    }
    $db->sql_query('START TRANSACTION');
    foreach ($statements as $statement) {
        if (!pdl_sys_exec($db, $statement)) {
            $error = pdl_sys_db_error($db);
            $db->sql_query('ROLLBACK');
            return ['ok' => false, 'error' => $error !== '' ? $error : 'Unbekannter Datenbankfehler'];
        }
    }
    $db->sql_query('COMMIT');
    return ['ok' => true, 'error' => ''];
}

/**
 * Zahl mit Tausenderpunkt.
 */
function pdl_sys_num(int $value): string
{
    return number_format($value, 0, ',', '.');
}

/**
 * Bytes lesbar (KB/MB) mit Komma.
 */
function pdl_sys_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return pdl_sys_num($bytes) . ' Byte';
    }
    if ($bytes < 1048576) {
        return number_format($bytes / 1024, 1, ',', '.') . ' KB';
    }
    return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
}

/**
 * Zahl mit Einheit in Einzahl oder Mehrzahl, z. B. „1 Kommentar“, „12 Kommentare“.
 */
function pdl_sys_count(int $count, string $singular, string $plural): string
{
    return pdl_sys_num($count) . ' ' . ($count === 1 ? $singular : $plural);
}
