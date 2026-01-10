<?
include("header.inc.php");

if($user_rights[god] == "Y")
 {
  if($submit == 1)
   {
    $db_handler->sql_query("INSERT INTO $sql_table[settings] VALUES ('', '$name','$bez','$wert','$eingabe','$variablenname','$sgroup_id','')");
    echo "Setting eingetragen.";
   }
  else
   {
    echo "
<br>
<form action=\"addsettings.php?submit=1\" method=\"post\">
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"90%\">
  <tr>
    <td bgcolor=\"$template[table_border]\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"$template[header_bg]\" align=\"center\" colspan=\"2\">
            <b>Setting hinzufügen</b>
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"$alt\">
            Name<br>
            <small>Name des Settings</small>
          </td>
          <td bgcolor=\"$alt\">
            <input type=\"text\" name=\"name\" size=\"35\">
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"$alt\">
            Beschreibung<br>
            <small>Beschreibung des Settings.</small>
          </td>
          <td bgcolor=\"$alt\">
            <textarea cols=\"50\" rows=\"5\" name=\"bez\"></textarea>
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"$alt\">
            Variablenname<br>
            <small>Wird dann als \$settings[variablenname] im System verfügbar</small>
          </td>
          <td bgcolor=\"$alt\">
            <input type=\"text\" name=\"variablenname\" size=\"35\">
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"$alt\">
            Eingabeart<br>
            <small>4 Sachen kann man eingeben: \"input\" für ein normales input Feld,
            \"textarea\" für eine Textarea, \"anaus\" für eine Boolsche Option (nur ja/nein)
            oder man kann eine eigene Eingabe eingeben. Zum beispiel für ein Selectfeld
            mit mehreren auswahlmöglichkeiten.<br>
            Bitte beachten: aus einem \" wird ein \\\". <br>
            Der Code wird wie ein PHP echo behandelt also kann man auch die Funktion
            pdlif(bedingung,wahr,falsch) ist die Bedingung wahr wird \"wahr\" ausgegeben
            wenn nicht wird \"falsch\" ausgegeben.</small>
          </td>
          <td bgcolor=\"$alt\">
            <textarea cols=\"50\" rows=\"5\" name=\"eingabe\"></textarea>
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"$alt\">
            Anfangswert<br>
            <small>Startwert des Settings.</small>
          </td>
          <td bgcolor=\"$alt\">
            <textarea cols=\"50\" rows=\"5\" name=\"wert\"></textarea>
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"$alt\">
            Settingsgruppe<br>
            <small>In welche der Settingsgruppen wird das Setting gelegt?</small>
          </td>
          <td bgcolor=\"$alt\">
            <select name=\"sgroup_id\">";
    $sgroup_res = $db_handler->sql_query("SELECT * FROM $sql_table[settingsgroup]");
    while($sgroup_row = $db_handler->sql_fetch_array($sgroup_res))
     {
      echo "<option value=\"$sgroup_row[sgroup_id]\">$sgroup_row[name]</option>";
     }
    echo "        </select>
          </td>
        </tr>
        <tr>
          <td bgcolor=\"$template[footer_bg]\" align=\"center\" colspan=\"2\">
            <input type=\"submit\" value=\"Setting hinzufügen\">
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
