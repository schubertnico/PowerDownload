<?php
include("header.inc.php");

// Extract POST/GET variables
$ordner_id = isset($_REQUEST['ordner_id']) ? (int)$_REQUEST['ordner_id'] : 0;

if($user_rights['deldirs'] == "Y")
 {
  $subordner_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM " . $sql_table['ordner'] . " WHERE sordner_id=" . $db_handler->sql_escape_int($ordner_id)));
  $release_check = $db_handler->sql_num_rows($db_handler->sql_query("SELECT * FROM " . $sql_table['release'] . " WHERE ordner_id=" . $db_handler->sql_escape_int($ordner_id)));
  if($subordner_check > 0 OR $release_check > 0)
   { echo "<br>Ordner kann nicht gelöscht werden: Ordner enthält noch Release oder Unterordner."; }
  else
   {
    $db_handler->sql_query("DELETE FROM " . $sql_table['ordner'] . " WHERE ordner_id=" . $db_handler->sql_escape_int($ordner_id));
    echo "<br>done...";
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
