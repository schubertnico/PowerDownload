<?php
include("header.inc.php");

// Extract POST/GET variables
$file_id = isset($_REQUEST['file_id']) ? (int)$_REQUEST['file_id'] : 0;
$submit = isset($_REQUEST['submit']) ? (int)$_REQUEST['submit'] : 0;

if($user_rights['editfiles'] == "Y")
 {
  if($submit == 1)
   {
    $release = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT release_id FROM " . $sql_table['files'] . " WHERE file_id=" . $db_handler->sql_escape_int($file_id)));
    $release_id = $release['release_id'];
    $db_handler->sql_query("DELETE FROM " . $sql_table['files'] . " WHERE file_id=" . $db_handler->sql_escape_int($file_id));
    $db_handler->sql_query("DELETE FROM " . $sql_table['files'] . " WHERE mirror=" . $db_handler->sql_escape_int($file_id));
    echo pdl_admin_alert('success', '<strong>Datei wurde gelöscht.</strong>');
    echo '<a class="btn btn-primary" href="editrelease.php?release_id=' . (int)$release_id . '">Zurück zum Release</a>';
   }
  else
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'Releases', 'href' => 'or_list.php'],
        ['title' => 'Datei löschen'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Datei löschen</h1>';
    echo makedialog(
        "Datei wirklich löschen?",
        '<input type="hidden" name="file_id" value="' . (int)$file_id . '">'
        . '<p class="mb-2">Wenn Sie die Datei löschen, werden auch <strong>alle dazugehörigen Mirrors</strong> entfernt.</p>'
        . '<p class="mb-0">Wollen Sie die Datei wirklich löschen?</p>',
        "Ja, jetzt löschen",
        "delfile.php"
    );
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
