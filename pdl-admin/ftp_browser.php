<?php
include("header.inc.php");

$chdir = isset($_GET['chdir']) ? $_GET['chdir'] : '';
$cdup = isset($_GET['cdup']) ? (int)$_GET['cdup'] : 0;
$release_id = isset($_GET['release_id']) ? (int)$_GET['release_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 0;

if($user_rights['adminaccess'] == "Y")
 {
  if($settings['ftp_on'] == "Y" && function_exists("ftp_connect"))
   {
    set_time_limit(300);
    $ftp_handler = ftp_connect($settings['ftp_server']);
    if(!ftp_login($ftp_handler,$settings['ftp_user'],$settings['ftp_passwort'])) {
        echo pdl_admin_alert('danger', 'Login fehlgeschlagen. Bitte ueberprüfen Sie die Login-Daten.');
    } else {
      if($chdir) ftp_chdir($ftp_handler,$chdir);
      if($cdup) ftp_cdup($ftp_handler);
      $ftp_ordner = ftp_pwd($ftp_handler);
      if(substr($ftp_ordner,strlen($ftp_ordner)-1,strlen($ftp_ordner)) != "/") $ftp_ordner .= "/";
      $rawlist = ftp_rawlist($ftp_handler,$ftp_ordner);
      $ordner = array();
      $dateien = array();
      for($i = 0; $i < count($rawlist); $i++) {
        preg_match("!([-drwx]+)\s+([0-9]+)\s+([a-zA-Z0-9]+)\s+([a-zA-Z0-9]+)\s+([0-9]+)\s+([a-zA-Z]+)\s+([0-9]+)\s+([0-9:]+)\s+(.+)!", $rawlist[$i], $daten);
        if(substr($daten[1],0,1) == "d" && ($daten[9] != "." && $daten[9] != "..")) $ordner[] = $daten;
        elseif($daten[9] != "." && $daten[9] != "..") $dateien[] = $daten;
      }
      function sortnachname($a,$b) { return strnatcasecmp($a[9], $b[9]); }
      if(count($dateien) > 1) usort($dateien, "sortnachname");
      if(count($ordner) > 1) usort($ordner, "sortnachname");

      pdl_admin_breadcrumb([
          ['title' => 'Admin-Center', 'href' => 'index.php'],
          ['title' => 'FTP-Browser'],
      ]);
      echo '<h1 class="h3 pdl-page-title">FTP-Browser</h1>';
?>
<section class="card pdl-card mb-4">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <span class="text-muted small">Aktueller Pfad:</span>
            <code><?php echo htmlspecialchars($settings['ftp_server_url'].$ftp_ordner); ?></code>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-sm btn-primary" href="ftp_upload.php?upload_to=<?php echo urlencode($ftp_ordner); ?>&amp;release_id=<?php echo (int)$release_id; ?>">Datei uploaden</a>
            <?php if($ftp_ordner != "/") { ?>
            <a class="btn btn-sm btn-outline-light" href="ftp_browser.php?cdup=1&amp;release_id=<?php echo (int)$release_id; ?>">Zum Unterordner (eine Ebene hoch)</a>
            <?php } ?>
        </div>
    </div>
</section>

<section class="card pdl-card mb-4">
    <header class="card-header"><h2 class="h5 mb-0">Ordner</h2></header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead><tr><th scope="col">Name</th><th scope="col" class="text-end">Optionen</th></tr></thead>
            <tbody>
            <?php
            if(count($ordner) == 0) { ?>
                <tr><td colspan="2" class="text-muted text-center">Keine Ordner vorhanden.</td></tr>
            <?php } else {
                for($i = 0; $i < count($ordner); $i++) { ?>
                <tr>
                    <td><a href="ftp_browser.php?chdir=<?php echo urlencode($ftp_ordner.$ordner[$i][9]."/"); ?>&amp;release_id=<?php echo (int)$release_id; ?>"><?php echo htmlspecialchars($ordner[$i][9]); ?></a></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-light" href="ftp_upload.php?upload_to=<?php echo urlencode($ftp_ordner.$ordner[$i][9]."/"); ?>&amp;release_id=<?php echo (int)$release_id; ?>">Datei uploaden</a></td>
                </tr>
                <?php }
            } ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card pdl-card mb-4">
    <header class="card-header"><h2 class="h5 mb-0">Dateien</h2></header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead><tr><th scope="col">Name</th><th scope="col" class="text-end">Größe</th><th scope="col" class="text-end">Optionen</th></tr></thead>
            <tbody>
            <?php
            $total_size = 0;
            if(count($dateien) == 0) { ?>
                <tr><td colspan="3" class="text-muted text-center">Keine Dateien vorhanden.</td></tr>
            <?php } else {
                if(!$page) $page = 1;
                $start = $page * 25 - 25;
                if($start + 25 > count($dateien)) $ende = count($dateien);
                else $ende = $start + 25;

                for($i = $start; $i < $ende; $i++) {
                    $total_size += $dateien[$i][5];
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($dateien[$i][9]); ?></td>
                    <td class="text-end"><?php echo htmlspecialchars(size($dateien[$i][5])); ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-primary" href="addfile.php?url=<?php echo urlencode($settings['ftp_server_url'].$ftp_ordner.$dateien[$i][9]); ?>&amp;release_id=<?php echo (int)$release_id; ?>&amp;size=<?php echo (int)$dateien[$i][5]; ?>">Zum Release hinzufügen</a>
                    </td>
                </tr>
            <?php } } ?>
            </tbody>
            <?php if(count($dateien) > 0) { ?>
            <tfoot class="table-group-divider">
                <tr class="fw-bold">
                    <td><strong>Total:</strong> <?php echo (int)count($dateien); ?> Files</td>
                    <td class="text-end"><?php echo htmlspecialchars(size($total_size)); ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php } ?>
        </table>
    </div>
    <?php if(count($dateien) > 25) { ?>
    <div class="card-footer text-center">
        <?php echo seiten(count($dateien),25,"","ftp_browser.php?chdir=".urlencode($ftp_ordner)."&amp;release_id=".(int)$release_id."&amp;"); ?>
    </div>
    <?php } ?>
</section>
<?php
    }
    ftp_quit($ftp_handler);
   } else {
    if (!function_exists('ftp_connect')) {
        echo pdl_admin_alert('warning', 'Der PHP-FTP-Support ist auf diesem Server nicht aktiv. Bitte die Erweiterung <code>ext-ftp</code> installieren oder freischalten.');
    } elseif (($settings['ftp_on'] ?? 'N') !== 'Y') {
        echo pdl_admin_alert('warning', 'Der FTP-Browser wurde in den Settings deaktiviert. Aktiviere ihn unter Settings → FTP.');
    } else {
        echo pdl_admin_alert('warning', 'Der Server unterstützt keine FTP-Funktionen oder ein Admin hat den FTP-Browser ausgeschaltet.');
    }
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
