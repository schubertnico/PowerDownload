<?
include("header.inc.php");
if($user_rights[god] == "Y")
 {
  if($submit == 1)
   {
    $db_handler->sql_query("UPDATE $sql_table[release] SET views='0', votes='0', voted='0'");
    $db_handler->sql_query("UPDATE $sql_table[screens] SET views='0'");
    $db_handler->sql_query("UPDATE $sql_table[files] SET downloads='0'");
    $db_handler->sql_query("DELETE FROM $sql_table[comments]");
    $db_handler->sql_query("DELETE FROM $sql_table[iplock]");
    echo "<br>done...";
   }
  else
   {
    echo makedialog("Reseten?","
         Beim Reseten der Datenbank geschieht folgendes:<br>
         - Views der Release werden auf 0 gesetzt<br>
         - Downloads der Files werden auf 0 gesetzt<br>
         - Alle Kommentare werden gelöscht<br>
         - Views der Screens werden gelöscht<br>
         - Bewertungen werden auf 0 gesetzt<br>
         Soll die Datenbank wirklich resetet werden?","  Ja  ","reset.php");
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
