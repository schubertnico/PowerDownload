<?php
include("header.inc.php");

$submit = isset($_POST['submit']) ? (int)$_POST['submit'] : (isset($_GET['submit']) ? (int)$_GET['submit'] : 0);

if($user_rights['backup'] == "Y")
 {
  if($submit == 1)
   {
    $db_handler->sql_query("UPDATE " . $sql_table['release'] . " SET views='0', votes='0', voted='0'");
    $db_handler->sql_query("UPDATE " . $sql_table['screens'] . " SET views='0'");
    $db_handler->sql_query("UPDATE " . $sql_table['files'] . " SET downloads='0'");
    $db_handler->sql_query("DELETE FROM " . $sql_table['comments']);
    $db_handler->sql_query("DELETE FROM " . $sql_table['iplock']);
    echo pdl_admin_alert('success', '<strong>Datenbank wurde zurückgesetzt.</strong>');
   }
  else
   {
    pdl_admin_breadcrumb([
        ['title' => 'Admin-Center', 'href' => 'index.php'],
        ['title' => 'System'],
        ['title' => 'Datenbank zurücksetzen'],
    ]);
    echo '<h1 class="h3 pdl-page-title">Datenbank zurücksetzen</h1>';
    echo makedialog(
        "Datenbank wirklich zurücksetzen?",
        '<p class="mb-2">Beim Zurücksetzen geschieht folgendes:</p>'
        . '<ul class="mb-2"><li>Views der Releases werden auf <strong>0</strong> gesetzt</li>'
        . '<li>Downloads der Files werden auf <strong>0</strong> gesetzt</li>'
        . '<li>Alle Kommentare werden <strong>gelöscht</strong></li>'
        . '<li>Views der Screens werden auf <strong>0</strong> gesetzt</li>'
        . '<li>Bewertungen werden auf <strong>0</strong> gesetzt</li></ul>'
        . '<p class="mb-0"><strong>Soll die Datenbank wirklich zurückgesetzt werden?</strong></p>',
        "Ja, zurücksetzen",
        "reset.php"
    );
   }
 }
else
 { echo pdl_admin_alert('warning', 'Sie haben keine Berechtigung diese Seite zu sehen.'); }
include("footer.inc.php");
?>
