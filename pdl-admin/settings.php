<?
include("header.inc.php");

if($user_rights[god] == "Y")
 {
  if($submit == 1)
   {
    foreach($_POST as $variablenname => $wert)
     {
      $db_handler->sql_query("UPDATE $sql_table[settings] SET wert='$wert' WHERE variablenname='$variablenname'");
     }
    echo "Settings übernommen.";
   }
  else
   {
    $sgroup_res = $db_handler->sql_query("SELECT * FROM $sql_table[settingsgroup] ORDER BY reihenfolge ASC");
    while($sgroup_row = $db_handler->sql_fetch_array($sgroup_res))
     {
      echo "<li><a href=\"#$sgroup_row[sgroup_id]\">$sgroup_row[name]</a></li>";
     }
    echo "
    <br>
    <form action=\"settings.php?submit=1\" method=\"post\">";

    $sgroup_res = $db_handler->sql_query("SELECT * FROM $sql_table[settingsgroup] ORDER BY reihenfolge ASC");
    while($sgroup_row = $db_handler->sql_fetch_array($sgroup_res))
     {
      echo "
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"90%\">
  <tr>
    <td bgcolor=\"$template[table_border]\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"$template[header_bg]\" align=\"center\" colspan=\"2\">
            <a name=\"$sgroup_row[sgroup_id]\"><b>$sgroup_row[name]</b></a>
          </td>
        </tr>
      ";

      $settings_res = $db_handler->sql_query("SELECT * FROM $sql_table[settings] WHERE sgroup_id='$sgroup_row[sgroup_id]' ORDER BY reihenfolge ASC");
      while($settings_row = $db_handler->sql_fetch_array($settings_res))
       {
        $alt = alt_switch();
        echo "
        <tr>
          <td bgcolor=\"$alt\" width=\"35%\">
            <b>$settings_row[name]</b><br>
            <small>$settings_row[bez]</small>
          </td>
          <td bgcolor=\"$alt\">";
        if($settings_row[eingabe] == "anaus")
         {
          echo "
          <select name=\"$settings_row[variablenname]\">
          <option value=\"Y\">An</option>
          <option value=\"N\"".pdlif($settings_row[wert] == "N"," selected","").">Aus</option>
          </select>
          ";
         }
        elseif($settings_row[eingabe] == "input")
         {
          echo "<input type=\"text\" name=\"$settings_row[variablenname]\" value=\"$settings_row[wert]\" size=\"35\">";
         }
        elseif($settings_row[eingabe] == "textarea")
         {
          echo "<textarea cols=\"50\" rows=\"5\" name=\"$settings_row[variablenname]\">$settings_row[wert]</textarea>";
         }
        else
         {
          eval("echo \"$settings_row[eingabe]\";");
         }
        echo "
          </td>
        </tr>
        ";
       }

      echo "
        </tr>
        <tr>
          <td bgcolor=\"$template[footer_bg]\" colspan=\"2\">
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
    <input type=\"submit\" value=\"Settings ändern\">
    </form>";
   }
 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
