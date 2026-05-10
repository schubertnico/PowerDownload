<?php
include("header.inc.php");

// Extract POST/GET variables
$rep_id = isset($_REQUEST['rep_id']) ? (int)$_REQUEST['rep_id'] : 0;
$submit = isset($_REQUEST['submit']) ? (int)$_REQUEST['submit'] : 0;

if($user_rights['replacements'] == "Y")
 {
  if($submit == 1)
   {
    $getrep = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM " . $sql_table['replacements'] . " WHERE rep_id=" . $db_handler->sql_escape_int($rep_id)));
    $db_handler->sql_query("DELETE FROM " . $sql_table['replacements'] . " WHERE rep_id=" . $db_handler->sql_escape_int($rep_id));
    if($getrep['type'] == "s" && !preg_match("/http:\/\//siU",$getrep['neu']))
     { unlink("../".$getrep['neu']); }
    if (function_exists('pdl_audit_log')) {
        pdl_audit_log($db_handler, $sql_table, $user_details, 'delete', 'replacement', $rep_id);
    }
    echo pdl_admin_alert('success', '<strong>Ersetzung wurde gelöscht.</strong>');
    echo '<a class="btn btn-outline-light" href="delreplacement.php">Zurück zur Übersicht</a>';
   }
  else
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'Vorlagen und Ersetzungen'],
        ['title' => 'Ersetzungen anzeigen', 'href' => 'showreplacements.php'],
        ['title' => 'Ersetzung löschen'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Ersetzung löschen</h1>';
?>
<section class="card pdl-card mb-4">
    <header class="card-header"><h2 class="h5 mb-0">Zensur</h2></header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead><tr><th scope="col">Wort</th><th scope="col" class="text-end">Aktion</th></tr></thead>
            <tbody>
            <?php
            $badwords_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['replacements'] . " WHERE type='b' ORDER BY old ASC");
            $bw_count = 0;
            while($badwords_row = $db_handler->sql_fetch_array($badwords_res))
             {
                $bw_count++;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($badwords_row['old']); ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-danger" href="delreplacement.php?submit=1&amp;rep_id=<?php echo (int)$badwords_row['rep_id']; ?>"
                            onclick="return confirm('Wirklich löschen?');">Löschen</a>
                    </td>
                </tr>
            <?php } if ($bw_count == 0) { ?>
                <tr><td colspan="2" class="text-muted text-center">Keine Einträge.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card pdl-card mb-4">
    <header class="card-header"><h2 class="h5 mb-0">Smilies</h2></header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead><tr><th scope="col">Smilie-Code</th><th scope="col">Smilie-Bild</th><th scope="col" class="text-end">Aktion</th></tr></thead>
            <tbody>
            <?php
            $smilies_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['replacements'] . " WHERE type='s' ORDER BY LENGTH(old) DESC");
            $sm_count = 0;
            while($smilies_row = $db_handler->sql_fetch_array($smilies_res))
             {
                $sm_count++;
            ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($smilies_row['old']); ?></code></td>
                    <td>
                        <?php
                        if(preg_match("/http:\/\//siU",$smilies_row['neu']))
                         { echo '<img src="' . htmlspecialchars($smilies_row['neu']) . '" alt="">'; }
                        else
                         { echo '<img src="../' . htmlspecialchars($smilies_row['neu']) . '" alt="">'; }
                        ?>
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-danger" href="delreplacement.php?submit=1&amp;rep_id=<?php echo (int)$smilies_row['rep_id']; ?>"
                            onclick="return confirm('Wirklich löschen?');">Löschen</a>
                    </td>
                </tr>
            <?php } if ($sm_count == 0) { ?>
                <tr><td colspan="3" class="text-muted text-center">Keine Einträge.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card pdl-card mb-4">
    <header class="card-header"><h2 class="h5 mb-0">Glossar</h2></header>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead><tr><th scope="col">Vorher</th><th scope="col">Nachher</th><th scope="col" class="text-end">Aktion</th></tr></thead>
            <tbody>
            <?php
            $glossary_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['replacements'] . " WHERE type='g' ORDER BY LENGTH(old) DESC");
            $gl_count = 0;
            while($glossary_row = $db_handler->sql_fetch_array($glossary_res))
             {
                $gl_count++;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($glossary_row['old']); ?></td>
                    <td><?php echo htmlspecialchars($glossary_row['neu']); ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-danger" href="delreplacement.php?submit=1&amp;rep_id=<?php echo (int)$glossary_row['rep_id']; ?>"
                            onclick="return confirm('Wirklich löschen?');">Löschen</a>
                    </td>
                </tr>
            <?php } if ($gl_count == 0) { ?>
                <tr><td colspan="3" class="text-muted text-center">Keine Einträge.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</section>
<?php
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
