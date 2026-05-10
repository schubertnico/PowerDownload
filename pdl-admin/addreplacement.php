<?php
include("header.inc.php");

// Anfrage-Daten einsammeln (sicher: Strings, Path-Traversal-Schutz).
$type = isset($_GET['type']) ? (string) $_GET['type'] : '';
$submit = isset($_GET['submit']) ? (int) $_GET['submit'] : 0;
$wort_raw = isset($_POST['wort']) ? (string) $_POST['wort'] : '';
$old_raw = isset($_POST['old']) ? (string) $_POST['old'] : '';
$neu_raw = isset($_POST['neu']) ? (string) $_POST['neu'] : '';
$code_raw = isset($_POST['code']) ? (string) $_POST['code'] : '';
$smilie_url_raw = isset($_POST['smilie_url']) ? (string) $_POST['smilie_url'] : '';
$csrf_token_post = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';

// Datei-Upload-Variablen
$smilie_tmp = isset($_FILES['smilie']['tmp_name']) ? (string) $_FILES['smilie']['tmp_name'] : '';
$smilie_name = isset($_FILES['smilie']['name']) ? (string) $_FILES['smilie']['name'] : '';

if (($user_rights['replacements'] ?? '') !== 'Y') {
    echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.');
    include("footer.inc.php");
    return;
}

pdl_admin_breadcrumb([
    ['title' => 'Admin-Center', 'href' => 'index.php'],
    ['title' => 'Vorlagen und Ersetzungen'],
    ['title' => 'Ersetzungen anzeigen', 'href' => 'showreplacements.php'],
    ['title' => 'Ersetzung hinzufügen'],
]);
echo '<h1 class="h3 pdl-page-title">Ersetzung hinzufügen</h1>';

$errors = [];

if ($type === 'b') {
    if ($submit === 1) {
        if (!csrf_verify($csrf_token_post)) {
            $errors['_csrf'] = 'Sicherheits-Token ungültig oder abgelaufen.';
        }
        if (trim($wort_raw) === '') {
            $errors['wort'] = 'Pflichtfeld';
        }
        if (empty($errors)) {
            $wort_safe = $db_handler->sql_escape_string(trim($wort_raw));
            $db_handler->sql_query("INSERT INTO `" . $sql_table['replacements'] . "` (old,type) VALUES ('" . $wort_safe . "','b')");
            $new_id = (int) $db_handler->sql_insert_id();
            pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'replacement_badword', $new_id);
            echo pdl_admin_alert(
                'success',
                '<strong>Zensur-Eintrag „' . htmlspecialchars(trim($wort_raw), ENT_QUOTES, 'UTF-8') . '" wurde hinzugefügt.</strong>'
                . ' <a class="alert-link" href="addreplacement.php?type=b">Weiteren Zensur-Eintrag hinzufügen</a>'
                . ' oder <a class="alert-link" href="showreplacements.php">zurück zur Übersicht</a>.'
            );
            include("footer.inc.php");
            return;
        }
        echo pdl_admin_alert('danger', pdl_admin_render_errors($errors));
    }
    $wort_attr = htmlspecialchars($wort_raw, ENT_QUOTES, 'UTF-8');
    ?>
<form action="addreplacement.php?type=b&amp;submit=1" method="post" novalidate>
    <?php echo csrf_input(); ?>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Zensur-Eintrag hinzufügen</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlBwWort" class="form-label">Zu zensierendes Wort <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlBwWort" name="wort" class="form-control<?php echo isset($errors['wort']) ? ' is-invalid' : ''; ?>" required aria-required="true" aria-describedby="pdlBwWortHelp" value="<?php echo $wort_attr; ?>">
                <div id="pdlBwWortHelp" class="form-text">Pflichtfeld. Dieses Wort wird in öffentlichen Texten unkenntlich gemacht (z.&nbsp;B. durch Sternchen ersetzt).</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="showreplacements.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Zensur-Eintrag hinzufügen</button>
    </div>
</form>
<?php
} elseif ($type === 'g') {
    if ($submit === 1) {
        if (!csrf_verify($csrf_token_post)) {
            $errors['_csrf'] = 'Sicherheits-Token ungültig oder abgelaufen.';
        }
        if (trim($old_raw) === '') {
            $errors['old'] = 'Pflichtfeld';
        }
        if (trim($neu_raw) === '') {
            $errors['neu'] = 'Pflichtfeld';
        }
        if (empty($errors)) {
            $old_safe = $db_handler->sql_escape_string(trim($old_raw));
            $neu_safe = $db_handler->sql_escape_string(trim($neu_raw));
            $db_handler->sql_query("INSERT INTO `" . $sql_table['replacements'] . "` (old,neu,type) VALUES ('" . $old_safe . "','" . $neu_safe . "','g')");
            $new_id = (int) $db_handler->sql_insert_id();
            pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'replacement_glossary', $new_id);
            echo pdl_admin_alert(
                'success',
                '<strong>Glossar-Eintrag „' . htmlspecialchars(trim($old_raw), ENT_QUOTES, 'UTF-8')
                . '" → „' . htmlspecialchars(trim($neu_raw), ENT_QUOTES, 'UTF-8') . '" wurde hinzugefügt.</strong>'
                . ' <a class="alert-link" href="addreplacement.php?type=g">Weiteren Glossar-Eintrag hinzufügen</a>'
                . ' oder <a class="alert-link" href="showreplacements.php">zurück zur Übersicht</a>.'
            );
            include("footer.inc.php");
            return;
        }
        echo pdl_admin_alert('danger', pdl_admin_render_errors($errors));
    }
    $old_attr = htmlspecialchars($old_raw, ENT_QUOTES, 'UTF-8');
    $neu_attr = htmlspecialchars($neu_raw, ENT_QUOTES, 'UTF-8');
    ?>
<form action="addreplacement.php?type=g&amp;submit=1" method="post" novalidate>
    <?php echo csrf_input(); ?>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Glossar-Eintrag hinzufügen</h2></header>
        <div class="card-body">
            <p class="form-text">Glossar-Einträge ersetzen Wörter in öffentlichen Texten automatisch. Beispiel: "PHP" könnte durch "PHP (Skriptsprache)" ersetzt werden.</p>
            <div class="mb-3">
                <label for="pdlGlOld" class="form-label">Wort vorher <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlGlOld" name="old" class="form-control<?php echo isset($errors['old']) ? ' is-invalid' : ''; ?>" required aria-required="true" aria-describedby="pdlGlOldHelp" value="<?php echo $old_attr; ?>">
                <div id="pdlGlOldHelp" class="form-text">Pflichtfeld. Das Wort, das im Text gesucht werden soll.</div>
            </div>
            <div class="mb-3">
                <label for="pdlGlNeu" class="form-label">Wort nachher <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlGlNeu" name="neu" class="form-control<?php echo isset($errors['neu']) ? ' is-invalid' : ''; ?>" required aria-required="true" aria-describedby="pdlGlNeuHelp" value="<?php echo $neu_attr; ?>">
                <div id="pdlGlNeuHelp" class="form-text">Pflichtfeld. Die neue Schreibweise. Auch HTML-Code (z.&nbsp;B. ein Link) ist erlaubt.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="showreplacements.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Glossar-Eintrag hinzufügen</button>
    </div>
</form>
<?php
} elseif ($type === 's') {
    if ($submit === 1) {
        if (!csrf_verify($csrf_token_post)) {
            $errors['_csrf'] = 'Sicherheits-Token ungültig oder abgelaufen.';
        }
        if (trim($code_raw) === '') {
            $errors['code'] = 'Pflichtfeld';
        }
        $has_upload = is_uploaded_file($smilie_tmp);
        $has_url = trim($smilie_url_raw) !== '';
        if (!$has_upload && !$has_url) {
            $errors['smilie'] = 'Bitte ein Smilie-Bild hochladen oder eine URL angeben.';
        }
        if ($has_upload) {
            $upErr = pdl_validate_screen_upload($_FILES['smilie'] ?? []);
            // Wir akzeptieren hier auch GIF/PNG, da Smilies oft animiert sind.
            // pdl_validate_screen_upload erlaubt nur JPG → wir prüfen daher
            // selbst auf erlaubte Bild-Endungen.
            $name = (string) $smilie_name;
            $dot = strrpos($name, '.');
            $ext = $dot === false ? '' : strtolower(substr($name, $dot + 1));
            if (preg_match('/[\\/\\\\\\x00]/', $name) === 1 || strpos($name, '..') !== false) {
                $errors['smilie'] = 'Dateiname enthält unzulässige Zeichen.';
            } elseif (!in_array($ext, ['gif', 'png', 'jpg', 'jpeg', 'webp'], true)) {
                $errors['smilie'] = 'Smilie muss eine Bilddatei sein (gif, png, jpg, webp).';
            }
        }
        if ($has_url) {
            $urlErr = pdl_validate_url_optional($smilie_url_raw);
            if ($urlErr !== null) {
                $errors['smilie_url'] = $urlErr;
            }
        }
        if (empty($errors)) {
            $code_safe = $db_handler->sql_escape_string(trim($code_raw));
            if ($has_upload) {
                $safe_name = pdl_sanitize_upload_filename($smilie_name);
                $smilies_dir = realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'pdl-gfx' . DIRECTORY_SEPARATOR . 'smilies';
                if (!is_dir($smilies_dir)) {
                    @mkdir($smilies_dir, 0775, true);
                }
                $target = $smilies_dir . DIRECTORY_SEPARATOR . $safe_name;
                if (move_uploaded_file($smilie_tmp, $target)) {
                    @chmod($target, 0644);
                    $url_db = $db_handler->sql_escape_string('pdl-gfx/smilies/' . $safe_name);
                    $db_handler->sql_query("INSERT INTO `" . $sql_table['replacements'] . "` (old,neu,type) VALUES ('" . $code_safe . "','" . $url_db . "','s')");
                    $new_id = (int) $db_handler->sql_insert_id();
                    pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'replacement_smilie', $new_id);
                    echo pdl_admin_alert(
                        'success',
                        '<strong>Smilie „' . htmlspecialchars(trim($code_raw), ENT_QUOTES, 'UTF-8')
                        . '" wurde hochgeladen und hinzugefügt.</strong>'
                        . ' <a class="alert-link" href="addreplacement.php?type=s">Weiteres Smilie hinzufügen</a>'
                        . ' oder <a class="alert-link" href="showreplacements.php">zurück zur Übersicht</a>.'
                    );
                    include("footer.inc.php");
                    return;
                }
                $errors['smilie'] = 'Smilie konnte nicht ins Zielverzeichnis verschoben werden.';
            } else {
                $url_db = $db_handler->sql_escape_string(trim($smilie_url_raw));
                $db_handler->sql_query("INSERT INTO `" . $sql_table['replacements'] . "` (old,neu,type) VALUES ('" . $code_safe . "','" . $url_db . "','s')");
                $new_id = (int) $db_handler->sql_insert_id();
                pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'replacement_smilie', $new_id);
                echo pdl_admin_alert(
                    'success',
                    '<strong>Smilie „' . htmlspecialchars(trim($code_raw), ENT_QUOTES, 'UTF-8')
                    . '" wurde hinzugefügt.</strong>'
                    . ' <a class="alert-link" href="addreplacement.php?type=s">Weiteres Smilie hinzufügen</a>'
                    . ' oder <a class="alert-link" href="showreplacements.php">zurück zur Übersicht</a>.'
                );
                include("footer.inc.php");
                return;
            }
        }
        // Wenn wir hier ankommen, ist mindestens ein Fehler vorhanden
        // (sonst hätten wir oben mit success+return abgebrochen).
        echo pdl_admin_alert('danger', pdl_admin_render_errors($errors));
    }
    $code_attr = htmlspecialchars($code_raw, ENT_QUOTES, 'UTF-8');
    $smilie_url_attr = htmlspecialchars($smilie_url_raw, ENT_QUOTES, 'UTF-8');
    ?>
<form action="addreplacement.php?type=s&amp;submit=1" method="post" enctype="multipart/form-data" novalidate>
    <?php echo csrf_input(); ?>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Smilie hinzufügen</h2></header>
        <div class="card-body">
            <p class="form-text">Smilies ersetzen kurze Codes wie <code>:)</code> oder <code>:D</code> in Texten automatisch durch kleine Bilder.</p>
            <div class="mb-3">
                <label for="pdlSmCode" class="form-label">Smilie-Code <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlSmCode" name="code" class="form-control<?php echo isset($errors['code']) ? ' is-invalid' : ''; ?>" required aria-required="true" aria-describedby="pdlSmCodeHelp" value="<?php echo $code_attr; ?>" maxlength="20">
                <div id="pdlSmCodeHelp" class="form-text">Pflichtfeld. Diese Zeichenfolge wird im Text gesucht und ersetzt (z.&nbsp;B. <code>:smile:</code>).</div>
            </div>
            <div class="mb-3">
                <label for="pdlSmFile" class="form-label">Smilie-Bild hochladen</label>
                <input type="file" id="pdlSmFile" name="smilie" class="form-control<?php echo isset($errors['smilie']) ? ' is-invalid' : ''; ?>" accept="image/png,image/gif,image/jpeg,image/webp" aria-describedby="pdlSmFileHelp">
                <div id="pdlSmFileHelp" class="form-text">Erlaubte Formate: GIF, PNG, JPG, WebP. Die Datei wird unter <code>pdl-gfx/smilies/</code> gespeichert.</div>
            </div>
            <div class="mb-3">
                <label for="pdlSmUrl" class="form-label">… oder Bild-URL eingeben</label>
                <input type="url" id="pdlSmUrl" name="smilie_url" class="form-control<?php echo isset($errors['smilie_url']) ? ' is-invalid' : ''; ?>" value="<?php echo $smilie_url_attr; ?>" aria-describedby="pdlSmUrlHelp">
                <div id="pdlSmUrlHelp" class="form-text">Wenn das Bild bereits online liegt, hier die vollständige <code>https://…</code>-Adresse angeben.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="showreplacements.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Smilie hinzufügen</button>
    </div>
</form>
<?php
} else {
    ?>
<section class="card pdl-card mx-auto" style="max-width: 640px;">
    <header class="card-header"><h2 class="h5 mb-0">Welche Art von Ersetzung möchten Sie hinzufügen?</h2></header>
    <div class="card-body">
        <p class="form-text mb-3">Bitte wählen Sie eine Kategorie aus. Eine kurze Erklärung steht jeweils dabei.</p>
    </div>
    <div class="list-group list-group-flush">
        <a class="list-group-item list-group-item-action bg-transparent text-body" href="addreplacement.php?type=b">
            <strong>Zensur</strong>
            <div class="text-muted small">Wörter werden in öffentlichen Texten unkenntlich gemacht.</div>
        </a>
        <a class="list-group-item list-group-item-action bg-transparent text-body" href="addreplacement.php?type=g">
            <strong>Glossar</strong>
            <div class="text-muted small">Ein Wort wird automatisch durch eine andere Schreibweise oder durch HTML (z.&nbsp;B. Link) ersetzt.</div>
        </a>
        <a class="list-group-item list-group-item-action bg-transparent text-body" href="addreplacement.php?type=s">
            <strong>Smilie</strong>
            <div class="text-muted small">Kurzcodes wie <code>:)</code> werden durch kleine Bilder ersetzt.</div>
        </a>
    </div>
    <div class="card-footer text-muted small">Hinweis: Alle Ersetzungen können später unter „Ersetzung löschen" entfernt werden.</div>
</section>
    <?php
}

include("footer.inc.php");
?>
