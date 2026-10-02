<?php
/**
 * PowerDownload - Release hinzufügen
 */
include("header.inc.php");

$name = (string) ($_POST['name'] ?? '');
$text = (string) ($_POST['text'] ?? '');
$ordner_id = isset($_POST['ordner_id']) ? (int) $_POST['ordner_id'] : (isset($_GET['ordner_id']) ? (int) $_GET['ordner_id'] : 0);
$released = (string) ($_POST['released'] ?? 'Y');
$autor_type = isset($_POST['autor_type']) ? (int) $_POST['autor_type'] : -1;
$autor_nick = (string) ($_POST['autor_nick'] ?? '');
$autor_email = (string) ($_POST['autor_email'] ?? '');
$autor_homepage = (string) ($_POST['autor_homepage'] ?? '');
$autor_id = isset($_POST['autor_id']) ? (int) $_POST['autor_id'] : (int) ($user_details['user_id'] ?? 0);
$submit = isset($_GET['submit']) ? (int) $_GET['submit'] : 0;
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

$errors = [];

if (!pdl_admin_require_right('addfiles')) {
    include("footer.inc.php");
    return;
}

$breadcrumb = [
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Ordner und Releases', 'href' => 'or_list.php'],
    ['title' => 'Release hinzufügen'],
];

if ($submit === 1) {
    if (!csrf_verify($csrf_token_post)) {
        $errors['_csrf'] = 'Sicherheits-Token ungültig oder abgelaufen. Bitte senden Sie das Formular erneut ab.';
    }
    if (empty($errors)) {
        $validation = pdl_validate_release_input($_POST, $db_handler, $sql_table);
        $errors = $validation['errors'];
    }
    if (empty($errors)) {
        $autor_value = $autor_type === 1 ? $autor_id : $autor_type;
        $is_manual = $autor_type === 0;
        $insert_ok = $db_handler->sql_query(
            "INSERT INTO " . $sql_table['release']
            . " (name, text, time, ordner_id, released, uploader, autor, autor_nick, autor_email, autor_homepage) VALUES ("
            . "'" . $db_handler->sql_escape_string(trim($name)) . "', "
            . "'" . $db_handler->sql_escape_string($text) . "', "
            . "'" . time() . "', "
            . "'" . $db_handler->sql_escape_int($ordner_id) . "', "
            . "'" . ($released === 'N' ? 'N' : 'Y') . "', "
            . "'" . $db_handler->sql_escape_int((int) ($user_details['user_id'] ?? 0)) . "', "
            . "'" . $db_handler->sql_escape_int($autor_value) . "', "
            . "'" . $db_handler->sql_escape_string($is_manual ? trim($autor_nick) : '') . "', "
            . "'" . $db_handler->sql_escape_string($is_manual ? trim($autor_email) : '') . "', "
            . "'" . $db_handler->sql_escape_string($is_manual ? trim($autor_homepage) : '') . "')"
        );
        $release_id = $insert_ok === true ? (int) $db_handler->sql_insert_id() : 0;

        if ($release_id > 0) {
            pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'release', $release_id);
            pdl_admin_breadcrumb($breadcrumb);
            echo '<h1 class="h3 pdl-page-title">Release hinzufügen</h1>';
            echo pdl_admin_result(
                'success',
                '<strong>Das Release „' . htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8') . '“ wurde angelegt.</strong> '
                . 'Fügen Sie jetzt die erste Datei hinzu. Solange das Release keine Datei hat, sehen Besucher nur die Beschreibung.',
                [
                    ['label' => 'Datei hinzufügen', 'href' => 'addfile.php?release_id=' . $release_id, 'id' => 'pdlNextAddFile', 'primary' => true],
                    ['label' => 'Screenshot hochladen', 'href' => 'addscreen.php?release_id=' . $release_id, 'id' => 'pdlNextAddScreen'],
                    ['label' => 'Release bearbeiten', 'href' => 'editrelease.php?release_id=' . $release_id, 'id' => 'pdlNextEditRelease'],
                    ['label' => 'Zur Übersicht', 'href' => 'or_list.php?ordner_id=' . $ordner_id . '#pdl-aktuell', 'id' => 'pdlNextOverview'],
                ]
            );
            include("footer.inc.php");
            return;
        }
        $errors['_db'] = 'Das Release konnte nicht gespeichert werden: ' . ($db_handler->sql_error() ?: 'unbekannter Fehler') . '.';
    }
}

pdl_admin_breadcrumb($breadcrumb);
echo '<h1 class="h3 pdl-page-title">Release hinzufügen</h1>';

if (!empty($errors)) {
    echo pdl_admin_alert('danger', pdl_admin_render_errors($errors));
}

$name_attr = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$text_attr = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
$autor_nick_attr = htmlspecialchars($autor_nick, ENT_QUOTES, 'UTF-8');
$autor_email_attr = htmlspecialchars($autor_email, ENT_QUOTES, 'UTF-8');
$autor_homepage_attr = htmlspecialchars($autor_homepage, ENT_QUOTES, 'UTF-8');
?>
<form action="addrelease.php?submit=1" method="post" novalidate id="pdlAddRelease">
    <?php echo csrf_input(); ?>
    <section class="card pdl-card mb-4">
        <header class="card-header">
            <h2 class="h5 mb-0">Allgemein</h2>
        </header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlReleaseName" class="form-label">Name <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlReleaseName" name="name" class="form-control<?php echo isset($errors['name']) ? ' is-invalid' : ''; ?>" required aria-required="true" aria-describedby="pdlReleaseNameHelp"<?php if (isset($errors['name'])) echo ' aria-invalid="true"'; ?> value="<?php echo $name_attr; ?>" maxlength="128">
                <div id="pdlReleaseNameHelp" class="form-text">Pflichtfeld, höchstens 128 Zeichen. So heißt das Release in Listen und Suchergebnissen.</div>
            </div>
            <div class="mb-3">
                <label for="pdlReleaseOrdner" class="form-label">Ordner</label>
                <select id="pdlReleaseOrdner" name="ordner_id" class="form-select<?php echo isset($errors['ordner_id']) ? ' is-invalid' : ''; ?>" aria-describedby="pdlReleaseOrdnerHelp">
                    <option value="0" data-name="Index"<?php echo $ordner_id === 0 ? ' selected' : ''; ?>>Index</option>
                    <?php echo pdl_admin_ordner_options($ordner_id); ?>
                </select>
                <div id="pdlReleaseOrdnerHelp" class="form-text">In welchem Ordner soll das Release erscheinen? „Index“ ist die oberste Ebene.</div>
            </div>
            <div class="mb-3">
                <label for="pdlReleaseStatus" class="form-label">Sichtbarkeit</label>
                <select id="pdlReleaseStatus" name="released" class="form-select" aria-describedby="pdlReleaseStatusHelp">
                    <option value="Y"<?php echo $released !== 'N' ? ' selected' : ''; ?>>Sichtbar</option>
                    <option value="N"<?php echo $released === 'N' ? ' selected' : ''; ?>>Versteckt</option>
                </select>
                <div id="pdlReleaseStatusHelp" class="form-text">„Sichtbar“ zeigt das Release in der öffentlichen Übersicht. „Versteckt“ blendet es im öffentlichen Bereich überall aus (Listen, Suche, Statistik, Newsletter), solange Sie es noch vorbereiten; auch seine Dateien lassen sich dann nicht herunterladen. Im Adminbereich bleibt es sichtbar.</div>
            </div>
        </div>
    </section>

    <section class="card pdl-card mb-4">
        <header class="card-header">
            <h2 class="h5 mb-0">Autor</h2>
        </header>
        <div class="card-body">
            <p class="form-text">Wer hat den Inhalt erstellt? Wählen Sie „Unbekannt“, geben Sie Name und Kontakt selbst ein oder wählen Sie einen registrierten Benutzer.</p>

            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="autor_type" value="-1" id="pdlAutorUnk"<?php echo $autor_type === -1 ? ' checked' : ''; ?>>
                <label class="form-check-label" for="pdlAutorUnk">Unbekannt</label>
            </div>

            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="autor_type" value="0" id="pdlAutorEigen"<?php echo $autor_type === 0 ? ' checked' : ''; ?>>
                <label class="form-check-label" for="pdlAutorEigen">Daten eingeben</label>
            </div>
            <div class="row g-2 ps-4 mb-3">
                <div class="col-12 col-md-6">
                    <label for="pdlAutorNick" class="form-label">Name</label>
                    <input type="text" id="pdlAutorNick" name="autor_nick" class="form-control<?php echo isset($errors['autor_nick']) ? ' is-invalid' : ''; ?>" maxlength="128" value="<?php echo $autor_nick_attr; ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label for="pdlAutorEmail" class="form-label">E-Mail</label>
                    <input type="email" id="pdlAutorEmail" name="autor_email" class="form-control<?php echo isset($errors['autor_email']) ? ' is-invalid' : ''; ?>"<?php if (isset($errors['autor_email'])) echo ' aria-invalid="true"'; ?> maxlength="128" value="<?php echo $autor_email_attr; ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label for="pdlAutorHomepage" class="form-label">Homepage</label>
                    <input type="url" id="pdlAutorHomepage" name="autor_homepage" class="form-control<?php echo isset($errors['autor_homepage']) ? ' is-invalid' : ''; ?>"<?php if (isset($errors['autor_homepage'])) echo ' aria-invalid="true"'; ?> maxlength="128" value="<?php echo $autor_homepage_attr; ?>" placeholder="https://">
                </div>
            </div>

            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="autor_type" value="1" id="pdlAutorReg"<?php echo $autor_type === 1 ? ' checked' : ''; ?>>
                <label class="form-check-label" for="pdlAutorReg">Registrierten Benutzer wählen</label>
            </div>
            <div class="ps-4">
                <label for="pdlAutorId" class="form-label visually-hidden">Registrierter Benutzer</label>
                <select id="pdlAutorId" name="autor_id" class="form-select w-auto<?php echo isset($errors['autor_id']) ? ' is-invalid' : ''; ?>">
<?php
$user_res = $db_handler->sql_query("SELECT user_id, nick FROM " . $sql_table['user'] . " ORDER BY nick ASC");
while ($user_row = $db_handler->sql_fetch_array($user_res)) {
    $uid = (int) $user_row['user_id'];
    $sel = $uid === $autor_id ? ' selected' : '';
    echo '<option value="' . $uid . '"' . $sel . '>' . htmlspecialchars($user_row['nick'] ?? '', ENT_QUOTES, 'UTF-8') . '</option>';
}
?>
                </select>
            </div>
        </div>
    </section>

    <section class="card pdl-card mb-4">
        <header class="card-header">
            <h2 class="h5 mb-0">Beschreibung</h2>
        </header>
        <div class="card-body">
            <label for="pdlReleaseText" class="form-label">Text</label>
            <textarea id="pdlReleaseText" name="text" class="form-control" rows="10" aria-describedby="pdlReleaseTextHelp"><?php echo $text_attr; ?></textarea>
            <div id="pdlReleaseTextHelp" class="form-text">
                Es gelten die hinterlegten <a href="showreplacements.php">Ersetzungen</a> (Glossar, Smileys, Zensur).<br>
                HTML ist <strong><?php echo pdlif(($settings['html_releases'] ?? '') == "Y", "an", "aus"); ?></strong>,
                BB-Code ist <strong><?php echo pdlif(($settings['bb_code'] ?? '') == "Y", "an", "aus"); ?></strong>,
                Glossar ist <strong><?php echo pdlif(($settings['glossary'] ?? '') == "Y", "an", "aus"); ?></strong>,
                Smileys sind <strong><?php echo pdlif(($settings['smilies'] ?? '') == "Y", "an", "aus"); ?></strong>,
                Zensur ist <strong><?php echo pdlif(($settings['badwords_releases'] ?? '') == "Y", "an", "aus"); ?></strong>.
            </div>
        </div>
    </section>

    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="or_list.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlReleaseSubmit">Release hinzufügen</button>
    </div>
</form>
<?php
include("footer.inc.php");
