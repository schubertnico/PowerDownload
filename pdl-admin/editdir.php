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

if (($user_rights['editdirs'] ?? '') !== 'Y') {
    echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.');
    include("footer.inc.php");
    return;
}

if ($submit === 1) {
    if (!csrf_verify($csrf_token_post)) {
        $errors['_csrf'] = 'Sicherheits-Token ungültig oder abgelaufen.';
    }
    if (empty($errors)) {
        $errors = pdl_validate_required(['name' => $name], ['name']);
        if (!pdl_ordner_exists($db_handler, $sql_table, $sordner_id)) {
            $errors['sordner_id'] = 'Übergeordneter Ordner existiert nicht.';
        }
        $parentErr = pdl_validate_ordner_parent($ordner_id, $sordner_id);
        if ($parentErr !== null) {
            $errors['sordner_id'] = $parentErr;
        }
        if ($move_files === 'Y' && !pdl_ordner_exists($db_handler, $sql_table, $release_to)) {
            $errors['release_to'] = 'Zielordner für Verschieben existiert nicht.';
        }
        if ($move_subdirs === 'Y' && !pdl_ordner_exists($db_handler, $sql_table, $subdirs_to)) {
            $errors['subdirs_to'] = 'Zielordner für Subordner-Verschieben existiert nicht.';
        }
    }

    if (empty($errors)) {
        $name_escaped = $db_handler->sql_escape_string(trim($name));
        $text_escaped = $db_handler->sql_escape_string($text);
        $sordner_id_escaped = $db_handler->sql_escape_int($sordner_id);
        $ordner_id_escaped = $db_handler->sql_escape_int($ordner_id);
        $db_handler->sql_query("UPDATE `{$sql_table['ordner']}` SET name='$name_escaped', text='$text_escaped', sordner_id='$sordner_id_escaped' WHERE ordner_id='$ordner_id_escaped'");
        if ($move_files === 'Y') {
            $release_to_escaped = $db_handler->sql_escape_int($release_to);
            $files_res = $db_handler->sql_query("SELECT release_id FROM `{$sql_table['release']}` WHERE ordner_id='$ordner_id_escaped'");
            while ($files_row = $db_handler->sql_fetch_array($files_res)) {
                $db_handler->sql_query("UPDATE `{$sql_table['release']}` SET ordner_id='$release_to_escaped' WHERE release_id='" . $db_handler->sql_escape_int($files_row['release_id']) . "'");
            }
        }
        if ($delete_files === 'Y') {
            $files_res = $db_handler->sql_query("SELECT release_id FROM `{$sql_table['release']}` WHERE ordner_id='$ordner_id_escaped'");
            while ($files_row = $db_handler->sql_fetch_array($files_res)) {
                delrelease((int) $files_row['release_id']);
                pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'release', (int) $files_row['release_id']);
            }
        }
        if ($move_subdirs === 'Y') {
            $subdirs_to_escaped = $db_handler->sql_escape_int($subdirs_to);
            $ordner_res = $db_handler->sql_query("SELECT ordner_id FROM `{$sql_table['ordner']}` WHERE sordner_id='$ordner_id_escaped'");
            while ($ordner_row = $db_handler->sql_fetch_array($ordner_res)) {
                $db_handler->sql_query("UPDATE `{$sql_table['ordner']}` SET sordner_id='$subdirs_to_escaped' WHERE ordner_id='" . $db_handler->sql_escape_int($ordner_row['ordner_id']) . "'");
            }
        }

        pdl_audit_log($db_handler, $sql_table, $user_details, 'update', 'ordner', $ordner_id);

        echo pdl_admin_alert('success', '<strong>Ordner wurde aktualisiert.</strong>');
        echo '<a class="btn btn-outline-light" href="or_list.php">Zurück zur Übersicht</a>';
        include("footer.inc.php");
        return;
    }
}

pdl_admin_breadcrumb([
    ['title' => 'Admin-Center', 'href' => 'index.php'],
    ['title' => 'Ordner', 'href' => 'or_list.php'],
    ['title' => 'Ordner bearbeiten'],
]);
echo '<h1 class="h3 pdl-page-title">Ordner bearbeiten</h1>';

if (!empty($errors)) {
    echo pdl_admin_alert('danger', pdl_admin_render_errors($errors));
}

$ordner_id_escaped = $db_handler->sql_escape_int($ordner_id);
$ordner_res = $db_handler->sql_query("SELECT * FROM `{$sql_table['ordner']}` WHERE ordner_id='$ordner_id_escaped'");
$subordner_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM `{$sql_table['ordner']}` WHERE sordner_id='$ordner_id_escaped'"));
$release_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM `{$sql_table['release']}` WHERE ordner_id='$ordner_id_escaped'"));

while ($ordner_row = $db_handler->sql_fetch_array($ordner_res)) {
    if ($submit !== 1) {
        $name = (string) ($ordner_row['name'] ?? '');
        $text = (string) ($ordner_row['text'] ?? '');
        $sordner_id = (int) ($ordner_row['sordner_id'] ?? 0);
    }
    treeview_select_reset_cache();
    $name_attr = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $text_attr = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
?>
<form action="editdir.php?submit=1" method="post" novalidate>
    <?php echo csrf_input(); ?>
    <input type="hidden" name="ordner_id" value="<?php echo $ordner_id; ?>">
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Allgemein</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlEdOName" class="form-label">Name</label>
                <input type="text" id="pdlEdOName" name="name" class="form-control<?php echo isset($errors['name']) ? ' is-invalid' : ''; ?>" required<?php if (isset($errors['name'])) echo ' aria-invalid="true"'; ?> value="<?php echo $name_attr; ?>">
                <div class="form-text">Wie der Ordner heisst.</div>
            </div>
            <div class="mb-3">
                <label for="pdlEdOSordner" class="form-label">Subordner</label>
                <select id="pdlEdOSordner" name="sordner_id" class="form-select<?php echo isset($errors['sordner_id']) ? ' is-invalid' : ''; ?>">
                    <option value="0"<?php echo $sordner_id === 0 ? ' selected' : ''; ?>>Index</option>
                    <?php echo treeview_select(0, "-", $sordner_id); ?>
                </select>
                <div class="form-text">Wählen Sie den uebergeordneten Ordner aus.</div>
            </div>
            <div class="mb-3">
                <label for="pdlEdOText" class="form-label">Beschreibung</label>
                <textarea id="pdlEdOText" name="text" class="form-control" rows="5"><?php echo $text_attr; ?></textarea>
                <div class="form-text">Detailliertere Beschreibung was in dem Ordner zu finden ist.</div>
            </div>
        </div>
    </section>
    <?php if ($subordner_check > 0 || $release_check > 0) { ?>
    <section class="card pdl-card mb-4 pdl-danger-action">
        <header class="card-header bg-danger text-white"><h2 class="h5 mb-0">Optionen für Inhalte</h2></header>
        <div class="card-body">
            <p class="form-text">Was soll mit den Files und Unterordnern geschehen?</p>
            <?php if ($release_check > 0) { ?>
                <?php if (($user_rights['editfiles'] ?? '') === 'Y') { ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="move_files" value="Y" id="pdlEdOMoveFiles">
                    <label class="form-check-label" for="pdlEdOMoveFiles">Releases verschieben nach</label>
                </div>
                <select name="release_to" class="form-select mb-3 ms-4 w-auto">
                    <option value="0">Index</option>
                    <?php treeview_select_reset_cache(); echo treeview_select(0, "-"); ?>
                </select>
                <?php } ?>
                <?php if (($user_rights['delfiles'] ?? '') === 'Y') { ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="delete_files" value="Y" id="pdlEdODelFiles">
                    <label class="form-check-label text-danger fw-bold" for="pdlEdODelFiles">Releases LOESCHEN</label>
                </div>
                <?php } ?>
            <?php }
            if ($subordner_check > 0) { ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="move_subdirs" value="Y" id="pdlEdOMoveSub">
                    <label class="form-check-label" for="pdlEdOMoveSub">Subordner verschieben nach</label>
                </div>
                <select name="subdirs_to" class="form-select mb-3 ms-4 w-auto">
                    <option value="0">Index</option>
                    <?php treeview_select_reset_cache(); echo treeview_select(0, "-"); ?>
                </select>
            <?php } ?>
        </div>
    </section>
    <?php } ?>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="or_list.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Änderungen speichern</button>
    </div>
</form>
<?php
}
include("footer.inc.php");
