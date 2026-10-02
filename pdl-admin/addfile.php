<?php
include("header.inc.php");

$name = (string) ($_POST['name'] ?? '');
$url = (string) ($_POST['url'] ?? ($_GET['url'] ?? ''));
$mirror = isset($_POST['mirror']) ? (int) $_POST['mirror'] : 0;
$source = (string) ($_POST['file_source'] ?? 'url'); // 'url' oder 'upload'
$release_id = isset($_POST['release_id']) ? (int) $_POST['release_id'] : (isset($_GET['release_id']) ? (int) $_GET['release_id'] : 0);
$submit = isset($_GET['submit']) ? (int) $_GET['submit'] : 0;
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

// Größe: Zahl plus Einheit. Ältere Aufrufe (FTP-Browser) übergeben "size" in Byte.
$size_value = (string) ($_POST['size_value'] ?? '');
$size_unit = (string) ($_POST['size_unit'] ?? 'MB');
if (!isset($_POST['size_value']) && isset($_REQUEST['size']) && (int) $_REQUEST['size'] > 0) {
    $prefill = pdl_format_size_input((int) $_REQUEST['size']);
    $size_value = $prefill['value'];
    $size_unit = $prefill['unit'];
}

$errors = [];

if (!pdl_admin_require_right('addfiles', 'editfiles')) {
    include("footer.inc.php");
    return;
}

$release = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT release_id, name, ordner_id FROM " . $sql_table['release'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "' LIMIT 1"
));

$breadcrumb = [
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Ordner und Releases', 'href' => 'or_list.php?ordner_id=' . (int) ($release['ordner_id'] ?? 0) . '#pdl-aktuell'],
    ['title' => is_array($release) ? (string) $release['name'] : 'Release', 'href' => 'editrelease.php?release_id=' . $release_id],
    ['title' => 'Datei hinzufügen'],
];

if (!is_array($release)) {
    pdl_admin_breadcrumb($breadcrumb);
    echo '<h1 class="h3 pdl-page-title">Datei hinzufügen</h1>';
    echo pdl_admin_result('warning', 'Bitte wählen Sie zuerst ein Release aus. Dateien gehören immer zu einem Release.', [
        ['label' => 'Zur Übersicht', 'href' => 'or_list.php', 'primary' => true],
    ]);
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

// Hauptdateien dieses Releases (mögliche Ziele für einen Spiegel-Server)
$main_files = [];
$main_res = $db_handler->sql_query("SELECT file_id, name, size FROM " . $sql_table['files'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "' AND mirror='0' ORDER BY file_id ASC");
while ($main_row = $db_handler->sql_fetch_array($main_res)) {
    $main_files[(int) $main_row['file_id']] = $main_row;
}

if ($submit === 1) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errors['upload_file'] = 'Die Datei ist größer als der Server annimmt (höchstens ' . pdl_format_bytes($max_upload_bytes) . ').';
    } elseif (!csrf_verify($csrf_token_post)) {
        $errors['_csrf'] = 'Sicherheits-Token ungültig oder abgelaufen. Bitte senden Sie das Formular erneut ab.';
    }
    if (empty($errors)) {
        // Anzeigename ist immer Pflicht.
        $errors = pdl_validate_required(['name' => $name], ['name']);
        if (!isset($errors['name'])) {
            $lenErr = pdl_validate_max_length(trim($name), 128);
            if ($lenErr !== null) {
                $errors['name'] = $lenErr;
            }
        }
        if ($mirror !== 0 && !isset($main_files[$mirror])) {
            $errors['mirror'] = 'Bitte wählen Sie eine vorhandene Datei dieses Releases oder „Kein Spiegel-Server“.';
        }

        // Je nach Quelle: URL ODER Upload prüfen.
        if ($source === 'upload') {
            $upload_err = pdl_validate_file_upload($_FILES['upload_file'] ?? [], $max_upload_bytes);
            if ($upload_err !== null) {
                $errors['upload_file'] = $upload_err;
            }
        } else {
            $urlErr = pdl_validate_file_url($url);
            if ($urlErr !== null) {
                $errors['url'] = $urlErr;
            }
            $size_bytes = pdl_parse_size_input($size_value, $size_unit);
            if ($size_bytes === null) {
                $errors['size'] = 'Bitte geben Sie eine Zahl ein, zum Beispiel 2,5 (Einheit daneben wählen), oder lassen Sie das Feld leer.';
            }
        }
    }

    $final_url = trim($url);
    $final_size = 0;
    $uploaded_path = null;
    if (empty($errors) && $source === 'upload') {
        // Upload-Quelle: Datei ins Ziel-Verzeichnis verschieben.
        $base_dir = realpath(pdl_admin_base_dir());
        if ($base_dir === false) {
            $errors['upload_file'] = 'Das Installationsverzeichnis konnte nicht ermittelt werden.';
        } else {
            $target_dir = $base_dir . DIRECTORY_SEPARATOR . 'pdl-files' . DIRECTORY_SEPARATOR . $release_id;
            if (!is_dir($target_dir) && !@mkdir($target_dir, 0775, true) && !is_dir($target_dir)) {
                $errors['upload_file'] = 'Das Verzeichnis pdl-files/' . $release_id . '/ konnte nicht angelegt werden. Bitte prüfen Sie die Schreibrechte.';
            } else {
                pdl_admin_ensure_files_htaccess($base_dir . DIRECTORY_SEPARATOR . 'pdl-files');
                $safe_name = pdl_sanitize_upload_filename((string) $_FILES['upload_file']['name']);
                // Doppelte Dateinamen vermeiden.
                $final_name = $safe_name;
                $counter = 1;
                while (file_exists($target_dir . DIRECTORY_SEPARATOR . $final_name) && $counter <= 999) {
                    $dotPos = strrpos($safe_name, '.');
                    $base = $dotPos === false ? $safe_name : substr($safe_name, 0, $dotPos);
                    $ext = $dotPos === false ? '' : substr($safe_name, $dotPos);
                    $final_name = $base . '_' . $counter . $ext;
                    $counter++;
                }
                $target_path = $target_dir . DIRECTORY_SEPARATOR . $final_name;
                if (!move_uploaded_file((string) $_FILES['upload_file']['tmp_name'], $target_path)) {
                    $errors['upload_file'] = 'Die Datei konnte nicht gespeichert werden. Bitte prüfen Sie die Schreibrechte von pdl-files/.';
                } else {
                    @chmod($target_path, 0644);
                    $uploaded_path = $target_path;
                    $final_url = 'pdl-files/' . $release_id . '/' . rawurlencode($final_name);
                    $final_size = (int) filesize($target_path);
                }
            }
        }
    } elseif (empty($errors)) {
        $final_size = (int) pdl_parse_size_input($size_value, $size_unit);
        // Liegt die Datei auf diesem Server, kennt PowerDownload die Größe selbst.
        if ($final_size === 0) {
            $local = pdl_admin_local_file($final_url);
            if ($local !== null) {
                $final_size = (int) filesize($local);
            }
        }
    }
    if (empty($errors) && $mirror !== 0) {
        // Ein Spiegel-Server ist dieselbe Datei an anderem Ort: gleiche Größe.
        $final_size = (int) ($main_files[$mirror]['size'] ?? $final_size);
    }

    if (empty($errors)) {
        $insert_ok = $db_handler->sql_query(
            "INSERT INTO " . $sql_table['files'] . " (release_id,url,size,name,mirror) VALUES ("
            . "'" . $db_handler->sql_escape_int($release_id) . "', "
            . "'" . $db_handler->sql_escape_string($final_url) . "', "
            . "'" . $db_handler->sql_escape_int($final_size) . "', "
            . "'" . $db_handler->sql_escape_string(trim($name)) . "', "
            . "'" . $db_handler->sql_escape_int($mirror) . "')"
        );
        $new_id = $insert_ok === true ? (int) $db_handler->sql_insert_id() : 0;
        if ($new_id > 0) {
            pdl_audit_log($db_handler, $sql_table, $user_details, 'create', 'file', $new_id);
            pdl_admin_breadcrumb($breadcrumb);
            echo '<h1 class="h3 pdl-page-title">Datei hinzufügen</h1>';
            echo pdl_admin_result(
                'success',
                '<strong>Die Datei „' . htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8') . '“ wurde gespeichert</strong>'
                . ($final_size > 0 ? ' (' . htmlspecialchars(size($final_size)) . ')' : '') . '.'
                . ($mirror !== 0 ? ' Sie ist als Spiegel-Server von „' . htmlspecialchars((string) $main_files[$mirror]['name'], ENT_QUOTES, 'UTF-8') . '“ eingetragen.' : ''),
                [
                    ['label' => 'Zurück zum Release', 'href' => 'editrelease.php?release_id=' . $release_id . '#pdlEdFiles', 'id' => 'pdlNextEditRelease', 'primary' => true],
                    ['label' => 'Weitere Datei hinzufügen', 'href' => 'addfile.php?release_id=' . $release_id, 'id' => 'pdlNextAddFile'],
                    ['label' => 'Screenshot hochladen', 'href' => 'addscreen.php?release_id=' . $release_id, 'id' => 'pdlNextAddScreen'],
                ]
            );
            include("footer.inc.php");
            return;
        }
        if ($uploaded_path !== null && is_file($uploaded_path)) {
            @unlink($uploaded_path);
        }
        $errors['_db'] = 'Die Datei konnte nicht gespeichert werden: ' . ($db_handler->sql_error() ?: 'unbekannter Fehler') . '.';
    }
}

pdl_admin_breadcrumb($breadcrumb);
echo '<h1 class="h3 pdl-page-title">Datei hinzufügen</h1>';
echo '<p class="text-muted">Release: <strong>' . htmlspecialchars((string) $release['name'], ENT_QUOTES, 'UTF-8') . '</strong></p>';

if (!empty($errors)) {
    echo pdl_admin_alert('danger', pdl_admin_render_errors($errors));
}

$name_attr = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$url_attr = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
$cur_source = $source === 'upload' ? 'upload' : 'url';
?>
<form action="addfile.php?submit=1" method="post" novalidate enctype="multipart/form-data" id="pdlAddFile">
    <?php echo csrf_input(); ?>
    <input type="hidden" name="release_id" value="<?php echo $release_id; ?>">
    <section class="card pdl-card mb-4">
        <header class="card-header">
            <h2 class="h5 mb-0">Datei</h2>
        </header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlFileName" class="form-label">Anzeigename <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlFileName" name="name" class="form-control<?php echo isset($errors['name']) ? ' is-invalid' : ''; ?>" required aria-required="true" aria-describedby="pdlFileNameHelp"<?php if (isset($errors['name'])) echo ' aria-invalid="true"'; ?> value="<?php echo $name_attr; ?>" maxlength="128">
                <div id="pdlFileNameHelp" class="form-text">Pflichtfeld. Dieser Name steht später auf dem Download-Knopf (z.&nbsp;B. „Satzung als PDF“).</div>
            </div>

            <fieldset class="mb-3" aria-describedby="pdlFileSourceHelp">
                <legend class="form-label fs-6">Woher kommt die Datei? <span class="text-danger" aria-hidden="true">*</span></legend>
                <div id="pdlFileSourceHelp" class="form-text mb-2">Verlinken Sie eine vorhandene Adresse oder laden Sie die Datei direkt von Ihrem Rechner hoch.</div>
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
                <input type="text" id="pdlFileUrl" name="url" maxlength="255" class="form-control<?php echo isset($errors['url']) ? ' is-invalid' : ''; ?>" value="<?php echo $url_attr; ?>" aria-describedby="pdlFileUrlHelp" placeholder="https://"<?php if (isset($errors['url'])) echo ' aria-invalid="true"'; ?>>
                <div id="pdlFileUrlHelp" class="form-text">Vollständige Download-Adresse, beginnend mit <code>http://</code> oder <code>https://</code> (höchstens 255 Zeichen).<?php
                if (($settings['ftp_on'] ?? '') == "Y" && function_exists("ftp_connect")) {
                    echo ' Größere Dateien können Sie über den <a href="ftp_browser.php?release_id=' . $release_id . '">FTP-Browser</a> hochladen.';
                }
                ?></div>
                <label for="pdlFileSize" class="form-label mt-3">Dateigröße</label>
                <div class="input-group pdl-size-input">
                    <input type="text" inputmode="decimal" id="pdlFileSize" name="size_value" class="form-control<?php echo isset($errors['size']) ? ' is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($size_value, ENT_QUOTES, 'UTF-8'); ?>" placeholder="z. B. 2,5" aria-describedby="pdlFileSizeHelp">
                    <select id="pdlFileSizeUnit" name="size_unit" class="form-select" aria-label="Einheit der Dateigröße">
                        <?php foreach (pdl_size_units() as $unit => $unit_factor) { ?>
                        <option value="<?php echo $unit; ?>"<?php echo strtoupper($size_unit) === $unit ? ' selected' : ''; ?>><?php echo $unit; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div id="pdlFileSizeHelp" class="form-text">Zahl mit Komma oder Punkt und Einheit, z.&nbsp;B. „2,5 MB“. Leer lassen, wenn die Datei auf diesem Server unter <code>pdl-files/</code> liegt – dann ermittelt PowerDownload die Größe selbst. Ohne Angabe wird keine Größe angezeigt.</div>
            </div>

            <div id="pdlFileSourceUploadBlock" class="mb-3 ps-4 border-start border-secondary"<?php echo $cur_source !== 'upload' ? ' hidden' : ''; ?>>
                <label for="pdlFileUpload" class="form-label">Datei hochladen <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="file" id="pdlFileUpload" name="upload_file" class="form-control<?php echo isset($errors['upload_file']) ? ' is-invalid' : ''; ?>" aria-describedby="pdlFileUploadHelp"<?php if (isset($errors['upload_file'])) echo ' aria-invalid="true"'; ?>>
                <div id="pdlFileUploadHelp" class="form-text">
                    Größe höchstens <strong><?php echo htmlspecialchars(pdl_format_bytes($max_upload_bytes)); ?></strong>; die Größe wird automatisch eingetragen.
                    Aus Sicherheitsgründen sind Skript-Endungen wie .php, .phtml, .phar oder .sh nicht erlaubt.
                    Die Datei wird unter <code>pdl-files/<?php echo $release_id; ?>/</code> gespeichert.
                </div>
            </div>

            <div class="mb-3">
                <label for="pdlFileMirror" class="form-label">Spiegel-Server</label>
                <select id="pdlFileMirror" name="mirror" class="form-select<?php echo isset($errors['mirror']) ? ' is-invalid' : ''; ?>" aria-describedby="pdlFileMirrorHelp">
                    <option value="0">Kein Spiegel-Server (eigenständige Datei)</option>
                    <?php
                    foreach ($main_files as $main_id => $main_row) {
                        echo '<option value="' . $main_id . '" data-name="' . htmlspecialchars((string) ($main_row['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '"'
                            . ($mirror === $main_id ? ' selected' : '') . '>Spiegel-Server von „'
                            . htmlspecialchars((string) ($main_row['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '“</option>';
                    }
                    ?>
                </select>
                <div id="pdlFileMirrorHelp" class="form-text">Ein Spiegel-Server ist ein zweiter Download-Ort für <em>dieselbe</em> Datei. Er übernimmt deren Größe und wird mit ihr gelöscht. Für eine andere Datei (z.&nbsp;B. eine zweite Version) wählen Sie „Kein Spiegel-Server“.</div>
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
        <a href="editrelease.php?release_id=<?php echo $release_id; ?>#pdlEdFiles" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlFileSubmit">Datei hinzufügen</button>
    </div>
</form>
<?php
include("footer.inc.php");
