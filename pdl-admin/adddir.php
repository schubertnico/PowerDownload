<?php
include("header.inc.php");

$ordner_id = isset($_POST['ordner_id']) ? (int) $_POST['ordner_id'] : (isset($_GET['ordner_id']) ? (int) $_GET['ordner_id'] : 0);
$name = (string) ($_POST['name'] ?? '');
$text = (string) ($_POST['text'] ?? '');
$submit = isset($_GET['submit']) ? (int) $_GET['submit'] : 0;
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

$errors = [];

if (($user_rights['adddirs'] ?? '') === 'Y') {
    if ($submit === 1) {
        if (!csrf_verify($csrf_token_post)) {
            $errors['_csrf'] = 'Sicherheits-Token ungültig oder abgelaufen. Bitte Formular erneut absenden.';
        }
        if (empty($errors)) {
            $errors = pdl_validate_required(['name' => $name], ['name']);
            if (!pdl_ordner_exists($db_handler, $sql_table, $ordner_id)) {
                $errors['ordner_id'] = 'Übergeordneter Ordner existiert nicht.';
            }
        }
        if (empty($errors)) {
            $db_handler->sql_query(
                "INSERT INTO " . $sql_table['ordner'] . " (sordner_id, name, text) VALUES ("
                . $db_handler->sql_escape_int($ordner_id) . ", "
                . "'" . $db_handler->sql_escape_string(trim($name)) . "', "
                . "'" . $db_handler->sql_escape_string($text) . "')"
            );
            $new_id = (int) $db_handler->sql_insert_id();
            pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'ordner', $new_id);

            echo pdl_admin_alert(
                'success',
                '<strong>Ordner „' . htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8') . '" wurde angelegt</strong> (ID ' . $new_id . ').'
            );
            echo '<a class="btn btn-primary" href="adddir.php">Weiteren Ordner anlegen</a> ';
            echo '<a class="btn btn-outline-light" href="or_list.php">Zurück zur Übersicht</a>';
            include("footer.inc.php");
            return;
        }
    }

    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'Ordner', 'href' => 'or_list.php'],
        ['title' => 'Ordner erstellen'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Ordner erstellen</h1>';

    if (!empty($errors)) {
        echo pdl_admin_alert('danger', pdl_admin_render_errors($errors));
    }

    $name_attr = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $text_attr = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    ?>
<form action="adddir.php?submit=1" method="post" novalidate>
    <?php echo csrf_input(); ?>
    <section class="card pdl-card mb-4">
        <header class="card-header">
            <h2 class="h5 mb-0">Ordnerdaten</h2>
        </header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlOrdnerName" class="form-label">Name <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlOrdnerName" name="name" class="form-control<?php echo isset($errors['name']) ? ' is-invalid' : ''; ?>" required aria-required="true" aria-describedby="pdlOrdnerNameHelp"<?php if (isset($errors['name'])) echo ' aria-invalid="true"'; ?> value="<?php echo $name_attr; ?>" maxlength="200">
                <div id="pdlOrdnerNameHelp" class="form-text">Pflichtfeld. So heißt der Ordner in der Übersicht.</div>
            </div>
            <div class="mb-3">
                <label for="pdlOrdnerParent" class="form-label">Übergeordneter Ordner</label>
                <select id="pdlOrdnerParent" name="ordner_id" class="form-select<?php echo isset($errors['ordner_id']) ? ' is-invalid' : ''; ?>" aria-describedby="pdlOrdnerParentHelp">
                    <option value="0"<?php echo $ordner_id === 0 ? ' selected' : ''; ?>>Index</option>
                    <?php echo treeview_select(0, "-", $ordner_id); ?>
                </select>
                <div id="pdlOrdnerParentHelp" class="form-text">In welchem Ordner soll der neue Ordner angelegt werden? "Index" ist die oberste Ebene.</div>
            </div>
            <div class="mb-3">
                <label for="pdlOrdnerText" class="form-label">Beschreibung</label>
                <textarea id="pdlOrdnerText" name="text" class="form-control" rows="5" aria-describedby="pdlOrdnerTextHelp"><?php echo $text_attr; ?></textarea>
                <div id="pdlOrdnerTextHelp" class="form-text">Kurze Erklärung, was in diesem Ordner zu finden ist. Erscheint öffentlich in der Ordner-Ansicht.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="or_list.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Ordner erstellen</button>
    </div>
</form>
<?php
} else {
    echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.');
}
include("footer.inc.php");
