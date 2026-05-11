<?php
include("header.inc.php");

// Extract POST/GET variables
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$upload_to = isset($_GET['upload_to']) ? $_GET['upload_to'] : '';
$release_id = isset($_GET['release_id']) ? (int)$_GET['release_id'] : 0;

// Handle file upload variables
$upload = isset($_FILES['upload']['tmp_name']) ? $_FILES['upload']['tmp_name'] : '';
$upload_name = isset($_FILES['upload']['name']) ? $_FILES['upload']['name'] : '';

if($user_rights['adminaccess'] == "Y")
 {
  if($settings['ftp_on'] == "Y" && function_exists("ftp_connect"))
   {
    set_time_limit(300);
    $ftp_handler = ftp_connect($settings['ftp_server']);
    if(!ftp_login($ftp_handler,$settings['ftp_user'],$settings['ftp_passwort'])) {
        echo pdl_admin_alert('danger', 'Login fehlgeschlagen. Bitte ueberprüfen Sie die Login-Daten.');
    } else {
      if($submit == 1) {
        if(is_uploaded_file($upload)) {
          if(ftp_size($ftp_handler,$upload_to.$upload_name) != -1) {
            echo pdl_admin_alert('warning', 'Datei mit selbem Namen existiert bereits.');
          } else {
            $upload_result = ftp_put($ftp_handler, $upload_to.$upload_name, $upload, FTP_BINARY);
            echo pdl_admin_alert('success', '<strong>Datei wurde hochgeladen.</strong>');
            echo '<a class="btn btn-outline-light" href="ftp_browser.php?chdir=' . htmlspecialchars(urlencode($upload_to)) . '&amp;release_id=' . (int)$release_id . '">Zurück zum FTP-Browser</a>';
          }
        } else {
          echo pdl_admin_alert('warning', 'Bitte eine Datei auswählen.');
        }
      } else {
        $max = get_cfg_var("upload_max_filesize");
        if(substr($max,strlen($max)-1,strlen($max)) == "M") $max = substr($max,0,strlen($max)-1)*1024*1024;
        elseif(substr($max,strlen($max)-1,strlen($max)) == "K") $max = substr($max,0,strlen($max)-1)*1024;

        pdl_admin_breadcrumb([
            ['title' => 'Admin-Center', 'href' => 'index.php'],
            ['title' => 'FTP-Browser', 'href' => 'ftp_browser.php?release_id=' . (int)$release_id],
            ['title' => 'Datei uploaden'],
        ]);
        echo '<h1 class="h3 pdl-page-title">Upload in den Ordner ' . htmlspecialchars($settings['ftp_server_url'].$upload_to) . '</h1>';
?>
<form enctype="multipart/form-data" action="ftp_upload.php?upload_to=<?php echo htmlspecialchars(urlencode($upload_to)); ?>&amp;release_id=<?php echo (int)$release_id; ?>&amp;submit=1" method="post" novalidate>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Datei hochladen</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlFtpUpload" class="form-label">Datei</label>
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo htmlspecialchars($max); ?>">
                <input type="file" id="pdlFtpUpload" name="upload" class="form-control" required>
                <div class="form-text">Wählen Sie die hochzuladende Datei. Maximale Dateigröße: <strong><?php echo size($max); ?></strong>.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="ftp_browser.php?release_id=<?php echo (int)$release_id; ?>" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Hochladen</button>
    </div>
</form>
<?php
      }
    }
    ftp_quit($ftp_handler);
   } else {
    echo pdl_admin_alert('warning', 'Der Server unterstützt keine FTP-Funktionen oder ein Admin hat den FTP-Browser ausgeschaltet.');
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
