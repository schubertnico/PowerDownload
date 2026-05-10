<?php
include("header.inc.php");

$name = (string) ($_POST['name'] ?? '');
$size = isset($_POST['size']) ? (int) $_POST['size'] : 0;
$url = (string) ($_POST['url'] ?? '');
$mirror = (string) ($_POST['mirror'] ?? '');
$source = (string) ($_POST['file_source'] ?? 'url'); // 'url' oder 'upload'
$release_id = isset($_POST['release_id']) ? (int) $_POST['release_id'] : (isset($_GET['release_id']) ? (int) $_GET['release_id'] : 0);
$submit = isset($_GET['submit']) ? (int) $_GET['submit'] : 0;
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

$errors = [];

$has_right = (($user_rights['editfiles'] ?? '') === 'Y') || (($user_rights['adminaccess'] ?? '') === 'Y');

if (!$has_right) {
    echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.');
    include("footer.inc.php");
    return;
}

// Maximal-Upload-Größe in Bytes. Wir nehmen das Minimum aus
// upload_max_filesize, post_max_size und unserem Hard-Limit (100 MB).
$ini_upload = pdl_ini_size_in_bytes((string) ini_get('upload_max_filesize'));
$ini_post = pdl_ini_size_in_bytes((string) ini_get('post_max_size'));
$max_upload_bytes = (int) min(100 * 1024 * 1024, max(0, $ini_upload), max(0, $ini_post));
if ($max_upload_bytes <= 0) {
    $max_upload_bytes = 100 * 1024 * 1024;
}

if ($submit === 1) {
    if (!csrf_verify($csrf_token_post)) {
        $errors['_csrf'] = 'Sicherheits-Token ungültig oder abgelaufen.';
    }
    if (empty($errors)) {
        // Anzeigename ist immer Pflicht.
        $errors = pdl_validate_required(['name' => $name], ['name']);
        if (!pdl_release_exists($db_handler, $sql_table, $release_id)) {
            $errors['release_id'] = 'Release existiert nicht.';
        }
        $sizeErr = pdl_validate_int_min($size, 0);
        if ($sizeErr !== null) {
            $errors['size'] = $sizeErr;
        }

        // Je nach Quelle: URL ODER Upload prüfen.
        $uploaded_filename = '';
        if ($source === 'upload') {
            $upload_err = pdl_validate_file_upload($_FILES['upload_file'] ?? [], $max_upload_bytes);
            if ($upload_err !== null) {
                $errors['upload_file'] = $upload_err;
            }
        } else {
            $url_required = pdl_validate_required(['url' => $url], ['url']);
            if (!empty($url_required)) {
                $errors['url'] = $url_required['url'];
            } else {
                $urlErr = pdl_validate_url_optional($url);
                if ($urlErr !== null) {
                    $errors['url'] = $urlErr;
                }
            }
        }
    }

    if (empty($errors)) {
        // Upload-Quelle: Datei ins Ziel-Verzeichnis verschieben.
        $final_url = trim($url);
        $final_size = $size;
        if ($source === 'upload' && isset($_FILES['upload_file']['tmp_name'])) {
            $base_dir = realpath(dirname(__DIR__));
            if ($base_dir === false) {
                $errors['upload_file'] = 'Basisverzeichnis konnte nicht ermittelt werden.';
            } else {
                $target_dir = $base_dir . DIRECTORY_SEPARATOR . 'pdl-files' . DIRECTORY_SEPARATOR . $release_id;
                if (!is_dir($target_dir) && !@mkdir($target_dir, 0775, true) && !is_dir($target_dir)) {
                    $errors['upload_file'] = 'Ziel-Verzeichnis konnte nicht erstellt werden.';
                } else {
                    $safe_name = pdl_sanitize_upload_filename((string) $_FILES['upload_file']['name']);
                    // Doppelte Dateinamen vermeiden.
                    $final_name = $safe_name;
                    $counter = 1;
                    while (file_exists($target_dir . DIRECTORY_SEPARATOR . $final_name)) {
                        $dotPos = strrpos($safe_name, '.');
                        $base = $dotPos === false ? $safe_name : substr($safe_name, 0, $dotPos);
                        $ext = $dotPos === false ? '' : substr($safe_name, $dotPos);
                        $final_name = $base . '_' . $counter . $ext;
                        $counter++;
                        if ($counter > 999) {
                            break;
                        }
                    }
                    $target_path = $target_dir . DIRECTORY_SEPARATOR . $final_name;
                    if (!move_uploaded_file((string) $_FILES['upload_file']['tmp_name'], $target_path)) {
                        $errors['upload_file'] = 'Datei konnte nicht ins Ziel-Verzeichnis verschoben werden.';
                    } else {
                        @chmod($target_path, 0644);
                        $final_url = 'pdl-files/' . $release_id . '/' . rawurlencode($final_name);
                        $final_size = (int) filesize($target_path);
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        $db_handler->sql_query(
            "INSERT INTO " . $sql_table['files'] . " (release_id,url,size,name,mirror) VALUES ("
            . "'" . $db_handler->sql_escape_int($release_id) . "', "
            . "'" . $db_handler->sql_escape_string($final_url) . "', "
            . "'" . $db_handler->sql_escape_int($final_size) . "', "
            . "'" . $db_handler->sql_escape_string(trim($name)) . "', "
            . "'" . $db_handler->sql_escape_string($mirror) . "')"
        );
        $new_id = (int) $db_handler->sql_insert_id();
        pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'file', $new_id);

        echo pdl_admin_alert(
            'success',
            '<strong>Datei „' . htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8')
            . '" wurde gespeichert</strong> (ID ' . $new_id . ').'
            . ' <a class="alert-link" href="addfile.php?release_id=' . $release_id . '">Weitere Datei hinzufügen</a>'
            . ' oder <a class="alert-link" href="editrelease.php?release_id=' . $release_id . '">zurück zum Release</a>.'
        );
        echo '<a class="btn btn-primary" href="editrelease.php?release_id=' . $release_id . '">Zurück zum Release</a>';
        include("footer.inc.php");
        return;
    }
}

pdl_admin_breadcrumb([
    ['title' => 'Admin-Center', 'href' => 'index.php'],
    ['title' => 'Releases', 'href' => 'or_list.php'],
    ['title' => 'Datei hinzufügen'],
]);
echo '<h1 class="h3 pdl-page-title">Datei hinzufügen</h1>';

if (!empty($errors)) {
    echo pdl_admin_alert('danger', pdl_admin_render_errors($errors));
}

$name_attr = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$url_attr = htmlspecialchars($url === '' ? '' : urldecode($url), ENT_QUOTES, 'UTF-8');
?>
<form action="addfile.php?submit=1" method="post" novalidate enctype="multipart/form-data">
    <?php echo csrf_input(); ?>
    <input type="hidden" name="release_id" value="<?php echo $release_id; ?>">
    <section class="card pdl-card mb-4">
        <header class="card-header">
            <h2 class="h5 mb-0">Datei</h2>
        </header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlFileName" class="form-label">Anzeigename <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlFileName" name="name" class="form-control<?php echo isset($errors['name']) ? ' is-invalid' : ''; ?>" required aria-required="true" aria-describedby="pdlFileNameHelp"<?php if (isset($errors['name'])) echo ' aria-invalid="true"'; ?> value="<?php echo $name_attr; ?>" maxlength="200">
                <div id="pdlFileNameHelp" class="form-text">Pflichtfeld. Dieser Name steht später auf dem Download-Knopf (z.&nbsp;B. "Setup-Datei Windows").</div>
            </div>

            <fieldset class="mb-3" aria-describedby="pdlFileSourceHelp">
                <legend class="form-label">Woher kommt die Datei? <span class="text-danger" aria-hidden="true">*</span></legend>
                <div id="pdlFileSourceHelp" class="form-text mb-2">Wählen Sie aus, ob Sie eine bestehende Adresse verlinken oder eine Datei direkt von Ihrem Rechner hochladen möchten.</div>
                <?php $cur_source = $source === 'upload' ? 'upload' : 'url'; ?>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="file_source" value="url" id="pdlFileSourceUrl"<?php echo $cur_source === 'url' ? ' checked' : ''; ?> aria-controls="pdlFileSourceUrlBlock">
                    <label class="form-check-label" for="pdlFileSourceUrl">Adresse (URL) angeben</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="file_source" value="upload" id="pdlFileSourceUpload"<?php echo $cur_source === 'upload' ? ' checked' : ''; ?> aria-controls="pdlFileSourceUploadBlock">
                    <label class="form-check-label" for="pdlFileSourceUpload">Datei vom Rechner hochladen</label>
                </div>
            </fieldset>

            <div id="pdlFileSourceUrlBlock" class="mb-3 ps-4 border-start border-secondary"<?php echo $cur_source === 'upload' ? ' hidden' : ''; ?>>
                <label for="pdlFileUrl" class="form-label">URL zur Datei <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlFileUrl" name="url" class="form-control<?php echo isset($errors['url']) ? ' is-invalid' : ''; ?>" value="<?php echo $url_attr; ?>" aria-describedby="pdlFileUrlHelp"<?php if (isset($errors['url'])) echo ' aria-invalid="true"'; ?>>
                <div id="pdlFileUrlHelp" class="form-text">Vollständige Download-Adresse, beginnend mit <code>http://</code> oder <code>https://</code>.<?php
                if (($settings['ftp_on'] ?? '') == "Y" && function_exists("ftp_connect")) {
                    echo ' Alternativ können Sie über den <a href="ftp_browser.php?release_id=' . $release_id . '" class="link-light">FTP-Browser</a> eine Datei hochladen.';
                }
                ?></div>
                <label for="pdlFileSize" class="form-label mt-3">Dateigröße in Byte</label>
                <input type="number" min="0" step="1" id="pdlFileSize" name="size" class="form-control<?php echo isset($errors['size']) ? ' is-invalid' : ''; ?>" value="<?php echo $size; ?>" aria-describedby="pdlFileSizeHelp">
                <div id="pdlFileSizeHelp" class="form-text">Wird Besuchern vor dem Download angezeigt. Bei 0 wird keine Größe angezeigt.</div>
            </div>

            <div id="pdlFileSourceUploadBlock" class="mb-3 ps-4 border-start border-secondary"<?php echo $cur_source !== 'upload' ? ' hidden' : ''; ?>>
                <label for="pdlFileUpload" class="form-label">Datei hochladen <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="file" id="pdlFileUpload" name="upload_file" class="form-control<?php echo isset($errors['upload_file']) ? ' is-invalid' : ''; ?>" aria-describedby="pdlFileUploadHelp"<?php if (isset($errors['upload_file'])) echo ' aria-invalid="true"'; ?>>
                <div id="pdlFileUploadHelp" class="form-text">
                    Bitte eine Datei von Ihrem Rechner auswählen.
                    Größenlimit: <strong><?php echo htmlspecialchars(pdl_format_bytes($max_upload_bytes)); ?></strong>.
                    Aus Sicherheitsgründen sind ausführbare Skript-Endungen wie .php, .phtml, .phar, .sh nicht erlaubt.
                    Die Datei wird im Ordner <code>pdl-files/<?php echo (int) $release_id; ?>/</code> gespeichert.
                </div>
            </div>

            <div class="mb-3">
                <label for="pdlFileMirror" class="form-label">Spiegel-Server (Mirror)</label>
                <select id="pdlFileMirror" name="mirror" class="form-select" aria-describedby="pdlFileMirrorHelp">
                    <option value="0">Kein Spiegel-Server (eigenständige Datei)</option>
                    <?php
                    $escaped_release_id = $db_handler->sql_escape_int($release_id);
                    $mirror_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['files'] . " WHERE release_id='" . $escaped_release_id . "' AND mirror='0'");
                    while ($mirror_row = $db_handler->sql_fetch_array($mirror_res)) {
                        echo '<option value="' . (int) $mirror_row['file_id'] . '">' . htmlspecialchars($mirror_row['name'] ?? '', ENT_QUOTES, 'UTF-8') . '</option>';
                    }
                    ?>
                </select>
                <div id="pdlFileMirrorHelp" class="form-text">Ein Spiegel-Server (engl. <em>Mirror</em>) ist ein zusätzlicher Download-Ort für dieselbe Datei. Wählen Sie nur dann eine bestehende Datei aus, wenn diese URL eine Kopie davon ist.</div>
            </div>
        </div>
    </section>

    <script>
    (function () {
        function toggleSource() {
            var urlSel = document.getElementById('pdlFileSourceUrl');
            var upSel = document.getElementById('pdlFileSourceUpload');
            var urlBlock = document.getElementById('pdlFileSourceUrlBlock');
            var upBlock = document.getElementById('pdlFileSourceUploadBlock');
            if (!urlSel || !upSel) return;
            urlBlock.hidden = !urlSel.checked;
            upBlock.hidden = !upSel.checked;
        }
        document.addEventListener('change', function (e) {
            if (e.target && e.target.name === 'file_source') toggleSource();
        });
        toggleSource();
    })();
    </script>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="editrelease.php?release_id=<?php echo $release_id; ?>" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Datei hinzufügen</button>
    </div>
</form>
<?php
include("footer.inc.php");
