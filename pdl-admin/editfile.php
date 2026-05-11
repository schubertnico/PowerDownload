<?php
include("header.inc.php");

// Extract variables for PHP 8.4 compatibility
$file_id = isset($_GET['file_id']) ? (int)$_GET['file_id'] : (isset($_POST['file_id']) ? (int)$_POST['file_id'] : 0);
$name = $_POST['name'] ?? '';
$downloads = isset($_POST['downloads']) ? (int)$_POST['downloads'] : 0;
$size = isset($_POST['size']) ? (int)$_POST['size'] : 0;
$url = $_POST['url'] ?? '';
$mirror = $_POST['mirror'] ?? '';
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;

if($user_rights['editfiles'] == "Y")
 {
  if($submit == 1)
   {
    $safe_file_id = $db_handler->sql_escape_int($file_id);
    $release = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT release_id FROM " . $sql_table['files'] . " WHERE file_id=" . $safe_file_id));
    $release_id = (int)$release['release_id'];

    $safe_name = $db_handler->sql_escape_string($name);
    $safe_downloads = $db_handler->sql_escape_int($downloads);
    $safe_size = $db_handler->sql_escape_int($size);
    $safe_url = $db_handler->sql_escape_string($url);
    $safe_mirror = $db_handler->sql_escape_string($mirror);

    $db_handler->sql_query("UPDATE " . $sql_table['files'] . " SET name='" . $safe_name . "', downloads=" . $safe_downloads . ", size=" . $safe_size . ", url='" . $safe_url . "', mirror='" . $safe_mirror . "' WHERE file_id=" . $safe_file_id);
    echo pdl_admin_alert('success', '<strong>Datei wurde aktualisiert.</strong>');
    echo '<a class="btn btn-primary" href="editrelease.php?release_id=' . (int)$release_id . '">Zurück zum Release</a>';
   }
  else
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'Releases', 'href' => 'or_list.php'],
        ['title' => 'Datei bearbeiten'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Datei bearbeiten</h1>';

    $safe_file_id = $db_handler->sql_escape_int($file_id);
    $getfile = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM " . $sql_table['files'] . " WHERE file_id=" . $safe_file_id));
?>
<form action="editfile.php?submit=1" method="post" novalidate>
    <input type="hidden" name="file_id" value="<?php echo (int)$file_id; ?>">
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Datei-Daten</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlEdFName" class="form-label">Name</label>
                <input type="text" id="pdlEdFName" name="name" class="form-control" required value="<?php echo htmlspecialchars($getfile['name']); ?>">
                <div class="form-text">Wird beim Download-Link angezeigt.</div>
            </div>
            <div class="mb-3">
                <label for="pdlEdFDls" class="form-label">Downloads</label>
                <input type="number" min="0" id="pdlEdFDls" name="downloads" class="form-control" value="<?php echo htmlspecialchars((string)$getfile['downloads']); ?>">
                <div class="form-text">Wie oft die Datei heruntergeladen wurde.</div>
            </div>
            <div class="mb-3">
                <label for="pdlEdFSize" class="form-label">Größe</label>
                <input type="number" min="0" id="pdlEdFSize" name="size" class="form-control" value="<?php echo htmlspecialchars((string)$getfile['size']); ?>">
                <div class="form-text">Dateigröße in Byte.</div>
            </div>
            <div class="mb-3">
                <label for="pdlEdFUrl" class="form-label">URL</label>
                <input type="text" id="pdlEdFUrl" name="url" class="form-control" value="<?php echo htmlspecialchars($getfile['url']); ?>" required>
                <div class="form-text">URL zur Datei.</div>
            </div>
            <div class="mb-3">
                <label for="pdlEdFMirror" class="form-label">Fungiert als Mirror von</label>
                <select id="pdlEdFMirror" name="mirror" class="form-select">
                    <option value="0">Kein Mirror</option>
                    <?php
                    $safe_release_id = $db_handler->sql_escape_int($getfile['release_id']);
                    $safe_file_id_2 = $db_handler->sql_escape_int($file_id);
                    $mirror_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['files'] . " WHERE release_id=" . $safe_release_id . " AND mirror='0' AND file_id!=" . $safe_file_id_2);
                    while($mirror_row = $db_handler->sql_fetch_array($mirror_res))
                     {
                      echo '<option value="' . htmlspecialchars((string)$mirror_row['file_id']) . '"' . pdlif($mirror_row['file_id'] == $getfile['mirror'], ' selected', '') . '>' . htmlspecialchars($mirror_row['name']) . '</option>';
                     }
                    ?>
                </select>
                <div class="form-text">Geben Sie hier die Datei an, dessen Mirror diese Datei darstellen soll.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="editrelease.php?release_id=<?php echo (int)$getfile['release_id']; ?>" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Änderungen speichern</button>
    </div>
</form>
<?php
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
