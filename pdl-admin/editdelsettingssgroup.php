<?php
include("header.inc.php");

$prot_settings = array("referer_check", "allowed_referer", "enable_comments",
"enable_search", "enable_treeview", "enable_extrernadmin", "date_format", "spages",
"perpage", "orderby", "orderseq", "dlspeed", "trenn_durch", "trenn_zeichen",
"trenn_string", "bb_code", "smilies", "badwords_comments", "badwords_releases",
"glossary", "html_releases", "html_comments", "mail_fromname", "mail_fromaddr",
"screen_autosize", "screen_size", "screen_verhalt", "ftp_on", "script_file",
"ftp_server", "ftp_user", "ftp_passwort", "ftp_server_url", "top_count");
$prot_sgroups = array(1,2,3,4,5,6,7,8,9);

$submit = isset($_GET['submit']) ? (int)$_GET['submit'] : (isset($_POST['submit']) ? (int)$_POST['submit'] : 0);
$sgroup = isset($_POST['sgroup']) ? $_POST['sgroup'] : array();
$setting = isset($_POST['setting']) ? $_POST['setting'] : array();

if($user_rights['god'] == "Y")
 {
  if($submit == 1)
   {
    for($i = 0; $i < count($sgroup); $i++)
     {
      $sgroup_id = $db_handler->sql_escape_int($sgroup[$i]['sgroup_id']);
      $reihenfolge = $db_handler->sql_escape_string($sgroup[$i]['reihenfolge']);
      $name = $db_handler->sql_escape_string($sgroup[$i]['name']);
      $db_handler->sql_query("UPDATE ".$sql_table['settingsgroup']." SET reihenfolge='".$reihenfolge."', name='".$name."' WHERE sgroup_id='".$sgroup_id."'");
      if(isset($sgroup[$i]['delete']) && $sgroup[$i]['delete'] == "Y") $db_handler->sql_query("DELETE FROM ".$sql_table['settingsgroup']." WHERE sgroup_id='".$sgroup_id."'");
     }
    for($i = 0; $i < count($setting); $i++)
     {
      $settings_id = $db_handler->sql_escape_int($setting[$i]['settings_id']);
      $reihenfolge = $db_handler->sql_escape_string($setting[$i]['reihenfolge']);
      $name = $db_handler->sql_escape_string($setting[$i]['name']);
      $bez = $db_handler->sql_escape_string($setting[$i]['bez']);
      $eingabe = $db_handler->sql_escape_string($setting[$i]['eingabe']);
      $variablenname = $db_handler->sql_escape_string($setting[$i]['variablenname']);
      $sgroup_id = $db_handler->sql_escape_int($setting[$i]['sgroup_id']);
      $db_handler->sql_query("UPDATE ".$sql_table['settings']." SET reihenfolge='".$reihenfolge."', name='".$name."', bez='".$bez."', eingabe='".$eingabe."', variablenname='".$variablenname."', sgroup_id='".$sgroup_id."' WHERE settings_id='".$settings_id."'");
      if(isset($setting[$i]['delete']) && $setting[$i]['delete'] == "Y") $db_handler->sql_query("DELETE FROM ".$sql_table['settings']." WHERE settings_id='".$settings_id."'");
     }
    echo "<br>Settings/Gruppen geaendert/geloescht.<br>";
   }
  echo "
<br>
<form action=\"editdelsettingssgroup.php?submit=1\" method=\"post\">
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"75%\">
  <tr>
    <td bgcolor=\"".htmlspecialchars($template['table_border'])."\">
      <table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\">
        <tr>
          <td bgcolor=\"".htmlspecialchars($template['header_bg'])."\" align=\"center\" colspan=\"3\">
            <b>Settings/Gruppen aendern/loeschen</b>
          </td>
        </tr>";
  $sgroup_count = -1;
  $setting_count = -1;
  $sgroup_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['settingsgroup']." ORDER BY reihenfolge ASC");
  while($sgroup_row = $db_handler->sql_fetch_array($sgroup_res))
   {
    $sgroup_count++;
    echo "
        <tr>
          <td bgcolor=\"".htmlspecialchars($template['footer_bg'])."\">
            <input type=\"text\" name=\"sgroup[".$sgroup_count."][reihenfolge]\" value=\"".htmlspecialchars($sgroup_row['reihenfolge'])."\" size=\"1\">
          </td>
          <td bgcolor=\"".htmlspecialchars($template['footer_bg'])."\" colspan=\"2\">
            <input type=\"hidden\" name=\"sgroup[".$sgroup_count."][sgroup_id]\" value=\"".htmlspecialchars($sgroup_row['sgroup_id'])."\">
            <input type=\"text\" name=\"sgroup[".$sgroup_count."][name]\" value=\"".htmlspecialchars($sgroup_row['name'])."\" size=\"35\">";
    $dodelete = true;
    for($i = 0; $i < count($prot_sgroups); $i++)
     {
      if($prot_sgroups[$i] == $sgroup_row['sgroup_id'])
       {
        $dodelete = false;
        break;
       }
     }
    if($dodelete == true)
     {
      echo "
      ( <input type=\"checkbox\" name=\"sgroup[".$sgroup_count."][delete]\" value=\"Y\"> Loeschen )
      ";
     }
    echo "      </td>
        </tr>
    ";
    $settings_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['settings']." WHERE sgroup_id='".$db_handler->sql_escape_int($sgroup_row['sgroup_id'])."' ORDER BY reihenfolge ASC");
    while($settings_row = $db_handler->sql_fetch_array($settings_res))
     {
      $setting_count++;
      $alt = alt_switch();
      echo "
        <tr>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            &nbsp;&nbsp;<input type=\"text\" name=\"setting[".$setting_count."][reihenfolge]\" value=\"".htmlspecialchars($settings_row['reihenfolge'])."\" size=\"1\">
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\" colspan=\"2\">
            <input type=\"hidden\" name=\"setting[".$setting_count."][settings_id]\" value=\"".htmlspecialchars($settings_row['settings_id'])."\">
            <input type=\"text\" name=\"setting[".$setting_count."][name]\" value=\"".htmlspecialchars($settings_row['name'])."\" size=\"35\">";
      $dodelete = true;
      for($i = 0; $i < count($prot_settings); $i++)
       {
        if($prot_settings[$i] == $settings_row['variablenname'])
         {
          $dodelete = false;
          break;
         }
       }
      if($dodelete == true)
       {
        echo "
        ( <input type=\"checkbox\" name=\"setting[".$setting_count."][delete]\" value=\"Y\"> Loeschen )
        ";
       }
      echo "</td>
        </tr>
        <tr>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            &nbsp;
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            Beschreibung<br>
            <small>Beschreibung des Settings.</small>
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            <textarea cols=\"50\" rows=\"5\" name=\"setting[".$setting_count."][bez]\">".htmlspecialchars($settings_row['bez'])."</textarea>
          </td>
        </tr>
        <tr>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            &nbsp;
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            Eingabeart<br>
            <small>\"input\",\"textarea\",\"anaus\" oder ein beliebiger Text,
            der dann per eval() umgesetzt wird.</small>
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            <textarea cols=\"50\" rows=\"5\" name=\"setting[".$setting_count."][eingabe]\">".htmlspecialchars($settings_row['eingabe'])."</textarea>
          </td>
        </tr>
        <tr>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            &nbsp;
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            Variablenname<br>
            <small>Wird im System als \$settings[variablenname] verfuegbar</small>
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">";
      if($dodelete == true) echo "<input type=\"text\" name=\"setting[".$setting_count."][variablenname]\" value=\"".htmlspecialchars($settings_row['variablenname'])."\" size=\"35\">";
      else echo "<input type=\"hidden\" name=\"setting[".$setting_count."][variablenname]\" value=\"".htmlspecialchars($settings_row['variablenname'])."\">".htmlspecialchars($settings_row['variablenname'])." - Kann nicht geaendert werden.";
      echo "
          </td>
        </tr>
        <tr>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            &nbsp;
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            Settingsgruppe<br>
            <small>zu welcher Settingsgruppe gehoert das Setting?</small>
          </td>
          <td bgcolor=\"".htmlspecialchars($alt)."\">
            <select name=\"setting[".$setting_count."][sgroup_id]\">";
      $sgroup2_res = $db_handler->sql_query("SELECT * FROM ".$sql_table['settingsgroup']." ORDER BY reihenfolge ASC");
      while($sgroup2_row = $db_handler->sql_fetch_array($sgroup2_res))
       {
        echo "<option value=\"".htmlspecialchars($sgroup2_row['sgroup_id'])."\"".pdlif($settings_row['sgroup_id'] == $sgroup2_row['sgroup_id']," selected","").">".htmlspecialchars($sgroup2_row['name'])."</option>";
       }
      echo "        </select>
          </td>
        </tr>
      ";
     }

   }
  echo "      <tr>
          <td bgcolor=\"".htmlspecialchars($template['footer_bg'])."\" align=\"center\" colspan=\"3\">
            <input type=\"submit\" value=\"Settings/Gruppen aendern/loeschen\">
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
