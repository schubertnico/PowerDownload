<?
include("header.inc.php");
if($user_rights[editfiles] == "Y")
 {
  if($submit == 1)
   {
    $release_id = $db_handler->sql_fetch_array($db_handler->sql_query("SELECT * FROM $sql_table[screens] WHERE screen_id='$screen_id'"));
    $db_handler->sql_query("DELETE FROM $sql_table[screens] WHERE screen_id='$screen_id'");
    unlink("../pdl-gfx/screens/release".$release_id[release_id]."screen".$screen_id."g.jpg");
    unlink("../pdl-gfx/screens/release".$release_id[release_id]."screen".$screen_id."k.jpg");
    echo "<br>done...";
   }
  else
   {
    echo makedialog("Screen wirklich löschen?","
         <input type=\"hidden\" name=\"screen_id\" value=\"$screen_id\">
         Wollen sie den Screen wirklich löschen? Dabei wird der Datenbankeintrag und
         die hochgeladenen Screenshots gelöscht.","  Ja  ","delscreen.php");
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
