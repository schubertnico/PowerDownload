<?php
include("header.inc.php"); ?>
<!-- CodeMirror 5 (HTML-Mixed-Mode + Add-Ons fuer Vollbild, Suche, Comment) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/theme/dracula.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/dialog/dialog.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/search/matchesonscrollbar.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/display/fullscreen.css">
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

@media (max-width: 991.98px) {
    .pdl-tpl-sidebar { position: relative; top: auto; }
}
</style>
<script>
function updatecolor(preview, newvalue) { preview.style.background = newvalue; }
</script>
<?php

$submit = isset($_GET['submit']) ? $_GET['submit'] : (isset($_POST['submit']) ? $_POST['submit'] : 0);

if($user_rights['templates'] == "Y") {
  if($submit == 1) {
    foreach($_POST as $variablenname => $wert) {
      $wert = (string) preg_replace('/&amp;/', '&', (string) preg_replace('/&quot;/',"\"", (string) preg_replace('/&lt;/', '<', (string) preg_replace('/&gt;/', '>', $wert))));
      $variablenname_escaped = $db_handler->sql_escape_string($variablenname);
      $wert_escaped = $db_handler->sql_escape_string($wert);
      $db_handler->sql_query("UPDATE `" . $sql_table['template'] . "` SET wert='" . $wert_escaped . "' WHERE variablenname='" . $variablenname_escaped . "'");
    }
    echo pdl_admin_alert('success', '<strong>Templates übernommen.</strong>');
    echo '<a class="btn btn-outline-light" href="templates.php">Zurück zu den Templates</a>';
  }
  else
  {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'Vorlagen und Ersetzungen'],
        ['title' => 'Vorlagen bearbeiten'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Vorlagen bearbeiten</h1>';

    /*
     * Bekannte Platzhalter mit Kurzbeschreibung. Quelle: showtempvars.php.
     * Werden in der Sidebar als Klick-Buttons angezeigt - per Klick wird
     * der Platzhalter am Cursor in den aktiven Editor eingefuegt.
     */
    $known_placeholders = [
        '{script_file}'    => 'URL zum Hauptscript (?/& werden automatisch ergänzt).',
        '{name}'           => 'Name der Datei / des Releases / des Ordners.',
        '{id}'             => 'ID des aktuellen Eintrags.',
        '{titel}'          => 'Titel eines Kommentars.',
        '{text}'           => 'Beschreibung / Text-Inhalt.',
        '{time}'           => 'Datum (im Format aus den Settings).',
        '{autor}'          => 'Autor des Releases / Kommentars.',
        '{user}'           => 'Nick des aktuellen Nutzers (im Kommentar-Formular).',
        '{uploader}'       => 'Uploader des Releases.',
        '{count}'          => 'Laufende Nummer (1, 2, 3, …) — nur in Top-Listen.',
        '{rows}'           => 'Wird durch die zusammengesetzten Zeilen ersetzt — nur in Box-Templates.',
        '{size}'           => 'Dateigröße (Einheit automatisch).',
        '{filename}'       => 'Dateiname (Basename der URL).',
        '{downloads}'      => 'Downloadzähler für die Datei.',
        '{views}'          => 'Aufrufe der Detailseite.',
        '{votes}'          => 'Anzahl Bewertungen.',
        '{vote}'           => 'Durchschnittliche Bewertung.',
        '{vote_form}'      => 'HTML-Formular zum Bewerten.',
        '{screens}'        => 'Screenshots (verlinkte Thumbnails).',
        '{traffic}'        => 'Verursachter Traffic.',
        '{dlspeed}'        => 'Geschätzte Downloadzeit.',
        '{files}'          => 'Anzahl Releases / Files (Ordner-/Statistik-Templates).',
        '{subdirs}'        => 'Anzahl Unterordner.',
        '{durch_traffic}'  => 'Durchschnittlicher Traffic pro Tag.',
        '{durch_downloads}'=> 'Durchschnittliche Downloads pro Tag.',
        '{header_bg}'      => 'Header-Farbe (Theme).',
        '{footer_bg}'      => 'Footer-Farbe (Theme).',
        '{table_border}'   => 'Rahmenfarbe (Theme).',
        '{alt_1}'          => '1. Alternativfarbe (Theme).',
        '{alt_2}'          => '2. Alternativfarbe (Theme).',
        '{alt}'            => 'Wechselt zwischen alt_1/alt_2 — nur in Zeilen-Templates.',
        '{nick}'           => 'Nickname (Register-/Profil-Templates).',
        '{email}'          => 'E-Mail-Adresse (Register-/Profil-Templates).',
        '{homepage}'       => 'Homepage (Register-/Profil-Templates).',
        '{get_letter}'     => 'Newsletter-Status („checked" wenn aktiviert).',
    ];
    ?>

<div class="alert alert-info" role="alert">
    <strong>Hinweis:</strong> Die Templates werden ohne <code>eval()</code> verarbeitet — alle Platzhalter
    der Form <code>{variable}</code> werden 1:1 ersetzt. <code>&lt;script&gt;</code>-Tags werden
    direkt ausgeliefert; achten Sie hier auf XSS-Risiken.
    Tastenkürzel: <kbd>Strg</kbd>+<kbd>S</kbd> speichert,
    <kbd>F11</kbd> Vollbild, <kbd>Esc</kbd> verlässt Vollbild,
    <kbd>Strg</kbd>+<kbd>F</kbd> sucht im aktiven Editor,
    <kbd>Strg</kbd>+<kbd>/</kbd> kommentiert die markierte Zeile.
</div>

<div class="row g-4">
    <div class="col-12 col-lg-3 order-lg-2">
        <div class="pdl-tpl-sidebar">
            <section class="card pdl-card mb-3">
                <header class="card-header"><h2 class="h6 mb-0">Platzhalter einfügen</h2></header>
                <div class="card-body p-2">
                    <p class="form-text small mb-2">Klick fügt den Platzhalter im aktiven Editor ein.</p>
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
                    <p class="form-text small mt-2 mb-0">Wirkt auf alle Editoren auf dieser Seite.</p>
                </div>
            </section>
        </div>
    </div>

    <div class="col-12 col-lg-9 order-lg-1">
        <?php
        // Sprungnavigation Quick-Jump
        $tgroup_res = $db_handler->sql_query("SELECT * FROM `" . $sql_table['templategroup'] . "` ORDER BY reihenfolge ASC");
        echo '<nav aria-label="Template-Gruppen" class="pdl-tpl-quickjump mb-3"><ul class="nav nav-pills flex-wrap gap-2">';
        while($tgroup_row = $db_handler->sql_fetch_array($tgroup_res)) {
            echo '<li class="nav-item"><a class="nav-link bg-secondary-subtle" href="#tg_'
                . htmlspecialchars($tgroup_row['tgroup_id']) . '">'
                . htmlspecialchars($tgroup_row['name']) . '</a></li>';
        }
        echo '</ul></nav>';

        echo '<form action="templates.php?submit=1" method="post" id="pdlTplForm">';

        $tgroup_res = $db_handler->sql_query("SELECT * FROM `" . $sql_table['templategroup'] . "` ORDER BY reihenfolge ASC");
        while($tgroup_row = $db_handler->sql_fetch_array($tgroup_res)) {
            $tg_id = htmlspecialchars($tgroup_row['tgroup_id']);
            echo '<section class="card pdl-card pdl-tpl-card" id="tg_' . $tg_id . '">';
            echo '<header class="card-header"><h2 class="h5 mb-0">'
                . htmlspecialchars($tgroup_row['name']) . '</h2></header>';
            echo '<div class="card-body">';

            $templates_res = $db_handler->sql_query("SELECT * FROM `" . $sql_table['template'] . "` WHERE tgroup_id='" . $db_handler->sql_escape_string($tgroup_row['tgroup_id']) . "' ORDER BY reihenfolge ASC");
            while($templates_row = $db_handler->sql_fetch_array($templates_res)) {
                $field_id = 'tpl_' . htmlspecialchars($templates_row['variablenname'] ?? '');
                $help_id = $field_id . '_help';
                $is_textarea = $templates_row['eingabe'] == 'textarea';
                ?>
                <div class="pdl-tpl-block mb-4" id="block_<?php echo $field_id; ?>">
                    <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                        <div>
                            <label for="<?php echo $field_id; ?>" class="form-label fw-bold mb-1">
                                <?php echo htmlspecialchars($templates_row['name']); ?>
                            </label>
                            <?php if (!empty($templates_row['bez'])) { ?>
                            <div id="<?php echo $help_id; ?>" class="form-text mb-0">
                                <?php echo htmlspecialchars($templates_row['bez']); ?>
                            </div>
                            <?php } ?>
                            <code class="small text-muted">$template['<?php echo htmlspecialchars($templates_row['variablenname']); ?>']</code>
                        </div>
                    </div>

                    <?php if ($is_textarea) { ?>
                    <div class="pdl-tpl-editor-wrap" data-pdl-editor>
                        <div class="pdl-tpl-toolbar" role="toolbar" aria-label="Editor-Werkzeuge">
                            <button type="button" class="btn btn-sm btn-outline-light pdl-tpl-fullscreen" title="Vollbild umschalten (F11)" aria-label="Vollbild">
                                ⛶ Vollbild
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light pdl-tpl-find" title="Im Editor suchen (Strg+F)" aria-label="Suchen">
                                🔍 Suchen
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-light pdl-tpl-reset" title="Auf Stand beim Laden zurücksetzen" aria-label="Editor zurücksetzen">
                                ↺ Zurücksetzen
                            </button>
                            <span class="pdl-tpl-status" data-pdl-status>unverändert</span>
                        </div>
                        <textarea id="<?php echo $field_id; ?>" name="<?php echo htmlspecialchars($templates_row['variablenname']); ?>"
                                  class="pdl-tpl-textarea form-control"
                                  rows="10"
                                  aria-describedby="<?php echo $help_id; ?>"><?php echo htmlspecialchars($templates_row['wert']); ?></textarea>
                    </div>
                    <?php } elseif ($templates_row['eingabe'] == 'input') { ?>
                    <input type="text" id="<?php echo $field_id; ?>" name="<?php echo htmlspecialchars($templates_row['variablenname']); ?>"
                           class="form-control" value="<?php echo htmlspecialchars($templates_row['wert']); ?>"
                           aria-describedby="<?php echo $help_id; ?>">
                    <?php } elseif ($templates_row['eingabe'] == 'farbe') { ?>
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" id="<?php echo $field_id; ?>" name="<?php echo htmlspecialchars($templates_row['variablenname']); ?>"
                               class="form-control font-monospace" style="max-width: 140px"
                               value="<?php echo htmlspecialchars($templates_row['wert']); ?>"
                               onchange="updatecolor(prev_<?php echo htmlspecialchars($templates_row['variablenname']); ?>,this.value)"
                               aria-describedby="<?php echo $help_id; ?>">
                        <input type="button" disabled id="prev_<?php echo htmlspecialchars($templates_row['variablenname']); ?>"
                               class="border" style="background:<?php echo htmlspecialchars($templates_row['wert']); ?>; width: 60px; height: 38px;"
                               aria-label="Farbvorschau">
                    </div>
                    <?php } else { ?>
                    <!-- Eigener Eingabetyp - aus Sicherheitsgruenden nur escaped angezeigt -->
                    <div class="form-control-plaintext small text-muted">
                        <?php echo htmlspecialchars($templates_row['eingabe']); ?>
                    </div>
                    <?php } ?>
                </div>
                <?php
            }
            echo '</div></section>';
        }
        ?>

        <div class="pdl-tpl-savebar d-flex flex-wrap gap-2 align-items-center">
            <span class="badge text-bg-secondary" id="pdlTplDirtyCount">0 ungespeicherte Änderungen</span>
            <span class="form-text small mb-0 d-none d-md-block">Tipp: <kbd>Strg</kbd>+<kbd>S</kbd> speichert.</span>
            <div class="ms-auto d-flex gap-2">
                <a href="templates.php" class="btn btn-outline-light btn-sm" id="pdlTplCancel">Abbrechen</a>
                <button type="submit" class="btn btn-primary" id="pdlTplSave">Templates ändern</button>
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
    var dirtyBadge = document.getElementById('pdlTplDirtyCount');
    var saveBtn = document.getElementById('pdlTplSave');
    var form = document.getElementById('pdlTplForm');
    var lastFocused = null;

    function updateDirtyBadge() {
        if (!dirtyBadge) return;
        if (dirtyCount === 0) {
            dirtyBadge.textContent = '0 ungespeicherte Änderungen';
            dirtyBadge.classList.remove('text-bg-warning');
            dirtyBadge.classList.add('text-bg-secondary');
            saveBtn.classList.remove('btn-warning');
            saveBtn.classList.add('btn-primary');
        } else {
            dirtyBadge.textContent = dirtyCount + (dirtyCount === 1 ? ' ungespeicherte Änderung' : ' ungespeicherte Änderungen');
            dirtyBadge.classList.remove('text-bg-secondary');
            dirtyBadge.classList.add('text-bg-warning');
            saveBtn.classList.remove('btn-primary');
            saveBtn.classList.add('btn-warning');
        }
    }

    document.querySelectorAll('.pdl-tpl-textarea').forEach(function(textarea){
        var wrap = textarea.closest('[data-pdl-editor]');
        var statusEl = wrap.querySelector('[data-pdl-status]');
        var fullscreenBtn = wrap.querySelector('.pdl-tpl-fullscreen');
        var findBtn = wrap.querySelector('.pdl-tpl-find');
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
                'F11': function(cmInst) {
                    cmInst.setOption('fullScreen', !cmInst.getOption('fullScreen'));
                },
                'Esc': function(cmInst) {
                    if (cmInst.getOption('fullScreen')) cmInst.setOption('fullScreen', false);
                },
                'Ctrl-/': 'toggleComment',
                'Cmd-/': 'toggleComment'
            }
        });

        var original = cm.getValue();
        cm.markClean();
        var isDirty = false;

        cm.on('change', function() {
            var dirty = !cm.isClean();
            if (dirty !== isDirty) {
                isDirty = dirty;
                if (dirty) {
                    dirtyCount++;
                    statusEl.textContent = '● geändert';
                    statusEl.classList.add('is-dirty');
                } else {
                    dirtyCount = Math.max(0, dirtyCount - 1);
                    statusEl.textContent = 'unverändert';
                    statusEl.classList.remove('is-dirty');
                }
                updateDirtyBadge();
            }
        });

        cm.on('focus', function() { lastFocused = cm; });

        editors.push({cm: cm, original: original, statusEl: statusEl, wrap: wrap,
            getDirty: function(){ return isDirty; },
            setClean: function(){
                cm.markClean();
                if (isDirty) {
                    isDirty = false;
                    dirtyCount = Math.max(0, dirtyCount - 1);
                    statusEl.textContent = 'unverändert';
                    statusEl.classList.remove('is-dirty');
                    updateDirtyBadge();
                }
            }
        });

        fullscreenBtn.addEventListener('click', function(){
            cm.setOption('fullScreen', !cm.getOption('fullScreen'));
            cm.focus();
        });
        findBtn.addEventListener('click', function(){
            cm.execCommand('find');
        });
        resetBtn.addEventListener('click', function(){
            if (!isDirty) return;
            if (confirm('Diesen Editor auf den Stand beim Öffnen zurücksetzen?')) {
                cm.setValue(original);
                cm.markClean();
                if (isDirty) {
                    isDirty = false;
                    dirtyCount = Math.max(0, dirtyCount - 1);
                    statusEl.textContent = 'unverändert';
                    statusEl.classList.remove('is-dirty');
                    updateDirtyBadge();
                }
            }
        });
    });

    document.querySelectorAll('.pdl-tpl-placeholder-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var ph = btn.getAttribute('data-placeholder');
            var target = lastFocused;
            if (!target && editors.length > 0) target = editors[0].cm;
            if (!target) return;
            target.replaceSelection(ph);
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
            var btn = document.querySelector('.pdl-tpl-fontsize[data-size="'+savedSize+'"]');
            if (btn) btn.click();
        }
    } catch(e){}

    document.addEventListener('keydown', function(e){
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            editors.forEach(function(e){ e.cm.save(); });
            form.submit();
        }
    });

    form.addEventListener('submit', function(){
        editors.forEach(function(e){ e.cm.save(); });
        dirtyCount = 0;
    });

    window.addEventListener('beforeunload', function(e){
        if (dirtyCount > 0) {
            e.preventDefault();
            e.returnValue = '';
            return '';
        }
    });

    updateDirtyBadge();
})();
</script>
<?php
  }
}
else {
    echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.');
}
include("footer.inc.php");
?>
