<?php
include("pdl-inc/pdl_header.inc.php");
include("pdl-admin/functions.inc.php");
$mysqlversion = $db_handler->sql_fetch_array($db_handler->sql_query("SHOW VARIABLES LIKE 'version'"));
$settings['mysqlversion'] = intval(str_replace(".","",$mysqlversion[1]));

//checkt die GD Version. Hoffe das geht auch irgendwie schneller...
ob_start();
phpinfo(8);
$phpinfo=ob_get_contents();
ob_end_clean();
$phpinfo=strip_tags($phpinfo);
$phpinfo=stristr($phpinfo,"gd version");
$phpinfo=stristr($phpinfo,"version");
$end=strpos($phpinfo," ");
$phpinfo=substr($phpinfo,0,$end);
$phpinfo=substr($phpinfo,7);
$settings['gdversion'] = intval($phpinfo);

$install = 1;

$step = isset($_GET['step']) ? (int)$_GET['step'] : 0;
$PHP_SELF = $_SERVER['PHP_SELF'] ?? '';

// Variablen aus dem alten Form
$admins = $_POST['admins'] ?? 'pdl_admins';
$comments = $_POST['comments'] ?? 'pdl_comments';
$dirs = $_POST['dirs'] ?? 'pdl_dirs';
$files = $_POST['files'] ?? 'pdl_files';
$mirrors = $_POST['mirrors'] ?? 'pdl_mirrors';
?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PowerDownload <?php echo htmlspecialchars($settings['pdlversion']); ?> - Update</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="pdl-admin/admin.css" rel="stylesheet">
</head>
<body class="pdl-admin">
<nav class="navbar navbar-expand-lg pdl-admin-navbar">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold">PowerDownload <?php echo htmlspecialchars($settings['pdlversion']); ?> - Update von 2.2.4</span>
    </div>
</nav>
<main class="container py-4">
<?php
if($step == 0)
 {
?>
<h1 class="h3 pdl-page-title">Installations-Voraussetzungen</h1>
<form action="<?php echo htmlspecialchars($PHP_SELF); ?>?step=1" method="post">
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Prüfung</h2></header>
        <div class="table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead>
                    <tr>
                        <th scope="col">Eigenschaft</th>
                        <th scope="col">Erforderlich</th>
                        <th scope="col">Vorhanden</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>PHP-Version</td>
                        <td>4.1.0</td>
                        <td><span class="badge text-bg-<?php echo pdlif($settings['phpversion'] >= 410, 'success', 'danger'); ?>"><?php echo htmlspecialchars(phpversion()); ?></span></td>
                    </tr>
                    <tr>
                        <td>MySQL-Version</td>
                        <td>3.23.20</td>
                        <td><span class="badge text-bg-<?php echo pdlif($settings['mysqlversion'] >= 32320, 'success', 'danger'); ?>"><?php echo htmlspecialchars($mysqlversion[1]); ?></span></td>
                    </tr>
                    <tr>
                        <td>GD-Lib-Version</td>
                        <td>2 - optional</td>
                        <td>
                            <?php if($settings['gdversion'] == 2) echo '<span class="badge text-bg-success">2.x</span>';
                            elseif($settings['gdversion'] == 1) echo '<span class="badge text-bg-warning text-dark">1.x</span>';
                            else echo '<span class="badge text-bg-danger">nicht installiert</span>'; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>FTP-Funktionen</td>
                        <td>aktiviert - optional</td>
                        <td>
                            <?php $ftp = function_exists("ftp_connect") ? 1 : 0; ?>
                            <span class="badge text-bg-<?php echo pdlif($ftp == 1,'success','danger'); ?>"><?php echo pdlif($ftp == 1,"aktiviert","deaktiviert"); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td>upload_max_filesize</td>
                        <td>&gt; 0</td>
                        <td><span class="badge text-bg-<?php echo pdlif(get_cfg_var("upload_max_filesize") > 0,'success','danger'); ?>"><?php echo htmlspecialchars(get_cfg_var("upload_max_filesize")); ?></span></td>
                    </tr>
                    <tr>
                        <td>Schreibrechte in <code>pdl-gfx/screens/</code></td>
                        <td>Ja</td>
                        <td><span class="badge text-bg-<?php echo pdlif(is_writable("pdl-gfx/screens"),'success','danger'); ?>"><?php echo pdlif(is_writable("pdl-gfx/screens"),"Ja","Nein"); ?></span></td>
                    </tr>
                    <tr>
                        <td>Schreibrechte in <code>pdl-gfx/smilies/</code></td>
                        <td>Ja</td>
                        <td><span class="badge text-bg-<?php echo pdlif(is_writable("pdl-gfx/smilies"),'success','danger'); ?>"><?php echo pdlif(is_writable("pdl-gfx/smilies"),"Ja","Nein"); ?></span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
    <div class="d-grid d-md-flex justify-content-md-end">
        <button type="submit" class="btn btn-primary">Update starten</button>
    </div>
</form>
<?php
 }
if($step == 1)
 {
  set_time_limit(300);

  include("install_querys.inc");
  $querys = array();
  split_query($querys,$install_querys);
  for($i = 0; $i < count($querys); $i++) {
    $db_handler->sql_query($querys[$i]);
  }
  echo '<div class="alert alert-success" role="alert"><strong>Tabellen und Standardkonfiguration erzeugt.</strong></div>';
?>
<h1 class="h3 pdl-page-title">Alte Tabellennamen</h1>
<form action="<?php echo htmlspecialchars($PHP_SELF); ?>?step=2" method="post" novalidate>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Hinweise</h2></header>
        <div class="card-body small">
            <p>Geben Sie hier die Tabellennamen von PDL 2.2.4 an. Sollten Sie die nicht geändert haben, lassen Sie die Tabellennamen einfach so.</p>
            <p>Mit dem nächsten Schritt werden alle Daten von PowerDownload umkonvertiert. Dies kann u.U. sehr lange dauern, je nachdem wie viele Downloads Sie in der Datenbank haben. Die alte Datenbank bleibt auf jeden Fall erhalten, falls dieses Update fehlschlaegt.</p>
            <p class="mb-0"><strong>Was passiert:</strong></p>
            <ul class="mb-0">
                <li>Alle User werden umgewandelt. Die Einteilung in Usergruppen wird automatisch vorgenommen. Bitte nach dem Update die Rechte prüfen.</li>
                <li>Kommentare werden 1:1 umkonvertiert.</li>
                <li>Ordner werden 1:1 umkonvertiert.</li>
                <li>Releases werden 1:1 umkonvertiert.</li>
                <li>Name der Hauptdatei bzw. der Mirrors wird nach dem Dateinamen vergeben.</li>
                <li>Alte Settings werden, falls möglich, uebernommen.</li>
                <li>Screenshots werden NICHT uebernommen.</li>
            </ul>
        </div>
    </section>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Tabellennamen</h2></header>
        <div class="card-body">
            <div class="row g-3">
                <?php foreach (['admins'=>'Admins','comments'=>'Comments','dirs'=>'Dirs','files'=>'Files','mirrors'=>'Mirrors','settings'=>'Settings'] as $key => $label) { ?>
                <div class="col-12 col-md-6">
                    <label for="pdlUp_<?php echo $key; ?>" class="form-label"><?php echo htmlspecialchars($label); ?></label>
                    <input type="text" id="pdlUp_<?php echo $key; ?>" name="<?php echo $key; ?>" class="form-control" value="pdl_<?php echo $key; ?>">
                </div>
                <?php } ?>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex justify-content-md-end">
        <button type="submit" class="btn btn-primary">Weiter zum nächsten Schritt</button>
    </div>
</form>
<?php
 }
if($step == 2)
 {
  set_time_limit(300);

  // user convertieren
  $oldusers_res = $db_handler->sql_query("SELECT * FROM $admins");
  while($oldusers_row = $db_handler->sql_fetch_array($oldusers_res))
   {
    if($oldusers_row['access_admin'] == "Y")
     {
      if($oldusers_row['extra_recht'] == "Y") $ugroup_id = 1;
      elseif($oldusers_row['adddirs'] == "Y" && $oldusers_row['editdirs'] == "Y") $ugroup_id = 5;
      else $ugroup_id = 4;
     }
    else
     { $ugroup_id = 2; }

    // ICQ-Spalte (frueher Index 5) wird seit 2026-05 nicht mehr gepflegt -> Wert wird verworfen.
    $db_handler->sql_query("INSERT INTO " . $sql_table['user'] . " (user_id, nick, email, passwort, homepage, get_letter, signatur, ugroup_id, remind_code, lastactive) VALUES ('" . (int)$oldusers_row['id'] . "','" . $db_handler->sql_escape_string($oldusers_row['nick']) . "','" . $db_handler->sql_escape_string($oldusers_row['mail']) . "','" . md5(base64_decode($oldusers_row['pw'])) . "','" . $db_handler->sql_escape_string($oldusers_row['url']) . "','Y','','$ugroup_id','','" . time() . "')");
   }

  // ordner,files,mirrors und screens convertieren
  $ordner_res = $db_handler->sql_query("SELECT * FROM $dirs");
  while($ordner_row = $db_handler->sql_fetch_array($ordner_res))
   {
    $db_handler->sql_query("INSERT INTO $sql_table[ordner] VALUES ('$ordner_row[id]','$ordner_row[ordner_id]','".addslashes($ordner_row['name'])."','".addslashes($ordner_row['text'])."')");
   }

  // release convertieren
  $release_res = $db_handler->sql_query("SELECT * FROM $files");
  while($release_row = $db_handler->sql_fetch_array($release_res))
   {
    // autor_icq wurde 2026-05 entfernt -> wird beim Import nicht mehr uebernommen.
    $db_handler->sql_query("INSERT INTO " . $sql_table['release'] . " (release_id, name, text, time, views, ordner_id, uploader, autor, autor_nick, autor_email, autor_homepage, released, votes, voted) VALUES ('" . (int)$release_row['id'] . "','" . addslashes($release_row['name']) . "','" . addslashes($release_row['text']) . "','" . (int)$release_row['timestamp'] . "','" . (int)$release_row['views'] . "','" . (int)$release_row['ordner_id'] . "','" . (int)$release_row['uploader'] . "','" . (int)$release_row['author'] . "','" . addslashes($release_row['author_nick']) . "','" . addslashes($release_row['author_mail']) . "','" . addslashes($release_row['author_url']) . "','" . $db_handler->sql_escape_string($release_row['released']) . "','" . (int)$release_row['votes'] . "','" . (int)$release_row['vote'] . "')");
    if($release_row['loads'] > $release_row['views']) $release_row['loads'] = $release_row['views'];
    $dateiname = basename($release_row['url']);
    $size = $release_row['size']*1024;
    $db_handler->sql_query("INSERT INTO $sql_table[files] VALUES ('','$release_row[id]','$release_row[loads]','$release_row[url]','$size','$dateiname','')");
    $file_id = $db_handler->sql_insert_id();
    $mirror_res = $db_handler->sql_query("SELECT * FROM $mirrors WHERE file_id='$release_row[id]'");
    while($mirror_row = $db_handler->sql_fetch_array($mirror_res))
     {
      $db_handler->sql_query("INSERT INTO $sql_table[files] VALUES ('','$release_row[id]','0','$mirror_row[url]','','$dateiname - Mirror by $mirror_row[name]','$file_id')");
     }
   }

  // settings convertieren
  $oldsettings_res = $db_handler->sql_query("SELECT * FROM $_POST[settings]");
  while($oldsettings_row = $db_handler->sql_fetch_array($oldsettings_res))
   {
    $oldsettings[$oldsettings_row['name']] = $oldsettings_row['value'];
   }

  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[script_file]' WHERE variablenname='script_file'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[time_config]' WHERE variablenname='date_format'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[ftp_server]' WHERE variablenname='ftp_server'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[ftp_user]' WHERE variablenname='ftp_user'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[ftp_passwort]' WHERE variablenname='ftp_passwort'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[ftp_server_url]' WHERE variablenname='ftp_server_url'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[top_count]' WHERE variablenname='top_count'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[show_comments]' WHERE variablenname='enable_comments'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[show_search]' WHERE variablenname='enable_search'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[show_treeview]' WHERE variablenname='enable_treeview'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[show_extern_admin]' WHERE variablenname='enable_extrernadmin'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[allowed_referer]' WHERE variablenname='allowed_referer'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[referer_check]' WHERE variablenname='referer_check'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[trennstring]' WHERE variablenname='trenn_string'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[chars_count]' WHERE variablenname='trenn_zeichen'");
  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$oldsettings[config_seite]' WHERE variablenname='perpage'");

  $installed = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT timestamp FROM $files ORDER BY timestamp ASC LIMIT 0,1"));

  $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$installed[timestamp]' WHERE variablenname='installed'");

  // kommentare convertieren
  $comments_res = $db_handler->sql_query("SELECT * FROM $comments");
  while($comments_row = $db_handler->sql_fetch_array($comments_res))
   {
    $db_handler->sql_query("INSERT INTO $sql_table[comments] VALUES ('','$comments_row[user]','$comments_row[file_id]','".addslashes($comments_row['titel'])."','".addslashes($comments_row['text'])."','$comments_row[timestamp]')");
   }

  echo '<div class="alert alert-success" role="alert"><strong>Update erfolgreich durchgeführt.</strong> Bitte ueberprüfen Sie alle Admins und erstellen ggf. neue Usergruppen. Prüfen Sie alle Releases mit mehreren Files. Außerdem müssen Sie die alten Screens neu hochladen.</div>';
  echo '<div class="alert alert-warning" role="alert"><strong>Wichtig:</strong> Löschen Sie nun diese Update-Datei.</div>';
  echo '<a class="btn btn-primary" href="pdl-admin/">Weiter zum Admin-Center</a>';
 }

$rendertime2=microtime();
$rendertimetemp=explode(" ",$rendertime2);
$rendertime2=$rendertimetemp[0]+$rendertimetemp[1];
$rendertime=$rendertime2-$rendertime1;
$rendertime=round($rendertime,3);
?>
    <hr>
    <p class="text-center small text-muted">
        Renderzeit: <?php echo htmlspecialchars((string)$rendertime); ?>s &middot;
        <?php echo htmlspecialchars((string)$db_handler->querys); ?> SQL-Anfragen
    </p>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
