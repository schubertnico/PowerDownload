<?php
include("header.inc.php");

$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : (isset($_POST['submit']) ? (int)$_POST['submit'] : 0);
$backup = isset($_FILES['backup']['tmp_name']) ? $_FILES['backup']['tmp_name'] : '';

if($user_rights['backup'] == "Y")
 {
  if($submit == 1)
   {
    if(is_uploaded_file($backup))
     {
      $lines = file($backup);
      if ($lines !== false)
       {
        $query = implode("", $lines);
        $querys = array();
        set_time_limit(300);
        split_query($querys,$query);
        for($i = 0; $i < count($querys); $i++)
         {
          $db_handler->sql_query($querys[$i]);
         }
        echo pdl_admin_alert('success', '<strong>Backup wurde erfolgreich eingespielt.</strong>');
       }
      else
       { echo pdl_admin_alert('danger', 'Fehler beim Lesen der Datei.'); }
     }
    else
     { echo pdl_admin_alert('warning', 'Bitte eine Datei auswählen.'); }
   }
  else
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'System'],
        ['title' => 'Backup ausführen'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Backup ausführen</h1>';
?>
<div class="alert alert-warning">
    <strong>Vorsicht:</strong> Fuehren Sie ein Backup nur aus, wenn es absolut notwendig ist. Je nachdem wie alt das Backup ist, gehen Daten verloren.
</div>
<form action="dobackup.php?submit=1" method="post" enctype="multipart/form-data" novalidate>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Backup einspielen</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlBackupFile" class="form-label">Backup-Datei</label>
                <input type="file" id="pdlBackupFile" name="backup" class="form-control" accept=".sql" required>
                <div class="form-text">Die <code>.sql</code>-Datei, die beim Backup erstellt wurde.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="index.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-danger">Backup einspielen</button>
    </div>
</form>
<?php
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
