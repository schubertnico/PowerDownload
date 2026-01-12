<?php
include("header.inc.php");

$submit = $_GET['submit'] ?? '';
$name = $_POST['name'] ?? '';
$bez = $_POST['bez'] ?? '';
$variablenname = $_POST['variablenname'] ?? '';
$wert = $_POST['wert'] ?? '';
$eingabe = $_POST['eingabe'] ?? '';
$tgroup_id = $_POST['tgroup_id'] ?? '';

if($user_rights['god'] == "Y")
 {
  if($submit == 1)
   {
    $name_escaped = $db_handler->sql_escape_string($name);
    $bez_escaped = $db_handler->sql_escape_string($bez);
    $variablenname_escaped = $db_handler->sql_escape_string($variablenname);
    $wert_escaped = $db_handler->sql_escape_string($wert);
    $eingabe_escaped = $db_handler->sql_escape_string($eingabe);
    $tgroup_id_escaped = $db_handler->sql_escape_int($tgroup_id);
    $db_handler->sql_query("INSERT INTO " . $sql_table['template'] . " VALUES ('','" . $name_escaped . "','" . $bez_escaped . "','" . $variablenname_escaped . "','" . $wert_escaped . "','" . $eingabe_escaped . "','" . $tgroup_id_escaped . "','')");
    echo "Template eingetragen.";
   }
  else
   {
    echo "
<br>
<form action=\"addtemplate.php?submit=1\" method=\"post\">
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"90%\">
  <tr>
    <td bgcolor=\"" . htmlspecialchars($template['table_border']) . "\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['header_bg']) . "\" align=\"center\" colspan=\"2\">
            <b>Template hinzufügen</b>
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            Name<br>
            <small>Name des Templates</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <input type=\"text\" name=\"name\" size=\"35\">
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            Beschreibung<br>
            <small>Beschreibung des Templates.</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <textarea cols=\"50\" rows=\"5\" name=\"bez\"></textarea>
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            Variablenname<br>
            <small>Wird dann als \$template[variablenname] im System verfügbar</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <input type=\"text\" name=\"variablenname\" size=\"35\">
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            Eingabeart<br>
            <small>3 Sachen kann man eingeben: \"input\" für ein normales input Feld,
            \"textarea\" für eine Textarea und \"farbe\" für die Farbauswahl.</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <select name=\"eingabe\">
            <option value=\"textarea\">textarea</option>
            <option value=\"input\">Inputfeld</option>
            <option value=\"farbe\">Farbauswahl</option>
            </select>
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            Template<br>
            <small>Hier das Template eingeben</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <textarea cols=\"50\" rows=\"10\" name=\"wert\"></textarea>
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            Templategruppe<br>
            <small>In welche der Templategruppen kommt das Template?</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <select name=\"tgroup_id\">";
    $tgroup_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['templategroup']);
    while($tgroup_row = $db_handler->sql_fetch_array($tgroup_res))
     {
      echo "<option value=\"" . htmlspecialchars($tgroup_row['tgroup_id']) . "\">" . htmlspecialchars($tgroup_row['name']) . "</option>";
     }
    echo "        </select>
          </td>
        </tr>
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['footer_bg']) . "\" align=\"center\" colspan=\"2\">
            <input type=\"submit\" value=\"Template hinzufügen\">
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
