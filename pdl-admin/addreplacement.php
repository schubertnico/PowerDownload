<?php
/**
 * PowerDownload - Ersetzung hinzufügen (Zensur, Glossar, Smiley)
 */
include("header.inc.php");
include_once("system_helpers.inc.php");

$type = pdl_sys_get('type');
$is_post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$wort_raw = pdl_sys_post('wort');
$old_raw = pdl_sys_post('old');
$neu_raw = pdl_sys_post('neu');
$code_raw = pdl_sys_post('code');
$smilie_url_raw = pdl_sys_post('smilie_url');

// Datei-Upload-Variablen
$smilie_tmp = isset($_FILES['smilie']['tmp_name']) && is_string($_FILES['smilie']['tmp_name']) ? $_FILES['smilie']['tmp_name'] : '';
$smilie_name = isset($_FILES['smilie']['name']) && is_string($_FILES['smilie']['name']) ? $_FILES['smilie']['name'] : '';

if (!pdl_sys_can($user_rights, 'replacements')) {
    echo pdl_admin_alert('warning', pdl_sys_denied_text('replacements'));
    include("footer.inc.php");
    return;
}

$rep_t = pdl_sys_ident($sql_table['replacements']);

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Vorlagen und Ersetzungen'],
    ['title' => 'Ersetzungen', 'href' => 'showreplacements.php'],
    ['title' => 'Ersetzung hinzufügen'],
]);
echo '<h1 class="h3 pdl-page-title">Ersetzung hinzufügen</h1>';

$errors = [];

/**
 * Legt den Eintrag an und liefert die neue ID (0 bei Fehler).
 */
$insert = static function (string $old, string $neu, string $kind) use ($db_handler, $rep_t): int {
    $ok = pdl_sys_exec($db_handler, 'INSERT INTO ' . $rep_t . " (`old`, `neu`, `type`) VALUES ('"
        . $db_handler->sql_escape_string($old) . "', '" . $db_handler->sql_escape_string($neu) . "', '" . $kind . "')");
    return $ok ? max(1, (int) $db_handler->sql_insert_id()) : 0;
};
$exists = static function (string $old, string $kind) use ($db_handler, $rep_t): bool {
    return pdl_sys_scalar_int($db_handler, 'SELECT COUNT(*) FROM ' . $rep_t . " WHERE `type` = '" . $kind
        . "' AND BINARY `old` = '" . $db_handler->sql_escape_string($old) . "'") > 0;
};
$render_errors = static function (array $errs): string {
    $labels = ['_csrf' => 'Sicherheits-Token', '_db' => 'Datenbank', 'wort' => 'Wort', 'old' => 'Begriff im Text',
        'neu' => 'Ersetzen durch', 'code' => 'Kürzel', 'smilie' => 'Bild', 'smilie_url' => 'Bildadresse'];
    $items = '';
    foreach ($errs as $field => $msg) {
        $items .= '<li>' . htmlspecialchars($labels[$field] ?? (string) $field, ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars((string) $msg, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    return '<strong>Bitte korrigieren Sie folgende Eingaben:</strong><ul class="mb-0">' . $items . '</ul>';
};
$done = static function (string $message, string $kind_param, string $again): void {
    echo pdl_admin_alert('success', '<strong>' . $message . '</strong> '
        . '<a class="alert-link" href="addreplacement.php?type=' . $kind_param . '" id="pdlReplAgain">' . $again . '</a>'
        . ' oder <a class="alert-link" href="showreplacements.php" id="pdlReplOverview">zurück zur Übersicht</a>.');
};

if ($type === 'b') {
    if ($is_post) {
        $wort = trim($wort_raw);
        if (!pdl_sys_csrf_ok()) {
            $errors['_csrf'] = pdl_sys_csrf_error_text();
        }
        if ($wort === '') {
            $errors['wort'] = 'Pflichtfeld';
        } elseif (strlen($wort) > 128) {
            $errors['wort'] = 'Höchstens 128 Zeichen.';
        } elseif ($exists($wort, 'b')) {
            $errors['wort'] = 'Dieses Wort steht bereits in der Zensurliste.';
        }
        if (empty($errors)) {
            $new_id = $insert($wort, '', 'b');
            if ($new_id > 0) {
                pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'replacement_badword', $new_id);
                $done('Zensur-Eintrag „' . htmlspecialchars($wort, ENT_QUOTES, 'UTF-8') . '“ wurde angelegt.', 'b', 'Weiteren Zensur-Eintrag hinzufügen');
                include("footer.inc.php");
                return;
            }
            $errors['_db'] = 'Die Datenbank hat den Eintrag abgelehnt. Bitte versuchen Sie es erneut.';
        }
        echo pdl_admin_alert('danger', $render_errors($errors));
    }
    $wort_attr = htmlspecialchars($wort_raw, ENT_QUOTES, 'UTF-8');
    ?>
<form action="addreplacement.php?type=b" method="post" id="pdlReplForm" novalidate>
    <?php echo csrf_input(); ?>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Zensur-Eintrag hinzufügen</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlBwWort" class="form-label">Zu zensierendes Wort <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlBwWort" name="wort" maxlength="128" class="form-control<?php echo isset($errors['wort']) ? ' is-invalid' : ''; ?>" required aria-required="true" aria-describedby="pdlBwWortHelp" value="<?php echo $wort_attr; ?>">
                <div id="pdlBwWortHelp" class="form-text">Pflichtfeld. Das Wort wird in Release-Texten und Kommentaren bis auf den ersten Buchstaben durch Sternchen ersetzt, auch innerhalb längerer Wörter und unabhängig von Groß- und Kleinschreibung.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="showreplacements.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlReplSave">Zensur-Eintrag hinzufügen</button>
    </div>
</form>
<?php
} elseif ($type === 'g') {
    if ($is_post) {
        $old = trim($old_raw);
        $neu = trim($neu_raw);
        if (!pdl_sys_csrf_ok()) {
            $errors['_csrf'] = pdl_sys_csrf_error_text();
        }
        if ($old === '') {
            $errors['old'] = 'Pflichtfeld';
        } elseif (strlen($old) > 128) {
            $errors['old'] = 'Höchstens 128 Zeichen.';
        } elseif ($exists($old, 'g')) {
            $errors['old'] = 'Für diesen Begriff gibt es bereits einen Glossar-Eintrag.';
        }
        if ($neu === '') {
            $errors['neu'] = 'Pflichtfeld';
        } elseif (strlen($neu) > 255) {
            $errors['neu'] = 'Höchstens 255 Zeichen (derzeit ' . strlen($neu) . ').';
        }
        if (empty($errors)) {
            $new_id = $insert($old, $neu, 'g');
            if ($new_id > 0) {
                pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'replacement_glossary', $new_id);
                $done('Glossar-Eintrag „' . htmlspecialchars($old, ENT_QUOTES, 'UTF-8') . '“ wurde angelegt.', 'g', 'Weiteren Glossar-Eintrag hinzufügen');
                include("footer.inc.php");
                return;
            }
            $errors['_db'] = 'Die Datenbank hat den Eintrag abgelehnt. Bitte versuchen Sie es erneut.';
        }
        echo pdl_admin_alert('danger', $render_errors($errors));
    }
    $old_attr = htmlspecialchars($old_raw, ENT_QUOTES, 'UTF-8');
    $neu_attr = htmlspecialchars($neu_raw, ENT_QUOTES, 'UTF-8');
    ?>
<form action="addreplacement.php?type=g" method="post" id="pdlReplForm" novalidate>
    <?php echo csrf_input(); ?>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Glossar-Eintrag hinzufügen</h2></header>
        <div class="card-body">
            <p class="form-text">Ein Glossar-Eintrag ersetzt beim Anzeigen einen Begriff durch einen anderen Text, z. B. einen Link. Mit eindeutigen Kürzeln wie <code>#lizenz#</code> entstehen Textbausteine, die Sie an einer Stelle pflegen.</p>
            <div class="mb-3">
                <label for="pdlGlOld" class="form-label">Begriff im Text <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlGlOld" name="old" maxlength="128" class="form-control<?php echo isset($errors['old']) ? ' is-invalid' : ''; ?>" required aria-required="true" aria-describedby="pdlGlOldHelp" value="<?php echo $old_attr; ?>">
                <div id="pdlGlOldHelp" class="form-text">Pflichtfeld. Wird genau so gesucht (Groß- und Kleinschreibung zählen), auch mitten in Wörtern.</div>
            </div>
            <div class="mb-3">
                <label for="pdlGlNeu" class="form-label">Ersetzen durch <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlGlNeu" name="neu" maxlength="255" class="form-control<?php echo isset($errors['neu']) ? ' is-invalid' : ''; ?>" required aria-required="true" aria-describedby="pdlGlNeuHelp" value="<?php echo $neu_attr; ?>">
                <div id="pdlGlNeuHelp" class="form-text">Pflichtfeld, höchstens 255 Zeichen. HTML wirkt, z. B. <code>&lt;a href="https://example.org/lizenz"&gt;Lizenz&lt;/a&gt;</code>. BB-Code wie <code>[b]…[/b]</code> wirkt hier dagegen nicht, weil das Glossar erst nach dem BB-Code eingesetzt wird.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="showreplacements.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlReplSave">Glossar-Eintrag hinzufügen</button>
    </div>
</form>
<?php
} elseif ($type === 's') {
    if ($is_post) {
        $code = trim($code_raw);
        $smilie_url = trim($smilie_url_raw);
        if (!pdl_sys_csrf_ok()) {
            $errors['_csrf'] = pdl_sys_csrf_error_text();
        }
        if ($code === '') {
            $errors['code'] = 'Pflichtfeld';
        } elseif (strlen($code) > 20) {
            $errors['code'] = 'Höchstens 20 Zeichen.';
        } elseif ($exists($code, 's')) {
            $errors['code'] = 'Für dieses Kürzel gibt es bereits einen Smiley.';
        }
        $has_upload = $smilie_tmp !== '' && is_uploaded_file($smilie_tmp);
        $has_url = $smilie_url !== '';
        if (!$has_upload && !$has_url) {
            $errors['smilie'] = 'Bitte laden Sie ein Bild hoch oder geben Sie die Adresse eines Bildes an.';
        }
        if ($has_upload) {
            $dot = strrpos($smilie_name, '.');
            $ext = $dot === false ? '' : strtolower(substr($smilie_name, $dot + 1));
            $info = @getimagesize($smilie_tmp);
            $allowed_types = [IMAGETYPE_GIF, IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP];
            if (!in_array($ext, ['gif', 'png', 'jpg', 'jpeg', 'webp'], true) || $info === false || !in_array($info[2], $allowed_types, true)) {
                $errors['smilie'] = 'Der Smiley muss ein Bild im Format GIF, PNG, JPG oder WebP sein.';
            }
        } elseif ($has_url) {
            $url_error = pdl_validate_url_optional($smilie_url);
            if ($url_error !== null || strlen($smilie_url) > 255) {
                $errors['smilie_url'] = 'Bitte geben Sie eine vollständige Bildadresse ein, die mit https:// beginnt (höchstens 255 Zeichen).';
            }
        }
        if (empty($errors)) {
            if ($has_upload) {
                // Entschärft auch doppelte Endungen: „bild.php.png“ → „bild_php.png“.
                $safe_name = pdl_sanitize_upload_filename($smilie_name);
                $smilies_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'pdl-gfx' . DIRECTORY_SEPARATOR . 'smilies';
                if (!is_dir($smilies_dir)) {
                    @mkdir($smilies_dir, 0775, true);
                }
                // Schutzdatei nachlegen, falls sie beim FTP-Upload fehlte.
                if (is_dir($smilies_dir) && !is_file($smilies_dir . DIRECTORY_SEPARATOR . '.htaccess')) {
                    @file_put_contents($smilies_dir . DIRECTORY_SEPARATOR . '.htaccess', pdl_admin_smilies_htaccess());
                }
                // Gleichnamige Datei nicht überschreiben: name_1.png, name_2.png …
                $dot = strrpos($safe_name, '.');
                $base = $dot === false ? $safe_name : substr($safe_name, 0, $dot);
                $suffix = $dot === false ? '' : substr($safe_name, $dot);
                $final = $safe_name;
                for ($n = 1; is_file($smilies_dir . DIRECTORY_SEPARATOR . $final) && $n < 1000; $n++) {
                    $final = $base . '_' . $n . $suffix;
                }
                $target = $smilies_dir . DIRECTORY_SEPARATOR . $final;
                if (move_uploaded_file($smilie_tmp, $target)) {
                    @chmod($target, 0644);
                    $new_id = $insert($code, 'pdl-gfx/smilies/' . $final, 's');
                    if ($new_id > 0) {
                        pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'replacement_smilie', $new_id);
                        $done('Smiley „' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '“ wurde hochgeladen und angelegt.', 's', 'Weiteren Smiley hinzufügen');
                        include("footer.inc.php");
                        return;
                    }
                    @unlink($target);
                    $errors['_db'] = 'Die Datenbank hat den Eintrag abgelehnt. Bitte versuchen Sie es erneut.';
                } else {
                    $errors['smilie'] = 'Das Bild konnte nicht in den Ordner pdl-gfx/smilies/ gespeichert werden. Bitte prüfen Sie die Schreibrechte.';
                }
            } else {
                $new_id = $insert($code, $smilie_url, 's');
                if ($new_id > 0) {
                    pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'replacement_smilie', $new_id);
                    $done('Smiley „' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '“ wurde angelegt.', 's', 'Weiteren Smiley hinzufügen');
                    include("footer.inc.php");
                    return;
                }
                $errors['_db'] = 'Die Datenbank hat den Eintrag abgelehnt. Bitte versuchen Sie es erneut.';
            }
        }
        echo pdl_admin_alert('danger', $render_errors($errors));
    }
    $code_attr = htmlspecialchars($code_raw, ENT_QUOTES, 'UTF-8');
    $smilie_url_attr = htmlspecialchars($smilie_url_raw, ENT_QUOTES, 'UTF-8');
    ?>
<form action="addreplacement.php?type=s" method="post" enctype="multipart/form-data" id="pdlReplForm" novalidate>
    <?php echo csrf_input(); ?>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Smiley hinzufügen</h2></header>
        <div class="card-body">
            <p class="form-text">Smileys ersetzen kurze Kürzel wie <code>:)</code> oder <code>:D</code> in Release-Texten und Kommentaren durch kleine Bilder.</p>
            <div class="mb-3">
                <label for="pdlSmCode" class="form-label">Kürzel <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlSmCode" name="code" class="form-control<?php echo isset($errors['code']) ? ' is-invalid' : ''; ?>" required aria-required="true" aria-describedby="pdlSmCodeHelp" value="<?php echo $code_attr; ?>" maxlength="20" style="max-width: 16rem">
                <div id="pdlSmCodeHelp" class="form-text">Pflichtfeld. Diese Zeichenfolge wird im Text durch das Bild ersetzt (z.&nbsp;B. <code>:smile:</code>).</div>
            </div>
            <div class="mb-3">
                <label for="pdlSmFile" class="form-label">Bild hochladen</label>
                <input type="file" id="pdlSmFile" name="smilie" class="form-control<?php echo isset($errors['smilie']) ? ' is-invalid' : ''; ?>" accept="image/png,image/gif,image/jpeg,image/webp" aria-describedby="pdlSmFileHelp">
                <div id="pdlSmFileHelp" class="form-text">GIF, PNG, JPG oder WebP. Die Datei wird unter <code>pdl-gfx/smilies/</code> gespeichert; eine gleichnamige Datei wird nicht überschrieben.</div>
            </div>
            <div class="mb-3">
                <label for="pdlSmUrl" class="form-label">… oder Adresse eines Bildes</label>
                <input type="url" id="pdlSmUrl" name="smilie_url" maxlength="255" class="form-control<?php echo isset($errors['smilie_url']) ? ' is-invalid' : ''; ?>" value="<?php echo $smilie_url_attr; ?>" placeholder="https://" aria-describedby="pdlSmUrlHelp">
                <div id="pdlSmUrlHelp" class="form-text">Liegt das Bild schon im Internet, geben Sie die vollständige Adresse mit <code>https://</code> an.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="showreplacements.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlReplSave">Smiley hinzufügen</button>
    </div>
</form>
<?php
} else {
    ?>
<section class="card pdl-card mx-auto" style="max-width: 640px;">
    <header class="card-header"><h2 class="h5 mb-0">Welche Art von Ersetzung möchten Sie hinzufügen?</h2></header>
    <div class="card-body">
        <p class="form-text mb-0">Ersetzungen wirken beim Anzeigen von Release-Texten und Kommentaren. Ob sie aktiv sind, legen Sie unter Einstellungen → Textformatierung fest.</p>
    </div>
    <div class="list-group list-group-flush">
        <a class="list-group-item list-group-item-action bg-transparent text-body" href="addreplacement.php?type=b" id="pdlReplTypeB">
            <strong>Zensur</strong>
            <div class="text-muted small">Unerwünschte Wörter werden durch Sternchen unkenntlich gemacht.</div>
        </a>
        <a class="list-group-item list-group-item-action bg-transparent text-body" href="addreplacement.php?type=g" id="pdlReplTypeG">
            <strong>Glossar</strong>
            <div class="text-muted small">Ein Begriff oder Kürzel wird durch einen anderen Text oder HTML (z.&nbsp;B. einen Link) ersetzt.</div>
        </a>
        <a class="list-group-item list-group-item-action bg-transparent text-body" href="addreplacement.php?type=s" id="pdlReplTypeS">
            <strong>Smiley</strong>
            <div class="text-muted small">Kürzel wie <code>:)</code> werden durch kleine Bilder ersetzt.</div>
        </a>
    </div>
    <div class="card-footer text-muted small">Alle Ersetzungen lassen sich unter „Ersetzung löschen“ wieder entfernen.</div>
</section>
    <?php
}

include("footer.inc.php");
