<?php
include("header.inc.php");

// Extract POST/GET variables
$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;
$name = isset($_POST['name']) ? $db_handler->sql_escape_string($_POST['name']) : '';
$rights = isset($_POST['rights']) ? $_POST['rights'] : array();

if($user_rights['edituser'] == "Y" && $user_rights['deluser'] == "Y")
 {
  if($submit == 1)
   {
    $into = '';
    $values = '';
    for($i = 0;$i < count($rights);$i++)
     {
      $into .= ", " . $db_handler->sql_escape_string($rights[$i]['variablenname']);
      $values .= ", '" . $db_handler->sql_escape_string($rights[$i]['wert']) . "'";
     }
    $db_handler->sql_query("INSERT INTO `" . $sql_table['usergroup'] . "` (name$into) VALUES ('$name'$values)");
    echo "Usergruppe eingetragen.";
   }
  else
   {
    echo "
<br>
<form action=\"addugroup.php?submit=1\" method=\"post\">
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"90%\">
  <tr>
    <td bgcolor=\"" . htmlspecialchars($template['table_border']) . "\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['header_bg']) . "\" align=\"center\" colspan=\"2\">
            <b>Usergruppe hinzuf&uuml;gen</b>
          </td>
        </tr>";
    $alt = alt_switch();
    echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <b>Name</b><br>
            Name der Usergruppe
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <input type=\"text\" name=\"name\" size=\"35\">
          </td>
        </tr>
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['footer_bg']) . "\" align=\"center\" colspan=\"2\">
            <b>Rechte</b>
          </td>
        </tr>";
    $rights_count = -1;
    $rights_res = $db_handler->sql_query("SELECT * FROM `" . $sql_table['rights'] . "` ORDER BY reihenfolge ASC");
    while($rights_row = $db_handler->sql_fetch_array($rights_res))
     {
      $rights_count++;
      $alt = alt_switch();
      echo "    <tr>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <b>" . htmlspecialchars($rights_row['name']) . "</b><br>
            " . htmlspecialchars($rights_row['bez']) . "
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt) . "\">
            <input type=\"hidden\" name=\"rights[$rights_count][variablenname]\" value=\"" . htmlspecialchars($rights_row['variablenname']) . "\">
            <input type=\"radio\" name=\"rights[$rights_count][wert]\" value=\"N\" checked>Nein,
            <input type=\"radio\" name=\"rights[$rights_count][wert]\" value=\"Y\">Ja
          </td>
        </tr>";
     }
    echo "
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['footer_bg']) . "\" align=\"center\" colspan=\"2\">
            <input type=\"submit\" value=\"Usergruppe hinzuf&uuml;gen\">
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
