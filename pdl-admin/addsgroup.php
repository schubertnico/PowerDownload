<?php
include("header.inc.php");

$submit = $_GET['submit'] ?? '';
$name = $_POST['name'] ?? '';

if($user_rights['settings'] == "Y")
 {
  if($submit == 1)
   {
    $name_escaped = $db_handler->sql_escape_string($name);
    $db_handler->sql_query("INSERT INTO " . $sql_table['settingsgroup'] . " VALUES ('','" . $name_escaped . "','')");
    echo pdl_admin_alert('success', '<strong>Setting-Gruppe wurde eingetragen.</strong>');
    echo '<a class="btn btn-outline-light" href="addsgroup.php">Weitere Gruppe hinzufügen</a>';
   }
  else
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'System-Erweiterungen'],
        ['title' => 'Setting-Gruppe hinzufügen'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Setting-Gruppe hinzufügen</h1>';
?>
<form action="addsgroup.php?submit=1" method="post" novalidate>
    <section class="card pdl-card mb-4">
        <header class="card-header">
            <h2 class="h5 mb-0">Neue Setting-Gruppe</h2>
        </header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlSGroupName" class="form-label">Name</label>
                <input type="text" id="pdlSGroupName" name="name" class="form-control" required aria-describedby="pdlSGroupNameHelp">
                <div id="pdlSGroupNameHelp" class="form-text">Name der Setting-Gruppe.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="index.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Setting-Gruppe hinzufügen</button>
    </div>
</form>
<?php
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
