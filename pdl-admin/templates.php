<?php
/**
 * PowerDownload - Vorlagen bearbeiten
 *
 * Editor (CodeMirror) für alle Vorlagen. Gespeichert werden nur Vorlagen,
 * deren Inhalt sich geändert hat; der Text wird unverändert übernommen
 * (Entitäten wie &amp; bleiben erhalten). Keine Browser-Dialoge: Wer die
 * Seite mit ungespeicherten Änderungen verlassen will, sieht einen Hinweis
 * im Seitenfuß.
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

/** @return array<string, array{template_id: int, variablenname: string, name: string, bez: string, eingabe: string, wert: string, tgroup_id: int}> */
$load_templates = static function () use ($db_handler, $template_t): array {
    $list = [];
    $res = $db_handler->sql_query('SELECT template_id, variablenname, name, bez, eingabe, wert, tgroup_id FROM ' . $template_t . ' ORDER BY reihenfolge ASC, template_id ASC');
    while ($row = $db_handler->sql_fetch_array($res)) {
        $list[(string) $row['variablenname']] = [
            'template_id' => (int) $row['template_id'],
            'variablenname' => (string) $row['variablenname'],
            'name' => (string) $row['name'],
            'bez' => (string) $row['bez'],
            'eingabe' => (string) $row['eingabe'],
            'wert' => (string) $row['wert'],
            'tgroup_id' => (int) $row['tgroup_id'],
        ];
    }
    return $list;
};
$templates_list = $load_templates();
$notice = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $posted = is_array($_POST['tpl'] ?? null) ? $_POST['tpl'] : [];
    if (!pdl_sys_csrf_ok()) {
        $notice = pdl_admin_alert('danger', pdl_sys_csrf_error_text() . ' Ihre Änderungen wurden nicht gespeichert.');
    } else {
        $saved = [];
        $failed = [];
        $invalid = [];
        foreach ($templates_list as $var => $row) {
            if (!isset($posted[$var]) || !is_string($posted[$var])) {
                continue;
            }
            $new = str_replace("\r\n", "\n", $posted[$var]);
            if ($row['eingabe'] !== 'textarea') {
                $new = trim($new);
            }
            if ($new === str_replace("\r\n", "\n", $row['wert'])) {
                continue;
            }
            if ($row['eingabe'] === 'farbe' && $new !== '' && preg_match('/^(#[0-9a-fA-F]{3,8}|[a-zA-Z]{3,30}|(rgb|hsl)a?\([0-9.,%\s]+\))$/', $new) !== 1) {
                $invalid[] = $row['name'];
                continue;
            }
            $ok = pdl_sys_exec($db_handler, 'UPDATE ' . $template_t . " SET wert = '" . $db_handler->sql_escape_string($new) . "' WHERE template_id = " . $row['template_id']);
            if ($ok) {
                $saved[] = $row['name'];
            } else {
                $failed[] = $row['name'];
            }
        }
        $parts = [];
        if ($saved !== []) {
            pdl_audit_log($db_handler, $sql_table, $user_details, 'update', 'templates', count($saved));
            $parts[] = pdl_admin_alert('success', '<strong>' . (count($saved) === 1 ? 'Die Vorlage „' . htmlspecialchars($saved[0]) . '“ wurde gespeichert.' : count($saved) . ' Vorlagen wurden gespeichert.') . '</strong>'
                . (count($saved) > 1 ? ' ' . htmlspecialchars(implode(', ', $saved)) : ''));
        }
        if ($invalid !== []) {
            $parts[] = pdl_admin_alert('danger', '<strong>Nicht gespeichert:</strong> ' . htmlspecialchars(implode(', ', $invalid))
                . '. Bitte geben Sie eine Farbe wie <code>#336699</code> oder <code>darkred</code> an.');
        }
        if ($failed !== []) {
            $parts[] = pdl_admin_alert('danger', '<strong>Die Datenbank hat diese Vorlagen nicht gespeichert:</strong> ' . htmlspecialchars(implode(', ', $failed)) . '. Bitte versuchen Sie es erneut.');
        }
        if ($parts === []) {
            $parts[] = pdl_admin_alert('info', 'Sie haben nichts geändert. Es wurde nichts gespeichert.');
        }
        $notice = implode('', $parts);
        $templates_list = $load_templates();
    }
}

// Vorlagen, die nachweislich nichts bewirken: Gesamtbreite gibt es im
// Bootstrap-Layout nicht mehr; Farben wirken nur über ihren Platzhalter.
$all_values = '';
foreach ($templates_list as $row) {
    if ($row['eingabe'] === 'textarea') {
        $all_values .= $row['wert'] . "\n";
    }
}
$tpl_hints = [];
foreach ($templates_list as $var => $row) {
    if ($var === 'all_width') {
        $tpl_hints[$var] = ['danger', 'Ohne Wirkung: Das aktuelle Layout verwendet diese Vorlage nicht. Änderungen hier ändern nichts.'];
    } elseif (in_array($var, ['header_bg', 'footer_bg', 'table_border', 'alt_1', 'alt_2'], true)) {
        $used = str_contains($all_values, '{' . $var . '}') || (in_array($var, ['alt_1', 'alt_2'], true) && str_contains($all_values, '{alt}'));
        $tpl_hints[$var] = $used
            ? ['info', 'Wird über den Platzhalter {' . $var . '}' . (in_array($var, ['alt_1', 'alt_2'], true) ? ' bzw. {alt}' : '') . ' in Ihren Vorlagen verwendet.']
            : ['warning', 'Derzeit ohne Wirkung: Keine Vorlage verwendet den Platzhalter {' . $var . '}' . (in_array($var, ['alt_1', 'alt_2'], true) ? ' oder {alt}' : '') . '.'];
    }
}

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Vorlagen und Ersetzungen'],
    ['title' => 'Vorlagen bearbeiten'],
]);
echo '<h1 class="h3 pdl-page-title">Vorlagen bearbeiten</h1>';
echo '<div id="pdlTplNotice">' . $notice . '</div>';

/*
 * Bekannte Platzhalter mit Kurzbeschreibung (vgl. showtempvars.php). Ein Klick
 * fügt den Platzhalter im zuletzt benutzten Editor ein.
 */
$known_placeholders = [
    '{script_file}'    => 'Adresse der Download-Seite (endet auf ? bzw. &).',
    '{name}'           => 'Name des Releases, der Datei oder des Ordners.',
    '{id}'             => 'Nummer (ID) des aktuellen Eintrags.',
    '{titel}'          => 'Titel eines Kommentars.',
    '{text}'           => 'Beschreibung bzw. Text.',
    '{time}'           => 'Datum (Format aus den Einstellungen).',
    '{autor}'          => 'Autor des Releases bzw. Kommentars.',
    '{user}'           => 'Benutzername (im Kommentarformular).',
    '{uploader}'       => 'Wer das Release angelegt hat.',
    '{count}'          => 'Laufende Nummer (1, 2, 3 …), nur in Bestenlisten.',
    '{rows}'           => 'Die zusammengesetzten Zeilen, nur in Rahmen-Vorlagen („Box“).',
    '{size}'           => 'Dateigröße (Einheit automatisch).',
    '{filename}'       => 'Dateiname.',
    '{downloads}'      => 'Anzahl der Downloads.',
    '{views}'          => 'Aufrufe der Detailseite.',
    '{votes}'          => 'Anzahl der Bewertungen.',
    '{vote}'           => 'Durchschnittliche Bewertung.',
    '{vote_form}'      => 'Formular zum Bewerten.',
    '{screens}'        => 'Screenshots als verlinkte Vorschaubilder.',
    '{traffic}'        => 'Übertragene Datenmenge.',
    '{dlspeed}'        => 'Geschätzte Downloadzeit.',
    '{files}'          => 'Anzahl Releases bzw. Dateien (Ordner, Statistik).',
    '{subdirs}'        => 'Anzahl Unterordner.',
    '{durch_traffic}'  => 'Durchschnittliche Datenmenge pro Tag.',
    '{durch_downloads}'=> 'Durchschnittliche Downloads pro Tag.',
    '{header_bg}'      => 'Farbe aus der Vorlage „header_bg“.',
    '{footer_bg}'      => 'Farbe aus der Vorlage „footer_bg“.',
    '{table_border}'   => 'Farbe aus der Vorlage „table_border“.',
    '{alt_1}'          => 'Farbe aus der Vorlage „alt_1“.',
    '{alt_2}'          => 'Farbe aus der Vorlage „alt_2“.',
    '{alt}'            => 'Wechselt zeilenweise zwischen alt_1 und alt_2.',
];
?>
<style>
.pdl-tpl-card { margin-bottom: 1.5rem; }
.pdl-tpl-toolbar { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; padding: 0.5rem 0.75rem; background: var(--pdl-admin-surface-alt); border: 1px solid var(--pdl-admin-border); border-bottom: 0; border-top-left-radius: 0.25rem; border-top-right-radius: 0.25rem; }
.pdl-tpl-toolbar .btn { min-height: 36px; }
.pdl-tpl-toolbar .pdl-tpl-status { margin-left: auto; font-size: 0.875rem; color: var(--pdl-admin-muted); }
.pdl-tpl-status.is-dirty { color: var(--pdl-admin-warning); font-weight: 600; }
.pdl-tpl-editor-wrap { position: relative; }
.CodeMirror { height: auto; min-height: 220px; font-family: 'Cascadia Code', Consolas, 'Fira Code', monospace; line-height: 1.55; border: 1px solid var(--pdl-admin-border); border-bottom-left-radius: 0.25rem; border-bottom-right-radius: 0.25rem; }
.CodeMirror.cm-s-dracula { background: #1a0a0d; color: #f8f8f2; }
.CodeMirror-fullscreen { z-index: 1090; background: #1a0a0d; }
body.pdl-tpl-fontsize-sm .CodeMirror { font-size: 12px; }
body.pdl-tpl-fontsize-md .CodeMirror { font-size: 14px; }
body.pdl-tpl-fontsize-lg .CodeMirror { font-size: 17px; line-height: 1.7; }
body.pdl-tpl-fontsize-xl .CodeMirror { font-size: 21px; line-height: 1.75; }
.pdl-tpl-sidebar { position: sticky; top: 70px; }
.pdl-tpl-placeholder-list { max-height: 320px; overflow-y: auto; }
.pdl-tpl-placeholder-btn { font-family: monospace; text-align: left; width: 100%; }
.pdl-tpl-quickjump .nav-link { font-size: 0.85rem; padding: 0.25rem 0.6rem; }
.pdl-tpl-savebar { position: sticky; bottom: 0; z-index: 1080; background: var(--pdl-admin-surface); border-top: 2px solid var(--pdl-admin-accent); padding: 0.6rem 0.75rem; margin: 1.5rem -12px 0 -12px; box-shadow: 0 -4px 12px rgba(0,0,0,0.45); }
.pdl-tpl-savebar .badge { font-size: 0.85rem; }
.pdl-tpl-swatch { display: inline-block; width: 60px; height: 38px; border-radius: .25rem; }
@media (max-width: 991.98px) {
    .pdl-tpl-sidebar { position: relative; top: auto; }
}
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/theme/dracula.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/dialog/dialog.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/search/matchesonscrollbar.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/display/fullscreen.css">

<div class="alert alert-info" role="note">
    Platzhalter in geschweiften Klammern wie <code>{name}</code> ersetzt PowerDownload beim Anzeigen.
    HTML und JavaScript aus Vorlagen werden unverändert ausgeliefert; vergeben Sie das Recht „Vorlagen verwalten“ deshalb nur an vertrauenswürdige Personen.
    Tastenkürzel: <kbd>Strg</kbd>+<kbd>S</kbd> speichert,
    <kbd>F11</kbd> Vollbild, <kbd>Esc</kbd> beendet das Vollbild,
    <kbd>Strg</kbd>+<kbd>F</kbd> sucht im Editor,
    <kbd>Strg</kbd>+<kbd>/</kbd> kommentiert die Zeile aus.
</div>

<div class="row g-4">
    <div class="col-12 col-lg-3 order-lg-2">
        <div class="pdl-tpl-sidebar">
            <section class="card pdl-card mb-3">
                <header class="card-header"><h2 class="h6 mb-0">Platzhalter einfügen</h2></header>
                <div class="card-body p-2">
                    <p class="form-text small mb-2">Ein Klick fügt den Platzhalter im zuletzt benutzten Editor ein. <a href="showtempvars.php">Alle Platzhalter mit Erklärung</a></p>
                    <div class="pdl-tpl-placeholder-list d-grid gap-1">
                        <?php foreach ($known_placeholders as $ph => $desc) { ?>
                        <button type="button" class="btn btn-sm btn-outline-light pdl-tpl-placeholder-btn"
                                data-placeholder="<?php echo htmlspecialchars($ph, ENT_QUOTES, 'UTF-8'); ?>"
                                title="<?php echo htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'); ?>"
                                aria-label="Platzhalter <?php echo htmlspecialchars($ph, ENT_QUOTES, 'UTF-8'); ?> einfügen">
                            <?php echo htmlspecialchars($ph); ?>
                        </button>
                        <?php } ?>
                    </div>
                </div>
            </section>

            <section class="card pdl-card mb-3">
                <header class="card-header"><h2 class="h6 mb-0">Schriftgröße</h2></header>
                <div class="card-body p-2">
                    <div class="btn-group btn-group-sm w-100" role="group" aria-label="Schriftgröße im Editor">
                        <button type="button" class="btn btn-outline-light pdl-tpl-fontsize" data-size="sm">A−</button>
                        <button type="button" class="btn btn-outline-light pdl-tpl-fontsize active" data-size="md">A</button>
                        <button type="button" class="btn btn-outline-light pdl-tpl-fontsize" data-size="lg">A+</button>
                        <button type="button" class="btn btn-outline-light pdl-tpl-fontsize" data-size="xl">A++</button>
                    </div>
                    <p class="form-text small mt-2 mb-0">Gilt für alle Editoren auf dieser Seite.</p>
                </div>
            </section>
        </div>
    </div>

    <div class="col-12 col-lg-9 order-lg-1">
        <?php
        $tgroups = [];
        $tgroup_res = $db_handler->sql_query('SELECT tgroup_id, name FROM ' . $tgroup_t . ' ORDER BY reihenfolge ASC, tgroup_id ASC');
        while ($tgroup_row = $db_handler->sql_fetch_array($tgroup_res)) {
            $tgroups[(int) $tgroup_row['tgroup_id']] = (string) $tgroup_row['name'];
        }

        echo '<nav aria-label="Vorlagengruppen" class="pdl-tpl-quickjump mb-3"><ul class="nav nav-pills flex-wrap gap-2">';
        foreach ($tgroups as $tg_id => $tg_name) {
            echo '<li class="nav-item"><a class="nav-link bg-secondary-subtle" href="#tg_' . $tg_id . '" id="pdlTplJump_' . $tg_id . '">'
                . htmlspecialchars($tg_name) . '</a></li>';
        }
        echo '</ul></nav>';

        echo '<form action="templates.php" method="post" id="pdlTplForm">';
        echo csrf_input();

        foreach ($tgroups as $tg_id => $tg_name) {
            echo '<section class="card pdl-card pdl-tpl-card" id="tg_' . $tg_id . '">';
            echo '<header class="card-header"><h2 class="h5 mb-0">' . htmlspecialchars($tg_name) . '</h2></header>';
            echo '<div class="card-body">';
            $count = 0;
            foreach ($templates_list as $var => $templates_row) {
                if ($templates_row['tgroup_id'] !== $tg_id) {
                    continue;
                }
                $count++;
                $field_id = 'tpl_' . htmlspecialchars($var);
                $help_id = $field_id . '_help';
                $field_name = 'tpl[' . htmlspecialchars($var) . ']';
                $is_textarea = $templates_row['eingabe'] === 'textarea';
                ?>
                <div class="pdl-tpl-block mb-4" id="block_<?php echo $field_id; ?>">
                    <div class="mb-2">
                        <label for="<?php echo $field_id; ?>" class="form-label fw-bold mb-1">
                            <?php echo htmlspecialchars($templates_row['name']); ?>
                        </label>
                        <?php if (isset($tpl_hints[$var])) { ?>
                            <span class="badge text-bg-<?php echo $tpl_hints[$var][0]; ?> ms-1 pdl-tpl-effect" id="<?php echo $field_id; ?>_effect"><?php echo $tpl_hints[$var][0] === 'info' ? 'in Verwendung' : 'ohne Wirkung'; ?></span>
                        <?php } ?>
                        <div id="<?php echo $help_id; ?>" class="form-text mb-0">
                            <?php echo htmlspecialchars($templates_row['bez']); ?>
                            <?php if (isset($tpl_hints[$var])) { echo '<br><span class="text-' . ($tpl_hints[$var][0] === 'info' ? 'info' : 'warning') . '">' . htmlspecialchars($tpl_hints[$var][1]) . '</span>'; } ?>
                        </div>
                        <code class="small text-muted"><?php echo htmlspecialchars($var); ?></code>
                    </div>

                    <?php if ($is_textarea) { ?>
                    <div class="pdl-tpl-editor-wrap" data-pdl-editor>
                        <div class="pdl-tpl-toolbar" role="toolbar" aria-label="Editor-Werkzeuge">
                            <button type="button" class="btn btn-sm btn-outline-light pdl-tpl-fullscreen" title="Vollbild umschalten (F11)">⛶ Vollbild</button>
                            <button type="button" class="btn btn-sm btn-outline-light pdl-tpl-find" title="Im Editor suchen (Strg+F)">🔍 Suchen</button>
                            <button type="button" class="btn btn-sm btn-outline-light pdl-tpl-reset" title="Auf den Stand beim Öffnen der Seite zurücksetzen">↺ Zurücksetzen</button>
                            <span class="pdl-tpl-status" data-pdl-status>unverändert</span>
                        </div>
                        <textarea id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>"
                                  class="pdl-tpl-textarea form-control"
                                  rows="10"
                                  aria-describedby="<?php echo $help_id; ?>"><?php echo htmlspecialchars($templates_row['wert']); ?></textarea>
                    </div>
                    <?php } elseif ($templates_row['eingabe'] === 'farbe') { ?>
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>"
                               class="form-control font-monospace pdl-tpl-color" style="max-width: 140px"
                               value="<?php echo htmlspecialchars($templates_row['wert']); ?>"
                               data-preview="<?php echo $field_id; ?>_preview"
                               aria-describedby="<?php echo $help_id; ?>">
                        <span id="<?php echo $field_id; ?>_preview" class="pdl-tpl-swatch border" style="background:<?php echo htmlspecialchars($templates_row['wert']); ?>" aria-hidden="true"></span>
                    </div>
                    <?php } else { ?>
                    <input type="text" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>"
                           class="form-control" style="max-width: 32rem" value="<?php echo htmlspecialchars($templates_row['wert']); ?>"
                           aria-describedby="<?php echo $help_id; ?>">
                    <?php } ?>
                </div>
                <?php
            }
            if ($count === 0) {
                echo '<p class="text-muted mb-0">Diese Gruppe enthält keine Vorlagen.</p>';
            }
            echo '</div></section>';
        }
        ?>

        <div class="pdl-tpl-savebar">
            <div id="pdlTplLeaveWarn" class="alert alert-warning d-none mb-2" role="alert">
                <strong>Sie haben ungespeicherte Änderungen.</strong> Was möchten Sie tun?
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <button type="button" class="btn btn-sm btn-primary" id="pdlTplLeaveSave">Speichern</button>
                    <button type="button" class="btn btn-sm btn-outline-dark" id="pdlTplLeaveGo">Verwerfen und Seite verlassen</button>
                    <button type="button" class="btn btn-sm btn-outline-dark" id="pdlTplLeaveStay">Weiter bearbeiten</button>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge text-bg-secondary" id="pdlTplDirtyCount">0 ungespeicherte Änderungen</span>
                <span class="form-text small mb-0 d-none d-md-block">Tipp: <kbd>Strg</kbd>+<kbd>S</kbd> speichert.</span>
                <div class="ms-auto d-flex gap-2">
                    <a href="templates.php" class="btn btn-outline-light btn-sm" id="pdlTplCancel">Änderungen verwerfen</a>
                    <button type="submit" class="btn btn-primary" id="pdlTplSave">Vorlagen speichern</button>
                </div>
            </div>
        </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/xml/xml.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/css/css.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/htmlmixed/htmlmixed.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/matchbrackets.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/closetag.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/comment/comment.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/dialog/dialog.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/search/searchcursor.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/search/search.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/search/match-highlighter.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/display/fullscreen.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/selection/active-line.js"></script>

<script>
(function(){
    'use strict';

    var editors = [];
    var dirtyCount = 0;
    var dirtyFields = 0;
    var dirtyBadge = document.getElementById('pdlTplDirtyCount');
    var saveBtn = document.getElementById('pdlTplSave');
    var form = document.getElementById('pdlTplForm');
    var leaveBox = document.getElementById('pdlTplLeaveWarn');
    var pendingHref = null;
    var lastFocused = null;

    function total() { return dirtyCount + dirtyFields; }

    function updateDirtyBadge() {
        if (!dirtyBadge) return;
        var n = total();
        dirtyBadge.textContent = n === 0 ? '0 ungespeicherte Änderungen' : n + (n === 1 ? ' ungespeicherte Änderung' : ' ungespeicherte Änderungen');
        dirtyBadge.classList.toggle('text-bg-warning', n > 0);
        dirtyBadge.classList.toggle('text-bg-secondary', n === 0);
        saveBtn.classList.toggle('btn-warning', n > 0);
        saveBtn.classList.toggle('btn-primary', n === 0);
        if (n === 0 && leaveBox) leaveBox.classList.add('d-none');
    }

    function submitForm() {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit(saveBtn);
        } else {
            editors.forEach(function(e){ e.cm.save(); });
            dirtyCount = 0; dirtyFields = 0;
            form.submit();
        }
    }

    if (window.CodeMirror) {
        document.querySelectorAll('.pdl-tpl-textarea').forEach(function(textarea){
            var wrap = textarea.closest('[data-pdl-editor]');
            var statusEl = wrap.querySelector('[data-pdl-status]');
            var resetBtn = wrap.querySelector('.pdl-tpl-reset');

            var cm = CodeMirror.fromTextArea(textarea, {
                mode: 'htmlmixed',
                theme: 'dracula',
                lineNumbers: true,
                lineWrapping: true,
                indentUnit: 2,
                tabSize: 2,
                indentWithTabs: false,
                matchBrackets: true,
                autoCloseTags: true,
                styleActiveLine: true,
                extraKeys: {
                    'Tab': function(cmInst) {
                        if (cmInst.somethingSelected()) {
                            cmInst.indentSelection('add');
                        } else {
                            cmInst.replaceSelection(Array(cmInst.getOption('indentUnit') + 1).join(' '), 'end', '+input');
                        }
                    },
                    'F11': function(cmInst) { cmInst.setOption('fullScreen', !cmInst.getOption('fullScreen')); },
                    'Esc': function(cmInst) { if (cmInst.getOption('fullScreen')) cmInst.setOption('fullScreen', false); },
                    'Ctrl-/': 'toggleComment',
                    'Cmd-/': 'toggleComment'
                }
            });

            var original = cm.getValue();
            var isDirty = false;
            function setDirty(dirty) {
                if (dirty === isDirty) return;
                isDirty = dirty;
                dirtyCount = Math.max(0, dirtyCount + (dirty ? 1 : -1));
                statusEl.textContent = dirty ? '● geändert' : 'unverändert';
                statusEl.classList.toggle('is-dirty', dirty);
                updateDirtyBadge();
            }
            cm.on('change', function() { setDirty(cm.getValue() !== original); });
            cm.on('focus', function() { lastFocused = cm; });
            editors.push({cm: cm});

            wrap.querySelector('.pdl-tpl-fullscreen').addEventListener('click', function(){
                cm.setOption('fullScreen', !cm.getOption('fullScreen'));
                cm.focus();
            });
            wrap.querySelector('.pdl-tpl-find').addEventListener('click', function(){ cm.execCommand('find'); });

            // Zurücksetzen in zwei Schritten statt Browser-Dialog
            var armTimer = null;
            resetBtn.addEventListener('click', function(){
                if (!isDirty) return;
                if (resetBtn.dataset.armed !== '1') {
                    resetBtn.dataset.armed = '1';
                    resetBtn.textContent = 'Wirklich zurücksetzen?';
                    resetBtn.classList.replace('btn-outline-light', 'btn-warning');
                    armTimer = setTimeout(function(){
                        resetBtn.dataset.armed = '';
                        resetBtn.textContent = '↺ Zurücksetzen';
                        resetBtn.classList.replace('btn-warning', 'btn-outline-light');
                    }, 4000);
                    return;
                }
                clearTimeout(armTimer);
                resetBtn.dataset.armed = '';
                resetBtn.textContent = '↺ Zurücksetzen';
                resetBtn.classList.replace('btn-warning', 'btn-outline-light');
                cm.setValue(original);
                setDirty(false);
            });
        });
    }

    // Einzeilige Felder und Farben
    document.querySelectorAll('#pdlTplForm input[type="text"]').forEach(function(inp){
        var start = inp.value;
        var dirty = false;
        inp.addEventListener('input', function(){
            var now = inp.value !== start;
            if (now !== dirty) {
                dirty = now;
                dirtyFields = Math.max(0, dirtyFields + (now ? 1 : -1));
                updateDirtyBadge();
            }
            var preview = inp.getAttribute('data-preview');
            if (preview) {
                var el = document.getElementById(preview);
                if (el) el.style.background = inp.value;
            }
        });
    });

    document.querySelectorAll('.pdl-tpl-placeholder-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var target = lastFocused || (editors.length > 0 ? editors[0].cm : null);
            if (!target) return;
            target.replaceSelection(btn.getAttribute('data-placeholder'));
            target.focus();
        });
    });

    document.querySelectorAll('.pdl-tpl-fontsize').forEach(function(btn){
        btn.addEventListener('click', function(){
            var size = btn.getAttribute('data-size');
            document.querySelectorAll('.pdl-tpl-fontsize').forEach(function(b){ b.classList.remove('active'); });
            btn.classList.add('active');
            document.body.classList.remove('pdl-tpl-fontsize-sm','pdl-tpl-fontsize-md','pdl-tpl-fontsize-lg','pdl-tpl-fontsize-xl');
            document.body.classList.add('pdl-tpl-fontsize-' + size);
            try { localStorage.setItem('pdlTplFontSize', size); } catch(e){}
            editors.forEach(function(e){ e.cm.refresh(); });
        });
    });
    try {
        var savedSize = localStorage.getItem('pdlTplFontSize');
        if (savedSize) {
            var sizeBtn = document.querySelector('.pdl-tpl-fontsize[data-size="'+savedSize+'"]');
            if (sizeBtn) sizeBtn.click();
        }
    } catch(e){}

    // Strg+S speichert über den normalen Absendeweg (kein Rückfrage-Dialog)
    document.addEventListener('keydown', function(e){
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            submitForm();
        }
    });

    form.addEventListener('submit', function(){
        editors.forEach(function(e){ e.cm.save(); });
        dirtyCount = 0;
        dirtyFields = 0;
    });

    // Seite verlassen mit ungespeicherten Änderungen: Hinweis im Seitenfuß
    document.addEventListener('click', function(e){
        if (total() === 0) return;
        var link = e.target.closest ? e.target.closest('a[href]') : null;
        if (!link || link.target === '_blank' || (link.getAttribute('href') || '').charAt(0) === '#') return;
        e.preventDefault();
        pendingHref = link.href;
        leaveBox.classList.remove('d-none');
        leaveBox.scrollIntoView({block: 'nearest'});
    });
    document.getElementById('pdlTplLeaveSave').addEventListener('click', submitForm);
    document.getElementById('pdlTplLeaveGo').addEventListener('click', function(){
        dirtyCount = 0;
        dirtyFields = 0;
        if (pendingHref) window.location.href = pendingHref;
    });
    document.getElementById('pdlTplLeaveStay').addEventListener('click', function(){ leaveBox.classList.add('d-none'); });

    updateDirtyBadge();
})();
</script>
<?php
include("footer.inc.php");
