<?php
include("header.inc.php");

$name = (string) ($_POST['name'] ?? '');
$text = (string) ($_POST['text'] ?? '');
$ordner_id = isset($_POST['ordner_id']) ? (int) $_POST['ordner_id'] : 0;
$released = (string) ($_POST['released'] ?? 'Y');
$views = isset($_POST['views']) ? (int) $_POST['views'] : 0;
$refresh = (string) ($_POST['refresh'] ?? '');
$autor_type = isset($_POST['autor_type']) ? (int) $_POST['autor_type'] : -1;
$autor_nick = (string) ($_POST['autor_nick'] ?? '');
$autor_email = (string) ($_POST['autor_email'] ?? '');
$autor_homepage = (string) ($_POST['autor_homepage'] ?? '');
$autor_id = isset($_POST['autor_id']) ? (int) $_POST['autor_id'] : 0;
$submit = isset($_GET['submit']) ? (int) $_GET['submit'] : 0;
$release_id = isset($_GET['release_id']) ? (int) $_GET['release_id'] : (isset($_POST['release_id']) ? (int) $_POST['release_id'] : 0);
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

$errors = [];

if (($user_rights['editfiles'] ?? '') !== 'Y') {
    echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.');
    include("footer.inc.php");
    return;
}

if (!pdl_release_exists($db_handler, $sql_table, $release_id)) {
    echo pdl_admin_alert('warning', 'Release existiert nicht.');
    include("footer.inc.php");
    return;
}

if ($submit === 1) {
    if (!csrf_verify($csrf_token_post)) {
        $errors['_csrf'] = 'Sicherheits-Token ungültig oder abgelaufen.';
    }
    if (empty($errors)) {
        $validation = pdl_validate_release_input($_POST, $db_handler, $sql_table);
        $errors = $validation['errors'];
    }

    if (empty($errors)) {
        $db_handler->sql_query(
            "UPDATE " . $sql_table['release'] . " SET "
            . "name='" . $db_handler->sql_escape_string(trim($name)) . "', "
            . "text='" . $db_handler->sql_escape_string($text) . "', "
            . "ordner_id='" . $db_handler->sql_escape_int($ordner_id) . "', "
            . "released='" . $db_handler->sql_escape_string($released === 'N' ? 'N' : 'Y') . "', "
            . "views='" . $db_handler->sql_escape_int($views) . "', "
            . "autor='', autor_nick='', autor_email='', autor_homepage='' "
            . "WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "'"
        );
        if ($autor_type === -1) {
            $db_handler->sql_query("UPDATE " . $sql_table['release'] . " SET autor='-1' WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "'");
        } elseif ($autor_type === 0) {
            $db_handler->sql_query(
                "UPDATE " . $sql_table['release'] . " SET autor='0', "
                . "autor_nick='" . $db_handler->sql_escape_string($autor_nick) . "', "
                . "autor_email='" . $db_handler->sql_escape_string($autor_email) . "', "
                . "autor_homepage='" . $db_handler->sql_escape_string($autor_homepage) . "' "
                . "WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "'"
            );
        } elseif ($autor_type === 1) {
            $db_handler->sql_query("UPDATE " . $sql_table['release'] . " SET autor='" . $db_handler->sql_escape_int($autor_id) . "' WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "'");
        }
        if ($refresh === 'Y') {
            $db_handler->sql_query("UPDATE " . $sql_table['release'] . " SET time='" . time() . "' WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "'");
        }

        pdl_audit_log($db_handler, $sql_table, $user_details, 'update', 'release', $release_id);

        echo pdl_admin_alert('success', '<strong>Release wurde aktualisiert.</strong>');
        echo '<a class="btn btn-primary" href="editrelease.php?release_id=' . $release_id . '">Zurück zum Release</a>';
        include("footer.inc.php");
        return;
    }
}

$release = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "'"));
if ($submit !== 1 && is_array($release)) {
    $name = (string) ($release['name'] ?? '');
    $text = (string) ($release['text'] ?? '');
    $ordner_id = (int) ($release['ordner_id'] ?? 0);
    $released = (string) ($release['released'] ?? 'Y');
    $views = (int) ($release['views'] ?? 0);
    $autor_value = (int) ($release['autor'] ?? -1);
    if ($autor_value === -1) {
        $autor_type = -1;
    } elseif ($autor_value === 0) {
        $autor_type = 0;
        $autor_nick = (string) ($release['autor_nick'] ?? '');
        $autor_email = (string) ($release['autor_email'] ?? '');
        $autor_homepage = (string) ($release['autor_homepage'] ?? '');
    } else {
        $autor_type = 1;
        $autor_id = $autor_value;
    }
}

pdl_admin_breadcrumb([
    ['title' => 'Admin-Center', 'href' => 'index.php'],
    ['title' => 'Releases', 'href' => 'or_list.php'],
    ['title' => 'Release bearbeiten'],
]);
echo '<h1 class="h3 pdl-page-title">Release bearbeiten</h1>';

if (!empty($errors)) {
    echo pdl_admin_alert('danger', pdl_admin_render_errors($errors));
}

echo '<nav aria-label="Bereiche" class="mb-4"><ul class="nav nav-pills flex-wrap gap-2">';
echo '<li class="nav-item"><a class="nav-link bg-secondary-subtle" href="#pdlEdRel">Allgemein</a></li>';
echo '<li class="nav-item"><a class="nav-link bg-secondary-subtle" href="#pdlEdFiles">Files</a></li>';
echo '<li class="nav-item"><a class="nav-link bg-secondary-subtle" href="#pdlEdScreens">Screenshots</a></li>';
echo '<li class="nav-item"><a class="nav-link bg-secondary-subtle" href="#pdlEdComments">Kommentare</a></li>';
echo '</ul></nav>';

treeview_select_reset_cache();
$name_attr = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$text_attr = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
$autor_nick_attr = htmlspecialchars($autor_nick, ENT_QUOTES, 'UTF-8');
$autor_email_attr = htmlspecialchars($autor_email, ENT_QUOTES, 'UTF-8');
$autor_homepage_attr = htmlspecialchars($autor_homepage, ENT_QUOTES, 'UTF-8');
?>
<form action="editrelease.php?submit=1" method="post" novalidate id="pdlEdRel">
    <?php echo csrf_input(); ?>
    <input type="hidden" name="release_id" value="<?php echo $release_id; ?>">
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Allgemein</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlERName" class="form-label">Name</label>
                <input type="text" id="pdlERName" name="name" class="form-control<?php echo isset($errors['name']) ? ' is-invalid' : ''; ?>" required<?php if (isset($errors['name'])) echo ' aria-invalid="true"'; ?> value="<?php echo $name_attr; ?>">
                <div class="form-text">Wie der Release heisst.</div>
            </div>
            <div class="mb-3">
                <label for="pdlEROrdner" class="form-label">Ordner</label>
                <select id="pdlEROrdner" name="ordner_id" class="form-select<?php echo isset($errors['ordner_id']) ? ' is-invalid' : ''; ?>">
                    <option value="0"<?php echo $ordner_id === 0 ? ' selected' : ''; ?>>Index</option>
                    <?php echo treeview_select(0, "-", $ordner_id); ?>
                </select>
                <div class="form-text">Welchem Ordner ist die Datei untergeordnet.</div>
            </div>
            <div class="mb-3">
                <label for="pdlERStatus" class="form-label">Datei sichtbar</label>
                <select id="pdlERStatus" name="released" class="form-select">
                    <option value="Y"<?php echo $released !== 'N' ? ' selected' : ''; ?>>Sichtbar</option>
                    <option value="N"<?php echo $released === 'N' ? ' selected' : ''; ?>>Versteckt</option>
                </select>
                <div class="form-text">Soll die Datei in der Übersicht sichtbar sein oder versteckt werden?</div>
            </div>
            <div class="mb-3">
                <label for="pdlERViews" class="form-label">Views</label>
                <input type="number" min="0" id="pdlERViews" name="views" class="form-control" value="<?php echo $views; ?>">
                <div class="form-text">Wie oft die Detailseite des Releases aufgerufen wurde.</div>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="pdlERRefresh" name="refresh" value="Y">
                <label class="form-check-label" for="pdlERRefresh">Datum refreshen</label>
                <div class="form-text">Aktivieren Sie diese Option, wird das Datum der Datei auf Heute gesetzt.</div>
            </div>
        </div>
    </section>

    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Autor</h2></header>
        <div class="card-body">
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="autor_type" value="-1" id="pdlERAutorUnk"<?php echo $autor_type === -1 ? ' checked' : ''; ?>>
                <label class="form-check-label" for="pdlERAutorUnk">Unbekannt</label>
            </div>

            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="autor_type" value="0" id="pdlERAutorEigen"<?php echo $autor_type === 0 ? ' checked' : ''; ?>>
                <label class="form-check-label" for="pdlERAutorEigen">Daten eingeben</label>
            </div>
            <div class="row g-2 ps-4 mb-3">
                <div class="col-12 col-md-6">
                    <label for="pdlERAutorNick" class="form-label">Nickname</label>
                    <input type="text" id="pdlERAutorNick" name="autor_nick" class="form-control" value="<?php echo $autor_nick_attr; ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label for="pdlERAutorEmail" class="form-label">E-Mail</label>
                    <input type="email" id="pdlERAutorEmail" name="autor_email" class="form-control<?php echo isset($errors['autor_email']) ? ' is-invalid' : ''; ?>"<?php if (isset($errors['autor_email'])) echo ' aria-invalid="true"'; ?> value="<?php echo $autor_email_attr; ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label for="pdlERAutorHp" class="form-label">Homepage</label>
                    <input type="url" id="pdlERAutorHp" name="autor_homepage" class="form-control<?php echo isset($errors['autor_homepage']) ? ' is-invalid' : ''; ?>"<?php if (isset($errors['autor_homepage'])) echo ' aria-invalid="true"'; ?> value="<?php echo $autor_homepage_attr; ?>">
                </div>
            </div>

            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="autor_type" value="1" id="pdlERAutorReg"<?php echo $autor_type === 1 ? ' checked' : ''; ?>>
                <label class="form-check-label" for="pdlERAutorReg">Angemeldeten User wählen</label>
            </div>
            <div class="ps-4">
                <select name="autor_id" class="form-select w-auto">
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
        <header class="card-header"><h2 class="h5 mb-0">Beschreibung</h2></header>
        <div class="card-body">
            <label for="pdlERText" class="form-label">Text</label>
            <textarea id="pdlERText" name="text" class="form-control" rows="10" aria-describedby="pdlERTextHelp"><?php echo $text_attr; ?></textarea>
            <div id="pdlERTextHelp" class="form-text">
                Beachten Sie die <a href="showreplacements.php">Replacements</a>.<br>
                HTML <strong><?php echo pdlif(($settings['html_releases'] ?? '') == "Y", "An", "Aus"); ?></strong>,
                BB-Code <strong><?php echo pdlif(($settings['bb_code'] ?? '') == "Y", "An", "Aus"); ?></strong>,
                Glossar <strong><?php echo pdlif(($settings['glossary'] ?? '') == "Y", "An", "Aus"); ?></strong>,
                Smilies <strong><?php echo pdlif(($settings['smilies'] ?? '') == "Y", "An", "Aus"); ?></strong>,
                Zensur <strong><?php echo pdlif(($settings['badwords_releases'] ?? '') == "Y", "An", "Aus"); ?></strong>.
            </div>
        </div>
    </section>

    <div class="d-grid d-md-flex gap-2 justify-content-md-end mb-4">
        <a href="or_list.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Änderungen speichern</button>
    </div>
</form>

<section class="card pdl-card mb-4" id="pdlEdFiles">
    <header class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="h5 mb-0">Files</h2>
        <a class="btn btn-sm btn-primary" href="addfile.php?release_id=<?php echo $release_id; ?>">Datei hinzufügen</a>
    </header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col" class="text-end">Größe</th>
                    <th scope="col" class="text-end">Downloads</th>
                    <th scope="col" class="text-end">Optionen</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $files_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['files'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "'");
            if ($db_handler->sql_num_rows($files_res) == 0) { ?>
                <tr><td colspan="4" class="text-muted text-center">Keine Files vorhanden.</td></tr>
            <?php } else {
                while ($files_row = $db_handler->sql_fetch_array($files_res)) {
                    if ((int) ($files_row['mirror'] ?? 0) > 0) {
                        $mirror_of = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT size FROM " . $sql_table['files'] . " WHERE file_id='" . $db_handler->sql_escape_int((int) $files_row['mirror']) . "'"));
                        $files_row['size'] = $mirror_of['size'] ?? 0;
                    }
            ?>
                <tr>
                    <td><?php echo htmlspecialchars((string) ($files_row['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="text-end"><?php echo size((int) ($files_row['size'] ?? 0)); ?></td>
                    <td class="text-end"><?php echo (int) ($files_row['downloads'] ?? 0); ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm" role="group">
                            <a class="btn btn-outline-light" href="editfile.php?file_id=<?php echo (int) $files_row['file_id']; ?>">ändern</a>
                            <a class="btn btn-outline-danger" href="delfile.php?file_id=<?php echo (int) $files_row['file_id']; ?>">löschen</a>
                        </div>
                    </td>
                </tr>
            <?php } } ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card pdl-card mb-4" id="pdlEdScreens">
    <header class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="h5 mb-0">Screenshots</h2>
        <a class="btn btn-sm btn-primary" href="addscreen.php?release_id=<?php echo $release_id; ?>">Screenshot hochladen</a>
    </header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th scope="col">Screen</th>
                    <th scope="col">Titel</th>
                    <th scope="col" class="text-end">Views</th>
                    <th scope="col" class="text-end">Optionen</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $screens_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['screens'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "'");
            if ($db_handler->sql_num_rows($screens_res) == 0) { ?>
                <tr><td colspan="4" class="text-muted text-center">Keine Screenshots vorhanden.</td></tr>
            <?php } else {
                while ($screens_row = $db_handler->sql_fetch_array($screens_res)) { ?>
                <tr>
                    <td><img src="../pdl-gfx/screens/release<?php echo $release_id; ?>screen<?php echo (int) $screens_row['screen_id']; ?>k.jpg" alt="" class="img-thumbnail" loading="lazy" style="max-height: 90px;"></td>
                    <td><?php echo htmlspecialchars((string) ($screens_row['text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="text-end"><?php echo (int) ($screens_row['views'] ?? 0); ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-danger" href="delscreen.php?screen_id=<?php echo (int) $screens_row['screen_id']; ?>">löschen</a>
                    </td>
                </tr>
            <?php } } ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card pdl-card mb-4" id="pdlEdComments">
    <header class="card-header"><h2 class="h5 mb-0">Kommentare</h2></header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th scope="col">Titel</th>
                    <th scope="col">Autor</th>
                    <th scope="col">Datum</th>
                    <th scope="col" class="text-end">Optionen</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $comments_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['comments'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "'");
            if ($db_handler->sql_num_rows($comments_res) == 0) { ?>
                <tr><td colspan="4" class="text-muted text-center">Keine Kommentare vorhanden.</td></tr>
            <?php } else {
                while ($comments_row = $db_handler->sql_fetch_array($comments_res)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars((string) ($comments_row['titel'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php if ((int) ($comments_row['user_id'] ?? 0) === 0) echo "Gast"; else echo user((int) $comments_row['user_id']); ?></td>
                    <td><?php echo date((string) ($settings['date_format'] ?? 'd.m.Y'), (int) ($comments_row['time'] ?? time())); ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm" role="group">
                            <a class="btn btn-outline-light" href="editcomment.php?comment_id=<?php echo (int) $comments_row['comment_id']; ?>">ändern</a>
                            <a class="btn btn-outline-danger" href="delcomment.php?comment_id=<?php echo (int) $comments_row['comment_id']; ?>">löschen</a>
                        </div>
                    </td>
                </tr>
            <?php } } ?>
            </tbody>
        </table>
    </div>
</section>
<?php
include("footer.inc.php");
