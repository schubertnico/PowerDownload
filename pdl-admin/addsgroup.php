<?
include("header.inc.php");

if($user_rights[god] == "Y")
 {
  if($submit == 1)
   {
    $db_handler->sql_query("INSERT INTO $sql_table[settingsgroup] VALUES ('','$name','')");
    echo "Setting Gruppe eingetragen.";
   }
  else
   {
    echo "
<br>
<form action=\"addsgroup.php?submit=1\" method=\"post\">
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"45%\">
  <tr>
    <td bgcolor=\"$template[table_border]\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"$template[header_bg]\" align=\"center\" colspan=\"2\">
            <b>Setting Gruppe hinzufügen</b>
          </td>
        </tr>
        <tr>
          <td bgcolor=\"$template[alt_1]\">
            <b>Name</b><br>
            Name der Setting Gruppe
          </td>
          <td bgcolor=\"$template[alt_1]\">
            <input type=\"text\" name=\"name\" size=\"35\">
          </td>
        </tr>
        <tr>
          <td bgcolor=\"$template[footer_bg]\" align=\"center\" colspan=\"2\">
            <input type=\"submit\" value=\"Setting Gruppe hinzufügen\">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>";
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
