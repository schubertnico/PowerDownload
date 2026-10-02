<?php
include("header.inc.php");

$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$upload_to = isset($_GET['upload_to']) && is_string($_GET['upload_to']) ? $_GET['upload_to'] : '';
$release_id = isset($_GET['release_id']) ? (int)$_GET['release_id'] : 0;
$csrf_token_post = (string) ($_POST['csrf_token'] ?? '');

// Hochgeladene Datei
$upload = isset($_FILES['upload']['tmp_name']) ? (string) $_FILES['upload']['tmp_name'] : '';
$upload_name = isset($_FILES['upload']['name']) ? pdl_sanitize_upload_filename((string) $_FILES['upload']['name']) : '';

if (!pdl_admin_require_right('addfiles', 'editfiles')) {
    include("footer.inc.php");
    return;
}

$browser_href = 'ftp_browser.php?chdir=' . urlencode($upload_to) . '&release_id=' . (int) $release_id;
pdl_admin_breadcrumb([
    ['title' => 'Adminbereich', 'href' => 'index.php'],
    ['title' => 'FTP-Browser', 'href' => $browser_href],
    ['title' => 'Datei hochladen'],
]);

if (($settings['ftp_on'] ?? '') == "Y" && function_exists("ftp_connect"))
 {
  set_time_limit(300);
  $ftp_handler = @ftp_connect((string) $settings['ftp_server']);
  if ($ftp_handler === false || !@ftp_login($ftp_handler, (string) $settings['ftp_user'], (string) $settings['ftp_passwort'])) {
      echo pdl_admin_alert('danger', 'Die Anmeldung am FTP-Server ist fehlgeschlagen. Bitte überprüfen Sie Server, Benutzername und Passwort unter Einstellungen → FTP.');
  } else {
    if ($submit == 1) {
      echo '<h1 class="h3 pdl-page-title">Datei hochladen</h1>';
      if (!csrf_verify($csrf_token_post)) {
        echo pdl_admin_alert('danger', 'Sicherheits-Token ungültig oder abgelaufen. Bitte wählen Sie die Datei erneut aus und senden Sie das Formular noch einmal ab.');
      } elseif (!is_uploaded_file($upload)) {
        echo pdl_admin_alert('warning', 'Bitte wählen Sie eine Datei aus.');
      } elseif (($blocked = pdl_validate_file_upload($_FILES['upload'] ?? [], PHP_INT_MAX)) !== null) {
        echo pdl_admin_alert('warning', htmlspecialchars($blocked));
      } elseif (ftp_size($ftp_handler, $upload_to . $upload_name) != -1) {
        echo pdl_admin_alert('warning', 'Im Zielordner gibt es bereits eine Datei mit diesem Namen. Bitte benennen Sie die Datei um.');
      } elseif (!@ftp_put($ftp_handler, $upload_to . $upload_name, $upload, FTP_BINARY)) {
        echo pdl_admin_alert('danger', 'Die Datei konnte nicht auf den FTP-Server übertragen werden. Bitte prüfen Sie die Schreibrechte im Zielordner.');
      } else {
        $file_url = (string) ($settings['ftp_server_url'] ?? '') . $upload_to . $upload_name;
        $actions = [['label' => 'Zurück zum FTP-Browser', 'href' => $browser_href, 'id' => 'pdlNextFtpBrowser']];
        if ($release_id > 0) {
            array_unshift($actions, [
                'label' => 'Zum Release hinzufügen',
                'href' => 'addfile.php?release_id=' . $release_id . '&url=' . urlencode($file_url) . '&size=' . (int) filesize($upload),
                'id' => 'pdlNextAddFile',
                'primary' => true,
            ]);
        }
        echo pdl_admin_result('success', '<strong>Die Datei „' . htmlspecialchars($upload_name, ENT_QUOTES, 'UTF-8') . '“ wurde hochgeladen.</strong>', $actions);
      }
    } else {
      $max = pdl_ini_size_in_bytes((string) ini_get("upload_max_filesize"));
      echo '<h1 class="h3 pdl-page-title">Datei hochladen</h1>';
      echo '<p class="text-muted">Zielordner: <code>' . htmlspecialchars((string) ($settings['ftp_server_url'] ?? '') . $upload_to) . '</code></p>';
?>
<form enctype="multipart/form-data" action="ftp_upload.php?upload_to=<?php echo htmlspecialchars(urlencode($upload_to)); ?>&amp;release_id=<?php echo (int)$release_id; ?>&amp;submit=1" method="post" novalidate>
    <?php echo csrf_input(); ?>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Datei</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlFtpUpload" class="form-label">Datei</label>
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo (int) $max; ?>">
                <input type="file" id="pdlFtpUpload" name="upload" class="form-control" required>
                <div class="form-text">Wählen Sie die Datei aus. Sie wird zuerst auf diesen Webserver und dann per FTP übertragen; höchstens <strong><?php echo size($max); ?></strong>.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="<?php echo htmlspecialchars($browser_href); ?>" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Hochladen</button>
    </div>
</form>
<?php
    }
  }
  if ($ftp_handler !== false) {
      ftp_quit($ftp_handler);
  }
 } else {
  echo pdl_admin_alert('warning', 'Der Server unterstützt keine FTP-Funktionen oder der FTP-Browser ist ausgeschaltet (Einstellungen → FTP).');
 }
include("footer.inc.php");
