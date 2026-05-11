<?php
include("pdl-inc/pdl_header.inc.php");
include("pdl-admin/functions.inc.php");
$mysqlversion = $db_handler->sql_fetch_array($db_handler->sql_query("SHOW VARIABLES LIKE 'version'"));
$settings['mysqlversion'] = intval(str_replace(".","",$mysqlversion[1]));

//checkt die GD Version. Hoffe das geht auch irgendwie schneller...
check_gd();

$install = 1;

// Extract variables
$step = isset($_GET['step']) ? (int)$_GET['step'] : 0;
$PHP_SELF = $_SERVER['PHP_SELF'] ?? '';
$nick = $_POST['nick'] ?? '';
$pw_neu = $_POST['pw_neu'] ?? '';
$pw_neu2 = $_POST['pw_neu2'] ?? '';
?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PowerDownload <?php echo htmlspecialchars($settings['pdlversion']); ?> - Install</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="pdl-admin/admin.css" rel="stylesheet">
</head>
<body class="pdl-admin">
<nav class="navbar navbar-expand-lg pdl-admin-navbar">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold">PowerDownload <?php echo htmlspecialchars($settings['pdlversion']); ?> - Install</span>
        <div class="d-none d-lg-block ms-auto">
            <small class="text-light opacity-75">&copy; <a href="https://www.powerscripts.org" target="_blank" rel="noopener" class="link-light">https://www.powerscripts.org</a></small>
        </div>
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
                        <td>GD-Lib-Version (Grafik)</td>
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
                            <span class="badge text-bg-<?php echo pdlif($ftp == 1,'success','danger'); ?>">
                                <?php echo pdlif($ftp == 1, 'aktiviert', 'deaktiviert'); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>upload_max_filesize (für Screenshot/Datei-Upload)</td>
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
        <button type="submit" class="btn btn-primary">Installation starten</button>
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
<h1 class="h3 pdl-page-title">Godadmin erstellen</h1>
<form action="<?php echo htmlspecialchars($PHP_SELF); ?>?step=2" method="post" novalidate>
    <section class="card pdl-card mb-4 mx-auto" style="max-width: 540px;">
        <header class="card-header"><h2 class="h5 mb-0">Admin-Daten</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlInstNick" class="form-label">Nickname</label>
                <input type="text" id="pdlInstNick" name="nick" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="pdlInstPw" class="form-label">Passwort</label>
                <input type="password" id="pdlInstPw" name="pw_neu" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="pdlInstPw2" class="form-label">Bestätigung</label>
                <input type="password" id="pdlInstPw2" name="pw_neu2" class="form-control" required>
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">Godadmin erstellen</button>
        </div>
    </section>
</form>
<?php
 }
if($step == 2)
 {
  if(($pw_neu == $pw_neu2) && $pw_neu) {
    $nick_escaped = $db_handler->sql_escape_string($nick);
    $db_handler->sql_query("INSERT INTO " . $sql_table['user'] . " (nick,passwort,ugroup_id, lastactive) VALUES ('" . $nick_escaped . "','" . md5($pw_neu) . "','1','" . time() . "')");
?>
    <div class="alert alert-success" role="alert">
        <h2 class="h5">Installation abgeschlossen!</h2>
        <p>Sie können sich nun ins <a class="alert-link" href="pdl-admin/">Admin-Interface</a> einloggen.</p>
        <p class="mb-0">Dort müssen Sie zuerst die Einstellungen unter <em>Settings</em> ändern und die Templates anpassen.</p>
    </div>
    <div class="alert alert-warning" role="alert">
        <strong>Wichtig:</strong> Löschen Sie diese Installationsdatei nach Abschluss!
    </div>
<?php
  } else {
    echo '<div class="alert alert-danger" role="alert">Passwort stimmt nicht mit Bestätigung überein oder wurde nicht ausgefüllt.</div>';
    echo '<a class="btn btn-outline-light" href="javascript:history.back()">Zurück</a>';
  }
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
