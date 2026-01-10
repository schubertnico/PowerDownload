<?php
include("header.inc.php");

// Extract POST/GET variables
$release_id = isset($_REQUEST['release_id']) ? (int)$_REQUEST['release_id'] : 0;
$submit = isset($_REQUEST['submit']) ? (int)$_REQUEST['submit'] : 0;

if($user_rights['delfiles'] == "Y")
 {
  if($submit == 1)
   {
    delrelease($release_id);
    echo "<br>done...";
   }
  else
   {
    echo makedialog("Release löschen?","
         <input type=\"hidden\" name=\"release_id\" value=\"" . htmlspecialchars($release_id) . "\">
         Beim löschen eines Releases werden alle zugehörigen Kommentare, Files und Screens
         gelöscht. Wollen sie den Release wirklich löschen?","  Ja  ","delrelease.php");
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
