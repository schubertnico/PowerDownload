<?
include("header.inc.php");
if($user_rights[delfiles] == "Y")
 {
  if($submit == 1)
   {
    delrelease($release_id);
    echo "<br>done...";
   }
  else
   {
    echo makedialog("Release löschen?","
         <input type=\"hidden\" name=\"release_id\" value=\"$release_id\">
         Beim löschen eines Releases werden alle zugehörigen Kommentare, Files und Screens
         gelöscht. Wollen sie den Release wirklich löschen?","  Ja  ","delrelease.php");
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
