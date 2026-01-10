<?
include("header.inc.php");
if($user_rights[editfiles] == "Y")
 {
  $release = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT release_id FROM $sql_table[comments] WHERE comment_id='$comment_id'"));
  $release_id = $release[release_id];
  if(!$release_id)
   {
    echo "<br>Bitte ein Kommentar auswählen.";
   }
  else
   {
    $db_handler->sql_query("DELETE FROM $sql_table[comments] WHERE comment_id='$comment_id'");
    echo "<br>done...<br><a href=\"editrelease.php?release_id=$release_id\">Zurück zum Release</a>";
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
