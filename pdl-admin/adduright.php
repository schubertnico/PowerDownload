<?
include("header.inc.php");

if($user_rights[god] == "Y")
 {
  if($submit == 1)
   {
    $db_handler->sql_query("INSERT INTO $sql_table[rights] VALUES ('','$name','$bez','$variablenname','')");
    $db_handler->sql_query("ALTER TABLE $sql_table[usergroup] ADD $variablenname ENUM('Y', 'N') DEFAULT '$standard' NOT NULL");
    $db_handler->sql_query("UPDATE $sql_table[usergroup] SET $variablenname='Y' WHERE ugroup_id='1'");
    echo "Userrecht eingetragen.";
   }
  else
   {
    echo "
<br>
<form action=\"adduright.php?submit=1\" method=\"post\">
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"90%\">
  <tr>
    <td bgcolor=\"$template[table_border]\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"$template[header_bg]\" align=\"center\" colspan=\"2\">
            <b>Userrecht hinzufügen</b>
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"$alt\">
            Name<br>
            <small>Name des Rechtes</small>
          </td>
          <td bgcolor=\"$alt\">
            <input type=\"text\" name=\"name\" size=\"35\">
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"$alt\">
            Beschreibung<br>
            <small>Nur eine kurze Beschreibung</small>
          </td>
          <td bgcolor=\"$alt\">
            <input type=\"text\" name=\"bez\" size=\"35\">
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"$alt\">
            Variablenname<br>
            <small>Wird dann als \$user_rights[variablenname] im System verfügbar sein</small>
          </td>
          <td bgcolor=\"$alt\">
            <input type=\"text\" name=\"variablenname\" size=\"35\">
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"$alt\">
            Standardwert<br>
            <small>Jede Usergruppe ausgenommen der Godadmin bekommt diesen wert. Der Godadmin bekommt immer den Wert Ja.</small>
          </td>
          <td bgcolor=\"$alt\">
            <select name=\"standard\">
            <option value=\"Y\">Ja</option>
            <option value=\"N\">Nein</option>
            </select>
          </td>
        </tr>
        <tr>
          <td bgcolor=\"$template[footer_bg]\" align=\"center\" colspan=\"2\">
            <input type=\"submit\" value=\"Userrecht hinzufügen\">
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
