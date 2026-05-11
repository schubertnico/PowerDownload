<?php
include("header.inc.php");

$submit = $_GET['submit'] ?? '';
$name = $_POST['name'] ?? '';
$bez = $_POST['bez'] ?? '';
$variablenname = $_POST['variablenname'] ?? '';
$wert = $_POST['wert'] ?? '';
$eingabe = $_POST['eingabe'] ?? '';
$tgroup_id = $_POST['tgroup_id'] ?? '';

if($user_rights['templates'] == "Y")
 {
  if($submit == 1)
   {
    $name_escaped = $db_handler->sql_escape_string($name);
    $bez_escaped = $db_handler->sql_escape_string($bez);
    $variablenname_escaped = $db_handler->sql_escape_string($variablenname);
    $wert_escaped = $db_handler->sql_escape_string($wert);
    $eingabe_escaped = $db_handler->sql_escape_string($eingabe);
    $tgroup_id_escaped = $db_handler->sql_escape_int($tgroup_id);
    $db_handler->sql_query("INSERT INTO " . $sql_table['template'] . " VALUES ('','" . $name_escaped . "','" . $bez_escaped . "','" . $variablenname_escaped . "','" . $wert_escaped . "','" . $eingabe_escaped . "','" . $tgroup_id_escaped . "','')");
    echo pdl_admin_alert('success', '<strong>Template wurde eingetragen.</strong>');
    echo '<a class="btn btn-outline-light" href="addtemplate.php">Weiteres Template hinzufügen</a>';
   }
  else
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'System-Erweiterungen'],
        ['title' => 'Template hinzufügen'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Template hinzufügen</h1>';
?>
<form action="addtemplate.php?submit=1" method="post" novalidate>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Template-Daten</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlTemplName" class="form-label">Name</label>
                <input type="text" id="pdlTemplName" name="name" class="form-control" required aria-describedby="pdlTemplNameHelp">
                <div id="pdlTemplNameHelp" class="form-text">Name des Templates.</div>
            </div>
            <div class="mb-3">
                <label for="pdlTemplBez" class="form-label">Beschreibung</label>
                <textarea id="pdlTemplBez" name="bez" class="form-control" rows="4"></textarea>
            </div>
            <div class="mb-3">
                <label for="pdlTemplVar" class="form-label">Variablenname</label>
                <input type="text" id="pdlTemplVar" name="variablenname" class="form-control" required aria-describedby="pdlTemplVarHelp">
                <div id="pdlTemplVarHelp" class="form-text">Wird dann als <code>$template[variablenname]</code> im System verfügbar.</div>
            </div>
            <div class="mb-3">
                <label for="pdlTemplEingabe" class="form-label">Eingabeart</label>
                <select id="pdlTemplEingabe" name="eingabe" class="form-select">
                    <option value="textarea">Textarea</option>
                    <option value="input">Inputfeld</option>
                    <option value="farbe">Farbauswahl</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="pdlTemplWert" class="form-label">Template</label>
                <textarea id="pdlTemplWert" name="wert" class="form-control" rows="10" aria-describedby="pdlTemplWertHelp"></textarea>
                <div id="pdlTemplWertHelp" class="form-text">Hier das Template eingeben.</div>
            </div>
            <div class="mb-3">
                <label for="pdlTemplGroup" class="form-label">Templategruppe</label>
                <select id="pdlTemplGroup" name="tgroup_id" class="form-select">
<?php
$tgroup_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['templategroup']);
while($tgroup_row = $db_handler->sql_fetch_array($tgroup_res)) {
    echo '<option value="' . htmlspecialchars($tgroup_row['tgroup_id']) . '">' . htmlspecialchars($tgroup_row['name']) . '</option>';
}
?>
                </select>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="index.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Template hinzufügen</button>
    </div>
</form>
<?php
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
