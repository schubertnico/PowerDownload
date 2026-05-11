<?php
include("header.inc.php");

// Extract POST/GET variables
$screen_id = isset($_REQUEST['screen_id']) ? (int)$_REQUEST['screen_id'] : 0;
$submit = isset($_REQUEST['submit']) ? (int)$_REQUEST['submit'] : 0;

if($user_rights['editfiles'] == "Y")
 {
  if($submit == 1)
   {
    $release_data = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM " . $sql_table['screens'] . " WHERE screen_id=" . $db_handler->sql_escape_int($screen_id)));
    $db_handler->sql_query("DELETE FROM " . $sql_table['screens'] . " WHERE screen_id=" . $db_handler->sql_escape_int($screen_id));
    unlink("../pdl-gfx/screens/release".$release_data['release_id']."screen".$screen_id."g.jpg");
    unlink("../pdl-gfx/screens/release".$release_data['release_id']."screen".$screen_id."k.jpg");
    echo pdl_admin_alert('success', '<strong>Screen wurde gelöscht.</strong>');
   }
  else
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'Releases', 'href' => 'or_list.php'],
        ['title' => 'Screen löschen'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Screen löschen</h1>';
    echo makedialog(
        "Screen wirklich löschen?",
        '<input type="hidden" name="screen_id" value="' . (int)$screen_id . '">'
        . '<p class="mb-0">Wollen Sie den Screen wirklich löschen? Dabei werden der Datenbankeintrag und die hochgeladenen Screenshots gelöscht.</p>',
        "Ja, jetzt löschen",
        "delscreen.php"
    );
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
