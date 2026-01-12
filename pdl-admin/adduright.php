<?php
include("header.inc.php");

$submit = $_GET['submit'] ?? '';
$name = $_POST['name'] ?? '';
$bez = $_POST['bez'] ?? '';
$variablenname = $_POST['variablenname'] ?? '';
$standard = $_POST['standard'] ?? '';

if($user_rights['god'] == "Y")
 {
  if($submit == 1)
   {
    $name_escaped = $db_handler->sql_escape_string($name);
    $bez_escaped = $db_handler->sql_escape_string($bez);
    $variablenname_escaped = $db_handler->sql_escape_string($variablenname);
    $standard_escaped = $db_handler->sql_escape_string($standard);
    $db_handler->sql_query("INSERT INTO " . $sql_table['rights'] . " VALUES ('','" . $name_escaped . "','" . $bez_escaped . "','" . $variablenname_escaped . "','')");
    $db_handler->sql_query("ALTER TABLE " . $sql_table['usergroup'] . " ADD " . $variablenname_escaped . " ENUM('Y', 'N') DEFAULT '" . $standard_escaped . "' NOT NULL");
    $db_handler->sql_query("UPDATE " . $sql_table['usergroup'] . " SET " . $variablenname_escaped . "='Y' WHERE ugroup_id='1'");
    echo "Userrecht eingetragen.";
   }
  else
   {
    echo "
<br>
<form action=\"adduright.php?submit=1\" method=\"post\">
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"90%\">
  <tr>
    <td bgcolor=\"" . htmlspecialchars($template['table_border']) . "\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['header_bg']) . "\" align=\"center\" colspan=\"2\">
            <b>Userrecht hinzufügen</b>
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            Name<br>
            <small>Name des Rechtes</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <input type=\"text\" name=\"name\" size=\"35\">
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            Beschreibung<br>
            <small>Nur eine kurze Beschreibung</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <input type=\"text\" name=\"bez\" size=\"35\">
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            Variablenname<br>
            <small>Wird dann als \$user_rights[variablenname] im System verfügbar sein</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <input type=\"text\" name=\"variablenname\" size=\"35\">
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            Standardwert<br>
            <small>Jede Usergruppe ausgenommen der Godadmin bekommt diesen wert. Der Godadmin bekommt immer den Wert Ja.</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <select name=\"standard\">
            <option value=\"Y\">Ja</option>
            <option value=\"N\">Nein</option>
            </select>
          </td>
        </tr>
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['footer_bg']) . "\" align=\"center\" colspan=\"2\">
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
