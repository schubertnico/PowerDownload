<?php
/**
 * PowerDownload - Ersetzung löschen
 *
 * Liste aller Ersetzungen mit „löschen“-Knopf; gelöscht wird erst nach der
 * Bestätigungsseite per POST mit CSRF-Token. Ein hochgeladenes Smiley-Bild
 * wird mit entfernt, sofern es unter pdl-gfx/smilies/ liegt und kein anderer
 * Eintrag es verwendet.
 */
include("header.inc.php");
include_once("system_helpers.inc.php");

if (!pdl_sys_can($user_rights, 'replacements')) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('replacements'));
    include("footer.inc.php");
    return;
}

$rep_t = pdl_sys_ident($sql_table['replacements']);
$rep_id = (int) ($_POST['rep_id'] ?? ($_GET['rep_id'] ?? 0));
$type_names = ['b' => 'Zensur-Eintrag', 's' => 'Smiley', 'g' => 'Glossar-Eintrag'];
$smiley_src = static function (string $neu): string {
    return preg_match('#^https?://#i', $neu) === 1 ? $neu : '../' . ltrim($neu, '/');
};
$back = '<a class="btn btn-outline-light" href="delreplacement.php" id="pdlReplDelBack">Zurück zur Liste</a>';

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Vorlagen und Ersetzungen'],
    ['title' => 'Ersetzungen', 'href' => 'showreplacements.php'],
    ['title' => 'Ersetzung löschen'],
]);
echo '<h1 class="h3 pdl-page-title">Ersetzung löschen</h1>';

if ($rep_id > 0) {
    $getrep = $db_handler->sql_fetch_array($db_handler->sql_query('SELECT rep_id, old, neu, type FROM ' . $rep_t . ' WHERE rep_id = ' . $rep_id));
    if ($getrep === null) {
        echo pdl_admin_alert('warning', 'Diese Ersetzung gibt es nicht (mehr).') . $back;
        include("footer.inc.php");
        return;
    }
    $kind = (string) $getrep['type'];
    $label = $type_names[$kind] ?? 'Eintrag';
    $old = (string) $getrep['old'];
    $neu = (string) $getrep['neu'];

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!pdl_sys_csrf_ok()) {
            echo pdl_admin_alert('danger', pdl_sys_csrf_error_text());
        } elseif (pdl_sys_exec($db_handler, 'DELETE FROM ' . $rep_t . ' WHERE rep_id = ' . $rep_id)) {
            $note = '';
            // Hochgeladenes Bild nur löschen, wenn es lokal liegt und sonst niemand es nutzt
            if ($kind === 's' && preg_match('#^pdl-gfx/smilies/[A-Za-z0-9._-]+$#', $neu) === 1 && !str_contains($neu, '..')) {
                $still_used = pdl_sys_scalar_int($db_handler, 'SELECT COUNT(*) FROM ' . $rep_t . " WHERE type = 's' AND neu = '" . $db_handler->sql_escape_string($neu) . "'") > 0;
                $file = dirname(__DIR__) . '/' . $neu;
                if (!$still_used && is_file($file)) {
                    $note = @unlink($file) ? ' Das Bild wurde ebenfalls gelöscht.' : ' Das Bild ' . htmlspecialchars($neu) . ' konnte nicht gelöscht werden; entfernen Sie es bei Bedarf per FTP.';
                }
            }
            pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'replacement', $rep_id);
            echo pdl_admin_alert('success', '<strong>' . $label . ' „' . htmlspecialchars($old) . '“ wurde gelöscht.</strong>' . $note) . $back;
            include("footer.inc.php");
            return;
        } else {
            echo pdl_admin_alert('danger', 'Die Datenbank hat das Löschen abgelehnt. Bitte versuchen Sie es erneut.');
        }
    }

    $detail = '';
    if ($kind === 'g') {
        $detail = '<p class="mb-0">Ersetzt bisher <code>' . htmlspecialchars($old) . '</code> durch: ' . htmlspecialchars($neu) . '</p>';
    } elseif ($kind === 's') {
        $detail = '<p class="mb-0">Bild: <img src="' . htmlspecialchars($smiley_src($neu)) . '" alt="" style="max-height: 32px"> '
            . (preg_match('#^https?://#i', $neu) === 1 ? '(externe Adresse, bleibt unverändert)' : '(wird mit gelöscht, wenn kein anderer Smiley es verwendet)') . '</p>';
    }
    echo makedialog(
        $label . ' „' . $old . '“ löschen?',
        '<input type="hidden" name="rep_id" value="' . $rep_id . '">'
        . '<p class="mb-2">Nach dem Löschen erscheint der Text in Release-Texten und Kommentaren wieder so, wie er geschrieben wurde.</p>'
        . $detail,
        'Ja, löschen',
        'delreplacement.php',
        'delreplacement.php'
    );
    include("footer.inc.php");
    return;
}

/**
 * Tabelle einer Art mit Löschen-Knöpfen.
 */
$table = static function (string $kind, string $title, array $columns) use ($db_handler, $rep_t, $smiley_src): void {
    $res = $db_handler->sql_query('SELECT rep_id, old, neu FROM ' . $rep_t . " WHERE type = '" . $kind . "' ORDER BY " . ($kind === 'b' ? 'old ASC' : 'LENGTH(old) DESC'));
    echo '<section class="card pdl-card mb-4" id="pdlReplDel_' . $kind . '"><header class="card-header"><h2 class="h5 mb-0">' . $title . '</h2></header>'
        . '<div class="table-responsive"><table class="table table-striped table-hover mb-0 align-middle"><thead><tr>';
    foreach ($columns as $column) {
        echo '<th scope="col">' . $column . '</th>';
    }
    echo '<th scope="col" class="text-end">Aktion</th></tr></thead><tbody>';
    $count = 0;
    while ($row = $db_handler->sql_fetch_array($res)) {
        $count++;
        $id = (int) $row['rep_id'];
        echo '<tr><td><code>' . htmlspecialchars((string) $row['old']) . '</code></td>';
        if ($kind === 's') {
            echo '<td><img src="' . htmlspecialchars($smiley_src((string) $row['neu'])) . '" alt="" style="max-height: 32px"></td>';
        } elseif ($kind === 'g') {
            echo '<td>' . htmlspecialchars((string) $row['neu']) . '</td>';
        }
        echo '<td class="text-end"><a class="btn btn-sm btn-outline-danger" href="delreplacement.php?rep_id=' . $id . '" id="pdlReplDel_' . $id . '">löschen</a></td></tr>';
    }
    if ($count === 0) {
        echo '<tr><td colspan="' . (count($columns) + 1) . '" class="text-muted text-center">Keine Einträge.</td></tr>';
    }
    echo '</tbody></table></div></section>';
};

$table('b', 'Zensur', ['Wort']);
$table('s', 'Smileys', ['Kürzel', 'Bild']);
$table('g', 'Glossar', ['Begriff im Text', 'wird ersetzt durch']);

include("footer.inc.php");
