<?php
include("header.inc.php");

$ordner_id = isset($_GET['ordner_id']) ? (int) $_GET['ordner_id'] : (isset($_POST['ordner_id']) ? (int) $_POST['ordner_id'] : 0);
$submit = isset($_GET['submit']) ? (int) $_GET['submit'] : 0;
$name = (string) ($_POST['name'] ?? '');
$text = (string) ($_POST['text'] ?? '');
$sordner_id = isset($_POST['sordner_id']) ? (int) $_POST['sordner_id'] : 0;
$move_files = (string) ($_POST['move_files'] ?? '');
$delete_files = (string) ($_POST['delete_files'] ?? '');
$release_to = isset($_POST['release_to']) ? (int) $_POST['release_to'] : 0;
$move_subdirs = (string) ($_POST['move_subdirs'] ?? '');
$subdirs_to = isset($_POST['subdirs_to']) ? (int) $_POST['subdirs_to'] : 0;
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

$errors = [];

if (!pdl_admin_require_right('editdirs')) {
    include("footer.inc.php");
    return;
}

$ordner_id_escaped = $db_handler->sql_escape_int($ordner_id);
$ordner_row = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM `{$sql_table['ordner']}` WHERE ordner_id='$ordner_id_escaped' LIMIT 1"));

$breadcrumb = [
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Ordner und Releases', 'href' => 'or_list.php?ordner_id=' . $ordner_id . '#pdl-aktuell'],
    ['title' => 'Ordner bearbeiten'],
];
pdl_admin_breadcrumb($breadcrumb);
echo '<h1 class="h3 pdl-page-title">Ordner bearbeiten</h1>';

if ($ordner_id <= 0 || !is_array($ordner_row)) {
    echo pdl_admin_result('warning', 'Diesen Ordner gibt es nicht (mehr).', [
        ['label' => 'Zur Übersicht', 'href' => 'or_list.php', 'primary' => true],
    ]);
    include("footer.inc.php");
    return;
}

if ($submit === 1) {
    if (!csrf_verify($csrf_token_post)) {
        $errors['_csrf'] = 'Sicherheits-Token ungültig oder abgelaufen. Bitte senden Sie das Formular erneut ab.';
    }
    if (empty($errors)) {
        $errors = pdl_validate_required(['name' => $name], ['name']);
        if (!isset($errors['name'])) {
            $lenErr = pdl_validate_max_length(trim($name), 128);
            if ($lenErr !== null) {
                $errors['name'] = $lenErr;
            }
        }
        if (!pdl_ordner_exists($db_handler, $sql_table, $sordner_id)) {
            $errors['sordner_id'] = 'Der übergeordnete Ordner existiert nicht.';
        } else {
            // Auch indirekte Kreise verhindern (Ordner unter eigenen Unterordner).
            $parentErr = pdl_validate_ordner_no_cycle($db_handler, $sql_table, $ordner_id, $sordner_id);
            if ($parentErr !== null) {
                $errors['sordner_id'] = $parentErr;
            }
        }
        if ($move_files === 'Y' && $delete_files === 'Y') {
            $errors['release_to'] = 'Bitte wählen Sie entweder „Releases verschieben“ oder „Releases löschen“.';
        }
        if ($move_files === 'Y' && !pdl_admin_has_right('editfiles')) {
            $errors['release_to'] = 'Zum Verschieben von Releases fehlt Ihnen das Recht „Releases und Dateien bearbeiten“.';
        } elseif ($move_files === 'Y' && !pdl_ordner_exists($db_handler, $sql_table, $release_to)) {
            $errors['release_to'] = 'Der Zielordner für die Releases existiert nicht.';
        }
        if ($delete_files === 'Y' && !pdl_admin_has_right('delfiles')) {
            $errors['release_to'] = 'Zum Löschen von Releases fehlt Ihnen das Recht „Releases und Dateien löschen“.';
        }
        if ($move_subdirs === 'Y') {
            if (!pdl_ordner_exists($db_handler, $sql_table, $subdirs_to)) {
                $errors['subdirs_to'] = 'Der Zielordner für die Unterordner existiert nicht.';
            } else {
                // Ziel darf nicht im eigenen Teilbaum liegen, sonst entstünde ein Kreis.
                $subErr = pdl_validate_ordner_no_cycle($db_handler, $sql_table, $ordner_id, $subdirs_to);
                if ($subErr !== null) {
                    $errors['subdirs_to'] = 'Die Unterordner können nicht in diesen Ordner oder einen seiner Unterordner verschoben werden.';
                }
            }
        }
    }

    if (empty($errors)) {
        $name_escaped = $db_handler->sql_escape_string(trim($name));
        $text_escaped = $db_handler->sql_escape_string($text);
        $sordner_id_escaped = $db_handler->sql_escape_int($sordner_id);
        $ok = $db_handler->sql_query("UPDATE `{$sql_table['ordner']}` SET name='$name_escaped', text='$text_escaped', sordner_id='$sordner_id_escaped' WHERE ordner_id='$ordner_id_escaped'") === true;
        $done = [];
        if ($ok && $move_files === 'Y') {
            $release_to_escaped = $db_handler->sql_escape_int($release_to);
            $ok = $db_handler->sql_query("UPDATE `{$sql_table['release']}` SET ordner_id='$release_to_escaped' WHERE ordner_id='$ordner_id_escaped'") === true;
            $done[] = 'Die Releases wurden nach „' . htmlspecialchars(pdl_admin_ordner_name($release_to), ENT_QUOTES, 'UTF-8') . '“ verschoben.';
        }
        if ($ok && $delete_files === 'Y') {
            $files_res = $db_handler->sql_query("SELECT release_id FROM `{$sql_table['release']}` WHERE ordner_id='$ordner_id_escaped'");
            $deleted = 0;
            while ($files_row = $db_handler->sql_fetch_array($files_res)) {
                if (!delrelease((int) $files_row['release_id'])) {
                    $ok = false;
                    break;
                }
                pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'release', (int) $files_row['release_id']);
                $deleted++;
            }
            $done[] = pdl_admin_count_label($deleted, 'Release wurde', 'Releases wurden') . ' gelöscht.';
        }
        if ($ok && $move_subdirs === 'Y') {
            $subdirs_to_escaped = $db_handler->sql_escape_int($subdirs_to);
            $ok = $db_handler->sql_query("UPDATE `{$sql_table['ordner']}` SET sordner_id='$subdirs_to_escaped' WHERE sordner_id='$ordner_id_escaped' AND ordner_id<>'$subdirs_to_escaped'") === true;
            $done[] = 'Die Unterordner wurden nach „' . htmlspecialchars(pdl_admin_ordner_name($subdirs_to), ENT_QUOTES, 'UTF-8') . '“ verschoben.';
        }

        if ($ok) {
            pdl_audit_log($db_handler, $sql_table, $user_details, 'update', 'ordner', $ordner_id);
            echo pdl_admin_result(
                'success',
                '<strong>Der Ordner „' . htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8') . '“ wurde gespeichert.</strong> ' . implode(' ', $done),
                [
                    ['label' => 'Zur Übersicht', 'href' => 'or_list.php?ordner_id=' . $ordner_id . '#pdl-aktuell', 'id' => 'pdlNextOverview', 'primary' => true],
                    ['label' => 'Ordner erneut bearbeiten', 'href' => 'editdir.php?ordner_id=' . $ordner_id, 'id' => 'pdlNextEditDir'],
                ]
            );
            include("footer.inc.php");
            return;
        }
        echo pdl_admin_db_error('Der Ordner konnte nicht vollständig gespeichert werden.');
    }
}

if (!empty($errors)) {
    echo pdl_admin_alert('danger', pdl_admin_render_errors($errors));
}

$subordner_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT ordner_id FROM `{$sql_table['ordner']}` WHERE sordner_id='$ordner_id_escaped'"));
$release_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT release_id FROM `{$sql_table['release']}` WHERE ordner_id='$ordner_id_escaped'"));

if ($submit !== 1) {
    $name = (string) ($ordner_row['name'] ?? '');
    $text = (string) ($ordner_row['text'] ?? '');
    $sordner_id = (int) ($ordner_row['sordner_id'] ?? 0);
}
$name_attr = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$text_attr = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
?>
<form action="editdir.php?submit=1" method="post" novalidate id="pdlEdDir">
    <?php echo csrf_input(); ?>
    <input type="hidden" name="ordner_id" value="<?php echo $ordner_id; ?>">
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Allgemein</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlEdOName" class="form-label">Name <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlEdOName" name="name" maxlength="128" class="form-control<?php echo isset($errors['name']) ? ' is-invalid' : ''; ?>" required<?php if (isset($errors['name'])) echo ' aria-invalid="true"'; ?> value="<?php echo $name_attr; ?>">
                <div class="form-text">So heißt der Ordner in der Übersicht.</div>
            </div>
            <div class="mb-3">
                <label for="pdlEdOSordner" class="form-label">Übergeordneter Ordner</label>
                <select id="pdlEdOSordner" name="sordner_id" class="form-select<?php echo isset($errors['sordner_id']) ? ' is-invalid' : ''; ?>">
                    <option value="0" data-name="Index"<?php echo $sordner_id === 0 ? ' selected' : ''; ?>>Index</option>
                    <?php echo pdl_admin_ordner_options($sordner_id, $ordner_id); ?>
                </select>
                <div class="form-text">In welchem Ordner dieser Ordner liegt. Der Ordner selbst und seine Unterordner stehen hier nicht zur Wahl.</div>
            </div>
            <div class="mb-3">
                <label for="pdlEdOText" class="form-label">Beschreibung</label>
                <textarea id="pdlEdOText" name="text" class="form-control" rows="5"><?php echo $text_attr; ?></textarea>
                <div class="form-text">Ausführlichere Beschreibung, was in dem Ordner zu finden ist.</div>
            </div>
        </div>
    </section>
    <?php if ($subordner_check > 0 || $release_check > 0) { ?>
    <section class="card pdl-card mb-4 pdl-danger-action" id="pdlEdOInhalte">
        <header class="card-header bg-danger text-white"><h2 class="h5 mb-0">Inhalte verschieben oder löschen</h2></header>
        <div class="card-body">
            <p class="form-text">Was soll mit den <?php echo pdl_admin_count_label($release_check, 'Release', 'Releases'); ?> und <?php echo pdl_admin_count_label($subordner_check, 'Unterordner', 'Unterordnern'); ?> dieses Ordners geschehen? Ohne Auswahl bleibt alles, wie es ist.</p>
            <?php if ($release_check > 0) { ?>
                <?php if (pdl_admin_has_right('editfiles')) { ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="move_files" value="Y" id="pdlEdOMoveFiles"<?php echo $move_files === 'Y' ? ' checked' : ''; ?>>
                    <label class="form-check-label" for="pdlEdOMoveFiles">Releases verschieben nach</label>
                </div>
                <label for="pdlEdOReleaseTo" class="visually-hidden">Zielordner für die Releases</label>
                <select name="release_to" id="pdlEdOReleaseTo" class="form-select mb-3 ms-4 w-auto">
                    <option value="0" data-name="Index">Index</option>
                    <?php echo pdl_admin_ordner_options($release_to); ?>
                </select>
                <?php } ?>
                <?php if (pdl_admin_has_right('delfiles')) { ?>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="delete_files" value="Y" id="pdlEdODelFiles">
                    <label class="form-check-label text-danger fw-bold" for="pdlEdODelFiles">Releases löschen (samt Dateien, Screenshots und Kommentaren)</label>
                </div>
                <?php } ?>
            <?php }
            if ($subordner_check > 0) { ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="move_subdirs" value="Y" id="pdlEdOMoveSub"<?php echo $move_subdirs === 'Y' ? ' checked' : ''; ?>>
                    <label class="form-check-label" for="pdlEdOMoveSub">Unterordner verschieben nach</label>
                </div>
                <label for="pdlEdOSubdirsTo" class="visually-hidden">Zielordner für die Unterordner</label>
                <select name="subdirs_to" id="pdlEdOSubdirsTo" class="form-select mb-3 ms-4 w-auto<?php echo isset($errors['subdirs_to']) ? ' is-invalid' : ''; ?>">
                    <option value="0" data-name="Index">Index</option>
                    <?php echo pdl_admin_ordner_options($subdirs_to, $ordner_id); ?>
                </select>
            <?php } ?>
        </div>
    </section>
    <?php } ?>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="or_list.php?ordner_id=<?php echo $ordner_id; ?>#pdl-aktuell" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlEdOSave">Änderungen speichern</button>
    </div>
</form>
<?php
include("footer.inc.php");
