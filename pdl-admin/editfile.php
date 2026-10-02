<?php
include("header.inc.php");

$file_id = isset($_GET['file_id']) ? (int)$_GET['file_id'] : (isset($_POST['file_id']) ? (int)$_POST['file_id'] : 0);
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

if (!pdl_admin_require_right('editfiles')) {
    include("footer.inc.php");
    return;
}

$getfile = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT * FROM " . $sql_table['files'] . " WHERE file_id='" . $db_handler->sql_escape_int($file_id) . "' LIMIT 1"
));

if (!is_array($getfile)) {
    pdl_admin_breadcrumb([
        ['title' => 'Adminbereich', 'href' => 'index.php'],
        ['title' => 'Ordner und Releases', 'href' => 'or_list.php'],
        ['title' => 'Datei bearbeiten'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Datei bearbeiten</h1>';
    echo pdl_admin_result('warning', 'Diese Datei gibt es nicht (mehr). Bitte wählen Sie die Datei über das Release aus.', [
        ['label' => 'Zur Übersicht', 'href' => 'or_list.php', 'primary' => true],
    ]);
    include("footer.inc.php");
    return;
}

$release_id = (int) $getfile['release_id'];
$release = $db_handler->sql_fetch_array($db_handler->sql_query(
    "SELECT name, ordner_id FROM " . $sql_table['release'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id) . "' LIMIT 1"
));

// Andere Hauptdateien desselben Releases (Ziele für „Spiegel-Server von“)
$main_files = [];
$main_res = $db_handler->sql_query(
    "SELECT file_id, name, size FROM " . $sql_table['files'] . " WHERE release_id='" . $db_handler->sql_escape_int($release_id)
    . "' AND mirror='0' AND file_id<>'" . $db_handler->sql_escape_int($file_id) . "' ORDER BY file_id ASC"
);
while ($main_row = $db_handler->sql_fetch_array($main_res)) {
    $main_files[(int) $main_row['file_id']] = $main_row;
}
$own_mirrors = $db_handler->sql_num_rows($db_handler->sql_query(
    "SELECT file_id FROM " . $sql_table['files'] . " WHERE mirror='" . $db_handler->sql_escape_int($file_id) . "'"
));

$stored_size = (int) ($getfile['size'] ?? 0);
$stored_display = pdl_format_size_input($stored_size);

$name = (string) ($getfile['name'] ?? '');
$downloads_raw = (string) ($getfile['downloads'] ?? '0');
$url = (string) ($getfile['url'] ?? '');
$mirror = (int) ($getfile['mirror'] ?? 0);
$size_value = $stored_display['value'];
$size_unit = $stored_display['unit'];
$errors = [];

if ($submit === 1) {
    $name = (string) ($_POST['name'] ?? '');
    $downloads_raw = trim((string) ($_POST['downloads'] ?? '0'));
    $url = trim((string) ($_POST['url'] ?? ''));
    $mirror = isset($_POST['mirror']) ? (int) $_POST['mirror'] : 0;
    $size_value = (string) ($_POST['size_value'] ?? '');
    $size_unit = (string) ($_POST['size_unit'] ?? 'MB');

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !csrf_verify($csrf_token_post)) {
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
        if (preg_match('/^\d+$/', $downloads_raw) !== 1) {
            $errors['downloads'] = 'Bitte eine ganze Zahl ab 0 eingeben.';
        }
        $urlErr = pdl_validate_file_url($url);
        if ($urlErr !== null) {
            $errors['url'] = $urlErr;
        }
        if ($mirror !== 0) {
            if (!isset($main_files[$mirror])) {
                $errors['mirror'] = 'Bitte wählen Sie eine andere Hauptdatei dieses Releases oder „Kein Spiegel-Server“.';
            } elseif ($own_mirrors > 0) {
                $errors['mirror'] = 'Diese Datei hat selbst Spiegel-Server und kann deshalb nicht Spiegel-Server einer anderen Datei sein.';
            }
        }
        // Unverändert gelassene Größe: gespeicherten Bytewert behalten (keine Rundungsverluste).
        if ($size_value === $stored_display['value'] && strtoupper($size_unit) === $stored_display['unit']) {
            $new_size = $stored_size;
        } else {
            $new_size = pdl_parse_size_input($size_value, $size_unit);
            if ($new_size === null) {
                $errors['size'] = 'Bitte geben Sie eine Zahl ein, zum Beispiel 2,5 (Einheit daneben wählen), oder lassen Sie das Feld leer.';
            } elseif ($new_size === 0) {
                $local = pdl_admin_local_file($url);
                $new_size = $local !== null ? (int) filesize($local) : 0;
            }
        }
    }

    if (empty($errors)) {
        if ($mirror !== 0) {
            $new_size = (int) ($main_files[$mirror]['size'] ?? $new_size);
        }
        $update_ok = $db_handler->sql_query(
            "UPDATE " . $sql_table['files'] . " SET "
            . "name='" . $db_handler->sql_escape_string(trim($name)) . "', "
            . "downloads='" . $db_handler->sql_escape_int((int) $downloads_raw) . "', "
            . "size='" . $db_handler->sql_escape_int((int) $new_size) . "', "
            . "url='" . $db_handler->sql_escape_string($url) . "', "
            . "mirror='" . $db_handler->sql_escape_int($mirror) . "' "
            . "WHERE file_id='" . $db_handler->sql_escape_int($file_id) . "'"
        );
        if ($update_ok === true) {
            // Spiegel-Server dieser Datei übernehmen die neue Größe.
            $db_handler->sql_query(
                "UPDATE " . $sql_table['files'] . " SET size='" . $db_handler->sql_escape_int((int) $new_size) . "' WHERE mirror='" . $db_handler->sql_escape_int($file_id) . "'"
            );
            pdl_audit_log($db_handler, $sql_table, $user_details, 'update', 'file', $file_id);
            // Neue Adresse: die bisher hochgeladene Datei entfernen, wenn kein
            // anderer Eintrag sie mehr nutzt.
            $old_local = pdl_admin_local_file((string) ($getfile['url'] ?? ''));
            $old_removed = $old_local !== null
                && $old_local !== pdl_admin_local_file($url)
                && pdl_admin_delete_local_file((string) ($getfile['url'] ?? ''));
            pdl_admin_breadcrumb([
                ['title' => 'Adminbereich', 'href' => 'index.php'],
                ['title' => 'Ordner und Releases', 'href' => 'or_list.php?ordner_id=' . (int) ($release['ordner_id'] ?? 0) . '#pdl-aktuell'],
                ['title' => (string) ($release['name'] ?? 'Release'), 'href' => 'editrelease.php?release_id=' . $release_id],
                ['title' => 'Datei bearbeiten'],
            ]);
            echo '<h1 class="h3 pdl-page-title">Datei bearbeiten</h1>';
            echo pdl_admin_result('success', '<strong>Die Datei „' . htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8') . '“ wurde gespeichert.</strong>'
                . ($old_removed ? ' Die bisher hochgeladene Datei unter der alten Adresse wurde vom Server entfernt, weil kein anderer Eintrag sie mehr nutzt.' : ''), [
                ['label' => 'Zurück zum Release', 'href' => 'editrelease.php?release_id=' . $release_id . '#pdlEdFiles', 'id' => 'pdlNextEditRelease', 'primary' => true],
                ['label' => 'Datei erneut bearbeiten', 'href' => 'editfile.php?file_id=' . $file_id, 'id' => 'pdlNextEditFile'],
            ]);
            include("footer.inc.php");
            return;
        }
        $errors['_db'] = 'Die Änderungen konnten nicht gespeichert werden: ' . ($db_handler->sql_error() ?: 'unbekannter Fehler') . '.';
    }
}

pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'Ordner und Releases', 'href' => 'or_list.php?ordner_id=' . (int) ($release['ordner_id'] ?? 0) . '#pdl-aktuell'],
    ['title' => (string) ($release['name'] ?? 'Release'), 'href' => 'editrelease.php?release_id=' . $release_id],
    ['title' => 'Datei bearbeiten'],
]);
echo '<h1 class="h3 pdl-page-title">Datei bearbeiten</h1>';
if (!empty($errors)) {
    echo pdl_admin_alert('danger', pdl_admin_render_errors($errors));
}
?>
<form action="editfile.php?submit=1" method="post" novalidate id="pdlEdFile">
    <?php echo csrf_input(); ?>
    <input type="hidden" name="file_id" value="<?php echo $file_id; ?>">
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Datei</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlEdFName" class="form-label">Anzeigename <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlEdFName" name="name" maxlength="128" class="form-control<?php echo isset($errors['name']) ? ' is-invalid' : ''; ?>" required value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-text">Steht auf dem Download-Knopf.</div>
            </div>
            <div class="mb-3">
                <label for="pdlEdFUrl" class="form-label">URL <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="pdlEdFUrl" name="url" maxlength="255" class="form-control<?php echo isset($errors['url']) ? ' is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" required>
                <div class="form-text">Vollständige Adresse mit <code>http://</code> oder <code>https://</code>; bei hochgeladenen Dateien der Pfad unter <code>pdl-files/</code>.<?php echo pdl_admin_local_file((string) ($getfile['url'] ?? '')) !== null ? ' Ändern Sie die Adresse, löscht PowerDownload die bisher hochgeladene Datei vom Server, sofern kein anderer Eintrag sie nutzt.' : ''; ?></div>
            </div>
            <div class="mb-3">
                <label for="pdlEdFSize" class="form-label">Dateigröße</label>
                <div class="input-group pdl-size-input">
                    <input type="text" inputmode="decimal" id="pdlEdFSize" name="size_value" class="form-control<?php echo isset($errors['size']) ? ' is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($size_value, ENT_QUOTES, 'UTF-8'); ?>" placeholder="z. B. 2,5">
                    <select id="pdlEdFSizeUnit" name="size_unit" class="form-select" aria-label="Einheit der Dateigröße">
                        <?php foreach (pdl_size_units() as $unit => $unit_factor) { ?>
                        <option value="<?php echo $unit; ?>"<?php echo strtoupper($size_unit) === $unit ? ' selected' : ''; ?>><?php echo $unit; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-text">Gespeichert: <?php echo htmlspecialchars(number_format($stored_size, 0, ',', '.')); ?> Byte. Leer lassen, um die Größe einer Datei unter <code>pdl-files/</code> neu zu ermitteln.</div>
            </div>
            <div class="mb-3">
                <label for="pdlEdFDls" class="form-label">Downloads</label>
                <input type="number" min="0" id="pdlEdFDls" name="downloads" class="form-control<?php echo isset($errors['downloads']) ? ' is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($downloads_raw, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-text">Wie oft die Datei heruntergeladen wurde.</div>
            </div>
            <div class="mb-3">
                <label for="pdlEdFMirror" class="form-label">Spiegel-Server von</label>
                <select id="pdlEdFMirror" name="mirror" class="form-select<?php echo isset($errors['mirror']) ? ' is-invalid' : ''; ?>">
                    <option value="0">Kein Spiegel-Server (eigenständige Datei)</option>
                    <?php
                    foreach ($main_files as $main_id => $main_row) {
                        echo '<option value="' . $main_id . '" data-name="' . htmlspecialchars((string) $main_row['name'], ENT_QUOTES, 'UTF-8') . '"'
                            . ($main_id === $mirror ? ' selected' : '') . '>' . htmlspecialchars((string) $main_row['name'], ENT_QUOTES, 'UTF-8') . '</option>';
                    }
                    ?>
                </select>
                <div class="form-text">Ist diese Datei nur ein zweiter Download-Ort einer anderen Datei, wählen Sie diese hier aus.<?php echo $own_mirrors > 0 ? ' Diese Datei hat selbst ' . pdl_admin_count_label($own_mirrors, 'Spiegel-Server', 'Spiegel-Server') . '.' : ''; ?></div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="editrelease.php?release_id=<?php echo $release_id; ?>#pdlEdFiles" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary" id="pdlEdFSave">Änderungen speichern</button>
    </div>
</form>
<?php
include("footer.inc.php");
