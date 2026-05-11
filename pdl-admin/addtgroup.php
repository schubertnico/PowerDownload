<?php
include("header.inc.php");

$submit = $_GET['submit'] ?? '';
$name = $_POST['name'] ?? '';

if($user_rights['templates'] == "Y")
 {
  if($submit == 1)
   {
    $name_escaped = $db_handler->sql_escape_string($name);
    $db_handler->sql_query("INSERT INTO " . $sql_table['templategroup'] . " VALUES ('','" . $name_escaped . "','')");
    echo pdl_admin_alert('success', '<strong>Template-Gruppe wurde eingetragen.</strong>');
    echo '<a class="btn btn-outline-light" href="addtgroup.php">Weitere Template-Gruppe hinzufügen</a>';
   }
  else
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'System-Erweiterungen'],
        ['title' => 'Template-Gruppe hinzufügen'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Template-Gruppe hinzufügen</h1>';
?>
<form action="addtgroup.php?submit=1" method="post" novalidate>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Neue Template-Gruppe</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlTGroupName" class="form-label">Name</label>
                <input type="text" id="pdlTGroupName" name="name" class="form-control" required aria-describedby="pdlTGroupNameHelp">
                <div id="pdlTGroupNameHelp" class="form-text">Name der Template-Gruppe.</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="index.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Template-Gruppe hinzufügen</button>
    </div>
</form>
<?php
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
