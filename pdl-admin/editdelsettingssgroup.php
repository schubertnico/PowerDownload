<?
include("header.inc.php");

$prot_settings = array("referer_check", "allowed_referer", "enable_comments",
"enable_search", "enable_treeview", "enable_extrernadmin", "date_format", "spages",
"perpage", "orderby", "orderseq", "dlspeed", "trenn_durch", "trenn_zeichen",
"trenn_string", "bb_code", "smilies", "badwords_comments", "badwords_releases",
"glossary", "html_releases", "html_comments", "mail_fromname", "mail_fromaddr",
"screen_autosize", "screen_size", "screen_verhalt", "ftp_on", "script_file",
"ftp_server", "ftp_user", "ftp_passwort", "ftp_server_url", "top_count");
$prot_sgroups = array(1,2,3,4,5,6,7,8,9);
if($user_rights[god] == "Y")
 {
  if($submit == 1)
   {
    for($i = 0; $i < count($sgroup); $i++)
     {
      $db_handler->sql_query("UPDATE $sql_table[settingsgroup] SET reihenfolge='".$sgroup[$i][reihenfolge]."', name='".$sgroup[$i][name]."' WHERE sgroup_id='".$sgroup[$i][sgroup_id]."'");
      if($sgroup[$i][delete] == "Y") $db_handler->sql_query("DELETE FROM $sql_table[settingsgroup] WHERE sgroup_id='".$sgroup[$i][sgroup_id]."'");
     }
    for($i = 0; $i < count($setting); $i++)
     {
      $db_handler->sql_query("UPDATE $sql_table[settings] SET reihenfolge='".$setting[$i][reihenfolge]."', name='".$setting[$i][name]."', bez='".$setting[$i][bez]."', eingabe='".$setting[$i][eingabe]."', variablenname='".$setting[$i][variablenname]."', sgroup_id='".$setting[$i][sgroup_id]."' WHERE settings_id='".$setting[$i][settings_id]."'");
      if($setting[$i][delete] == "Y") $db_handler->sql_query("DELETE FROM $sql_table[settings] WHERE settings_id='".$setting[$i][settings_id]."'");
     }
    echo "<br>Settings/Gruppen geändert/gelöscht.<br>";
   }
  echo "
<br>
<form action=\"editdelsettingssgroup.php?submit=1\" method=\"post\">
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"75%\">
  <tr>
    <td bgcolor=\"$template[table_border]\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"$template[header_bg]\" align=\"center\" colspan=\"3\">
            <b>Settings/Gruppen ändern/löschen</b>
          </td>
        </tr>";
  $sgroup_count = -1;
  $setting_count = -1;
  $sgroup_res = $db_handler->sql_query("SELECT * FROM $sql_table[settingsgroup] ORDER BY reihenfolge ASC");
  while($sgroup_row = $db_handler->sql_fetch_array($sgroup_res))
   {
    $sgroup_count++;
    echo "
        <tr>
          <td bgcolor=\"$template[footer_bg]\">
            <input type=\"text\" name=\"sgroup[$sgroup_count][reihenfolge]\" value=\"$sgroup_row[reihenfolge]\" size=\"1\">
          </td>
          <td bgcolor=\"$template[footer_bg]\" colspan=\"2\">
            <input type=\"hidden\" name=\"sgroup[$sgroup_count][sgroup_id]\" value=\"$sgroup_row[sgroup_id]\">
            <input type=\"text\" name=\"sgroup[$sgroup_count][name]\" value=\"$sgroup_row[name]\" size=\"35\">";
    $dodelete = true;
    for($i = 0; $i < count($prot_sgroups); $i++)
     {
      if($prot_sgroups[$i] == $sgroup_row[sgroup_id])
       {
        $dodelete = false;
        break;
       }
     }
    if($dodelete == true)
     {
      echo "
      ( <input type=\"checkbox\" name=\"sgroup[$sgroup_count][delete]\" value=\"Y\"> Löschen )
      ";
     }
    echo "      </td>
        </tr>
    ";
    $settings_res = $db_handler->sql_query("SELECT * FROM $sql_table[settings] WHERE sgroup_id='$sgroup_row[sgroup_id]' ORDER BY reihenfolge ASC");
    while($settings_row = $db_handler->sql_fetch_array($settings_res))
     {
      $setting_count++;
      $alt = alt_switch();
      echo "
        <tr>
          <td bgcolor=\"$alt\">
            &nbsp;&nbsp;<input type=\"text\" name=\"setting[$setting_count][reihenfolge]\" value=\"$settings_row[reihenfolge]\" size=\"1\">
          </td>
          <td bgcolor=\"$alt\" colspan=\"2\">
            <input type=\"hidden\" name=\"setting[$setting_count][settings_id]\" value=\"$settings_row[settings_id]\">
            <input type=\"text\" name=\"setting[$setting_count][name]\" value=\"$settings_row[name]\" size=\"35\">";
      $dodelete = true;
      for($i = 0; $i < count($prot_settings); $i++)
       {
        if($prot_settings[$i] == $settings_row[variablenname])
         {
          $dodelete = false;
          break;
         }
       }
      if($dodelete == true)
       {
        echo "
        ( <input type=\"checkbox\" name=\"setting[$setting_count][delete]\" value=\"Y\"> Löschen )
        ";
       }
      echo "</td>
        </tr>
        <tr>
          <td bgcolor=\"$alt\">
            &nbsp;
          </td>
          <td bgcolor=\"$alt\">
            Beschreibung<br>
            <small>Beschreibung des Settings.</small>
          </td>
          <td bgcolor=\"$alt\">
            <textarea cols=\"50\" rows=\"5\" name=\"setting[$setting_count][bez]\">$settings_row[bez]</textarea>
          </td>
        </tr>
        <tr>
          <td bgcolor=\"$alt\">
            &nbsp;
          </td>
          <td bgcolor=\"$alt\">
            Eingabeart<br>
            <small>\"input\",\"textarea\",\"anaus\" oder ein beliebiger Text,
            der dann per eval() umgesetzt wird.</small>
          </td>
          <td bgcolor=\"$alt\">
            <textarea cols=\"50\" rows=\"5\" name=\"setting[$setting_count][eingabe]\">".$settings_row[eingabe]."</textarea>
          </td>
        </tr>
        <tr>
          <td bgcolor=\"$alt\">
            &nbsp;
          </td>
          <td bgcolor=\"$alt\">
            Variablenname<br>
            <small>Wird im System als \$settings[variablenname] verfügbar</small>
          </td>
          <td bgcolor=\"$alt\">";
      if($dodelete == true) echo "<input type=\"text\" name=\"setting[$setting_count][variablenname]\" value=\"$settings_row[variablenname]\" size=\"35\">";
      else echo "<input type=\"hidden\" name=\"setting[$setting_count][variablenname]\" value=\"$settings_row[variablenname]\">$settings_row[variablenname] - Kann nicht geändert werden.";
      echo "
          </td>
        </tr>
        <tr>
          <td bgcolor=\"$alt\">
            &nbsp;
          </td>
          <td bgcolor=\"$alt\">
            Settingsgruppe<br>
            <small>zu welcher Settingsgruppe gehört das Setting?</small>
          </td>
          <td bgcolor=\"$alt\">
            <select name=\"setting[$setting_count][sgroup_id]\">";
      $sgroup2_res = $db_handler->sql_query("SELECT * FROM $sql_table[settingsgroup] ORDER BY reihenfolge ASC");
      while($sgroup2_row = $db_handler->sql_fetch_array($sgroup2_res))
       {
        echo "<option value=\"$sgroup2_row[sgroup_id]\"".pdlif($settings_row[sgroup_id] == $sgroup2_row[sgroup_id]," selected","").">$sgroup2_row[name]</option>";
       }
      echo "        </select>
          </td>
        </tr>
      ";
     }

   }
  echo "      <tr>
          <td bgcolor=\"$template[footer_bg]\" align=\"center\" colspan=\"3\">
            <input type=\"submit\" value=\"Settings/Gruppen ändern/löschen\">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</form>";

 }
else
 { echo "Sie haben keine Berechtigung diese Seite zu sehen"; }
include("footer.inc.php");
?>
