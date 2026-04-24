<?php
include("header.inc.php");

$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : 0;

if($user_rights['god'] == "Y")
 {
  if($submit == 1)
   {
    foreach($_POST as $variablenname => $wert)
     {
      $variablenname_escaped = $db_handler->sql_escape_string($variablenname);
      $wert_escaped = $db_handler->sql_escape_string($wert);
      $db_handler->sql_query("UPDATE " . $sql_table['settings'] . " SET wert='" . $wert_escaped . "' WHERE variablenname='" . $variablenname_escaped . "'");
     }
    echo "Settings uebernommen.";
   }
  else
   {
    $sgroup_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['settingsgroup'] . " ORDER BY reihenfolge ASC");
    while($sgroup_row = $db_handler->sql_fetch_array($sgroup_res))
     {
      echo "<li><a href=\"#" . htmlspecialchars($sgroup_row['sgroup_id'], ENT_QUOTES, 'UTF-8') . "\">" . htmlspecialchars($sgroup_row['name'], ENT_QUOTES, 'UTF-8') . "</a></li>";
     }
    echo "
    <br>
    <form action=\"settings.php?submit=1\" method=\"post\">";

    $sgroup_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['settingsgroup'] . " ORDER BY reihenfolge ASC");
    while($sgroup_row = $db_handler->sql_fetch_array($sgroup_res))
     {
      echo "
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"90%\">
  <tr>
    <td bgcolor=\"" . htmlspecialchars($template['table_border'], ENT_QUOTES, 'UTF-8') . "\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['header_bg'], ENT_QUOTES, 'UTF-8') . "\" align=\"center\" colspan=\"2\">
            <a name=\"" . htmlspecialchars($sgroup_row['sgroup_id'], ENT_QUOTES, 'UTF-8') . "\"><b>" . htmlspecialchars($sgroup_row['name'], ENT_QUOTES, 'UTF-8') . "</b></a>
          </td>
        </tr>
      ";

      $sgroup_id_escaped = $db_handler->sql_escape_string($sgroup_row['sgroup_id']);
      $settings_res = $db_handler->sql_query("SELECT * FROM " . $sql_table['settings'] . " WHERE sgroup_id='" . $sgroup_id_escaped . "' ORDER BY reihenfolge ASC");
      while($settings_row = $db_handler->sql_fetch_array($settings_res))
       {
        $alt = alt_switch();
        echo "
        <tr>
          <td bgcolor=\"" . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . "\" width=\"35%\">
            <b>" . htmlspecialchars($settings_row['name'], ENT_QUOTES, 'UTF-8') . "</b><br>
            <small>" . htmlspecialchars($settings_row['bez'], ENT_QUOTES, 'UTF-8') . "</small>
          </td>
          <td bgcolor=\"" . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . "\">";
        if($settings_row['eingabe'] == "anaus")
         {
          echo "
          <select name=\"" . htmlspecialchars($settings_row['variablenname'], ENT_QUOTES, 'UTF-8') . "\">
          <option value=\"Y\">An</option>
          <option value=\"N\"".pdlif($settings_row['wert'] == "N"," selected","").">Aus</option>
          </select>
          ";
         }
        elseif($settings_row['eingabe'] == "input")
         {
          echo "<input type=\"text\" name=\"" . htmlspecialchars($settings_row['variablenname'], ENT_QUOTES, 'UTF-8') . "\" value=\"" . htmlspecialchars($settings_row['wert'], ENT_QUOTES, 'UTF-8') . "\" size=\"35\">";
         }
        elseif($settings_row['eingabe'] == "textarea")
         {
          echo "<textarea cols=\"50\" rows=\"5\" name=\"" . htmlspecialchars($settings_row['variablenname'], ENT_QUOTES, 'UTF-8') . "\">" . htmlspecialchars($settings_row['wert'], ENT_QUOTES, 'UTF-8') . "</textarea>";
         }
        else
         {
          // WARNING: Original code used eval() here which is a security risk.
          // The 'eingabe' field may contain custom HTML/form elements from the database.
          // For safety, we now output the content escaped. If you need to render HTML,
          // ensure the database content is sanitized or use a whitelist approach.
          echo htmlspecialchars($settings_row['eingabe'], ENT_QUOTES, 'UTF-8');
         }
        echo "
          </td>
        </tr>
        ";
       }

      echo "
        </tr>
        <tr>
          <td bgcolor=\"" . htmlspecialchars($template['footer_bg'], ENT_QUOTES, 'UTF-8') . "\" colspan=\"2\">
            &nbsp;
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<br>
      ";
     }
    echo "
    <input type=\"submit\" value=\"Settings aendern\">
    </form>";
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
