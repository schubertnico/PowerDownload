<?php
include("header.inc.php");

// Extract POST/GET variables
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$name = isset($_POST['name']) ? $db_handler->sql_escape_string($_POST['name']) : '';
$bez = isset($_POST['bez']) ? $db_handler->sql_escape_string($_POST['bez']) : '';
$wert = isset($_POST['wert']) ? $db_handler->sql_escape_string($_POST['wert']) : '';
$eingabe = isset($_POST['eingabe']) ? $db_handler->sql_escape_string($_POST['eingabe']) : '';
$variablenname = isset($_POST['variablenname']) ? $db_handler->sql_escape_string($_POST['variablenname']) : '';
$sgroup_id = isset($_POST['sgroup_id']) ? (int)$_POST['sgroup_id'] : 0;

if($user_rights['settings'] == "Y")
 {
  if($submit == 1)
   {
    $db_handler->sql_query("INSERT INTO `" . $sql_table['settings'] . "` VALUES ('', '$name','$bez','$wert','$eingabe','$variablenname','$sgroup_id','')");
    echo pdl_admin_alert('success', '<strong>Setting wurde eingetragen.</strong>');
    echo '<a class="btn btn-outline-light" href="addsettings.php">Weiteres Setting hinzufügen</a>';
   }
  else
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'System-Erweiterungen'],
        ['title' => 'Setting hinzufügen'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Setting hinzufügen</h1>';
?>
<form action="addsettings.php?submit=1" method="post" novalidate>
    <section class="card pdl-card mb-4">
        <header class="card-header"><h2 class="h5 mb-0">Setting-Daten</h2></header>
        <div class="card-body">
            <div class="mb-3">
                <label for="pdlSetName" class="form-label">Name</label>
                <input type="text" id="pdlSetName" name="name" class="form-control" required>
                <div class="form-text">Name des Settings.</div>
            </div>
            <div class="mb-3">
                <label for="pdlSetBez" class="form-label">Beschreibung</label>
                <textarea id="pdlSetBez" name="bez" class="form-control" rows="4"></textarea>
                <div class="form-text">Beschreibung des Settings.</div>
            </div>
            <div class="mb-3">
                <label for="pdlSetVar" class="form-label">Variablenname</label>
                <input type="text" id="pdlSetVar" name="variablenname" class="form-control" required>
                <div class="form-text">Wird dann als <code>$settings[variablenname]</code> im System verfügbar.</div>
            </div>
            <div class="mb-3">
                <label for="pdlSetEingabe" class="form-label">Eingabeart</label>
                <textarea id="pdlSetEingabe" name="eingabe" class="form-control" rows="4"></textarea>
                <div class="form-text">
                    Vier Optionen sind möglich: <code>input</code> für ein normales Inputfeld,
                    <code>textarea</code> für eine Textarea, <code>anaus</code> für eine boolesche Option
                    (nur Ja/Nein) oder eine eigene HTML-Eingabe (z.B. ein Select mit Auswahlmöglichkeiten).
                    Hinweis: Ein <code>"</code> wird zu <code>\"</code>. Der Code wird wie ein PHP-echo behandelt
                    und kann <code>pdlif(bedingung,wahr,falsch)</code> verwenden.
                </div>
            </div>
            <div class="mb-3">
                <label for="pdlSetWert" class="form-label">Anfangswert</label>
                <textarea id="pdlSetWert" name="wert" class="form-control" rows="4"></textarea>
                <div class="form-text">Startwert des Settings.</div>
            </div>
            <div class="mb-3">
                <label for="pdlSetSGroup" class="form-label">Settingsgruppe</label>
                <select id="pdlSetSGroup" name="sgroup_id" class="form-select">
<?php
$sgroup_res = $db_handler->sql_query("SELECT * FROM `" . $sql_table['settingsgroup'] . "`");
while($sgroup_row = $db_handler->sql_fetch_array($sgroup_res)) {
    echo '<option value="' . htmlspecialchars($sgroup_row['sgroup_id']) . '">' . htmlspecialchars($sgroup_row['name']) . '</option>';
}
?>
                </select>
                <div class="form-text">In welche der Settingsgruppen wird das Setting gelegt?</div>
            </div>
        </div>
    </section>
    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
        <a href="index.php" class="btn btn-outline-light">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Setting hinzufügen</button>
    </div>
</form>
<?php
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
