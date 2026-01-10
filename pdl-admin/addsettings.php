<?php
include("header.inc.php");

// Extract POST/GET variables
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$name = isset($_POST['name']) ? $db_handler->sql_escape_string($_POST['name']) : '';
$bez = isset($_POST['bez']) ? $db_handler->sql_escape_string($_POST['bez']) : '';
$wert = isset($_POST['wert']) ? $db_handler->sql_escape_string($_POST['wert']) : '';
$eingabe = isset($_POST['eingabe']) ? $db_handler->sql_escape_string($_POST['eingabe']) : '';
$variablenname = isset($_POST['variablenname']) ? $db_handler->sql_escape_string($_POST['variablenname']) : '';
$sgroup_id = isset($_POST['sgroup_id']) ? (int)$_POST['sgroup_id'] : 0;

if($user_rights['god'] == "Y")
 {
  if($submit == 1)
   {
    $db_handler->sql_query("INSERT INTO `" . $sql_table['settings'] . "` VALUES ('', '$name','$bez','$wert','$eingabe','$variablenname','$sgroup_id','')");
    echo "Setting eingetragen.";
   }
  else
   {
    echo "
<br>
<form action=\"addsettings.php?submit=1\" method=\"post\">
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"90%\">
  <tr>
    <td bgcolor=\"" . htmlspecialchars($template['table_border']) . "\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['header_bg']) . "\" align=\"center\" colspan=\"2\">
            <b>Setting hinzuf&uuml;gen</b>
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            Name<br>
            <small>Name des Settings</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <input type=\"text\" name=\"name\" size=\"35\">
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            Beschreibung<br>
            <small>Beschreibung des Settings.</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <textarea cols=\"50\" rows=\"5\" name=\"bez\"></textarea>
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            Variablenname<br>
            <small>Wird dann als \$settings[variablenname] im System verf&uuml;gbar</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <input type=\"text\" name=\"variablenname\" size=\"35\">
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            Eingabeart<br>
            <small>4 Sachen kann man eingeben: \"input\" f&uuml;r ein normales input Feld,
            \"textarea\" f&uuml;r eine Textarea, \"anaus\" f&uuml;r eine Boolsche Option (nur ja/nein)
            oder man kann eine eigene Eingabe eingeben. Zum beispiel f&uuml;r ein Selectfeld
            mit mehreren auswahlm&ouml;glichkeiten.<br>
            Bitte beachten: aus einem \" wird ein \\\". <br>
            Der Code wird wie ein PHP echo behandelt also kann man auch die Funktion
            pdlif(bedingung,wahr,falsch) ist die Bedingung wahr wird \"wahr\" ausgegeben
            wenn nicht wird \"falsch\" ausgegeben.</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <textarea cols=\"50\" rows=\"5\" name=\"eingabe\"></textarea>
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            Anfangswert<br>
            <small>Startwert des Settings.</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <textarea cols=\"50\" rows=\"5\" name=\"wert\"></textarea>
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            Settingsgruppe<br>
            <small>In welche der Settingsgruppen wird das Setting gelegt?</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <select name=\"sgroup_id\">";
    $sgroup_res = $db_handler->sql_query("SELECT * FROM `" . $sql_table['settingsgroup'] . "`");
    while($sgroup_row = $db_handler->sql_fetch_array($sgroup_res))
     {
      echo "<option value=\"" . htmlspecialchars($sgroup_row['sgroup_id']) . "\">" . htmlspecialchars($sgroup_row['name']) . "</option>";
     }
    echo "        </select>
          </td>
        </tr>
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['footer_bg']) . "\" align=\"center\" colspan=\"2\">
            <input type=\"submit\" value=\"Setting hinzuf&uuml;gen\">
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
