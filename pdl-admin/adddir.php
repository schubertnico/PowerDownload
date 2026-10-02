<?php
include("header.inc.php");

$ordner_id = isset($_POST['ordner_id']) ? (int) $_POST['ordner_id'] : (isset($_GET['ordner_id']) ? (int) $_GET['ordner_id'] : 0);
$name = (string) ($_POST['name'] ?? '');
$text = (string) ($_POST['text'] ?? '');
$submit = isset($_GET['submit']) ? (int) $_GET['submit'] : 0;
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

$errors = [];

if (!pdl_admin_require_right('adddirs')) {
    include("footer.inc.php");
    return;
}

$breadcrumb = [
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Ordner und Releases', 'href' => 'or_list.php'],
    ['title' => 'Ordner hinzufügen'],
];

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
        if (!pdl_ordner_exists($db_handler, $sql_table, $ordner_id)) {
            $errors['ordner_id'] = 'Der übergeordnete Ordner existiert nicht.';
        }
    }
    if (empty($errors)) {
        $insert_ok = $db_handler->sql_query(
            "INSERT INTO " . $sql_table['ordner'] . " (sordner_id, name, text) VALUES ("
            . $db_handler->sql_escape_int($ordner_id) . ", "
            . "'" . $db_handler->sql_escape_string(trim($name)) . "', "
            . "'" . $db_handler->sql_escape_string($text) . "')"
        );
        $new_id = $insert_ok === true ? (int) $db_handler->sql_insert_id() : 0;
        if ($new_id > 0) {
            pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'ordner', $new_id);
            pdl_admin_breadcrumb($breadcrumb);
            echo '<h1 class="h3 pdl-page-title">Ordner hinzufügen</h1>';
            $actions = [];
            if (pdl_admin_has_right('addfiles')) {
                $actions[] = ['label' => 'Release in diesem Ordner anlegen', 'href' => 'addrelease.php?ordner_id=' . $new_id, 'id' => 'pdlNextAddRelease', 'primary' => true];
            }
            $actions[] = ['label' => 'Unterordner anlegen', 'href' => 'adddir.php?ordner_id=' . $new_id, 'id' => 'pdlNextAddSubdir'];
            $actions[] = ['label' => 'Weiteren Ordner anlegen', 'href' => 'adddir.php?ordner_id=' . $ordner_id, 'id' => 'pdlNextAddDir'];
            $actions[] = ['label' => 'Zur Übersicht', 'href' => 'or_list.php?ordner_id=' . $new_id . '#pdl-aktuell', 'id' => 'pdlNextOverview'];
            echo pdl_admin_result(
                'success',
                '<strong>Der Ordner „' . htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8') . '“ wurde angelegt</strong>'
                . ' in „' . htmlspecialchars(pdl_admin_ordner_name($ordner_id), ENT_QUOTES, 'UTF-8') . '“.',
                $actions
            );
            include("footer.inc.php");
            return;
        }
        $errors['_db'] = 'Der Ordner konnte nicht angelegt werden: ' . ($db_handler->sql_error() ?: 'unbekannter Fehler') . '.';
    }
}

pdl_admin_breadcrumb($breadcrumb);
echo '<h1 class="h3 pdl-page-title">Ordner hinzufügen</h1>';

if (!empty($errors)) {
    echo pdl_admin_alert('danger', pdl_admin_render_errors($errors));
}

$name_attr = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$text_attr = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
?>
<form action="adddir.php?submit=1" method="post" novalidate id="pdlAddDir">
    <?php echo csrf_input(); ?>
    <section class="card pdl-card mb-4">
        <header class="card-header">
            <h2 class="h5 mb-0">Ordner</h2>
        </header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlOrdnerName" class="form-label">Name <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlOrdnerName" name="name" class="form-control<?php echo isset($errors['name']) ? ' is-invalid' : ''; ?>" required aria-required="true" aria-describedby="pdlOrdnerNameHelp"<?php if (isset($errors['name'])) echo ' aria-invalid="true"'; ?> value="<?php echo $name_attr; ?>" maxlength="128">
                <div id="pdlOrdnerNameHelp" class="form-text">Pflichtfeld, höchstens 128 Zeichen. So heißt der Ordner in der Übersicht. Unterordner erscheinen alphabetisch.</div>
            </div>
            <div class="mb-3">
                <label for="pdlOrdnerParent" class="form-label">Übergeordneter Ordner</label>
                <select id="pdlOrdnerParent" name="ordner_id" class="form-select<?php echo isset($errors['ordner_id']) ? ' is-invalid' : ''; ?>" aria-describedby="pdlOrdnerParentHelp">
                    <option value="0" data-name="Index"<?php echo $ordner_id === 0 ? ' selected' : ''; ?>>Index</option>
                    <?php echo pdl_admin_ordner_options($ordner_id); ?>
                </select>
                <div id="pdlOrdnerParentHelp" class="form-text">In welchem Ordner soll der neue Ordner liegen? „Index“ ist die oberste Ebene.</div>
            </div>
            <div class="mb-3">
                <label for="pdlOrdnerText" class="form-label">Beschreibung</label>
                <textarea id="pdlOrdnerText" name="text" class="form-control" rows="5" aria-describedby="pdlOrdnerTextHelp"><?php echo $text_attr; ?></textarea>
                <div id="pdlOrdnerTextHelp" class="form-text">Kurze Erklärung, was in diesem Ordner zu finden ist. Erscheint öffentlich in der Ordneransicht.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="or_list.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlOrdnerSubmit">Ordner erstellen</button>
    </div>
</form>
<?php
include("footer.inc.php");
