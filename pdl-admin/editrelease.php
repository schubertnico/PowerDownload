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
$saved = false;

if (!pdl_admin_require_right('editfiles')) {
    include("footer.inc.php");
    return;
}

if (!pdl_release_exists($db_handler, $sql_table, $release_id)) {
    echo pdl_admin_result('warning', 'Dieses Release gibt es nicht (mehr).', [
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
        $validation = pdl_validate_release_input($_POST, $db_handler, $sql_table);
        $errors = $validation['errors'];
        if ($views < 0) {
            $errors['views'] = 'Bitte eine Zahl ab 0 eingeben.';
        }
    }

    if (empty($errors)) {
        // Alle Felder in EINER Anweisung: Autor-Spalten sind int bzw. Text und
        // werden direkt passend gesetzt (früher scheiterte das ganze UPDATE an autor='').
        $is_manual = $autor_type === 0;
        $autor_value = $autor_type === 1 ? $autor_id : $autor_type;
        $update_ok = $db_handler->sql_query(
            "UPDATE " . $sql_table['release'] . " SET "
            . "name='" . $db_handler->sql_escape_string(trim($name)) . "', "
            . "text='" . $db_handler->sql_escape_string($text) . "', "
            . "ordner_id='" . $db_handler->sql_escape_int($ordner_id) . "', "
            . "released='" . ($released === 'N' ? 'N' : 'Y') . "', "
            . "views='" . $db_handler->sql_escape_int($views) . "', "
            . "autor='" . $db_handler->sql_escape_int($autor_value) . "', "
            . "autor_nick='" . $db_handler->sql_escape_string($is_manual ? trim($autor_nick) : '') . "', "
            . "autor_email='" . $db_handler->sql_escape_string($is_manual ? trim($autor_email) : '') . "', "
            . "autor_homepage='" . $db_handler->sql_escape_string($is_manual ? trim($autor_homepage) : '') . "'"
            . ($refresh === 'Y' ? ", time='" . time() . "'" : '')
            . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "'"
        );
        if ($update_ok === true) {
            pdl_audit_log($db_handler, $sql_table, $user_details, 'update', 'release', $release_id);
            $saved = true;
        } else {
            $errors['_db'] = 'Die Änderungen konnten nicht gespeichert werden: ' . ($db_handler->sql_error() ?: 'unbekannter Fehler') . '.';
        }
    }
}

$release = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "'"));
if (($submit !== 1 || $saved) && is_array($release)) {
    $name = (string) ($release['name'] ?? '');
    $text = (string) ($release['text'] ?? '');
    $ordner_id = (int) ($release['ordner_id'] ?? 0);
    $released = (string) ($release['released'] ?? 'Y');
    $views = (int) ($release['views'] ?? 0);
    $autor_value = (int) ($release['autor'] ?? -1);
    $autor_nick = '';
    $autor_email = '';
    $autor_homepage = '';
    $autor_id = (int) ($user_details['user_id'] ?? 0);
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
$release_title = is_array($release) ? (string) ($release['name'] ?? '') : $name;

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Ordner und Releases', 'href' => 'or_list.php?ordner_id=' . (int) ($release['ordner_id'] ?? 0) . '#pdl-aktuell'],
    ['title' => 'Release bearbeiten'],
]);
echo '<h1 class="h3 pdl-page-title">Release bearbeiten: ' . htmlspecialchars($release_title, ENT_QUOTES, 'UTF-8') . '</h1>';

if ($saved) {
    echo '<div id="pdlERSaved">' . pdl_admin_alert(
        'success',
        '<strong>Das Release wurde gespeichert.</strong>'
        . ($refresh === 'Y' ? ' Es erscheint jetzt wieder als neu.' : '')
    ) . '</div>';
}
if (!empty($errors)) {
    echo pdl_admin_alert('danger', pdl_admin_render_errors($errors));
}

$public_href = '../' . (string) ($settings['script_file'] ?? '') . 'release_id=' . $release_id;
echo '<nav aria-label="Bereiche dieser Seite" class="mb-4"><ul class="nav nav-pills flex-wrap gap-2">';
echo '<li class="nav-item"><a class="nav-link bg-secondary-subtle" href="#pdlEdRel">Allgemein</a></li>';
echo '<li class="nav-item"><a class="nav-link bg-secondary-subtle" href="#pdlEdFiles">Dateien</a></li>';
echo '<li class="nav-item"><a class="nav-link bg-secondary-subtle" href="#pdlEdScreens">Screenshots</a></li>';
echo '<li class="nav-item"><a class="nav-link bg-secondary-subtle" href="#pdlEdComments">Kommentare</a></li>';
echo '<li class="nav-item ms-md-auto"><a class="nav-link bg-secondary-subtle" id="pdlERPublicLink" href="' . htmlspecialchars($public_href, ENT_QUOTES, 'UTF-8') . '">Auf der Webseite ansehen</a></li>';
echo '</ul></nav>';

$name_attr = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$text_attr = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
$autor_nick_attr = htmlspecialchars($autor_nick, ENT_QUOTES, 'UTF-8');
$autor_email_attr = htmlspecialchars($autor_email, ENT_QUOTES, 'UTF-8');
$autor_homepage_attr = htmlspecialchars($autor_homepage, ENT_QUOTES, 'UTF-8');
$can_add = pdl_admin_has_right('addfiles', 'editfiles');
$can_delete = pdl_admin_has_right('delfiles');
$can_moderate = pdl_admin_has_right('comment');
?>
<form action="editrelease.php?submit=1" method="post" novalidate id="pdlEdRel">
    <?php echo csrf_input(); ?>
    <input type="hidden" name="release_id" value="<?php echo $release_id; ?>">
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Allgemein</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlERName" class="form-label">Name <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlERName" name="name" class="form-control<?php echo isset($errors['name']) ? ' is-invalid' : ''; ?>" required maxlength="128"<?php if (isset($errors['name'])) echo ' aria-invalid="true"'; ?> value="<?php echo $name_attr; ?>">
                <div class="form-text">So heißt das Release in Listen und Suchergebnissen (höchstens 128 Zeichen).</div>
            </div>
            <div class="mb-3">
                <label for="pdlEROrdner" class="form-label">Ordner</label>
                <select id="pdlEROrdner" name="ordner_id" class="form-select<?php echo isset($errors['ordner_id']) ? ' is-invalid' : ''; ?>">
                    <option value="0" data-name="Index"<?php echo $ordner_id === 0 ? ' selected' : ''; ?>>Index</option>
                    <?php echo pdl_admin_ordner_options($ordner_id); ?>
                </select>
                <div class="form-text">In welchem Ordner das Release erscheint.</div>
            </div>
            <div class="mb-3">
                <label for="pdlERStatus" class="form-label">Sichtbarkeit</label>
                <select id="pdlERStatus" name="released" class="form-select">
                    <option value="Y"<?php echo $released !== 'N' ? ' selected' : ''; ?>>Sichtbar</option>
                    <option value="N"<?php echo $released === 'N' ? ' selected' : ''; ?>>Versteckt</option>
                </select>
                <div class="form-text">Soll das Release in der öffentlichen Übersicht erscheinen oder vorerst versteckt bleiben? Ein verstecktes Release fehlt im öffentlichen Bereich überall (Listen, Suche, Statistik, Newsletter), und seine Dateien lassen sich nicht herunterladen. Im Adminbereich bleibt es sichtbar.</div>
            </div>
            <div class="mb-3">
                <label for="pdlERViews" class="form-label">Aufrufe</label>
                <input type="number" min="0" id="pdlERViews" name="views" class="form-control<?php echo isset($errors['views']) ? ' is-invalid' : ''; ?>" value="<?php echo $views; ?>">
                <div class="form-text">Wie oft die Detailseite des Releases aufgerufen wurde.</div>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="pdlERRefresh" name="refresh" value="Y">
                <label class="form-check-label" for="pdlERRefresh">Als neu markieren (Datum aktualisieren)</label>
                <div class="form-text">Setzt das Datum des Releases auf heute. Es rückt dann bei „Neueste Downloads“ wieder nach oben.</div>
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
                    <label for="pdlERAutorNick" class="form-label">Name</label>
                    <input type="text" id="pdlERAutorNick" name="autor_nick" class="form-control<?php echo isset($errors['autor_nick']) ? ' is-invalid' : ''; ?>" maxlength="128" value="<?php echo $autor_nick_attr; ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label for="pdlERAutorEmail" class="form-label">E-Mail</label>
                    <input type="email" id="pdlERAutorEmail" name="autor_email" class="form-control<?php echo isset($errors['autor_email']) ? ' is-invalid' : ''; ?>"<?php if (isset($errors['autor_email'])) echo ' aria-invalid="true"'; ?> maxlength="128" value="<?php echo $autor_email_attr; ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label for="pdlERAutorHp" class="form-label">Homepage</label>
                    <input type="url" id="pdlERAutorHp" name="autor_homepage" class="form-control<?php echo isset($errors['autor_homepage']) ? ' is-invalid' : ''; ?>"<?php if (isset($errors['autor_homepage'])) echo ' aria-invalid="true"'; ?> maxlength="128" value="<?php echo $autor_homepage_attr; ?>" placeholder="https://">
                </div>
            </div>

            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="autor_type" value="1" id="pdlERAutorReg"<?php echo $autor_type === 1 ? ' checked' : ''; ?>>
                <label class="form-check-label" for="pdlERAutorReg">Registrierten Benutzer wählen</label>
            </div>
            <div class="ps-4">
                <label for="pdlERAutorId" class="form-label visually-hidden">Registrierter Benutzer</label>
                <select id="pdlERAutorId" name="autor_id" class="form-select w-auto<?php echo isset($errors['autor_id']) ? ' is-invalid' : ''; ?>">
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
                Es gelten die hinterlegten <a href="showreplacements.php">Ersetzungen</a>.<br>
                HTML <strong><?php echo pdlif(($settings['html_releases'] ?? '') == "Y", "an", "aus"); ?></strong>,
                BB-Code <strong><?php echo pdlif(($settings['bb_code'] ?? '') == "Y", "an", "aus"); ?></strong>,
                Glossar <strong><?php echo pdlif(($settings['glossary'] ?? '') == "Y", "an", "aus"); ?></strong>,
                Smileys <strong><?php echo pdlif(($settings['smilies'] ?? '') == "Y", "an", "aus"); ?></strong>,
                Zensur <strong><?php echo pdlif(($settings['badwords_releases'] ?? '') == "Y", "an", "aus"); ?></strong>.
            </div>
        </div>
    </section>

    <div class="d-grid d-md-flex gap-2 justify-content-md-end mb-4">
        <a href="or_list.php?ordner_id=<?php echo (int) ($release['ordner_id'] ?? 0); ?>#pdl-aktuell" class="btn btn-outline-light">Zur Übersicht</a>
        <button type="submit" class="btn btn-primary" id="pdlERSave">Änderungen speichern</button>
    </div>
</form>

<section class="card pdl-card mb-4" id="pdlEdFiles">
    <header class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="h5 mb-0">Dateien</h2>
        <?php if ($can_add) { ?>
        <a class="btn btn-sm btn-primary" id="pdlERAddFile" href="addfile.php?release_id=<?php echo $release_id; ?>">Datei hinzufügen</a>
        <?php } ?>
    </header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Quelle</th>
                    <th scope="col" class="text-end">Größe</th>
                    <th scope="col" class="text-end">Downloads</th>
                    <th scope="col" class="text-end">Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $files_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['files'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "' ORDER BY mirror ASC, file_id ASC");
            $file_names = [];
            $file_rows = [];
            while ($files_row = $db_handler->sql_fetch_array($files_res)) {
                $file_rows[] = $files_row;
                $file_names[(int) $files_row['file_id']] = $files_row;
            }
            if ($file_rows === []) { ?>
                <tr><td colspan="5" class="text-muted text-center">Dieses Release hat noch keine Dateien.</td></tr>
            <?php } else {
                foreach ($file_rows as $files_row) {
                    $fid = (int) $files_row['file_id'];
                    $mirror_of = (int) ($files_row['mirror'] ?? 0);
                    if ($mirror_of > 0 && isset($file_names[$mirror_of])) {
                        $files_row['size'] = $file_names[$mirror_of]['size'] ?? 0;
                    }
                    $url = (string) ($files_row['url'] ?? '');
                    $is_local = pdl_admin_local_file($url) !== null;
            ?>
                <tr data-file-id="<?php echo $fid; ?>">
                    <td>
                        <?php echo htmlspecialchars((string) ($files_row['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        <?php if ($mirror_of > 0) { ?>
                        <br><span class="badge text-bg-info">Spiegel-Server von „<?php echo htmlspecialchars((string) ($file_names[$mirror_of]['name'] ?? '?'), ENT_QUOTES, 'UTF-8'); ?>“</span>
                        <?php } ?>
                    </td>
                    <td class="small text-break"><?php echo $is_local ? '<span class="badge text-bg-secondary">hochgeladen</span> ' : ''; ?><code><?php echo htmlspecialchars(rawurldecode($url), ENT_QUOTES, 'UTF-8'); ?></code></td>
                    <td class="text-end"><?php echo size((int) ($files_row['size'] ?? 0)); ?></td>
                    <td class="text-end"><?php echo (int) ($files_row['downloads'] ?? 0); ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm" role="group">
                            <a class="btn btn-outline-light pdl-btn-edit-file" href="editfile.php?file_id=<?php echo $fid; ?>">bearbeiten</a>
                            <?php if ($can_delete) { ?>
                            <a class="btn btn-outline-danger pdl-btn-del-file" href="delfile.php?file_id=<?php echo $fid; ?>">löschen</a>
                            <?php } ?>
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
        <?php if ($can_add) { ?>
        <a class="btn btn-sm btn-primary" id="pdlERAddScreen" href="addscreen.php?release_id=<?php echo $release_id; ?>">Screenshot hochladen</a>
        <?php } ?>
    </header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th scope="col">Vorschau</th>
                    <th scope="col">Untertitel</th>
                    <th scope="col" class="text-end">Aufrufe</th>
                    <th scope="col" class="text-end">Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $screens_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['screens'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "' ORDER BY screen_id ASC");
            if ($db_handler->sql_num_rows($screens_res) == 0) { ?>
                <tr><td colspan="4" class="text-muted text-center">Dieses Release hat noch keine Screenshots.</td></tr>
            <?php } else {
                while ($screens_row = $db_handler->sql_fetch_array($screens_res)) {
                    $sid = (int) $screens_row['screen_id'];
                    $paths = pdl_admin_screen_paths($release_id, $sid); ?>
                <tr data-screen-id="<?php echo $sid; ?>">
                    <td><?php if (is_file($paths['k'])) { ?><img src="<?php echo htmlspecialchars($paths['k']); ?>" alt="Vorschau des Screenshots" class="img-thumbnail" loading="lazy" style="max-height: 90px;"><?php } else { ?><span class="text-muted small">Bilddatei fehlt</span><?php } ?></td>
                    <td><?php echo htmlspecialchars((string) ($screens_row['text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="text-end"><?php echo (int) ($screens_row['views'] ?? 0); ?></td>
                    <td class="text-end">
                        <?php if ($can_delete) { ?>
                        <a class="btn btn-sm btn-outline-danger pdl-btn-del-screen" href="delscreen.php?screen_id=<?php echo $sid; ?>">löschen</a>
                        <?php } ?>
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
                    <th scope="col" class="text-end">Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $comments_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['comments'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "' ORDER BY time DESC");
            if ($db_handler->sql_num_rows($comments_res) == 0) { ?>
                <tr><td colspan="4" class="text-muted text-center">Zu diesem Release gibt es noch keine Kommentare.</td></tr>
            <?php } else {
                while ($comments_row = $db_handler->sql_fetch_array($comments_res)) { $cid = (int) $comments_row['comment_id']; ?>
                <tr data-comment-id="<?php echo $cid; ?>">
                    <td><?php echo htmlspecialchars((string) ($comments_row['titel'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php if ((int) ($comments_row['user_id'] ?? 0) === 0) echo "Gast"; else echo user((int) $comments_row['user_id']); ?></td>
                    <td><?php echo date((string) ($settings['date_format'] ?? 'd.m.Y'), (int) ($comments_row['time'] ?? time())); ?></td>
                    <td class="text-end">
                        <?php if ($can_moderate) { ?>
                        <div class="btn-group btn-group-sm" role="group">
                            <a class="btn btn-outline-light pdl-btn-edit-comment" href="editcomment.php?comment_id=<?php echo $cid; ?>">bearbeiten</a>
                            <a class="btn btn-outline-danger pdl-btn-del-comment" href="delcomment.php?comment_id=<?php echo $cid; ?>">löschen</a>
                        </div>
                        <?php } ?>
                    </td>
                </tr>
            <?php } } ?>
            </tbody>
        </table>
    </div>
</section>
<?php
include("footer.inc.php");
