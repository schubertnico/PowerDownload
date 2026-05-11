<?php
include("header.inc.php");

$submit = $_GET['submit'] ?? '';
$name = $_POST['name'] ?? '';
$bez = $_POST['bez'] ?? '';
$variablenname = $_POST['variablenname'] ?? '';
$standard = $_POST['standard'] ?? '';

if($user_rights['adminaccess'] == "Y")
 {
  if($submit == 1)
   {
    $name_escaped = $db_handler->sql_escape_string($name);
    $bez_escaped = $db_handler->sql_escape_string($bez);
    $variablenname_escaped = $db_handler->sql_escape_string($variablenname);
    $standard_escaped = $db_handler->sql_escape_string($standard);
    $db_handler->sql_query("INSERT INTO " . $sql_table['rights'] . " VALUES ('','" . $name_escaped . "','" . $bez_escaped . "','" . $variablenname_escaped . "','')");
    $db_handler->sql_query("ALTER TABLE " . $sql_table['usergroup'] . " ADD " . $variablenname_escaped . " ENUM('Y', 'N') DEFAULT '" . $standard_escaped . "' NOT NULL");
    $db_handler->sql_query("UPDATE " . $sql_table['usergroup'] . " SET " . $variablenname_escaped . "='Y' WHERE ugroup_id='1'");
    echo pdl_admin_alert('success', '<strong>Userrecht wurde eingetragen.</strong>');
    echo '<a class="btn btn-outline-light" href="adduright.php">Weiteres Userrecht hinzufügen</a>';
   }
  else
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'System-Erweiterungen'],
        ['title' => 'Userrecht hinzufügen'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Userrecht hinzufügen</h1>';
?>
<form action="adduright.php?submit=1" method="post" novalidate>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Userrecht-Daten</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlURName" class="form-label">Name</label>
                <input type="text" id="pdlURName" name="name" class="form-control" required>
                <div class="form-text">Name des Rechtes.</div>
            </div>
            <div class="mb-3">
                <label for="pdlURBez" class="form-label">Beschreibung</label>
                <input type="text" id="pdlURBez" name="bez" class="form-control">
                <div class="form-text">Nur eine kurze Beschreibung.</div>
            </div>
            <div class="mb-3">
                <label for="pdlURVar" class="form-label">Variablenname</label>
                <input type="text" id="pdlURVar" name="variablenname" class="form-control" required>
                <div class="form-text">Wird dann als <code>$user_rights[variablenname]</code> im System verfügbar sein.</div>
            </div>
            <div class="mb-3">
                <label for="pdlURStandard" class="form-label">Standardwert</label>
                <select id="pdlURStandard" name="standard" class="form-select">
                    <option value="Y">Ja</option>
                    <option value="N">Nein</option>
                </select>
                <div class="form-text">Jede bestehende Usergruppe (ausser der geschützten Administrator-Gruppe) bekommt diesen Wert. Die Administrator-Gruppe bekommt immer „Ja".</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="index.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Userrecht hinzufügen</button>
    </div>
</form>
<?php
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
